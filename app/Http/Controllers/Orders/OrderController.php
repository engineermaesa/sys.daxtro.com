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
use Yajra\DataTables\Facades\DataTables;

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
        $query = Order::with([
            'lead.source',
            'lead.segment',
            'lead.region',
            'lead.quotation.proformas.invoice.payments',
            'lead.quotation.proformas.paymentConfirmation',
            'paymentTerms',
            'progressLogs.user',
        ])
            ->select('orders.*')
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->leftJoin('quotations', 'quotations.lead_id', '=', 'leads.id');

        if ($request->filled('segment_id')) {
            $query->whereHas('lead', fn($q) => $q->where('segment_id', $request->segment_id));
        }

        if ($request->filled('source_id')) {
            $query->whereHas('lead', fn($q) => $q->where('source_id', $request->source_id));
        }

        if ($request->filled('region_id')) {
            $query->where('leads.region_id', $request->region_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('ref_branches.id', $request->branch_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('min_total')) {
            $query->where('orders.total_billing', '>=', (float)$request->input('min_total'));
        }

        if ($request->filled('max_total')) {
            $query->where('orders.total_billing', '<=', (float)$request->input('max_total'));
        }

        $paidSub = "(select coalesce(sum(pc.amount),0) from proformas p left join payment_confirmations pc on pc.proforma_id = p.id and pc.confirmed_at is not null where p.quotation_id = quotations.id)";

        if ($request->payment_status === 'pending') {
            $query->whereRaw("$paidSub < orders.total_billing");
        } elseif ($request->payment_status === 'complete') {
            $query->whereRaw("$paidSub >= orders.total_billing");
        }

        return DataTables::of($query)
            ->addColumn('customer_name', fn($row) => $row->lead->name ?? '')
            ->addColumn('source_name', fn($row) => $row->lead->source->name ?? '')
            ->addColumn('segment_name', fn($row) => $row->lead->segment->name ?? '')
            ->addColumn('region_name', fn($row) => $row->lead->region->name ?? '')
            ->addColumn('quotation_no', function ($row) {
                $quotation = Quotation::where('lead_id', $row->lead_id)->first();
                return $quotation->quotation_no ?? '-';
            })
            ->addColumn('completion', function ($row) {
                $proformas = optional($row->lead->quotation)->proformas ?? collect();
                $paidCount = $proformas
                    ->filter(fn($p) => $p->proforma_type !== 'booking_fee'
                        && $p->paymentConfirmation
                        && $p->paymentConfirmation->confirmed_at)
                    ->count();
                $termTotal = $row->paymentTerms->count();
                if ($termTotal === 0) {
                    return '0/0';
                }
                return sprintf('%d/%d', min($termTotal, $paidCount), $termTotal);
            })
            ->addColumn('status', function ($row) {
                $stepLabels = [
                    1 => 'Order Publish',
                    2 => 'On Production',
                    3 => 'Running Test',
                    4 => 'Delivery to Indonesia',
                    5 => 'Legal Confirmation',
                    6 => 'Delivery to Customer Location',
                    7 => 'Installation',
                    8 => 'BAST',
                ];

                $latest = $row->progressLogs->first();
                if (!$latest) {
                    return '<span class="badge bg-secondary">-</span>';
                }

                $label = $stepLabels[$latest->progress_step] ?? $latest->progress_step;

                return '<span class="badge bg-primary">' . $label . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $detail = route('orders.show', $row->id);
                $btnId  = 'orderActionsDropdown' . $row->id;

                $html  = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                $html .= '    <a class="dropdown-item" href="' . e($detail) . '"><i class="bi bi-eye mr-2"></i> View Detail</a>';
                $html .= '    <button type="button" class="dropdown-item btn-progress-log" data-order="' . $row->id . '"><i class="bi bi-clock-history mr-2"></i> Progress Logs</button>';
                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
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

        return $this->respondWith($request, 'pages.orders.show', compact('order', 'quotation', 'terms'));
    }

    public function requestProforma(Request $request, $orderId, $term)
    {
        $order = Order::with('paymentTerms')->findOrFail($orderId);
        $quotation = Quotation::where('lead_id', $order->lead_id)->firstOrFail();

        $type = $term == 1 ? 'down_payment' : 'term_payment';

        $amount = 0;
        if ($pct = $order->paymentTerms->firstWhere('term_no', $term)) {
            $amount = $order->total_billing * ($pct->percentage / 100);
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
        $query = Order::join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->leftJoin('quotations', 'quotations.lead_id', '=', 'leads.id');

        if (!empty($filters['segment_id'])) {
            $query->where('leads.segment_id', $filters['segment_id']);
        }
        if (!empty($filters['source_id'])) {
            $query->where('leads.source_id', $filters['source_id']);
        }
        if (!empty($filters['region_id'])) {
            $query->where('leads.region_id', $filters['region_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('ref_branches.id', $filters['branch_id']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('orders.created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('orders.created_at', '<=', $filters['end_date']);
        }
        if (!empty($filters['min_total'])) {
            $query->where('orders.total_billing', '>=', (float)$filters['min_total']);
        }
        if (!empty($filters['max_total'])) {
            $query->where('orders.total_billing', '<=', (float)$filters['max_total']);
        }

        $base = clone $query;

        $paidSub = "(select coalesce(sum(pc.amount),0) from proformas p left join payment_confirmations pc on pc.proforma_id = p.id and pc.confirmed_at is not null where p.quotation_id = quotations.id)";

        $all = $base->count();
        $pending = (clone $query)->whereRaw("$paidSub < orders.total_billing")->count();
        $complete = (clone $query)->whereRaw("$paidSub >= orders.total_billing")->count();

        return [
            'all' => $all,
            'pending' => $pending,
            'complete' => $complete,
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
