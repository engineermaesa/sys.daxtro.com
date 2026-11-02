<?php

namespace App\Services\Aftersales;

use App\Http\Classes\ActivityLogger;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketRecoming;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketRecomings
{
    public static function current(Ticket $ticket): ?TicketRecoming
    {
        return $ticket->relationLoaded('recomings') ? $ticket->recomings->sortByDesc('id')->first() : $ticket->recomings()->latest('id')->first();
    }

    public static function canReschedule(Ticket $ticket): bool
    {
        $recoming = self::current($ticket);

        return $ticket->progress === TicketLog::STEP_RECOMING && $recoming?->scheduledAt()?->gt(Carbon::now('Asia/Jakarta')->endOfDay());
    }

    public static function guardPhysicalVisit(Ticket $ticket): void
    {
        if (TicketDocumentWorkflow::enabled($ticket) && self::current($ticket)?->recoming_type === TicketRecoming::TYPE_PHONE_CALL) {
            TicketDocumentWorkflow::error(409, 'phone_call_has_no_visit', 'Schedule a new visit_site Recoming cycle for a physical visit.');
        }
    }

    public static function canScheduleVisit(Ticket $ticket): bool
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return true;
        }
        $current = self::current($ticket);

        return ! $current || ($current->recoming_type !== TicketRecoming::TYPE_PHONE_CALL && self::canReschedule($ticket));
    }

    private static function scheduleData(Request $request, ?string $type = null): array
    {
        $creating = $type === null;
        $type ??= (string) $request->input('recoming_type');

        return $request->validate([
            'recoming_type' => $creating ? 'required|in:'.implode(',', TicketRecoming::TYPES) : 'prohibited',
            'planned_visit_at' => ($type === TicketRecoming::TYPE_VISIT_SITE ? 'required' : 'prohibited').'|nullable|date|after_or_equal:'.Carbon::now('Asia/Jakarta')->toDateString(),
            'planned_call_at' => [
                $type === TicketRecoming::TYPE_PHONE_CALL ? 'required' : 'prohibited', 'nullable', 'date',
                'regex:/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(?::\d{2}(?:\.\d{1,6})?)?(?:Z|[+-]\d{2}:\d{2})?$/',
                'after_or_equal:'.Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ],
            'assigned_technician_id' => 'prohibited',
            'supervisor_id' => 'prohibited',
        ]);
    }

    public static function schedule(Request $request, Ticket $ticket): TicketRecoming
    {
        $user = $request->user();
        abort_unless($user?->hasPermission('aftersales.tickets.manage'), 403);
        $data = self::scheduleData($request) + $request->validate([
            'reason' => 'required|in:'.implode(',', TicketRecoming::REASONS), 'notes' => 'nullable|string|max:10000',
        ]);
        $schedule = Carbon::parse($data[$data['recoming_type'] === TicketRecoming::TYPE_PHONE_CALL ? 'planned_call_at' : 'planned_visit_at'], 'Asia/Jakarta')->timezone('Asia/Jakarta');
        $sla = TicketSla::setup($request, $schedule);

        return DB::transaction(function () use ($ticket, $user, $data, $sla) {
            $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'This ticket is already closed.');
            abort_if($ticket->progress === TicketLog::STEP_RECOMING, 422, 'Reschedule the current Recoming before starting another one.');
            abort_if($ticket->serviceForm()->whereIn('status', ['saved', 'awaiting_spv_signature'])->exists(), 422, 'Unlock the Service Form first.');
            $fromStep = $ticket->progress;
            $cycle = TicketCycles::startRecoming($ticket);
            TicketSla::apply($ticket, $cycle, $sla);
            $visit = null;
            $visitNo = null;
            if ($data['recoming_type'] === TicketRecoming::TYPE_VISIT_SITE) {
                $visitNo = $ticket->visits()->count() + 1;
                $visit = $ticket->visits()->create([
                    'cycle_id' => $cycle->id, 'scheduled_at' => Carbon::parse($data['planned_visit_at'], 'Asia/Jakarta')->timezone('Asia/Jakarta'),
                ]);
            }
            $recoming = $ticket->recomings()->create([
                'cycle_id' => $cycle->id, 'recoming_type' => $data['recoming_type'],
                'visit_no' => $visitNo, 'ticket_visit_id' => $visit?->id,
                'reason' => $data['reason'], 'notes' => $data['notes'] ?? null,
                'planned_visit_at' => $visit?->scheduled_at,
                'planned_call_at' => $data['recoming_type'] === TicketRecoming::TYPE_PHONE_CALL ? Carbon::parse($data['planned_call_at'], 'Asia/Jakarta')->timezone('Asia/Jakarta') : null,
                'from_step' => $fromStep, 'created_by_user_id' => $user->id,
            ]);
            SatisfactionLink::deactivateFor($ticket);
            $ticket->logs()->create(['cycle_id' => $cycle->id, 'step' => TicketLog::STEP_RECOMING, 'actor_user_id' => $user->id, 'note' => $data['notes'] ?? null]);

            return $recoming->load('cycle');
        });
    }

    public static function reschedule(Request $request, Ticket $ticket): TicketRecoming
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        $current = self::current($ticket);
        abort_if(! $current, 422, 'No Recoming schedule exists.');
        $data = self::scheduleData($request, $current->recoming_type ?? TicketRecoming::TYPE_VISIT_SITE)
            + $request->validate(['note' => 'nullable|string|max:1000']);

        return DB::transaction(function () use ($ticket, $current, $data, $request) {
            $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            abort_unless(self::canReschedule($ticket), 422, 'Reschedule is only available before the scheduled day arrives and before Repair.');
            $recoming = $ticket->recomings()->latest('id')->lockForUpdate()->firstOrFail();
            abort_unless($recoming->id === $current->id, 409, 'The Recoming cycle changed. Reload the ticket.');
            $phone = $recoming->recoming_type === TicketRecoming::TYPE_PHONE_CALL;
            $from = $recoming->scheduledAt();
            $date = Carbon::parse($data[$phone ? 'planned_call_at' : 'planned_visit_at'], 'Asia/Jakarta')->timezone('Asia/Jakarta');
            $cycle = $ticket->cycles()->whereKey($recoming->cycle_id)->lockForUpdate()->firstOrFail();
            TicketSla::reschedule($request, $ticket, $cycle, $date);
            $recoming->update([$phone ? 'planned_call_at' : 'planned_visit_at' => $date]);
            if (! $phone) {
                $visit = $ticket->visits()->where('cycle_id', $recoming->cycle_id)->whereKey($recoming->ticket_visit_id)->lockForUpdate()->first();
                abort_if(! $visit, 409, 'The physical visit is not linked to this Recoming cycle.');
                $visit->update(['scheduled_at' => $date]);
            }
            ActivityLogger::writeLog('recoming_rescheduled', 'Recoming schedule updated', $ticket, [
                'cycle_id' => $recoming->cycle_id, 'recoming_type' => $recoming->recoming_type,
                'from' => $from?->toIso8601String(), 'to' => $date->toIso8601String(), 'note' => $data['note'] ?? null,
            ], $request->user());

            return $recoming->refresh()->load('cycle');
        });
    }
}
