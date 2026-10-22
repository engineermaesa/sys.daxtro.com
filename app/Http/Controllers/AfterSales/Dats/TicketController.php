<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Classes\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Models\Aftersales\DocumentCounter;
use App\Models\Aftersales\RefSparepart;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCost;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketPart;
use App\Models\Aftersales\TicketPhoto;
use App\Models\Aftersales\TicketRecoming;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\Aftersales\TicketVisit;
use App\Models\Aftersales\TicketWorkOrder;
use App\Services\Aftersales\TicketDocuments;
use App\Services\Aftersales\TicketStock;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function page(Request $request)
    {

        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage') || $request->user()?->hasPermission('aftersales.tickets.view-own'), 403);

        $this->pageTitle = 'Tickets';

        return $this->render('pages.aftersales.tickets.index');
    }

    public function createPage(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $this->pageTitle = 'Tambah Ticket';

        return $this->render('pages.aftersales.tickets.create');
    }

    public function workOrderPdf(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission('aftersales.work-orders.issue')
            || ($user?->hasPermission('aftersales.tickets.view-own') && $ticket->assigned_technician_id === $user->id),
            403
        );

        $ticket->load([
            'customer.region',
            'customer.province',
            'customerProduct.product',
            'technician',
            'visits',
            'parts.sparepart',
            'costs',
        ]);

        $workOrder = $ticket->workOrders()->latest('issued_at')->first();

        if (! $workOrder) {
            $now = Carbon::now('Asia/Jakarta');
            $latestVisit = $ticket->visits->sortByDesc('scheduled_at')->first();

            $workOrder = TicketWorkOrder::create([
                'ticket_id' => $ticket->id,
                'ticket_visit_id' => $latestVisit?->id,
                'wo_number' => str_replace('DAX-AF-', 'WO-', $ticket->ticket_code),
                'technician_id' => $ticket->assigned_technician_id,
                'issued_at' => $now,
            ]);
        }

        $workOrder->load('visit');

        $pdf = Pdf::loadView('pdfs.ticket_work_order', [
            'ticket' => $ticket,
            'workOrder' => $workOrder,
            'partsEstimated' => $ticket->parts->where('type', TicketPart::TYPE_ESTIMATED),
            'costs' => $ticket->costs,
            'rates' => app(\App\Services\ExchangeRateService::class)->get(),
        ])->setPaper('A4', 'portrait');

        return $pdf->download($workOrder->wo_number.'.pdf');
    }

    public function showPage(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission('aftersales.tickets.manage')
            || ($user?->hasPermission('aftersales.tickets.view-own') && $ticket->assigned_technician_id === $user->id),
            403
        );

        $this->pageTitle = 'Ticket '.$ticket->ticket_code;

        return $this->render('pages.aftersales.tickets.show', ['ticket' => $ticket]);
    }

    public function editPage(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $this->pageTitle = 'Edit Ticket';

        $ticket->load(['customer', 'customerProduct', 'technician', 'supervisor', 'visits', 'parts.sparepart', 'costs']);

        return $this->render('pages.aftersales.tickets.create', ['ticket' => $ticket]);
    }

    /**
     * Preview of the ticket code that will be assigned on save (PRD §2.1 /
     * §3.4 code format). Purely informational — the real number is claimed
     * atomically in store().
     */
    public function nextCode(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $now = Carbon::now('Asia/Jakarta');
        $sequence = DocumentCounter::peekNextNumber(DocumentCounter::TYPE_TICKET, $now);

        return response()->json([
            'status' => 'success',
            'data' => ['ticket_code' => 'DAX-AF-'.$now->format('Ymd').'-'.$sequence],
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isManager = (bool) $user?->hasPermission('aftersales.tickets.manage');

        $ownOnly = ! $isManager && $user?->hasPermission('aftersales.tickets.view-own');
        abort_unless($isManager || $ownOnly, 403);

        $query = Ticket::with(['customer.province', 'customer.region', 'customerProduct.product', 'technician', 'logs.actor', 'visits', 'recomings', 'serviceForm', 'surveyLink', 'satisfactions']);

        if ($ownOnly) {
            $query->where('assigned_technician_id', $user->id);
        }

        // "Supervisor: Saya" filter: tickets waiting for my signature (FR-08)
        if ($request->input('supervisor') === 'me') {
            $query->where('supervisor_id', $user->id);
        }
        if ($request->filled('supervisor_id')) {
            $query->where('supervisor_id', $request->input('supervisor_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('priority')) {
            $query->whereIn('priority', (array) $request->input('priority'));
        }

        if ($request->filled('assigned_technician_id')) {
            $query->where('assigned_technician_id', $request->input('assigned_technician_id'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('technician', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->orderByDesc('id')->paginate($perPage);

        $items = collect($paginated->items())->map(function (Ticket $t) {
            $latestVisit = $t->visits->sortByDesc('scheduled_at')->first();
            $closedLog = $t->logs->where('step', TicketLog::STEP_CLOSED)->sortByDesc('id')->first();
            $data = $t->toArray();
            // Reminder inputs are internal; never expose survey answers in the ticket list.
            unset($data['satisfactions'], $data['service_form'], $data['survey_link']);

            return array_merge($data, [
                'current_recoming' => $this->currentRecoming($t),
                'progress' => $t->progress,
                'aging_days' => $t->aging_days,
                'sla_status' => $t->sla_status,
                'visit_date' => $latestVisit?->scheduled_at,
                'visit_no' => $t->visits->count(),
                'closed_at' => $closedLog?->created_at,
                'reminders' => \App\Services\Aftersales\TicketReminders::for($t),
            ]);
        });

        if ($request->filled('progress')) {
            $items = $items->where('progress', $request->input('progress'))->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        // admin/supervisor see any ticket; a technician only their own (UC-02)
        abort_unless(
            $user?->hasPermission('aftersales.tickets.manage')
            || ($user?->hasPermission('aftersales.tickets.view-own') && $ticket->assigned_technician_id === $user->id),
            403
        );

        TicketDocuments::ensure($ticket);

        $ticket->load([
            'customer', 'customerProduct.industry', 'technician', 'supervisor',
            'logs' => fn ($q) => $q->orderBy('id'),
            'logs.actor',
            'visits', 'workOrders', 'parts.sparepart', 'costs', 'photos', 'satisfaction', 'recomings', 'serviceForm', 'agreement',
            'satisfactions' => fn ($q) => $q->orderByDesc('id'),
        ]);

        $isManager = (bool) $user->hasPermission('aftersales.tickets.manage');
        $data = $ticket->toArray();
        unset($data['satisfactions']);
        if (! $isManager) {
            unset($data['satisfaction']);
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge($data, [
                'survey_responses' => $isManager ? $ticket->satisfactions->map(fn (TicketSatisfaction $s) => [
                    'id' => $s->id,
                    'submitted_at' => $s->filled_at,
                    'average_score' => $s->average_score,
                    'machine_ok' => $s->machine_ok,
                    'source' => $s->source,
                    'review_status' => $s->review_status,
                    'review_note' => $s->review_note,
                ])->values() : null,
                'survey_filled' => $ticket->satisfactions->isNotEmpty(),
                'visit_history' => $this->visitHistory($ticket),
                'stock_held' => TicketStock::held($ticket),
                'can_reschedule' => $this->canReschedule($ticket),
                'documents' => [
                    'service_form' => $ticket->serviceForm?->status,
                    'agreement' => $ticket->agreement?->status,
                ],
                'current_recoming' => $this->currentRecoming($ticket),
                'reminders' => \App\Services\Aftersales\TicketReminders::for($ticket),
                'progress' => $ticket->progress,
                'aging_days' => $ticket->aging_days,
                'sla_status' => $ticket->sla_status,
                'total_cost' => $ticket->total_cost,
            ]),
        ]);
    }

    /** Users eligible to supervise new tickets. Existing assignments are retained. */
    public function supervisors(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $users = \App\Models\User::query()
            ->whereHas('role', fn ($q) => $q->where('code', 'supervisor_after_sales'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['status' => 'success', 'data' => $users]);
    }

    /** Live IDR rates for USD and CNY (cached), used by the Cost step of the ticket form. */
    public function exchangeRates(\App\Services\ExchangeRateService $rates)
    {
        return response()->json(['status' => 'success', 'data' => $rates->get()]);
    }

    /**
     * Input Ticket flow (PRD §2.1 / §3.4). Creates the ticket, its estimated
     * sparepart & cost lines, and the initial `published` log — atomically.
     */
    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:service_customers,id',
            'customer_product_id' => 'required|integer|exists:service_customer_products,id',
            'category' => 'required|in:'.implode(',', Ticket::CATEGORIES),
            'priority' => 'required|in:'.implode(',', Ticket::PRIORITIES),
            'description' => 'required|string',
            'sla_value' => 'required|integer|min:1',
            'sla_unit' => 'required|in:hour,day',
            'sla_due_at' => 'nullable|date',
            'cost_display_currency' => 'nullable|in:idr,usd,cny',
            'supervisor_id' => 'nullable|integer|exists:users,id',
            'parts' => 'nullable|array',
            'parts.*.ref_sparepart_id' => 'required_with:parts|integer|exists:ref_spareparts,id',
            'parts.*.qty' => 'required_with:parts|integer|min:1',
            'parts.*.unit' => 'required_with:parts|in:'.implode(',', TicketPart::UNITS),
            'costs' => 'nullable|array',
            'costs.*.category' => 'required_with:costs|in:'.implode(',', TicketCost::CATEGORIES),
            'costs.*.amount' => 'required_with:costs|numeric|min:0',
            'costs.*.currency' => 'required_with:costs|in:'.implode(',', TicketCost::CURRENCIES),
            'costs.*.exchange_rate' => 'nullable|numeric|min:0',
            'costs.*.remarks' => 'nullable|string',
        ]);

        $product = ServiceCustomerProduct::findOrFail($validated['customer_product_id']);
        $this->validateSupervisor($validated['supervisor_id'] ?? null);
        if ($product->customer_id !== (int) $validated['customer_id']) {
            throw ValidationException::withMessages([
                'customer_product_id' => 'The selected product does not belong to the selected customer.',
            ]);
        }

        $ticket = DB::transaction(function () use ($validated, $request) {
            $machine = ServiceCustomerProduct::whereKey($validated['customer_product_id'])->lockForUpdate()->firstOrFail();
            abort_unless($machine->customer_id === (int) $validated['customer_id'], 409, 'The machine owner changed. Reload before creating the ticket.');
            $now = Carbon::now('Asia/Jakarta');
            $sequence = DocumentCounter::nextNumber(DocumentCounter::TYPE_TICKET, $now);
            $ticketCode = 'DAX-AF-'.$now->format('Ymd').'-'.$sequence;

            $slaValue = (int) $validated['sla_value'];
            $slaDueAt = ! empty($validated['sla_due_at'])
                ? Carbon::parse($validated['sla_due_at'], 'Asia/Jakarta')
                : ($validated['sla_unit'] === 'hour'
                    ? $now->copy()->addHours($slaValue)
                    : $now->copy()->addDays($slaValue));

            $ticket = Ticket::create([
                'ticket_code' => $ticketCode,
                'customer_id' => $validated['customer_id'],
                'customer_product_id' => $validated['customer_product_id'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'description' => $validated['description'],
                'sla_value' => $validated['sla_value'],
                'sla_unit' => $validated['sla_unit'],
                'sla_due_at' => $slaDueAt,
                'cost_display_currency' => $validated['cost_display_currency'] ?? 'idr',
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach (collect($validated['parts'] ?? [])->groupBy('ref_sparepart_id')->sortKeys() as $sparepartId => $rows) {
                TicketStock::add($ticket, (int) $sparepartId, (int) $rows->sum('qty'), $rows[0]['unit'], $request->user()->id, true);
            }

            foreach ($validated['costs'] ?? [] as $cost) {
                TicketCost::create([
                    'ticket_id' => $ticket->id,
                    'category' => $cost['category'],
                    'amount' => $cost['amount'],
                    'currency' => $cost['currency'],
                    'exchange_rate' => $cost['currency'] !== 'idr' ? ($cost['exchange_rate'] ?? null) : null,
                    'remarks' => $cost['remarks'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
            }

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'step' => TicketLog::STEP_PUBLISHED,
                'actor_user_id' => $request->user()->id,
                'created_by' => $request->user()->id,
            ]);

            return $ticket;
        });

        $ticket->load(['customer', 'customerProduct', 'parts', 'costs', 'logs']);

        return $this->setJsonResponse('Ticket created successfully', ['data' => $ticket], 201);
    }

    /**
     * Updates the ticket's core fields (customer, machine, complaint details,
     * SLA, assignment) plus the estimated sparepart/cost lines from the
     * wizard. Visit scheduling keeps using its dedicated endpoint, and actual
     * (post-documentation) parts are never touched here.
     */
    public function update(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:service_customers,id',
            'customer_product_id' => 'required|integer|exists:service_customer_products,id',
            'category' => 'required|in:'.implode(',', Ticket::CATEGORIES),
            'priority' => 'required|in:'.implode(',', Ticket::PRIORITIES),
            'description' => 'required|string',
            'sla_value' => 'required|integer|min:1',
            'sla_unit' => 'required|in:hour,day',
            'sla_due_at' => 'nullable|date',
            'cost_display_currency' => 'nullable|in:idr,usd,cny',
            'supervisor_id' => 'nullable|integer|exists:users,id',
            'assigned_technician_id' => 'nullable|integer|exists:users,id',
            'parts' => 'nullable|array',
            'parts.*.ref_sparepart_id' => 'required_with:parts|integer|exists:ref_spareparts,id',
            'parts.*.qty' => 'required_with:parts|integer|min:0',
            'parts.*.unit' => 'required_with:parts|in:'.implode(',', TicketPart::UNITS),
            'costs' => 'nullable|array',
            'costs.*.category' => 'required_with:costs|in:'.implode(',', TicketCost::CATEGORIES),
            'costs.*.amount' => 'required_with:costs|numeric|min:0',
            'costs.*.currency' => 'required_with:costs|in:'.implode(',', TicketCost::CURRENCIES),
            'costs.*.exchange_rate' => 'nullable|numeric|min:0',
            'costs.*.remarks' => 'nullable|string',
        ]);

        $product = ServiceCustomerProduct::findOrFail($validated['customer_product_id']);
        $this->validateSupervisor($validated['supervisor_id'] ?? null, $ticket->supervisor_id);
        if ($product->customer_id !== (int) $validated['customer_id']) {
            throw ValidationException::withMessages([
                'customer_product_id' => 'The selected product does not belong to the selected customer.',
            ]);
        }

        $now = Carbon::now('Asia/Jakarta');
        $slaValue = (int) $validated['sla_value'];
        $slaDueAt = ! empty($validated['sla_due_at'])
            ? Carbon::parse($validated['sla_due_at'], 'Asia/Jakarta')
            : ($validated['sla_unit'] === 'hour'
                ? $now->copy()->addHours($slaValue)
                : $now->copy()->addDays($slaValue));

        DB::transaction(function () use ($validated, $ticket, $request, $slaDueAt) {
            $machine = ServiceCustomerProduct::whereKey($validated['customer_product_id'])->lockForUpdate()->firstOrFail();
            abort_unless($machine->customer_id === (int) $validated['customer_id'], 409, 'The machine owner changed. Reload before updating the ticket.');
            $ticket->update([
                'customer_id' => $validated['customer_id'],
                'customer_product_id' => $validated['customer_product_id'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'description' => $validated['description'],
                'sla_value' => $validated['sla_value'],
                'sla_unit' => $validated['sla_unit'],
                'sla_due_at' => $slaDueAt,
                'cost_display_currency' => $validated['cost_display_currency'] ?? $ticket->cost_display_currency ?? 'idr',
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'assigned_technician_id' => $validated['assigned_technician_id'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            if ($request->has('costs')) {
                $costs = $validated['costs'] ?? [];
                $submittedCategories = array_column($costs, 'category');

                $ticket->costs()->whereNotIn('category', $submittedCategories)->delete();

                foreach ($costs as $cost) {
                    TicketCost::updateOrCreate(
                        ['ticket_id' => $ticket->id, 'category' => $cost['category']],
                        [
                            'amount' => $cost['amount'],
                            'currency' => $cost['currency'],
                            'exchange_rate' => $cost['currency'] !== 'idr' ? ($cost['exchange_rate'] ?? null) : null,
                            'remarks' => $cost['remarks'] ?? null,
                            'updated_by' => $request->user()->id,
                        ]
                    );
                }
            }

            if ($request->has('parts')) {
                $submitted = collect($validated['parts'] ?? [])->groupBy('ref_sparepart_id');
                $existing = $ticket->parts()->where('type', TicketPart::TYPE_ESTIMATED)->get()->keyBy('ref_sparepart_id');
                $changed = $existing->contains(fn ($p, $id) => (int) $p->qty !== (int) ($submitted->get($id)?->sum('qty') ?? 0))
                    || $submitted->keys()->diff($existing->keys())->isNotEmpty();
                if ($changed) {
                    $this->guardPartsEditable($ticket);
                }

                $ids = $existing->keys()->merge($submitted->keys())->map(fn ($id) => (int) $id)->unique()->sort();
                foreach ($ids as $sparepartId) {
                    $rows = $submitted->get($sparepartId);
                    $part = $existing->get($sparepartId);
                    if (! $part) {
                        TicketStock::add($ticket, $sparepartId, (int) $rows->sum('qty'), $rows[0]['unit'], $request->user()->id);

                        continue;
                    }
                    if ($rows) {
                        $part->update(['unit' => $rows[0]['unit']]);
                    }
                    TicketStock::setUsed($part, $rows ? (int) $rows->sum('qty') : 0, $request->user()->id);
                }
            }
        });

        TicketDocuments::ensure($ticket->fresh());

        return $this->setJsonResponse('Ticket updated successfully', [
            'data' => $ticket->fresh(['customer', 'customerProduct', 'technician', 'supervisor', 'parts', 'costs']),
        ]);
    }

    public function assign(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.assign'), 403);
        $this->guardNotClosed($ticket);

        $validated = $request->validate([
            'assigned_technician_id' => 'required|integer|exists:users,id',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $ticket, $request) {
            $ticket->update([
                'assigned_technician_id' => $validated['assigned_technician_id'],
                'updated_by' => $request->user()->id,
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'step' => TicketLog::STEP_ASSIGNED,
                'actor_user_id' => $request->user()->id,
                'note' => $validated['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        TicketDocuments::ensure($ticket->fresh());

        return $this->setJsonResponse('Technician assigned successfully', ['data' => $ticket->fresh(['technician', 'logs'])]);
    }

    public function onSite(Request $request, Ticket $ticket)
    {
        return $this->logStep($request, $ticket, TicketLog::STEP_ON_SITE, 'aftersales.tickets.manage');
    }

    public function repair(Request $request, Ticket $ticket)
    {
        return $this->logStep($request, $ticket, TicketLog::STEP_REPAIR, 'aftersales.tickets.manage');
    }

    public function waitingSparepart(Request $request, Ticket $ticket)
    {
        return $this->logStep($request, $ticket, TicketLog::STEP_WAITING_SPAREPART, 'aftersales.tickets.manage');
    }

    private function logStep(Request $request, Ticket $ticket, string $step, string $permission)
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
        $this->guardNotClosed($ticket);
        $this->guardSequential($ticket, $step);

        if ($step === TicketLog::STEP_ON_SITE) {
            abort_if($ticket->visits()->count() === 0, 422, 'Set a visit schedule before logging On Site.');
        }

        $validated = $request->validate(['note' => 'nullable|string']);

        $log = TicketLog::create([
            'ticket_id' => $ticket->id,
            'step' => $step,
            'actor_user_id' => $request->user()->id,
            'note' => $validated['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return $this->setJsonResponse('Ticket progress updated successfully', ['data' => $log], 201);
    }

    public function storeVisit(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        $this->guardNotClosed($ticket);

        $validated = $request->validate([
            'scheduled_at' => 'required|date',
        ]);

        if ($ticket->progress === TicketLog::STEP_RECOMING) {
            $this->moveFollowUpVisit($request, $ticket, Carbon::parse($validated['scheduled_at'], 'Asia/Jakarta'), null);

            return $this->setJsonResponse('Follow-up visit updated successfully', ['data' => $ticket->visits()->latest('id')->first()]);
        }

        $pending = $ticket->visits()->whereNull('actual_start')->latest('id')->first();
        if ($pending) {
            $pending->update(['scheduled_at' => $validated['scheduled_at']]);

            return $this->setJsonResponse('Visit rescheduled successfully', ['data' => $pending->fresh()]);
        }

        $visit = TicketVisit::create([
            'ticket_id' => $ticket->id,
            'scheduled_at' => $validated['scheduled_at'],
            'created_by' => $request->user()->id,
        ]);

        return $this->setJsonResponse('Visit scheduled successfully', ['data' => $visit], 201);
    }

    public function updateVisit(Request $request, Ticket $ticket, TicketVisit $visit)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        abort_unless($visit->ticket_id === $ticket->id, 404);

        $validated = $request->validate([
            'actual_start' => 'nullable|date',
            'actual_end' => 'nullable|date|after_or_equal:actual_start',
        ]);

        $validated['updated_by'] = $request->user()->id;
        $visit->update($validated);

        return $this->setJsonResponse('Visit updated successfully', ['data' => $visit->fresh()]);
    }

    public function recoming(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $isManager = $user?->hasPermission('aftersales.tickets.manage');
        $isOwnTechnician = $user?->hasPermission('aftersales.tickets.recoming')
            && $ticket->assigned_technician_id === $user->id;
        abort_unless($isManager || $isOwnTechnician, 403);
        $this->guardNotClosed($ticket);

        $validated = $request->validate([
            'reason' => 'required|in:'.implode(',', TicketRecoming::REASONS),
            'planned_visit_at' => 'required|date|after_or_equal:'.Carbon::now('Asia/Jakarta')->toDateString(),
            'notes' => 'nullable|string',
        ]);

        $allowed = [TicketLog::STEP_ON_SITE, TicketLog::STEP_REPAIR, TicketLog::STEP_WAITING_SPAREPART];
        if ($isManager) {
            $allowed = array_merge($allowed, [TicketLog::STEP_DOCUMENTATION_PUBLISHED, TicketLog::STEP_SATISFACTION_SUBMITTED, TicketLog::STEP_HAPPY_CALL_COMPLETED]);
        }
        $fromStep = $ticket->progress;
        abort_unless(in_array($fromStep, $allowed, true), 422, 'Recoming is not allowed from the current progress.');
        // RC-04: the single Service Form must stay editable across visits
        abort_if($ticket->serviceForm()->where('status', 'saved')->exists(), 422, 'Unlock the Service Form first: it is saved and locked.');

        $recoming = DB::transaction(function () use ($validated, $ticket, $user, $fromStep) {
            $visitNo = $ticket->visits()->count() + 1;

            $ticket->visits()->create([
                'scheduled_at' => Carbon::parse($validated['planned_visit_at'], 'Asia/Jakarta'),
                'created_by' => $user->id,
            ]);

            $recoming = TicketRecoming::create([
                'ticket_id' => $ticket->id,
                'visit_no' => $visitNo,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'planned_visit_at' => Carbon::parse($validated['planned_visit_at'], 'Asia/Jakarta'),
                'from_step' => $fromStep,
                'created_by_user_id' => $user->id,
                'created_by' => $user->id,
            ]);

            SatisfactionLink::deactivateFor($ticket);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'step' => TicketLog::STEP_RECOMING,
                'actor_user_id' => $user->id,
                'note' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            return $recoming;
        });

        return $this->setJsonResponse('Recoming scheduled successfully', ['data' => $recoming], 201);
    }

    public function storeDocumentation(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.documentation.submit'), 403);
        $this->guardNotClosed($ticket);
        $this->guardSequential($ticket, TicketLog::STEP_DOCUMENTATION_PUBLISHED);
        $this->guardStepNotLogged($ticket, TicketLog::STEP_DOCUMENTATION_PUBLISHED, 'Documentation has already been submitted for this ticket.');

        $validated = $request->validate([
            'note' => 'required|string',
            'parts' => 'nullable|array',
            'parts.*.ref_sparepart_id' => 'required_with:parts|integer|exists:ref_spareparts,id',
            'parts.*.qty' => 'required_with:parts|integer|min:1',
            'parts.*.unit' => 'required_with:parts|in:'.implode(',', TicketPart::UNITS),
            'photos' => 'nullable|array',
            'photos.*.category' => 'required_with:photos|in:'.implode(',', [
                TicketPhoto::CATEGORY_PROBLEM, TicketPhoto::CATEGORY_ANALYSIS, TicketPhoto::CATEGORY_REPAIR,
            ]),
            'photos.*.file' => 'required_with:photos|file|image|max:10240',
        ]);

        $log = DB::transaction(function () use ($validated, $ticket, $request) {
            foreach ($validated['parts'] ?? [] as $part) {
                $sparepart = RefSparepart::findOrFail($part['ref_sparepart_id']);

                TicketPart::create([
                    'ticket_id' => $ticket->id,
                    'ref_sparepart_id' => $sparepart->id,
                    'qty' => $part['qty'],
                    'unit' => $part['unit'],
                    'unit_price' => $sparepart->price,
                    'type' => TicketPart::TYPE_ACTUAL,
                    'created_by' => $request->user()->id,
                ]);
            }

            if (! empty($validated['photos'])) {
                $now = Carbon::now('Asia/Jakarta');
                $counts = [];
                $rows = [];

                foreach ($validated['photos'] as $photo) {
                    $category = $photo['category'];
                    $counts[$category] = ($counts[$category] ?? TicketPhoto::where('ticket_id', $ticket->id)->where('category', $category)->max('sequence') ?? 0) + 1;

                    /** @var \Illuminate\Http\UploadedFile $file */
                    $file = $photo['file'];
                    $path = $file->store('ticket-photos/'.$ticket->id, 'public');

                    $rows[] = [
                        'ticket_id' => $ticket->id,
                        'category' => $category,
                        'sequence' => $counts[$category],
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'created_at' => $now,
                        'updated_at' => $now,
                        'created_by' => $request->user()->id,
                    ];
                }

                TicketPhoto::insert($rows);
            }

            SatisfactionLink::issue($ticket, $request->user()->id);

            return TicketLog::create([
                'ticket_id' => $ticket->id,
                'step' => TicketLog::STEP_DOCUMENTATION_PUBLISHED,
                'actor_user_id' => $request->user()->id,
                'note' => $validated['note'],
                'created_by' => $request->user()->id,
            ]);
        });

        return $this->setJsonResponse('Documentation submitted successfully', [
            'data' => $log,
        ], 201);
    }

    /**
     * Satisfaction Submitted (PRD §5.1 step 5b, actor Aftersales — §5.3: the
     * customer never fills CSAT directly, so this is always filled by after_sales
     * as part of the pre-close review).
     */
    public function storeSatisfaction(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.satisfaction.submit'), 403);
        $this->guardNotClosed($ticket);
        $this->guardSequential($ticket, TicketLog::STEP_SATISFACTION_SUBMITTED);
        $this->guardStepNotLogged($ticket, TicketLog::STEP_SATISFACTION_SUBMITTED, 'Satisfaction assessment has already been submitted for this ticket.');

        return app(SurveyLinkController::class)->enterOnBehalf($request, $ticket);
    }

    /**
     * Ticket Closed (PRD §5.1 step 6, actor Aftersales). Gate: only allowed once
     * both documentation_published and satisfaction_submitted are on record —
     * never automatic (PRD §5.2).
     */
    public function close(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.close'), 403);

        return DB::transaction(function () use ($request, $ticket) {
            $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->guardNotClosed($ticket);

            $steps = $this->cycleLogs($ticket)->pluck('step');
            abort_if($ticket->happy_call_required && ! $steps->contains(TicketLog::STEP_HAPPY_CALL_COMPLETED), 422, 'Cannot set to Closed. Happy Call Completed must be recorded first.');
            if (! $steps->contains(TicketLog::STEP_DOCUMENTATION_PUBLISHED)) {
                abort(422, 'This ticket cannot be closed until documentation is published.');
            }

            $current = $ticket->satisfactions()->latest('id')->first();
            if ($current && in_array($current->review_status, [TicketSatisfaction::REVIEW_NEEDS_REVIEW, TicketSatisfaction::REVIEW_REJECTED], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => $current->review_status === TicketSatisfaction::REVIEW_REJECTED
                        ? 'Cannot set to Closed. The latest survey response was rejected; waiting for the customer to submit a new response.'
                        : 'Cannot set to Closed. The latest survey response is Needs review. Review it first.',
                    'satisfaction_id' => $current->id,
                ], 422);
            }

            $validated = $request->validate(['note' => 'nullable|string']);

            $log = DB::transaction(function () use ($validated, $ticket, $request) {
                return TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'step' => TicketLog::STEP_CLOSED,
                    'actor_user_id' => $request->user()->id,
                    'note' => $validated['note'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
            });

            return $this->setJsonResponse('Ticket closed successfully', ['data' => $log], 201);
        }, 3);
    }

    public function happyCall(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        $input = $request->validate(['note' => 'nullable|string|max:10000']);
        $log = DB::transaction(function () use ($ticket, $request, $input) {
            $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->guardNotClosed($ticket);
            abort_if($ticket->progress === TicketLog::STEP_RECOMING, 422, 'The ticket is in Recoming until the follow-up visit.');
            $existing = $this->cycleLogs($ticket)->where('step', TicketLog::STEP_HAPPY_CALL_COMPLETED)->first();

            return $existing ?? $ticket->logs()->create(['step' => TicketLog::STEP_HAPPY_CALL_COMPLETED, 'actor_user_id' => $request->user()->id, 'note' => $input['note'] ?? null]);
        });

        return response()->json(['data' => $log], 201);
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        $userId = $request->user()->id;

        DB::transaction(function () use ($ticket, $validated, $userId) {
            $returned = TicketStock::returnAll($ticket, $userId);
            SatisfactionLink::deactivateFor($ticket);
            ActivityLogger::writeLog('ticket_deleted', "Ticket {$ticket->ticket_code} deleted: {$validated['reason']}", $ticket, [
                'reason' => $validated['reason'],
                'stock_returned' => $returned,
            ]);
            $ticket->update(['deleted_by' => $userId, 'is_deleted' => true]);
            $ticket->delete();
        });

        return $this->setJsonResponse('Ticket deleted successfully');
    }

    public function reschedule(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission('aftersales.tickets.manage')
            || ($user?->hasPermission('aftersales.tickets.recoming') && $ticket->assigned_technician_id === $user->id),
            403
        );
        abort_unless($this->canReschedule($ticket), 422, 'Reschedule is only available before the follow-up visit date arrives.');

        $validated = $request->validate([
            'planned_visit_at' => 'required|date|after_or_equal:'.Carbon::now('Asia/Jakarta')->toDateString(),
            'note' => 'nullable|string|max:1000',
        ]);

        $this->moveFollowUpVisit($request, $ticket, Carbon::parse($validated['planned_visit_at'], 'Asia/Jakarta'), $validated['note'] ?? null);

        return $this->setJsonResponse('Follow-up visit rescheduled successfully');
    }

    public function addPart(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        $this->guardPartsEditable($ticket);

        $validated = $request->validate([
            'ref_sparepart_id' => 'required|integer|exists:ref_spareparts,id',
            'qty' => 'required|integer|min:1',
            'unit' => 'nullable|in:'.implode(',', TicketPart::UNITS),
        ]);

        $part = TicketStock::add($ticket, (int) $validated['ref_sparepart_id'], (int) $validated['qty'], $validated['unit'] ?? 'pieces', $request->user()->id);

        return $this->setJsonResponse('Part added successfully', ['data' => $part->fresh('sparepart')], 201);
    }

    public function updatePart(Request $request, Ticket $ticket, TicketPart $part)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        abort_unless($part->ticket_id === $ticket->id && $part->type === TicketPart::TYPE_ESTIMATED, 404);
        $this->guardPartsEditable($ticket);

        $validated = $request->validate(['qty' => 'required|integer|min:0']);
        TicketStock::setUsed($part, (int) $validated['qty'], $request->user()->id);

        return $this->setJsonResponse('Part updated successfully', ['data' => $part->fresh('sparepart')]);
    }

    /**
     * Issues a Work Order (Surat Tugas Servis, PRD §3.6). Only metadata is
     * stored here — customer/machine/estimate content is rendered from
     * tickets + ticket_parts(estimated) + ticket_costs when the PDF is built.
     */
    public function issueWorkOrder(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.work-orders.issue'), 403);
        $this->guardNotClosed($ticket);
        abort_if(! $ticket->assigned_technician_id, 422, 'Assign a technician before issuing a work order.');

        $validated = $request->validate([
            'ticket_visit_id' => 'nullable|integer|exists:ticket_visits,id',
            'note' => 'nullable|string',
        ]);

        if (! empty($validated['ticket_visit_id'])) {
            $visit = TicketVisit::findOrFail($validated['ticket_visit_id']);
            abort_unless($visit->ticket_id === $ticket->id, 422, 'The selected visit does not belong to this ticket.');
        }

        $workOrder = DB::transaction(function () use ($validated, $ticket, $request) {
            $now = Carbon::now('Asia/Jakarta');
            $sequence = DocumentCounter::nextNumber(DocumentCounter::TYPE_WORK_ORDER, $now);
            $woNumber = 'WO-'.$now->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            return TicketWorkOrder::create([
                'ticket_id' => $ticket->id,
                'ticket_visit_id' => $validated['ticket_visit_id'] ?? null,
                'wo_number' => $woNumber,
                'technician_id' => $ticket->assigned_technician_id,
                'issued_at' => $now,
                'note' => $validated['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        return $this->setJsonResponse('Work order issued successfully', ['data' => $workOrder->fresh('technician')], 201);
    }

    /**
     * In-app confirmation by the assigned technician that the work order was
     * received — the customer signature stays on the printed paper (no
     * customer role in this system, PRD §3.6).
     */
    public function signWorkOrder(Request $request, Ticket $ticket, TicketWorkOrder $workOrder)
    {
        abort_unless($workOrder->ticket_id === $ticket->id, 404);
        abort_unless($request->user()?->id === $workOrder->technician_id, 403);
        abort_if($workOrder->technician_signed_at, 422, 'This work order has already been confirmed.');

        $workOrder->update([
            'technician_signed_at' => Carbon::now('Asia/Jakarta'),
            'updated_by' => $request->user()->id,
        ]);

        return $this->setJsonResponse('Work order confirmed successfully', ['data' => $workOrder->fresh()]);
    }

    private function guardPartsEditable(Ticket $ticket): void
    {
        abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'Spareparts are locked after the ticket is Closed.');
    }

    private function canReschedule(Ticket $ticket): bool
    {
        $recoming = $ticket->recomings()->latest('id')->first();

        return $ticket->progress === TicketLog::STEP_RECOMING
            && $recoming
            && $recoming->planned_visit_at->gt(Carbon::now('Asia/Jakarta')->endOfDay());
    }

    private function moveFollowUpVisit(Request $request, Ticket $ticket, Carbon $date, ?string $note): void
    {
        DB::transaction(function () use ($ticket, $date, $note) {
            $recoming = $ticket->recomings()->latest('id')->lockForUpdate()->firstOrFail();
            $visit = $ticket->visits()->latest('id')->firstOrFail();
            $from = $recoming->planned_visit_at;

            $recoming->update(['planned_visit_at' => $date]);
            $visit->update(['scheduled_at' => $date]);

            ActivityLogger::writeLog('recoming_rescheduled', "Follow-up visit for {$ticket->ticket_code} moved to {$date->toDateString()}", $ticket, [
                'from' => $from?->toDateString(),
                'to' => $date->toDateString(),
                'note' => $note,
            ]);
        });
    }

    private function visitHistory(Ticket $ticket): array
    {
        $recomings = $ticket->recomings->keyBy('visit_no');
        $reasons = ['sparepart_unavailable' => 'Part unavailable', 'further_work_needed' => 'Further work needed', 'site_access' => 'Site access or condition', 'other' => 'Other'];
        $count = $ticket->visits->count();

        return $ticket->visits->sortBy('id')->values()->map(function (TicketVisit $v, int $i) use ($recomings, $reasons, $count) {
            $r = $recomings->get($i + 1);

            return [
                'visit_no' => $i + 1,
                'scheduled_at' => $v->scheduled_at,
                'note' => $r ? 'Recoming: '.($reasons[$r->reason] ?? $r->reason).($r->notes ? ' - '.$r->notes : '') : null,
                'current' => $i + 1 === $count,
            ];
        })->all();
    }

    /** Latest recoming (visit_no, planned_visit_at, reason) while the ticket is in Recoming, else null. */
    private function currentRecoming(Ticket $ticket): ?array
    {
        if ($ticket->progress !== TicketLog::STEP_RECOMING) {
            return null;
        }

        $r = $ticket->recomings->sortByDesc('id')->first();

        return $r ? ['visit_no' => $r->visit_no, 'planned_visit_at' => $r->planned_visit_at, 'reason' => $r->reason, 'from_step' => $r->from_step] : null;
    }

    private function guardStepNotLogged(Ticket $ticket, string $step, string $message): void
    {
        if ($this->cycleLogs($ticket)->where('step', $step)->exists()) {
            abort(422, $message);
        }
    }

    private function cycleLogs(Ticket $ticket)
    {
        $lastRecomingId = $ticket->logs()->where('step', TicketLog::STEP_RECOMING)->max('id');

        return $ticket->logs()->when($lastRecomingId, fn ($q) => $q->where('id', '>', $lastRecomingId));
    }

    /**
     * Steps must be logged in order — except waiting_sparepart, which is
     * optional and can be logged any time once on_site has been reached
     * without consuming the "next required step" slot.
     */
    private function guardSequential(Ticket $ticket, string $step): void
    {
        if ($ticket->progress === TicketLog::STEP_RECOMING) {
            abort_if($step !== TicketLog::STEP_ON_SITE, 422, 'Ticket is in Recoming until the follow-up visit.');

            return;
        }

        if ($step === TicketLog::STEP_WAITING_SPAREPART) {
            $currentOrder = array_search($ticket->progress, TicketLog::STEPS, true);
            $onSiteOrder = array_search(TicketLog::STEP_ON_SITE, TicketLog::STEPS, true);

            abort_if($currentOrder < $onSiteOrder, 422, 'This step is out of order.');

            return;
        }

        $currentOrder = array_search($ticket->progress, TicketLog::STEPS, true);
        $stepOrder = array_search($step, TicketLog::STEPS, true);

        // waiting_sparepart is optional, so skipping over it must not block
        // the step that would otherwise have been the immediate next one.
        $nextOrder = $currentOrder + 1;
        if ((TicketLog::STEPS[$nextOrder] ?? null) === TicketLog::STEP_WAITING_SPAREPART) {
            $nextOrder++;
        }

        abort_if($stepOrder < $currentOrder || $stepOrder > $nextOrder, 422, 'This step is out of order.');
    }

    private function guardNotClosed(Ticket $ticket): void
    {
        if ($ticket->progress === TicketLog::STEP_CLOSED) {
            abort(422, 'This ticket is already closed.');
        }
    }

    private function validateSupervisor(?int $selected, ?int $existing = null): void
    {
        if ($selected === null || ($existing !== null && $selected === $existing)) {
            return;
        }
        $eligible = \App\Models\User::whereKey($selected)->whereHas('role', fn ($q) => $q->where('code', 'supervisor_after_sales'))->exists();
        if (! $eligible) {
            throw ValidationException::withMessages(['supervisor_id' => 'Select a user with the Supervisor After Sales role.']);
        }
    }
}
