<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCycle;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use App\Notifications\Aftersales\HappyCallOverdueNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketHappyCalls
{
    public static function deadline(?TicketSatisfaction $response): ?Carbon
    {
        if (! $response?->filled_at || ! in_array($response->review_status, ['normal', 'needs_review', 'reviewed'], true)
            || ! in_array($response->source, ['link', 'admin'], true)
            || ($response->source === 'admin' && $response->review_status === 'normal')) {
            return null;
        }

        return $response->filled_at->copy()->addDays(3);
    }

    public static function sync(Ticket $ticket, TicketCycle $cycle): void
    {
        $response = $ticket->satisfactions()->where('cycle_id', $cycle->id)
            ->when($cycle->ended_at, fn ($q) => $q->where('filled_at', '<=', $cycle->ended_at))
            ->orderByDesc('filled_at')->orderByDesc('id')->first();
        $due = self::deadline($response);
        $cycle->update(['happy_call_due_at' => $due]);
        if ($ticket->currentCycle()->value('id') === $cycle->id) {
            $ticket->update(['happy_call_due_at' => $due]);
        }
    }

    public static function documentationReady(Ticket $ticket): bool
    {
        $cycle = $ticket->currentCycle;

        return $cycle?->documentation_status === 'documentation_published'
            && $ticket->logs->contains(fn ($log) => $log->cycle_id === $cycle->id && $log->step === TicketLog::STEP_DOCUMENTATION_PUBLISHED);
    }

    public static function state(Ticket $ticket, bool $includeSurveyId = true): ?array
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return null;
        }
        $cycle = $ticket->currentCycle;
        $response = TicketSurveys::current($ticket, $cycle?->id);
        $valid = $response && TicketSurveys::exclusion($ticket, $response) === null;
        $due = self::deadline($response);
        $done = $valid ? $ticket->logs->filter(fn ($log) => $log->cycle_id === $cycle?->id
            && $log->step === TicketLog::STEP_HAPPY_CALL_COMPLETED && (int) $log->satisfaction_id === $response->id)->sortBy('id')->first() : null;
        $open = $ticket->progress !== TicketLog::STEP_CLOSED;
        $overdue = $open && ! $done && $due && now('Asia/Jakarta')->gt($due);
        $status = $done ? 'completed' : (! $due ? 'waiting_survey' : (! $valid ? 'needs_review' : ($overdue ? 'overdue' : 'pending')));

        return ['cycle_id' => $cycle?->id, ...($includeSurveyId ? ['satisfaction_id' => $response?->id] : []),
            'due_at' => $due, 'done_at' => $done?->created_at, 'overdue' => (bool) $overdue, 'status' => $status,
            'survey_valid' => (bool) $valid, 'completed' => $done !== null,
            'can_record' => $open && $ticket->progress !== TicketLog::STEP_RECOMING && self::documentationReady($ticket) && $valid && ! $done];
    }

    public static function head(User $user): bool
    {
        return $user->hasPermission('aftersales.tickets.close') && $user->role?->code === 'head_after_sales';
    }

    public static function resource(Ticket $ticket, User $user): ?array
    {
        $state = self::state($ticket, TicketDocuments::canViewAll($user));
        if ($state) {
            $state['can_record'] = $state['can_record'] && $user->hasPermission('aftersales.tickets.manage');
        }

        return $state;
    }

    public static function closable(Ticket $ticket): bool
    {
        $state = self::state($ticket);

        return $ticket->progress !== TicketLog::STEP_CLOSED && $ticket->progress !== TicketLog::STEP_RECOMING
            && self::documentationReady($ticket) && $state['survey_valid'] && $state['completed'];
    }

    public static function guard(Request $request, Ticket $ticket, bool $closing = false): array
    {
        $context = $request->validate(['cycle_id' => 'sometimes|required|integer', 'satisfaction_id' => 'sometimes|required|integer']);
        $state = self::state($ticket);
        if (isset($context['cycle_id']) && (int) $context['cycle_id'] !== $state['cycle_id']) {
            TicketDocumentWorkflow::error(409, 'ticket_cycle_changed', 'Reload the current ticket cycle.');
        }
        if (isset($context['satisfaction_id']) && (int) $context['satisfaction_id'] !== $state['satisfaction_id']) {
            TicketDocumentWorkflow::error(409, 'survey_response_changed', 'Reload the current survey response.');
        }
        if ($ticket->progress === TicketLog::STEP_RECOMING) {
            TicketDocumentWorkflow::error(422, 'ticket_in_recoming', 'Happy Call and Close are blocked during Recoming.');
        }
        if (! self::documentationReady($ticket)) {
            TicketDocumentWorkflow::error(422, 'documentation_not_published', 'Publish documentation for the current cycle first.');
        }
        if (! $state['survey_valid']) {
            TicketDocumentWorkflow::error(422, 'survey_not_valid', 'A valid survey for the current cycle is required.');
        }
        if ($closing && ! $state['completed']) {
            TicketDocumentWorkflow::error(422, 'happy_call_required', 'Happy Call Completed must be recorded for the current survey first.');
        }

        return $state;
    }

    public static function readyQuery()
    {
        return Ticket::query()->where('workflow_version', Ticket::WORKFLOW_V3)
            ->whereDoesntHave('logs', fn ($q) => $q->where('step', TicketLog::STEP_CLOSED))
            ->whereHas('currentCycle', fn ($q) => $q->whereNull('ended_at')->where('documentation_status', 'documentation_published')
                ->whereHas('logs', fn ($logs) => $logs->where('step', TicketLog::STEP_DOCUMENTATION_PUBLISHED)))
            ->whereRaw('(SELECT latest_log.step FROM ticket_logs latest_log WHERE latest_log.ticket_id = tickets.id AND latest_log.deleted_at IS NULL ORDER BY latest_log.id DESC LIMIT 1) <> ?', [TicketLog::STEP_RECOMING]);
    }

    public static function notifyOverdue(int $ticketId): int
    {
        return DB::transaction(function () use ($ticketId) {
            $ticket = Ticket::whereKey($ticketId)->lockForUpdate()->first();
            if (! $ticket || ! TicketDocumentWorkflow::enabled($ticket)) {
                return 0;
            }
            $cycle = $ticket->currentCycle()->lockForUpdate()->first();
            if (! $cycle || $cycle->ended_at || ! self::documentationReady($ticket) || $ticket->progress === TicketLog::STEP_RECOMING) {
                return 0;
            }
            self::sync($ticket, $cycle);
            $state = self::state($ticket);
            if (! $state['overdue']) {
                return 0;
            }
            $recipients = User::where(fn ($users) => $users->whereHas('role', fn ($q) => $q->where('code', 'head_after_sales'))
                ->orWhere(fn ($q) => $q->whereKey($ticket->created_by)->whereHas('role', fn ($role) => $role->where('code', 'after_sales'))))->get();
            $count = 0;
            foreach ($recipients as $user) {
                if (DB::table('ticket_happy_call_escalations')->where('cycle_id', $cycle->id)->where('user_id', $user->id)->exists()) {
                    continue;
                }
                $notification = new HappyCallOverdueNotification($ticket, $cycle->id, $state['due_at']);
                $notification->id = (string) Str::uuid();
                $user->notify($notification);
                DB::table('ticket_happy_call_escalations')->insert(['ticket_id' => $ticket->id, 'cycle_id' => $cycle->id,
                    'user_id' => $user->id, 'notification_id' => $notification->id, 'due_at' => $state['due_at'], 'notified_at' => now('Asia/Jakarta')]);
                $count++;
            }

            return $count;
        }, 3);
    }
}
