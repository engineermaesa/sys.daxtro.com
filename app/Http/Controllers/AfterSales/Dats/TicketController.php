<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\DocumentCounter;
use App\Models\Aftersales\PartStockMovement;
use App\Models\Aftersales\RefSparepart;
use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCost;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketPart;
use App\Models\Aftersales\TicketPhoto;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\Aftersales\TicketVisit;
use App\Models\Aftersales\TicketWorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function page(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $this->pageTitle = 'Tickets';

        return $this->render('pages.aftersales.tickets.index');
    }

    public function createPage(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $this->pageTitle = 'Tambah Ticket';

        return $this->render('pages.aftersales.tickets.create');
    }

    public function showPage(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $this->pageTitle = 'Ticket ' . $ticket->ticket_code;

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
            'data' => ['ticket_code' => 'DAX-AF-' . $now->format('Ymd') . '-' . $sequence],
        ]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $query = Ticket::with(['customer.province', 'customer.region', 'customerProduct.product', 'technician', 'logs.actor', 'visits']);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
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
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->orderByDesc('id')->paginate($perPage);

        $items = collect($paginated->items())->map(function (Ticket $t) {
            $latestVisit = $t->visits->sortByDesc('scheduled_at')->first();
            $closedLog = $t->logs->where('step', TicketLog::STEP_CLOSED)->sortByDesc('id')->first();

            return array_merge($t->toArray(), [
                'progress' => $t->progress,
                'aging_days' => $t->aging_days,
                'sla_status' => $t->sla_status,
                'visit_date' => $latestVisit?->scheduled_at,
                'closed_at' => $closedLog?->created_at,
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
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);

        $ticket->load([
            'customer', 'customerProduct.industry', 'technician', 'supervisor',
            'logs' => fn ($q) => $q->orderBy('id'),
            'logs.actor',
            'visits', 'workOrders', 'parts.sparepart', 'costs', 'photos', 'satisfaction',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => array_merge($ticket->toArray(), [
                'progress' => $ticket->progress,
                'aging_days' => $ticket->aging_days,
                'sla_status' => $ticket->sla_status,
                'total_cost' => $ticket->total_cost,
            ]),
        ]);
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
            'category' => 'required|in:' . implode(',', Ticket::CATEGORIES),
            'priority' => 'required|in:' . implode(',', Ticket::PRIORITIES),
            'description' => 'required|string',
            'sla_value' => 'required|integer|min:1',
            'sla_unit' => 'required|in:hour,day',
            'supervisor_id' => 'nullable|integer|exists:users,id',
            'parts' => 'nullable|array',
            'parts.*.ref_sparepart_id' => 'required_with:parts|integer|exists:ref_spareparts,id',
            'parts.*.qty' => 'required_with:parts|numeric|min:0.01',
            'parts.*.unit' => 'required_with:parts|in:' . implode(',', TicketPart::UNITS),
            'costs' => 'nullable|array',
            'costs.*.category' => 'required_with:costs|in:' . implode(',', TicketCost::CATEGORIES),
            'costs.*.amount' => 'required_with:costs|numeric|min:0',
            'costs.*.currency' => 'required_with:costs|in:' . implode(',', TicketCost::CURRENCIES),
            'costs.*.exchange_rate' => 'nullable|numeric|min:0',
            'costs.*.remarks' => 'nullable|string',
        ]);

        $product = ServiceCustomerProduct::findOrFail($validated['customer_product_id']);
        if ($product->customer_id !== (int) $validated['customer_id']) {
            throw ValidationException::withMessages([
                'customer_product_id' => 'The selected product does not belong to the selected customer.',
            ]);
        }

        $ticket = DB::transaction(function () use ($validated, $request) {
            $now = Carbon::now('Asia/Jakarta');
            $sequence = DocumentCounter::nextNumber(DocumentCounter::TYPE_TICKET, $now);
            $ticketCode = 'DAX-AF-' . $now->format('Ymd') . '-' . $sequence;

            $slaValue = (int) $validated['sla_value'];
            $slaDueAt = $validated['sla_unit'] === 'hour'
                ? $now->copy()->addHours($slaValue)
                : $now->copy()->addDays($slaValue);

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
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['parts'] ?? [] as $part) {
                $sparepart = \App\Models\Aftersales\RefSparepart::find($part['ref_sparepart_id']);
                TicketPart::create([
                    'ticket_id' => $ticket->id,
                    'ref_sparepart_id' => $part['ref_sparepart_id'],
                    'qty' => $part['qty'],
                    'unit' => $part['unit'],
                    'unit_price' => $sparepart->price,
                    'type' => TicketPart::TYPE_ESTIMATED,
                    'created_by' => $request->user()->id,
                ]);
            }

            foreach ($validated['costs'] ?? [] as $cost) {
                TicketCost::create([
                    'ticket_id' => $ticket->id,
                    'category' => $cost['category'],
                    'amount' => $cost['amount'],
                    'currency' => $cost['currency'],
                    'exchange_rate' => $cost['currency'] === 'usd' ? ($cost['exchange_rate'] ?? null) : null,
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
            'category' => 'required|in:' . implode(',', Ticket::CATEGORIES),
            'priority' => 'required|in:' . implode(',', Ticket::PRIORITIES),
            'description' => 'required|string',
            'sla_value' => 'required|integer|min:1',
            'sla_unit' => 'required|in:hour,day',
            'supervisor_id' => 'nullable|integer|exists:users,id',
            'assigned_technician_id' => 'nullable|integer|exists:users,id',
            'parts' => 'nullable|array',
            'parts.*.ref_sparepart_id' => 'required_with:parts|integer|exists:ref_spareparts,id',
            'parts.*.qty' => 'required_with:parts|numeric|min:0.01',
            'parts.*.unit' => 'required_with:parts|in:' . implode(',', TicketPart::UNITS),
            'costs' => 'nullable|array',
            'costs.*.category' => 'required_with:costs|in:' . implode(',', TicketCost::CATEGORIES),
            'costs.*.amount' => 'required_with:costs|numeric|min:0',
            'costs.*.currency' => 'required_with:costs|in:' . implode(',', TicketCost::CURRENCIES),
            'costs.*.exchange_rate' => 'nullable|numeric|min:0',
            'costs.*.remarks' => 'nullable|string',
        ]);

        $product = ServiceCustomerProduct::findOrFail($validated['customer_product_id']);
        if ($product->customer_id !== (int) $validated['customer_id']) {
            throw ValidationException::withMessages([
                'customer_product_id' => 'The selected product does not belong to the selected customer.',
            ]);
        }

        $now = Carbon::now('Asia/Jakarta');
        $slaValue = (int) $validated['sla_value'];
        $slaDueAt = $validated['sla_unit'] === 'hour'
            ? $now->copy()->addHours($slaValue)
            : $now->copy()->addDays($slaValue);

        DB::transaction(function () use ($validated, $ticket, $request, $slaDueAt) {
            $ticket->update([
                'customer_id' => $validated['customer_id'],
                'customer_product_id' => $validated['customer_product_id'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'description' => $validated['description'],
                'sla_value' => $validated['sla_value'],
                'sla_unit' => $validated['sla_unit'],
                'sla_due_at' => $slaDueAt,
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
                            'exchange_rate' => $cost['currency'] === 'usd' ? ($cost['exchange_rate'] ?? null) : null,
                            'remarks' => $cost['remarks'] ?? null,
                            'updated_by' => $request->user()->id,
                        ]
                    );
                }
            }

            if ($request->has('parts')) {
                $ticket->parts()->where('type', TicketPart::TYPE_ESTIMATED)->delete();

                foreach ($validated['parts'] ?? [] as $part) {
                    $sparepart = RefSparepart::find($part['ref_sparepart_id']);
                    TicketPart::create([
                        'ticket_id' => $ticket->id,
                        'ref_sparepart_id' => $part['ref_sparepart_id'],
                        'qty' => $part['qty'],
                        'unit' => $part['unit'],
                        'unit_price' => $sparepart->price,
                        'type' => TicketPart::TYPE_ESTIMATED,
                        'created_by' => $request->user()->id,
                    ]);
                }
            }
        });

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

    /**
     * Documentation Published (PRD §5.1 step 5a, actor Technician). Submits the
     * repair narrative, actual sparepart usage, and site photos in one go.
     * Actual parts automatically create an `out` stock movement (PRD §9 rule 4).
     */
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
            'parts.*.qty' => 'required_with:parts|numeric|min:0.01',
            'parts.*.unit' => 'required_with:parts|in:' . implode(',', TicketPart::UNITS),
            'photos' => 'nullable|array',
            'photos.*.category' => 'required_with:photos|in:' . implode(',', [
                TicketPhoto::CATEGORY_PROBLEM, TicketPhoto::CATEGORY_ANALYSIS, TicketPhoto::CATEGORY_REPAIR,
            ]),
            'photos.*.file' => 'required_with:photos|file|image|max:10240',
        ]);

        $log = DB::transaction(function () use ($validated, $ticket, $request) {
            foreach ($validated['parts'] ?? [] as $part) {
                $sparepart = RefSparepart::query()->whereKey($part['ref_sparepart_id'])->lockForUpdate()->first();

                TicketPart::create([
                    'ticket_id' => $ticket->id,
                    'ref_sparepart_id' => $sparepart->id,
                    'qty' => $part['qty'],
                    'unit' => $part['unit'],
                    'unit_price' => $sparepart->price,
                    'type' => TicketPart::TYPE_ACTUAL,
                    'created_by' => $request->user()->id,
                ]);

                $qty = (int) round((float) $part['qty']);
                $sparepart->stock = max(0, $sparepart->stock - $qty);
                $sparepart->updated_by = $request->user()->id;
                $sparepart->save();

                PartStockMovement::create([
                    'ref_sparepart_id' => $sparepart->id,
                    'type' => PartStockMovement::TYPE_OUT,
                    'qty' => $qty,
                    'ticket_id' => $ticket->id,
                    'note' => 'Actual usage on ticket ' . $ticket->ticket_code,
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
                    $path = $file->store('ticket-photos/' . $ticket->id, 'public');

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

        $validated = $request->validate([
            'timeliness' => 'required|integer|min:1|max:5',
            'technician_attitude' => 'required|integer|min:1|max:5',
            'technical_knowledge' => 'required|integer|min:1|max:5',
            'work_neatness' => 'required|integer|min:1|max:5',
            'solution_quality' => 'required|integer|min:1|max:5',
        ]);

        $satisfaction = DB::transaction(function () use ($validated, $ticket, $request) {
            $satisfaction = TicketSatisfaction::create(array_merge($validated, [
                'ticket_id' => $ticket->id,
                'filled_by_user_id' => $request->user()->id,
                'filled_at' => Carbon::now('Asia/Jakarta'),
                'created_by' => $request->user()->id,
            ]));

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'step' => TicketLog::STEP_SATISFACTION_SUBMITTED,
                'actor_user_id' => $request->user()->id,
                'created_by' => $request->user()->id,
            ]);

            return $satisfaction;
        });

        return $this->setJsonResponse('Satisfaction assessment submitted successfully', ['data' => $satisfaction], 201);
    }

    /**
     * Ticket Closed (PRD §5.1 step 6, actor Aftersales). Gate: only allowed once
     * both documentation_published and satisfaction_submitted are on record —
     * never automatic (PRD §5.2).
     */
    public function close(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.close'), 403);
        $this->guardNotClosed($ticket);

        $steps = $ticket->logs()->pluck('step');
        if (! $steps->contains(TicketLog::STEP_DOCUMENTATION_PUBLISHED) || ! $steps->contains(TicketLog::STEP_SATISFACTION_SUBMITTED)) {
            abort(422, 'This ticket cannot be closed until documentation and satisfaction assessment are both submitted.');
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
            $woNumber = 'WO-' . $now->format('Ymd') . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

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

    private function guardStepNotLogged(Ticket $ticket, string $step, string $message): void
    {
        if ($ticket->logs()->where('step', $step)->exists()) {
            abort(422, $message);
        }
    }

    /**
     * Steps must be logged in order — except waiting_sparepart, which is
     * optional and can be logged any time once on_site has been reached
     * without consuming the "next required step" slot.
     */
    private function guardSequential(Ticket $ticket, string $step): void
    {
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
}
