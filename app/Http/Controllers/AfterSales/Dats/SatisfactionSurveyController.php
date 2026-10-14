<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\TicketSatisfaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SatisfactionSurveyController extends Controller
{
    private const SOURCE_LABELS = [
        TicketSatisfaction::SOURCE_LINK => 'Customer link',
        TicketSatisfaction::SOURCE_ADMIN => 'Admin assisted',
    ];

    private const WITH = ['ticket.customer', 'ticket.technician', 'ticket.visits', 'ticket.workOrders'];

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.view'), 403);

        $request->validate([
            'search' => 'nullable|string|max:100',
            'review_status' => 'nullable|in:' . implode(',', [
                TicketSatisfaction::REVIEW_NORMAL,
                TicketSatisfaction::REVIEW_NEEDS_REVIEW,
                TicketSatisfaction::REVIEW_REVIEWED,
            ]),
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = TicketSatisfaction::with(self::WITH);

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
            ]),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(Request $request, TicketSatisfaction $satisfaction)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.view'), 403);

        $satisfaction->load(array_merge(self::WITH, ['linkCreatedBy', 'reviewedBy']));

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
            ],
        ]);
    }

    public function form(Request $request, TicketSatisfaction $satisfaction)
    {
        abort_unless($request->user()?->hasPermission('satisfaction-survey.view'), 403);

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

        if ($satisfaction->review_status !== TicketSatisfaction::REVIEW_NEEDS_REVIEW) {
            abort(422, 'Only responses that need review can be marked as reviewed.');
        }

        $satisfaction->update([
            'review_status' => TicketSatisfaction::REVIEW_REVIEWED,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return $this->setJsonResponse('Survey response marked as reviewed', ['data' => $satisfaction->fresh()]);
    }

    private function feedback(TicketSatisfaction $s): array
    {
        return [
            'ratings' => $this->ratings($s),
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
        $ratings = [];
        foreach (TicketSatisfaction::ASPECTS as $key => $label) {
            
            if ($s->{$key} !== null) {
                $ratings[] = ['key' => $key, 'label' => $label, 'score' => (int) $s->{$key}];
            }
        }

        return $ratings;
    }

    private function serviceDate(TicketSatisfaction $s): ?Carbon
    {
        $visit = $s->ticket->visits->sortByDesc('scheduled_at')->first();

        return $visit?->actual_start ?? $visit?->scheduled_at;
    }
}
