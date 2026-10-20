<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Http\Classes\ActivityLogger;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketSatisfaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SatisfactionSurveyController extends Controller
{
    private const SOURCE_LABELS = [
        TicketSatisfaction::SOURCE_LINK => 'Customer link',
        TicketSatisfaction::SOURCE_ADMIN => 'Admin assisted',
    ];

    private const WITH = ['ticket.customer', 'ticket.technician', 'ticket.visits', 'ticket.workOrders', 'template', 'answers'];

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.view'), 403);

        $request->validate([
            'search' => 'nullable|string|max:100',
            'review_status' => 'nullable|in:' . implode(',', [
                TicketSatisfaction::REVIEW_NORMAL,
                TicketSatisfaction::REVIEW_NEEDS_REVIEW,
                TicketSatisfaction::REVIEW_REVIEWED,
                TicketSatisfaction::REVIEW_REJECTED,
            ]),
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = TicketSatisfaction::with(array_merge(self::WITH, ['reviewedBy']))->whereHas('ticket');

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->input('review_status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('ticket', function ($t) use ($search) {
                $t->where('ticket_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('technician', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $paginated = $query->orderByDesc('filled_at')->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => collect($paginated->items())->map(fn (TicketSatisfaction $s) => [
                'id' => $s->id,
                'ticket_id' => $s->ticket_id,
                'ticket_code' => $s->ticket->ticket_code,
                'customer' => $s->ticket->customer?->name,
                'technician' => $s->ticket->technician?->name,
                'service_date' => $this->serviceDate($s)?->toDateString(),
                'submitted_at' => $s->filled_at,
                'average_score' => $s->average_score,
                'machine_ok' => $s->machine_ok,
                'source' => $s->source,
                'source_label' => self::SOURCE_LABELS[$s->source] ?? $s->source,
                'review_status' => $s->review_status,
                'review_reasons' => $s->review_reasons ?? [],
                'reviewed_by' => $s->reviewedBy?->name,
                'is_current' => $s->ticket->satisfactions()->max('id') === $s->id,
            ]),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(Request $request, TicketSatisfaction $satisfaction)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.view'), 403);

        abort_unless($satisfaction->ticket, 404);
        $satisfaction->load(array_merge(self::WITH, ['linkCreatedBy', 'reviewedBy', 'ticket.satisfactions.reviewedBy']));
        $all = $satisfaction->ticket->satisfactions->sortByDesc('id')->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $satisfaction->id,
                'ticket_id' => $satisfaction->ticket_id,
                'ticket_code' => $satisfaction->ticket->ticket_code,
                'summary' => [
                    'average_score' => $satisfaction->average_score,
                    'customer' => $satisfaction->ticket->customer?->name,
                    'machine_ok' => $satisfaction->machine_ok,
                    'source' => $satisfaction->source,
                    'source_label' => self::SOURCE_LABELS[$satisfaction->source] ?? $satisfaction->source,
                    'admin_input_reason' => $satisfaction->admin_input_reason,
                ],
                'feedback' => $this->feedback($satisfaction),
                'security_trail' => [
                    'otp_verified' => $satisfaction->otp_verified_at !== null,
                    'otp_verified_at' => $satisfaction->otp_verified_at,
                    'otp_phone_masked' => $satisfaction->otp_phone_masked,
                    'link_created_by' => $satisfaction->linkCreatedBy?->name,
                    'shared_via' => $satisfaction->shared_via,
                    'ip_address' => $satisfaction->ip_address,
                    'user_agent' => $satisfaction->user_agent,
                    'duration_seconds' => $satisfaction->duration_seconds,
                    'review_status' => $satisfaction->review_status,
                    'review_reasons' => $satisfaction->review_reasons ?? [],
                ],
                'reviewed_by' => $satisfaction->reviewedBy?->name,
                'reviewed_at' => $satisfaction->reviewed_at,
                'review_note' => $satisfaction->review_note,
                'is_current' => $all->first()?->id === $satisfaction->id,
                'responses' => $all->map(fn (TicketSatisfaction $s, $i) => [
                    'id' => $s->id,
                    'submitted_at' => $s->filled_at,
                    'average_score' => $s->average_score,
                    'review_status' => $s->review_status,
                    'review_note' => $s->review_note,
                    'reviewed_by' => $s->reviewedBy?->name,
                    'current' => $i === 0,
                ]),
            ],
        ]);
    }

    public function form(Request $request, TicketSatisfaction $satisfaction)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.view'), 403);
        abort_unless($satisfaction->ticket, 404);

        $satisfaction->load(self::WITH);
        $ticket = $satisfaction->ticket;

        return response()->json([
            'status' => 'success',
            'data' => [
                'read_only' => true,
                'job_information' => [
                    'ticket_code' => $ticket->ticket_code,
                    'customer' => $ticket->customer?->name,
                    'technician' => $ticket->technician?->name,
                    'service_date' => $this->serviceDate($satisfaction)?->toDateString(),
                    'wo_number' => $ticket->workOrders->sortByDesc('id')->first()?->wo_number,
                ],
                'ratings' => $this->ratings($satisfaction),
                'template' => $this->templateData($satisfaction),
                'answers' => $this->answerData($satisfaction),
                'machine_ok' => $satisfaction->machine_ok,
                'machine_issue_note' => $satisfaction->machine_issue_note,
                'comments' => $satisfaction->feedback,
                'signer' => [
                    'name' => $satisfaction->signer_name,
                    'position' => $satisfaction->signer_position,
                    'signature_url' => $this->signatureUrl($satisfaction),
                ],
            ],
        ]);
    }

    public function signature(Request $request, TicketSatisfaction $satisfaction)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.view'), 403);
        abort_if(! $satisfaction->signature_path || ! Storage::disk('local')->exists($satisfaction->signature_path), 404, 'No signature for this response.');

        return Storage::disk('local')->response($satisfaction->signature_path, null, ['Content-Type' => 'image/png']);
    }

    public function review(Request $request, TicketSatisfaction $satisfaction)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.review'), 403);
        abort_unless($satisfaction->ticket, 404);

        $validated = $request->validate([
            'decision' => 'nullable|in:approve,reject',
            'note' => 'required_if:decision,reject|nullable|string|max:2000',
        ]);
        $decision = $validated['decision'] ?? 'approve';
        $user = $request->user();

        DB::transaction(function () use ($satisfaction, $decision, $validated, $user) {
            $locked = TicketSatisfaction::whereKey($satisfaction->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->review_status === TicketSatisfaction::REVIEW_NEEDS_REVIEW, 422, 'Only responses that need review can be reviewed.');

            $ticket = $satisfaction->ticket;
            if ($decision === 'reject') {
                abort_unless($ticket->satisfactions()->max('id') == $satisfaction->id, 422, 'Only the current response can be rejected.');
                abort_unless(in_array($ticket->progress, [TicketLog::STEP_DOCUMENTATION_PUBLISHED, TicketLog::STEP_SATISFACTION_SUBMITTED], true), 422, 'A new survey link can only be issued after Documentation Published.');
            }

            $locked->update([
                'review_status' => $decision === 'reject' ? TicketSatisfaction::REVIEW_REJECTED : TicketSatisfaction::REVIEW_REVIEWED,
                'review_note' => $validated['note'] ?? null,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => Carbon::now('Asia/Jakarta'),
            ]);

           
            if ($decision === 'reject') {
                SatisfactionLink::where('ticket_id', $ticket->id)->where('status', SatisfactionLink::STATUS_ACTIVE)
                    ->update(['status' => SatisfactionLink::STATUS_REPLACED]);
                SatisfactionLink::issue($ticket, $user->id);
            }

            ActivityLogger::writeLog('response_reviewed', "Survey response {$ticket->ticket_code}: {$decision}", $locked, [
                'decision' => $decision,
                'note' => $validated['note'] ?? null,
                'reviewer' => $user->name,
            ]);
        });

        return $this->setJsonResponse($decision === 'reject' ? 'Response rejected; a new survey link was issued' : 'Response approved', ['data' => $satisfaction->fresh()]);
    }

    public static function needsReview(): int
    {
        return TicketSatisfaction::where('review_status', TicketSatisfaction::REVIEW_NEEDS_REVIEW)->whereHas('ticket')->count();
    }

    private function feedback(TicketSatisfaction $s): array
    {
        return [
            'ratings' => $this->ratings($s),
            'template' => $this->templateData($s),
            'answers' => $this->answerData($s),
            'machine_ok' => $s->machine_ok,
            'machine_issue_note' => $s->machine_issue_note,
            'comments' => $s->feedback,
            'signer_name' => $s->signer_name,
            'signer_position' => $s->signer_position,
            'signature_url' => $this->signatureUrl($s),
        ];
    }

    private function signatureUrl(TicketSatisfaction $s): ?string
    {
        return $s->signature_path ? route('aftersales.satisfaction-surveys.signature', $s->id, false) : null;
    }

    private function ratings(TicketSatisfaction $s): array
    {
        return $s->ratingItems();
    }

    private function templateData(TicketSatisfaction $s): ?array
    {
        return $s->template ? ['version' => $s->template->version, 'title' => $s->template->title, 'scale_labels' => $s->template->content['scale_labels'], 'sections' => $s->template->documentSections()] : null;
    }

    private function answerData(TicketSatisfaction $s): array
    {
        if (! $s->template) return [];
        $answers = $s->answers->keyBy('question_key');
        return collect($s->template->questions())->map(function ($q) use ($answers) {
            $answer = $answers->get($q['question_key']);
            $value = in_array($q['type'], ['rating', 'yes_no']) ? $answer?->value_number : $answer?->value_text;
            return ['question_key' => $q['question_key'], 'label' => $q['label'], 'type' => $q['type'], 'value' => $q['type'] === 'yes_no' && $value !== null ? (bool) $value : $value];
        })->all();
    }

    private function serviceDate(TicketSatisfaction $s): ?Carbon
    {
        $visit = $s->ticket->visits->sortByDesc('scheduled_at')->first();

        return $visit?->actual_start ?? $visit?->scheduled_at;
    }
}
