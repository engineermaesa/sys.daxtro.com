<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Orders\FinanceRequest;
use App\Models\Orders\Proforma;
use App\Models\Orders\Order;
use App\Models\Orders\Quotation;
use App\Models\Orders\Invoice;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\MeetingExpense;
use App\Models\Orders\PaymentConfirmation;
use App\Models\Orders\PaymentLog;
use App\Models\Leads\LeadStatus;
use App\Models\Leads\LeadStatusLog;

use App\Models\Attachment;
use App\Models\Masters\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;


class FinanceRequestController extends Controller
{
    private const REQUEST_TYPES = [
        'meeting-expense',
        'payment-confirmation',
        'expense-realization',
    ];

    public function index(Request $request)
    {
        $this->pageTitle = 'Finance Requests';
        $counts = $this->getFinanceRequestCounts($request);

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $sales = User::query()
            ->with('role')
            ->whereHas('role', fn ($query) => $query->whereIn('code', ['sales', 'branch_manager']))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id', 'role_id']);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['counts' => $counts, 'pageTitle' => $this->pageTitle]);
        }

        return $this->render('pages.finance.requests.index', compact('counts', 'branches', 'sales'));
    }

    public function list(Request $request)
    {
        $type = $this->normalizeFinanceRequestType($request->input('type'));
        $query = $this->buildFinanceRequestQuery($type, $request);

        if ($request->has('draw')) {
            return $this->dataTablesResponse($query);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $rows = $paginator->getCollection()
            ->map(fn (FinanceRequest $row) => $this->serializeFinanceRequestRow($row))
            ->values();

        return response()->json([
            'data' => $rows,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]);
    }

    public function counts(Request $request)
    {
        return response()->json($this->getFinanceRequestCounts($request));
    }

    private function getFinanceRequestCounts(Request $request): array
    {
        $counts = [];

        foreach (self::REQUEST_TYPES as $type) {
            $counts[$type] = (clone $this->buildFinanceRequestQuery($type, $request))->count();
        }

        return $counts;
    }

    private function normalizeFinanceRequestType(?string $type): string
    {
        return in_array($type, self::REQUEST_TYPES, true) ? $type : 'meeting-expense';
    }

    private function buildFinanceRequestQuery(string $type, Request $request)
    {
        $query = FinanceRequest::query()
            ->where('request_type', $type)
            ->with(['requester.branch', 'approver'])
            ->latest('id');

        if ($type === 'meeting-expense') {
            $query->with([
                'meetingExpense.sales.branch',
                'meetingExpense.meeting.lead.branch',
                'meetingExpense.meeting.meetingType',
            ]);
        }

        if ($type === 'payment-confirmation') {
            $query->with([
                'paymentConfirmation.proforma.quotation.lead.branch',
                'paymentConfirmation.proforma.quotation.createdBy.branch',
            ]);
        }

        if ($type === 'expense-realization' && Schema::hasTable('expense_realizations')) {
            $query->with([
                'expenseRealization.sales.branch',
                'expenseRealization.meetingExpense.meeting.lead.branch',
                'expenseRealization.meetingExpense.meeting.meetingType',
                'expenseRealization.meetingExpense.sales.branch',
            ]);
        }

        $this->applyFinanceRequestFilters($query, $type, $request);

        return $query;
    }

    private function applyFinanceRequestFilters($query, string $type, Request $request): void
    {
        $status = trim((string) $request->input('status', ''));
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $branchId = $request->input('branch_id');
        if ($branchId !== null && $branchId !== '') {
            $query->where(function ($subQuery) use ($type, $branchId) {
                $subQuery->whereHas('requester', fn ($userQuery) => $userQuery->where('branch_id', $branchId));

                if ($type === 'meeting-expense') {
                    $subQuery
                        ->orWhereHas('meetingExpense.sales', fn ($salesQuery) => $salesQuery->where('branch_id', $branchId))
                        ->orWhereHas('meetingExpense.meeting.lead', fn ($leadQuery) => $leadQuery->where('branch_id', $branchId));
                }

                if ($type === 'payment-confirmation') {
                    $subQuery
                        ->orWhereHas('paymentConfirmation.proforma.quotation.createdBy', fn ($salesQuery) => $salesQuery->where('branch_id', $branchId))
                        ->orWhereHas('paymentConfirmation.proforma.quotation.lead', fn ($leadQuery) => $leadQuery->where('branch_id', $branchId));
                }

                if ($type === 'expense-realization' && Schema::hasTable('expense_realizations')) {
                    $subQuery
                        ->orWhereHas('expenseRealization.sales', fn ($salesQuery) => $salesQuery->where('branch_id', $branchId))
                        ->orWhereHas('expenseRealization.meetingExpense.sales', fn ($salesQuery) => $salesQuery->where('branch_id', $branchId))
                        ->orWhereHas('expenseRealization.meetingExpense.meeting.lead', fn ($leadQuery) => $leadQuery->where('branch_id', $branchId));
                }
            });
        }

        $salesId = $request->input('sales_id');
        if ($salesId !== null && $salesId !== '') {
            $query->where(function ($subQuery) use ($type, $salesId) {
                $subQuery->where('requester_id', $salesId);

                if ($type === 'meeting-expense') {
                    $subQuery->orWhereHas('meetingExpense', fn ($expenseQuery) => $expenseQuery->where('sales_id', $salesId));
                }

                if ($type === 'payment-confirmation') {
                    $subQuery->orWhereHas('paymentConfirmation.proforma.quotation', fn ($quotationQuery) => $quotationQuery->where('created_by', $salesId));
                }

                if ($type === 'expense-realization' && Schema::hasTable('expense_realizations')) {
                    $subQuery->orWhereHas('expenseRealization', fn ($realizationQuery) => $realizationQuery->where('sales_id', $salesId));
                }
            });
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($subQuery) use ($type, $search) {
                $like = '%' . $search . '%';

                if (ctype_digit($search)) {
                    $subQuery
                        ->where('id', (int) $search)
                        ->orWhere('reference_id', (int) $search);
                }

                $subQuery
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('requester', fn ($userQuery) => $userQuery->where('name', 'like', $like))
                    ->orWhereHas('requester.branch', fn ($branchQuery) => $branchQuery->where('name', 'like', $like));

                if ($type === 'meeting-expense') {
                    $subQuery
                        ->orWhereHas('meetingExpense.sales', fn ($salesQuery) => $salesQuery->where('name', 'like', $like))
                        ->orWhereHas('meetingExpense.meeting.lead', function ($leadQuery) use ($like) {
                            $leadQuery->where('name', 'like', $like)
                                ->orWhere('company', 'like', $like)
                                ->orWhere('phone', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        })
                        ->orWhereHas('meetingExpense.meeting', function ($meetingQuery) use ($like) {
                            $meetingQuery->where('city', 'like', $like)
                                ->orWhere('address', 'like', $like);
                        });
                }

                if ($type === 'payment-confirmation') {
                    $subQuery
                        ->orWhereHas('paymentConfirmation', function ($paymentQuery) use ($like) {
                            $paymentQuery->where('payer_name', 'like', $like)
                                ->orWhere('payer_bank', 'like', $like)
                                ->orWhere('payer_account_number', 'like', $like);
                        })
                        ->orWhereHas('paymentConfirmation.proforma', fn ($proformaQuery) => $proformaQuery->where('proforma_no', 'like', $like))
                        ->orWhereHas('paymentConfirmation.proforma.quotation.createdBy', fn ($salesQuery) => $salesQuery->where('name', 'like', $like))
                        ->orWhereHas('paymentConfirmation.proforma.quotation.lead', function ($leadQuery) use ($like) {
                            $leadQuery->where('name', 'like', $like)
                                ->orWhere('company', 'like', $like)
                                ->orWhere('phone', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                }

                if ($type === 'expense-realization' && Schema::hasTable('expense_realizations')) {
                    $subQuery
                        ->orWhereHas('expenseRealization.sales', fn ($salesQuery) => $salesQuery->where('name', 'like', $like))
                        ->orWhereHas('expenseRealization.meetingExpense.meeting.lead', function ($leadQuery) use ($like) {
                            $leadQuery->where('name', 'like', $like)
                                ->orWhere('company', 'like', $like)
                                ->orWhere('phone', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        })
                        ->orWhereHas('expenseRealization.meetingExpense.meeting', function ($meetingQuery) use ($like) {
                            $meetingQuery->where('city', 'like', $like)
                                ->orWhere('address', 'like', $like);
                        });
                }
            });
        }

        $this->applyFinanceRequestDateFilter($query, $type, $request);
    }

    private function applyFinanceRequestDateFilter($query, string $type, Request $request): void
    {
        $start = $request->input('date_start');
        $end = $request->input('date_end');

        if (!$start || !$end) {
            return;
        }

        try {
            $startAt = Carbon::parse($start)->startOfDay();
            $endAt = Carbon::parse($end)->endOfDay();
        } catch (\Throwable $e) {
            return;
        }

        $mode = $request->input('date_mode', 'requested_at');

        if ($mode === 'decided_at') {
            $query->whereBetween('decided_at', [$startAt, $endAt]);
            return;
        }

        if ($mode === 'meeting_date') {
            if ($type === 'meeting-expense') {
                $query->whereHas('meetingExpense.meeting', fn ($meetingQuery) => $meetingQuery->whereBetween('scheduled_start_at', [$startAt, $endAt]));
                return;
            }

            if ($type === 'expense-realization' && Schema::hasTable('expense_realizations')) {
                $query->whereHas('expenseRealization.meetingExpense.meeting', fn ($meetingQuery) => $meetingQuery->whereBetween('scheduled_start_at', [$startAt, $endAt]));
                return;
            }

            if ($type === 'payment-confirmation') {
                $query->whereHas('paymentConfirmation', fn ($paymentQuery) => $paymentQuery->whereBetween('paid_at', [$startAt, $endAt]));
                return;
            }
        }

        $query->whereBetween('created_at', [$startAt, $endAt]);
    }

    private function dataTablesResponse($query)
    {
        return DataTables::eloquent($query)
            ->addColumn('status_badge', fn (FinanceRequest $row) => $this->statusBadge($row->status))
            ->addColumn('requester_name', fn (FinanceRequest $row) => $this->serializeFinanceRequestRow($row)['sales_name'])
            ->addColumn('approver_name', fn (FinanceRequest $row) => $row->approver->name ?? '-')
            ->addColumn('amount', fn (FinanceRequest $row) => $this->serializeFinanceRequestRow($row)['amount'])
            ->addColumn('lead_name', fn (FinanceRequest $row) => $this->serializeFinanceRequestRow($row)['lead_name'])
            ->addColumn('meeting_date', fn (FinanceRequest $row) => $this->serializeFinanceRequestRow($row)['meeting_date'])
            ->addColumn('created_at', fn (FinanceRequest $row) => $row->created_at)
            ->addColumn('decided_at', fn (FinanceRequest $row) => $row->decided_at)
            ->addColumn('actions', fn (FinanceRequest $row) => $this->financeRequestAction($row))
            ->rawColumns(['actions', 'status_badge'])
            ->make(true);
    }

    private function serializeFinanceRequestRow(FinanceRequest $row): array
    {
        $type = $row->request_type;
        $branchName = $row->requester?->branch?->name ?? '-';
        $salesName = $row->requester?->name ?? '-';
        $leadName = '-';
        $meetingDate = null;
        $location = '-';
        $paymentDate = null;
        $reference = '-';
        $amount = null;
        $originalAmount = null;
        $realizedAmount = null;

        if ($type === 'meeting-expense') {
            $expense = $row->meetingExpense;
            $meeting = $expense?->meeting;
            $lead = $meeting?->lead;
            $branchName = $expense?->sales?->branch?->name ?? $lead?->branch?->name ?? $branchName;
            $salesName = $expense?->sales?->name ?? $salesName;
            $leadName = $lead?->name ?? '-';
            $meetingDate = $meeting?->scheduled_start_at;
            $location = $meeting?->city ?: ($meeting?->address ?: '-');
            $amount = $expense?->amount;
            $reference = $meeting?->meetingType?->name ?? 'Meeting Expense';
        }

        if ($type === 'payment-confirmation') {
            $payment = $row->paymentConfirmation;
            $proforma = $payment?->proforma;
            $quotation = $proforma?->quotation;
            $lead = $quotation?->lead;
            $sales = $quotation?->createdBy;
            $branchName = $sales?->branch?->name ?? $lead?->branch?->name ?? $branchName;
            $salesName = $sales?->name ?? $salesName;
            $leadName = $lead?->name ?? '-';
            $paymentDate = $payment?->paid_at;
            $location = $payment?->payer_bank ?: '-';
            $amount = $payment?->amount;
            $reference = $proforma?->proforma_no ?? 'Payment Confirmation';
        }

        if ($type === 'expense-realization' && Schema::hasTable('expense_realizations')) {
            $realization = $row->expenseRealization;
            $meetingExpense = $realization?->meetingExpense;
            $meeting = $meetingExpense?->meeting;
            $lead = $meeting?->lead;
            $branchName = $realization?->sales?->branch?->name
                ?? $meetingExpense?->sales?->branch?->name
                ?? $lead?->branch?->name
                ?? $branchName;
            $salesName = $realization?->sales?->name ?? $meetingExpense?->sales?->name ?? $salesName;
            $leadName = $lead?->name ?? '-';
            $meetingDate = $meeting?->scheduled_start_at;
            $location = $meeting?->city ?: ($meeting?->address ?: '-');
            $originalAmount = $meetingExpense?->amount;
            $realizedAmount = $realization?->realized_amount;
            $amount = $realizedAmount;
            $reference = 'Expense Realization';
        }

        return [
            'id' => $row->id,
            'type' => $type,
            'branch_name' => $branchName ?: '-',
            'sales_name' => $salesName ?: '-',
            'requester_name' => $salesName ?: '-',
            'lead_name' => $leadName ?: '-',
            'meeting_date' => $meetingDate,
            'payment_date' => $paymentDate,
            'location' => $location ?: '-',
            'reference' => $reference ?: '-',
            'requested_at' => $row->created_at,
            'created_at' => $row->created_at,
            'decided_at' => $row->decided_at,
            'amount' => $this->formatRupiah($amount),
            'original_amount' => $originalAmount !== null ? $this->formatRupiah($originalAmount) : '-',
            'realized_amount' => $realizedAmount !== null ? $this->formatRupiah($realizedAmount) : '-',
            'status' => $row->status,
            'status_badge' => $this->statusBadge($row->status),
            'actions' => $this->financeRequestAction($row),
        ];
    }

    private function formatRupiah($amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    private function statusBadge(?string $status): string
    {
        $status = $status ?: 'pending';
        $colors = [
            'pending' => 'bg-[#FFF4D8] text-[#976A00]',
            'approved' => 'bg-[#E7F3EE] text-[#115640]',
            'rejected' => 'bg-[#FDECEC] text-[#900B09]',
        ];
        $class = $colors[$status] ?? 'bg-gray-100 text-gray-700';
        $label = ucwords(str_replace('-', ' ', $status));

        return '<span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ' . $class . '">' . e($label) . '</span>';
    }

    private function financeRequestAction(FinanceRequest $row): string
    {
        $url = url('/finance-requests/' . $row->id);

        return '<a href="' . e($url) . '" class="inline-flex items-center justify-center rounded-md bg-[#115640] px-3 py-1.5 text-xs font-semibold text-white">View Detail</a>';
    }

    private function getFinanceRequestAmount($financeRequest)
    {
        switch ($financeRequest->request_type) {
            case 'meeting-expense':
                $expense = MeetingExpense::find(id: $financeRequest->reference_id);
                return $expense ? 'Rp ' . number_format($expense->amount, 0, ',', '.') : '-';

            case 'expense-realization':
                if (!Schema::hasTable('expense_realizations')) return '-';
                $realization = \App\Models\Orders\ExpenseRealization::find($financeRequest->reference_id);
                return $realization ? 'Rp ' . number_format($realization->realized_amount, 0, ',', '.') : '-';

            case 'payment-confirmation':
                $payment = PaymentConfirmation::find($financeRequest->reference_id);
                return $payment ? 'Rp ' . number_format($payment->amount, 0, ',', '.') : '-';

            case 'proforma':
                $proforma = Proforma::find($financeRequest->reference_id);
                return $proforma ? 'Rp ' . number_format($proforma->amount, 0, ',', '.') : '-';

            case 'invoice':
                $parts = explode('-', $financeRequest->reference_id);
                if (count($parts) >= 2) {
                    $orderId = $parts[0];
                    $termNo = $parts[1];
                    $order = Order::with('paymentTerms')->find($orderId);
                    if ($order) {
                        $term = $order->paymentTerms->firstWhere('term_no', (int)$termNo);
                        if ($term) {
                            $amount = $order->total_billing * ($term->percentage / 100);
                            return 'Rp ' . number_format($amount, 0, ',', '.');
                        }
                    }
                }
                return '-';

            default:
                return '-';
        }
    }

    private function getLeadName($financeRequest)
    {
        if ($financeRequest->request_type === 'meeting-expense') {
            $expense = MeetingExpense::with('meeting.lead')->find($financeRequest->reference_id);
            return $expense?->meeting?->lead?->name ?? '-';
        } elseif ($financeRequest->request_type === 'expense-realization') {
            if (!Schema::hasTable('expense_realizations')) return '-';
            $realization = \App\Models\Orders\ExpenseRealization::with('meetingExpense.meeting.lead')->find($financeRequest->reference_id);
            return $realization?->meetingExpense?->meeting?->lead?->name ?? '-';
        }
        return '-';
    }

    private function getMeetingDate($financeRequest)
    {
        if ($financeRequest->request_type === 'meeting-expense') {
            $expense = MeetingExpense::with('meeting')->find($financeRequest->reference_id);
            return $expense?->meeting?->scheduled_start_at ?? null;
        } elseif ($financeRequest->request_type === 'expense-realization') {
            if (!Schema::hasTable('expense_realizations')) return null;
            $realization = \App\Models\Orders\ExpenseRealization::with('meetingExpense.meeting')->find($financeRequest->reference_id);
            return $realization?->meetingExpense?->meeting?->scheduled_start_at ?? null;
        }
        return null;
    }

    public function form(Request $request, $id)
    {
        $financeRequest = FinanceRequest::with(['requester', 'approver'])->findOrFail($id);
        $order = null;
        $termNo = null;
        $meetingExpense = null;
        $proforma = null;
        $paymentConfirmation = null;
        $quotation = null;
        $invoice = null;

        if ($financeRequest->request_type === 'payment-confirmation') {
            $paymentConfirmation = PaymentConfirmation::with([
                'attachment',
                'proforma.attachment',
                'proforma.invoice',
                'proforma.paymentConfirmation.attachment',
                'proforma.quotation.items',
                'proforma.quotation.paymentTerms',
                'proforma.quotation.proformas.paymentConfirmation.attachment',
                'proforma.quotation.proformas.invoice',
                'proforma.quotation.order.orderItems',
                'proforma.quotation.order.paymentTerms',
                'proforma.quotation.order.lead',
            ])->find($financeRequest->reference_id);

            $proforma = $paymentConfirmation?->proforma;
            $quotation = $proforma?->quotation;
            $order = $quotation?->order;
            $invoice = $proforma?->invoice;
        } else if ($financeRequest->request_type === 'proforma') {
            $proforma = Proforma::with([
                'attachment',
                'invoice',
                'paymentConfirmation.attachment',
                'quotation.items',
                'quotation.paymentTerms',
                'quotation.proformas.paymentConfirmation.attachment',
                'quotation.proformas.invoice',
                'quotation.order.orderItems',
                'quotation.order.paymentTerms',
                'quotation.order.lead',
            ])->find($financeRequest->reference_id);

            $paymentConfirmation = $proforma?->paymentConfirmation;
            $quotation = $proforma?->quotation;
            $order = $quotation?->order;
            $invoice = $proforma?->invoice;
        } elseif ($financeRequest->request_type === 'invoice') {
            $referenceParts = explode('-', (string) $financeRequest->reference_id, 2);
            $orderId = $referenceParts[0] ?? null;
            $termNo = $referenceParts[1] ?? null;

            if ($orderId) {
                $order = Order::with(['orderItems', 'paymentTerms', 'lead'])->find($orderId);

                if ($order) {
                    $quotation = Quotation::with([
                        'items',
                        'paymentTerms',
                        'proformas.paymentConfirmation.attachment',
                        'proformas.invoice',
                        'order.orderItems',
                        'order.paymentTerms',
                        'order.lead',
                    ])->where('lead_id', $order->lead_id)->first();

                    if ($quotation) {
                        $proforma = $quotation->proformas
                            ->firstWhere('term_no', is_numeric($termNo) ? (int) $termNo : $termNo);
                    }
                    $paymentConfirmation = $proforma?->paymentConfirmation;
                    $invoice = $proforma?->invoice;
                }
            }
        } elseif ($financeRequest->request_type === 'meeting-expense') {
            $meetingExpense = MeetingExpense::with([
                'details.expenseType',
                'meeting.lead',
                'sales',
            ])->find($financeRequest->reference_id);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(compact('financeRequest', 'order', 'termNo', 'meetingExpense', 'proforma'));
        }

        return $this->render('pages.finance.requests.form', compact(
            'financeRequest',
            'order',
            'termNo',
            'meetingExpense',
            'proforma',
            'paymentConfirmation',
            'quotation',
            'invoice'
        ));
    }


    public function approve($id, Request $request)
    {
        $financeRequest = FinanceRequest::findOrFail($id);

        try {
            DB::beginTransaction();

            $financeRequest->update([
                'status' => 'approved',
                'approver_id' => Auth::id(),
                'decided_at' => now(),
                'notes' => $request->input('notes'),
            ]);

            switch ($financeRequest->request_type) {
                case 'proforma':
                    $this->approveProforma($financeRequest);
                    break;
                case 'invoice':
                    $this->approveInvoice($financeRequest);
                    break;
                case 'payment-confirmation':
                    $this->approvePaymentConfirmation($financeRequest, $request);
                    break;
                case 'meeting-expense':
                    $this->approveMeetingExpense($financeRequest);
                    break;
                case 'expense-realization':
                    $this->approveExpenseRealization($financeRequest);
                    break;
            }

            DB::commit();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'Request approved successfully.']);
            }

            return redirect()->route('finance-requests.index')->with('success', 'Request approved successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Failed to approve request: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to approve request: ' . $e->getMessage());
        }
    }

    public function approveWithRealization(Request $request)
    {
        $data = $request->validate([
            'finance_request_id' => 'required|integer',
            'meeting_expense_id' => 'required|integer',
            'realization_expenses' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        if (!Schema::hasTable('expense_realizations')) {
            return response()->json([
                'success' => false,
                'message' => 'Database table "expense_realizations" is missing. Please run migrations.'
            ], 200);
        }

        try {
            DB::beginTransaction();

            $financeRequest = FinanceRequest::findOrFail($data['finance_request_id']);
            $meetingExpense = MeetingExpense::findOrFail($data['meeting_expense_id']);

            $total = 0;
            foreach ($data['realization_expenses'] as $exp) {
                $amount = isset($exp['amount']) ? (float) $exp['amount'] : 0;
                $total += $amount;
            }

            $realization = \App\Models\Orders\ExpenseRealization::create([
                'meeting_expense_id' => $meetingExpense->id,
                'sales_id' => $meetingExpense->sales_id ?? $financeRequest->requester_id,
                'realized_amount' => $total,
                'status' => 'submitted',
                'notes' => $data['notes'] ?? 'Created from finance approval',
            ]);

            if (
                !empty($data['realization_expenses']) &&
                Schema::hasTable('expense_realization_details') &&
                method_exists($realization, 'details')
            ) {
                foreach ($data['realization_expenses'] as $exp) {
                    $realization->details()->create([
                        'expense_type_id' => $exp['expense_type_id'] ?? null,
                        'notes' => $exp['notes'] ?? null,
                        'amount' => $exp['amount'] ?? 0,
                    ]);
                }
            }

            $financeRequest->update([
                'status' => 'approved',
                'approver_id' => Auth::id(),
                'decided_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Expense realization created and request approved.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Failed to process: ' . $e->getMessage()], 500);
        }
    }

    private function approveExpenseRealization(FinanceRequest $request)
    {
        $realization = \App\Models\Orders\ExpenseRealization::find($request->reference_id);
        if ($realization) {
            $realization->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);
        }
    }

    private function approveProforma(FinanceRequest $request)
    {
        $proforma = Proforma::findOrFail($request->reference_id);
        $proforma->update([
            'proforma_no' => 'PROFORMA_' . $proforma->id,
            'status'      => 'confirmed',
            'issued_at'   => now(),
        ]);

        Attachment::create([
            'type'        => 'proforma_pdf',
            'file_path'   => 'storage/proformas/PROFORMA_' . $proforma->id . '.pdf',
            'mime_type'   => 'application/pdf',
            'size'        => 0,
            'uploaded_by' => Auth::id(),
        ]);

        PaymentLog::create([
            'quotation_id' => $proforma->quotation_id,
            'proforma_id'  => $proforma->id,
            'type'         => 'proforma',
            'user_id'      => Auth::id(),
            'logged_at'    => now(),
        ]);
    }

    private function approveInvoice(FinanceRequest $request)
    {
        [$orderId, $term] = explode('-', $request->reference_id);
        $order = Order::with('paymentTerms')->findOrFail($orderId);
        $quotation = Quotation::where('lead_id', $order->lead_id)->firstOrFail();

        $percentage = $order->paymentTerms->firstWhere('term_no', (int)$term)?->percentage ?? 0;
        $amount = $order->total_billing * ($percentage / 100);

        $proforma = $quotation->proformas()->firstOrCreate(
            ['term_no' => (int)$term],
            ['proforma_type' => $term == 1 ? 'down_payment' : 'term_payment', 'amount' => $amount]
        );

        if (!$proforma->proforma_no) {
            $proforma->update([
                'proforma_no' => 'PROFORMA_' . $proforma->id,
                'status'      => 'confirmed',
                'issued_at'   => now(),
            ]);

            Attachment::create([
                'type'        => 'proforma_pdf',
                'file_path'   => 'storage/proformas/PROFORMA_' . $proforma->id . '.pdf',
                'mime_type'   => 'application/pdf',
                'size'        => 0,
                'uploaded_by' => Auth::id(),
            ]);
        }

        $invoice = Invoice::create([
            'proforma_id'  => $proforma->id,
            'invoice_no'   => 'INVOICE_' . $proforma->id,
            'invoice_type' => $term == 3 ? 'final' : 'down_payment',
            'amount'       => $amount,
            'due_date'     => now()->addWeeks(4),
            'status'       => 'open',
            'issued_at'    => now(),
        ]);

        Attachment::create([
            'type'        => 'invoice_pdf',
            'file_path'   => 'storage/invoices/' . $invoice->invoice_no . '.pdf',
            'mime_type'   => 'application/pdf',
            'size'        => 0,
            'uploaded_by' => Auth::id(),
        ]);

        PaymentLog::create([
            'quotation_id' => $quotation->id,
            'invoice_id'   => $invoice->id,
            'type'         => 'invoice',
            'user_id'      => Auth::id(),
            'logged_at'    => now(),
        ]);

        QuotationLog::create([
            'quotation_id' => $quotation->id,
            'action'       => 'invoice_created',
            'user_id'      => Auth::id(),
            'logged_at'    => now(),
        ]);
    }

    private function approvePaymentConfirmation(FinanceRequest $request, Request $httpRequest)
    {
        $payment = PaymentConfirmation::with('proforma.quotation.lead')->findOrFail($request->reference_id);
        $quotation = $payment->proforma->quotation->load('items', 'paymentTerms');

        $invoiceType = match ($payment->proforma->proforma_type) {
            'booking_fee'  => 'booking_fee',
            'down_payment' => 'down_payment',
            default        => 'final',
        };

        $invoice = Invoice::create([
            'proforma_id'  => $payment->proforma->id,
            'invoice_no'   => 'INV-' . str_pad(Invoice::max('id') + 1, 5, '0', STR_PAD_LEFT),
            'invoice_type' => $invoiceType,
            'amount'       => $payment->amount,
            'due_date'     => now()->addDays(30),
            'status'       => 'paid',
            'issued_at'    => now(),
        ]);

        $html = view('pdfs.invoice', [
            'invoice'   => $invoice,
            'quotation' => $quotation,
            'proforma'  => $payment->proforma,
        ])->render();

        $pdf = PDF::loadHTML($html)->setPaper('A4', 'portrait');
        $fileName = $invoice->invoice_no . '.pdf';

        $storagePath = storage_path('app/public/invoices');
        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        $filePath = 'invoices/' . $fileName;
        $pdf->save(storage_path('app/public/' . $filePath));

        $attachment = Attachment::create([
            'type'        => 'invoice_pdf',
            'file_path'   => 'storage/' . $filePath,
            'mime_type'   => 'application/pdf',
            'size'        => strlen($pdf->output()),
            'uploaded_by' => Auth::id(),
        ]);

        $invoice->update(['attachment_id' => $attachment->id]);

        PaymentLog::create([
            'quotation_id' => $payment->proforma->quotation->id,
            'proforma_id'  => $payment->proforma->id,
            'invoice_id'   => $invoice->id,
            'type'         => 'invoice',
            'user_id'      => Auth::id(),
            'logged_at'    => now(),
        ]);

        QuotationLog::create([
            'quotation_id' => $payment->proforma->quotation->id,
            'action'       => 'invoice_created',
            'user_id'      => Auth::id(),
            'logged_at'    => now(),
        ]);

        $payment->update([
            'confirmed_by' => $httpRequest->user()->id,
            'confirmed_at' => now(),
        ]);

        // UPDATE LEAD STATUS
        $this->updateLeadStatusAfterPayment($payment);

        if ($payment->proforma->proforma_type === 'down_payment') {
            $this->createOrderFromQuotation($payment->proforma->quotation);
            $this->releaseIncentiveOnDownPayment($payment);
        }

        // CHECK IF ALL PROFORMAS ARE PAID
        $payment = PaymentConfirmation::with('proforma.quotation.lead')->findOrFail($request->reference_id);
        $this->checkOrderFinalization($payment);
    }

    private function createOrderFromQuotation(Quotation $quotation): void
    {
        // If order already exists, skip
        if ($quotation->order) {
            return;
        }

        $userId = Auth::user()->id;

        // Create the order
        $order = $quotation->order()->create([
            'lead_id'       => $quotation->lead_id,
            'order_no'      => 'ORDER_' . str_pad(\App\Models\Orders\Order::max('id') + 1, 5, '0', STR_PAD_LEFT),
            'total_billing' => $quotation->grand_total,
            'order_status'  => 'publish',
        ]);

        // Copy items to order_items
        foreach ($quotation->items as $item) {
            $order->orderItems()->create([
                'product_id'    => $item->product_id,
                'description'   => $item->description,
                'qty'           => $item->qty,
                'unit_price'    => $item->unit_price,
                'discount_pct'  => $item->discount_pct,
                'tax_pct'       => $quotation->tax_pct,
                'total_discount' => isset($item->total_discount) ? (float) $item->total_discount : 0.0,
                'line_total'    => $item->line_total,
            ]);
        }

        // Copy payment terms
        foreach ($quotation->paymentTerms as $term) {
            $order->paymentTerms()->create([
                'term_no'    => $term->term_no,
                'percentage' => $term->percentage,
            ]);
        }

        // Create initial order progress log
        $order->progressLogs()->create([
            'progress_step' => 1,
            'note'          => "Order created",
            'logged_at'     => now(),
            'user_id'       => $userId,
        ]);
    }

    private function updateLeadStatusAfterPayment(PaymentConfirmation $payment)
    {
        $lead = $payment->proforma->quotation->lead ?? null;

        if (!$lead) return;

        $type = $payment->proforma->proforma_type;

        if ($type === 'booking_fee' && $lead->status_id != LeadStatus::DEAL) {
            $lead->update(['status_id' => LeadStatus::HOT]);
            LeadStatusLog::create(['lead_id' => $lead->id, 'status_id' => LeadStatus::HOT]);
        } elseif ($type === 'down_payment') {
            // Ensure an order exists for this quotation before setting `deal_at`.
            // createOrderFromQuotation is idempotent and will return early if an order already exists.
            $quotation = $payment->proforma->quotation;
            if ($quotation) {
                $this->createOrderFromQuotation($quotation);
                $quotation->refresh();
                $hasOrder = (bool) ($quotation->order ?? null);
            } else {
                $hasOrder = false;
            }

            $data = ['status_id' => LeadStatus::DEAL];

            // Only set `deal_at` when the proforma is not booking_fee, it's the first term,
            // the payment is confirmed, and the quotation has an order.
            if ((($payment->proforma->proforma_type ?? '') !== 'booking_fee')
                && (($payment->proforma->term_no ?? null) === 1)
                && $payment->confirmed_at
                && $hasOrder) {
                if (empty($lead->deal_at)) {
                    $data['deal_at'] = $payment->confirmed_at;
                }
            }

            $lead->update($data);
            LeadStatusLog::create(['lead_id' => $lead->id, 'status_id' => LeadStatus::DEAL]);

            // Saat status lead menjadi DEAL, delegasikan ke PurchaseController
            app(\App\Http\Controllers\Purchasing\PurchaseController::class)
                ->handleLeadDeal($lead->id);
        }
    }

    private function checkOrderFinalization(PaymentConfirmation $payment)
    {
        $order = $payment->proforma->quotation->order;
        if (!$order) return;

        $quotation = $payment->proforma->quotation->load('proformas.paymentConfirmation', 'reviews');

        $allPaid = $quotation->proformas->every(
            fn($p) =>
            $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at
        );

        if ($allPaid) {
            $order->update(['order_status' => 'done']);
        }
    }

    private function releaseIncentiveOnDownPayment(PaymentConfirmation $payment)
    {
        $proforma = $payment->proforma;

        if ($proforma->proforma_type !== 'down_payment' || $proforma->term_no !== 1) {
            return;
        }

        $quotation = $proforma->quotation->load('reviews');

        $review = $quotation->reviews->where('decision', 'approve')->last();
        if (! $review) return;

        $salesId = $quotation->created_by;

        if ($salesId) {
            $balance = \App\Models\UserBalance::firstOrCreate(
                ['user_id' => $salesId],
                ['total_balance' => 0]
            );

            $balance->increment('total_balance', $review->incentive_nominal);

            \App\Models\UserBalanceLog::where('user_id', $salesId)
                ->where('quotation_id', $quotation->id)
                ->update(['status' => 'received']);
        }
    }

    private function approveMeetingExpense(FinanceRequest $request)
    {
        $expense = MeetingExpense::find($request->reference_id);
        if ($expense) {
            $expense->update(['status' => 'approved']);

            // Create expense realization with 'pending' status - waiting for sales to fill details
            if (Schema::hasTable('expense_realizations')) {
                try {
                    \App\Models\Orders\ExpenseRealization::create([
                        'meeting_expense_id' => $expense->id,
                        'sales_id' => $expense->sales_id,
                        'realized_amount' => 0,
                        'status' => 'pending', // Sales needs to fill amount and submit
                        'notes' => 'Auto-created from approved meeting expense #' . $expense->id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create expense_realization: ' . $e->getMessage(), ['expense_id' => $expense->id]);
                }
            } else {
                Log::warning('Table expense_realizations does not exist; skipping realization creation', ['expense_id' => $expense->id]);
            }
        }
    }


    public function reject($id, Request $request)
    {
        $financeRequest = FinanceRequest::findOrFail($id);

        if ($financeRequest->request_type === 'meeting-expense') {
            $expense = MeetingExpense::find($financeRequest->reference_id);
            if ($expense) {
                $expense->status = 'rejected';
                $expense->save();
            }
        }

        $financeRequest->update([
            'status' => 'rejected',
            'approver_id' => Auth::id(),
            'decided_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true, 'message' => 'Request rejected']);
        }

        return back()->with('status', 'Request rejected');
    }
}
