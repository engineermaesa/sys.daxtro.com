<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\Orders\Quotation;
use App\Models\Orders\Proforma;
use App\Models\Orders\FinanceRequest;
use App\Models\Orders\PaymentConfirmation;
use App\Models\Leads\LeadSegment;
use App\Models\Leads\LeadSource;
use App\Models\Masters\Region;
use App\Models\Masters\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Attachment;
use App\Models\Leads\LeadActivityLog;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->pageTitle = 'Orders';

        $segments = LeadSegment::all();
        $sources  = LeadSource::all();
        $branches = Branch::all();

        $user = Auth::user();
        $roleCode = $user?->role?->code ?? null;
        if ($roleCode === 'branch_manager') {
            $regions = Region::where('branch_id', $user?->branch_id)->get();
        } else {
            $regions = Region::all();
        }

        $counts   = $this->calculateCounts([]);

        return $this->respondWith($request, 'pages.orders.index', compact('segments', 'sources', 'regions', 'roleCode', 'branches', 'counts'));
    }

    public function list(Request $request)
    {
        $query = $this->ordersIndexQuery($request);

        if ($request->filled('payment_status')) {
            $this->applyPaymentStatusFilter($query, $request->input('payment_status'));
        }

        $perPage = max(1, (int) $request->input('per_page', 10));
        $orders = $query
            ->with($this->ordersIndexRelations())
            ->orderByDesc('orders.created_at')
            ->paginate($perPage);

        $data = collect($orders->items())
            ->map(fn (Order $order) => $this->formatOrderIndexRow($order))
            ->values();

        return response()->json([
            'data' => $data,
            'total' => $orders->total(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with('lead.quotation.proformas.invoice.payments', 'lead.quotation.proformas.paymentConfirmation', 'paymentTerms', 'progressLogs.user')->findOrFail($id);

        $quotation = Quotation::with(['proformas' => function ($q) {
            $q->orderBy('term_no');
        }, 'proformas.paymentConfirmation.attachment'])
            ->where('lead_id', $order->lead_id)
            ->first();

        $pendingInvoices = FinanceRequest::where('request_type', 'invoice')
            ->where('status', 'pending')
            ->pluck('id', 'reference_id');

        $invoiceDecisions = FinanceRequest::where('request_type', 'invoice')
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('decided_at')
            ->get()
            ->keyBy('reference_id');
        $proformaDecisions = FinanceRequest::where('request_type', 'proforma')
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('decided_at')
            ->get()
            ->keyBy('reference_id');
        $paymentDecisions = FinanceRequest::where('request_type', 'payment-confirmation')
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('decided_at')
            ->get()
            ->keyBy('reference_id');

        $terms = [];
        foreach ($order->paymentTerms as $paymentTerm) {
            $termNo   = $paymentTerm->term_no;
            $proforma = $quotation?->proformas->firstWhere('term_no', $termNo);
            $invoiceRef = $order->id . '-' . $termNo;

            $invoiceDecision = $invoiceDecisions[$invoiceRef] ?? null;
            $proformaDecision = $proforma ? ($proformaDecisions[$proforma->id] ?? null) : null;
            $paymentDecision = ($proforma && $proforma->paymentConfirmation) ? ($paymentDecisions[$proforma->paymentConfirmation->id] ?? null) : null;

            $termData = [
                'proforma'   => $proforma,
                'payment'    => $proforma?->paymentConfirmation?->loadMissing('attachment'),
                'invoice'    => $proforma?->invoice,
                'percentage' => $paymentTerm->percentage,
                'invoice_pending' => $pendingInvoices->has($invoiceRef),
                'invoice_note' => $invoiceDecision?->notes,
                'invoice_note_status' => $invoiceDecision?->status,
                'proforma_note' => $proformaDecision?->notes,
                'proforma_note_status' => $proformaDecision?->status,
                'payment_note' => $paymentDecision?->notes,
                'payment_note_status' => $paymentDecision?->status,
            ];
            $terms[] = $termData;
        }

        return $this->respondWith($request, 'pages.orders.show', compact('order', 'quotation', 'terms', 'id'));
    }

    public function requestProforma(Request $request, $orderId, $term)
    {
        $order = Order::with('paymentTerms')->findOrFail($orderId);
        $quotation = Quotation::where('lead_id', $order->lead_id)->firstOrFail();

        $type = $term == 1 ? 'down_payment' : 'term_payment';

        $amount = 0;
        if ($pct = $order->paymentTerms->firstWhere('term_no', $term)) {
            $base = $quotation->grand_total ?? $order->total_billing;
            $amount = $base * ($pct->percentage / 100);

            // If there's a booking fee on the quotation, subtract it from term 1's amount
            if ($term == 1 && ! empty($quotation->booking_fee)) {
                $amount = $amount - $quotation->booking_fee;
                if ($amount < 0) {
                    $amount = 0;
                }
            }
        }

        $proforma = $quotation->proformas()->firstOrCreate(
            ['term_no' => $term],
            ['proforma_type' => $type, 'amount' => $amount]
        );

        $proforma->update([
            'status' => 'pending',
            'proforma_no' => null,
            'issued_at' => null,
        ]);

        FinanceRequest::create([
            'request_type' => 'proforma',
            'reference_id' => $proforma->id,
            'requester_id' => Auth::user()->id,
            'status'       => 'pending',
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Proforma requested for term ' . $term, 'proforma' => $proforma], 200);
        }

        return back()->with('status', 'Proforma requested for term ' . $term);
    }

    public function paymentConfirmationForm(Request $request, $orderId, $term)
    {
        $order = Order::findOrFail($orderId);
        $quotation = Quotation::where('lead_id', $order->lead_id)->firstOrFail();
        $proforma = $quotation->proformas()->where('term_no', $term)->firstOrFail();
        return $this->respondWith($request, 'pages.orders.payment-confirmation-form', compact('order', 'proforma', 'term'));
    }

    public function confirmPayment(Request $request, $orderId, $term)
    {
        $order = Order::findOrFail($orderId);
        $quotation = Quotation::where('lead_id', $order->lead_id)->firstOrFail();

        $proforma = $quotation->proformas()->where('term_no', $term)->firstOrFail();

        $data = request()->validate([
            'payer_name'           => 'nullable|string',
            'payer_bank'           => 'nullable|string',
            'payer_account_number' => 'nullable|string',
            'paid_at'              => 'required|date',
            'amount'               => 'required|numeric',
            'evidence_image'       => 'required|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $file = request()->file('evidence_image');

        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('payment-confirmation', $filename, 'local');

        $attachment = Attachment::create([
            'type'        => 'payment_evidence',
            'file_path'   => 'storage/' . $path,
            'mime_type'   => $file->getClientMimeType(),
            'size'        => $file->getSize(),
            'uploaded_by' => $request->user()->id ?? null,
        ]);

        $payment = $proforma->paymentConfirmation()->create([
            'payer_name'           => $data['payer_name'] ?? null,
            'payer_bank'           => $data['payer_bank'] ?? null,
            'payer_account_number' => $data['payer_account_number'] ?? null,
            'paid_at'              => $data['paid_at'],
            'amount'               => $data['amount'],
            'attachment_id'        => $attachment->id,
        ]);

        FinanceRequest::create([
            'request_type' => 'payment-confirmation',
            'reference_id' => $payment->id,
            'requester_id' => Auth::user()->id,
            'status' => 'pending',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Payment confirmation submitted for term ' . $term, 'payment_id' => $payment->id], 200);
        }

        return redirect()->route('orders.show', $orderId)->with('status', 'Payment confirmation submitted for term ' . $term);
    }

    public function requestInvoice(Request $request, $orderId, $term)
    {
        $order = Order::findOrFail($orderId);

        FinanceRequest::create([
            'request_type' => 'invoice',
            'reference_id' => $orderId . '-' . $term,
            'requester_id' => Auth::user()->id,
            'status'       => 'pending',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Invoice requested for term ' . $term], 200);
        }

        return back()->with('status', 'Invoice requested for term ' . $term);
    }

    public function export(Request $request)
    {
        $query = Order::with([
            'lead.source',
            'lead.segment',
            'lead.region',
            'lead.quotation.proformas.invoice.payments',
            'lead.quotation.proformas.paymentConfirmation',
            'paymentTerms',
            'lead.customerType',
            'lead.quotation.items',
        ]);

        if ($request->filled('segment_id')) {
            $query->whereHas('lead', fn($q) => $q->where('segment_id', $request->segment_id));
        }

        if ($request->filled('source_id')) {
            $query->whereHas('lead', fn($q) => $q->where('source_id', $request->source_id));
        }

        if ($request->filled('region_id')) {
            $query->whereHas('lead', fn($q) => $q->where('region_id', $request->region_id));
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('lead.region.branch', fn($q) => $q->where('id', $request->branch_id));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('orders.created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('orders.created_at', '<=', $request->end_date);
        }

        if ($request->filled('min_total')) {
            $query->where('total_billing', '>=', (float)$request->min_total);
        }

        if ($request->filled('max_total')) {
            $query->where('total_billing', '<=', (float)$request->max_total);
        }

        $orders = $query->orderByDesc('id')->get();

        $user = Auth::user();
        $roleCode = $user?->role?->code ?? null;
        $showRegion = $roleCode !== 'sales';

        $columns = [
            'Order No',
            'Customer',
            'Source',
            'Segment',
        ];
        if ($showRegion) {
            $columns[] = 'Region';
        }
        $columns = array_merge($columns, [
            'Quotation No',
            'Total Billing',
            'Status',
            '% Completion',
        ]);

        $rows   = [];
        $rows[] = $columns;

        foreach ($orders as $order) {
            $quotationNo = $order->lead->quotation->quotation_no ?? '-';
            $proformas   = optional($order->lead->quotation)->proformas ?? collect();
            $paidCount   = $proformas
                ->filter(fn($p) => $p->proforma_type !== 'booking_fee'
                    && $p->paymentConfirmation
                    && $p->paymentConfirmation->confirmed_at)
                ->count();
            $termTotal   = $order->paymentTerms->count();
            if ($termTotal === 0) {
                $completion = '0/0';
            } else {
                $completion  = sprintf('%d/%d', min($termTotal, $paidCount), $termTotal);
            }
            $status      = ucwords(str_replace('_', ' ', $order->order_status));

            $row = [
                $order->order_no,
                $order->lead->name ?? '',
                $order->lead->source->name ?? '',
                $order->lead->segment->name ?? '',
            ];

            if ($showRegion) {
                $row[] = $order->lead->region->name ?? '';
            }

            $row = array_merge($row, [
                $quotationNo,
                $order->total_billing,
                $status,
                $completion,
            ]);

            $rows[] = $row;
        }

        $file = $this->createXlsx($rows);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Export ready', 'count' => $orders->count()]);
        }

        return response()->download($file, 'orders_' . date('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    public function counts(Request $request)
    {
        return response()->json($this->calculateCounts($request->all()));
    }

    private function calculateCounts(array $filters): array
    {
        $request = new Request($filters);
        $base = $this->ordersIndexQuery($request);

        $allQuery = clone $base;
        $pendingQuery = clone $base;
        $completeQuery = clone $base;

        $this->applyPaymentStatusFilter($pendingQuery, 'pending');
        $this->applyPaymentStatusFilter($completeQuery, 'complete');

        $all = $allQuery->count('orders.id');
        $pending = $pendingQuery->count('orders.id');
        $complete = $completeQuery->count('orders.id');

        return [
            'all' => $all,
            'pending' => $pending,
            'complete' => $complete,
            'completed' => $complete,
            'cards' => [
                'all' => $this->orderMetricsForQuery(clone $base),
                'pending' => $this->orderMetricsForQuery($pendingQuery),
                'completed' => $this->orderMetricsForQuery($completeQuery),
            ],
        ];
    }

    private function ordersIndexRelations(): array
    {
        return [
            'lead.source',
            'lead.region.branch',
            'lead.branch',
            'lead.firstSales',
            'lead.quotation.createdBy',
            'lead.quotation.proformas.paymentConfirmation',
            'paymentTerms',
            'progressLogs.user',
        ];
    }

    private function ordersIndexQuery(Request $request)
    {
        $query = Order::query()
            ->select('orders.*')
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'leads.branch_id', '=', 'ref_branches.id')
            ->leftJoin('ref_branches as region_branches', 'ref_regions.branch_id', '=', 'region_branches.id')
            ->leftJoin('quotations', 'quotations.lead_id', '=', 'leads.id')
            ->leftJoin('users as quotation_creators', 'quotation_creators.id', '=', 'quotations.created_by');

        if ($request->filled('segment_id')) {
            $query->where('leads.segment_id', $request->input('segment_id'));
        }

        if ($request->filled('source_id')) {
            $query->where('leads.source_id', $request->input('source_id'));
        }

        if ($request->filled('region_id')) {
            $query->where('leads.region_id', $request->input('region_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where(function ($branchQuery) use ($request) {
                $branchQuery->where('ref_branches.id', $request->input('branch_id'))
                    ->orWhere('region_branches.id', $request->input('branch_id'));
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('orders.created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('orders.created_at', '<=', $request->input('end_date'));
        }

        if ($request->filled('min_total')) {
            $query->where('orders.total_billing', '>=', (float) $request->input('min_total'));
        }

        if ($request->filled('max_total')) {
            $query->where('orders.total_billing', '<=', (float) $request->input('max_total'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('orders.order_no', 'like', '%' . $search . '%')
                    ->orWhere('orders.order_status', 'like', '%' . $search . '%')
                    ->orWhere('leads.name', 'like', '%' . $search . '%')
                    ->orWhere('leads.company', 'like', '%' . $search . '%')
                    ->orWhere('leads.phone', 'like', '%' . $search . '%')
                    ->orWhere('leads.email', 'like', '%' . $search . '%')
                    ->orWhere('quotations.quotation_no', 'like', '%' . $search . '%')
                    ->orWhere('ref_branches.name', 'like', '%' . $search . '%')
                    ->orWhere('region_branches.name', 'like', '%' . $search . '%')
                    ->orWhere('ref_regions.name', 'like', '%' . $search . '%')
                    ->orWhere('quotation_creators.name', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    private function applyPaymentStatusFilter($query, ?string $status): void
    {
        $paidSub = $this->paidAmountSubquery();

        if ($status === 'pending') {
            $query->whereRaw("$paidSub < orders.total_billing");
        } elseif (in_array($status, ['complete', 'completed'], true)) {
            $query->whereRaw("$paidSub >= orders.total_billing");
        }
    }

    private function paidAmountSubquery(): string
    {
        return "(
            select coalesce(sum(pc.amount), 0)
            from payment_confirmations pc
            inner join proformas p on p.id = pc.proforma_id
            inner join quotations q on q.id = p.quotation_id
            where q.lead_id = orders.lead_id
                and pc.confirmed_at is not null
        )";
    }

    private function orderMetricsForQuery($query): array
    {
        $orderIds = $query->pluck('orders.id')->unique()->values();

        if ($orderIds->isEmpty()) {
            return [
                'total_orders' => 0,
                'total_billing' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'latest_payment_date' => null,
            ];
        }

        $totalBilling = (float) Order::whereIn('id', $orderIds)->sum('total_billing');

        $paymentQuery = PaymentConfirmation::query()
            ->join('proformas', 'proformas.id', '=', 'payment_confirmations.proforma_id')
            ->join('quotations', 'quotations.id', '=', 'proformas.quotation_id')
            ->join('orders', 'orders.lead_id', '=', 'quotations.lead_id')
            ->whereIn('orders.id', $orderIds)
            ->whereNotNull('payment_confirmations.confirmed_at');

        $paidAmount = (float) (clone $paymentQuery)->sum('payment_confirmations.amount');
        $latestPaymentDate = (clone $paymentQuery)->max('payment_confirmations.paid_at');

        return [
            'total_orders' => $orderIds->count(),
            'total_billing' => $totalBilling,
            'paid_amount' => $paidAmount,
            'remaining_amount' => max($totalBilling - $paidAmount, 0),
            'latest_payment_date' => $latestPaymentDate
                ? \Carbon\Carbon::parse($latestPaymentDate)->format('Y-m-d')
                : null,
        ];
    }

    private function formatOrderIndexRow(Order $order): array
    {
        $quotation = $order->lead?->quotation;
        $proformas = $quotation?->proformas ?? collect();
        $confirmedPayments = $proformas
            ->map(fn ($proforma) => $proforma->paymentConfirmation)
            ->filter(fn ($payment) => $payment && $payment->confirmed_at);

        $termTotal = $order->paymentTerms->count();
        $paidTerms = $proformas
            ->filter(fn ($proforma) => $proforma->proforma_type !== 'booking_fee'
                && $proforma->paymentConfirmation
                && $proforma->paymentConfirmation->confirmed_at)
            ->count();

        $paidAmount = (float) $confirmedPayments->sum('amount');
        $branch = $order->lead?->branch?->name
            ??$order->lead?->region?->branch?->name
            ?? '-';
        $sales = $quotation?->createdBy?->name
            ?? $order->lead?->firstSales?->name
            ?? '-';
        $latestPayment = $confirmedPayments
            ->sortByDesc(fn ($payment) => $payment->paid_at)
            ->first();

        $detail = url('/orders/' . $order->id);

        $actions = '<div class="dropdown">';
        $actions .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
        $actions .= '<i class="bi bi-three-dots"></i>';
        $actions .= '</button>';
        $actions .= '<div class="dropdown-menu dropdown-menu-right rounded-lg!">';
        $actions .= '<a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($detail) . '"><i class="bi bi-eye"></i> View Detail</a>';
        $actions .= '<button type="button" class="dropdown-item btn-progress-log cursor-pointer flex! items-center! gap-2! text-[#1E1E1E]!" data-order="' . e($order->id) . '"><i class="bi bi-clock-history"></i> Progress Logs</button>';
        $actions .= '</div></div>';
        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'customer' => $order->lead?->name ?? '-',
            'branch' => $branch,
            'sales' => $sales,
            'quotation_no' => $quotation?->quotation_no ?? '-',
            'total_billing' => (float) $order->total_billing,
            'paid_amount' => $paidAmount,
            'remaining_amount' => max(((float) $order->total_billing) - $paidAmount, 0),
            'payment_progress' => $termTotal > 0 ? min($termTotal, $paidTerms) . '/' . $termTotal . ' paid' : '0/0 paid',
            'latest_payment_date' => $latestPayment?->paid_at?->format('Y-m-d') ?? '-',
            'order_status' => ucwords(str_replace('_', ' ', $order->order_status ?? '-')),
            'actions' => $actions,
        ];
    }

    private function columnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $mod    = ($number - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $number = (int)(($number - $mod) / 26);
        }
        return $letter;
    }

    private function buildSheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $rIndex => $row) {
            $xml .= '<row r="' . ($rIndex + 1) . '">';
            foreach ($row as $cIndex => $value) {
                $cell = $this->columnLetter($cIndex + 1) . ($rIndex + 1);
                $xml  .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $value) . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function createXlsx(array $rows): string
    {
        $contentTypes = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
            <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
            <Default Extension="xml" ContentType="application/xml"/>
            <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
            <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
        </Types>
        XML;

        $rels = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
            <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
        </Relationships>
        XML;

        $workbook = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
            <sheets>
                <sheet name="Sheet1" sheetId="1" r:id="rId1"/>
            </sheets>
        </workbook>
        XML;

        $workbookRels = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
            <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
        </Relationships>
        XML;

        $sheet = $this->buildSheetXml($rows);

        $tempFile = tempnam(sys_get_temp_dir(), 'orders_');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $tempFile;
    }

    public function activityLogs($id)
    {
        $order = Order::findOrFail($id);

        $logs = LeadActivityLog::with(['user', 'activity'])
            ->where('lead_id', $order->lead_id)
            ->orderByDesc('logged_at')
            ->get()
            ->map(function ($log) {
                $action = trim(($log->activity->code ?? '-') . ' - ' . ($log->activity->name ?? '-'));
                return [
                    'date'        => $log->logged_at ? $log->logged_at->format('d M Y') : '-',
                    'action'      => $action,
                    'description' => $log->note,
                    'user'        => $log->user->name ?? '-',
                ];
            });

        return response()->json($logs);
    }

    public function downloadFile($type, $file)
    {
        $path = public_path("storage/{$type}/{$file}.pdf");
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->download($path);
    }
}
