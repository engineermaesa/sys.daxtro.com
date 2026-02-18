<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\ExpenseRealization;
use App\Models\Orders\ExpenseRealizationDetail;
use App\Models\Orders\MeetingExpense;
use App\Models\Orders\FinanceRequest;
use App\Models\Masters\ExpenseType;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ExpenseRealizationController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Expense Realizations';
        return $this->render('pages.orders.expense-realizations.index');
    }

    public function list(Request $request)
    {
        $type = $request->input('type', 'expense-realization');

        try {
            $query = \App\Models\Orders\ExpenseRealization::with(['sales', 'meetingExpense.meeting.lead']);

            \Log::info('Expense Realization Count: ' . $query->count());
            \Log::info('Request Type: ' . $type);

            return DataTables::of($query)
                ->addColumn('status_badge', function ($row) {
                    $colors = [
                        'pending' => 'warning',
                        'submitted' => 'info', 
                        'approved' => 'success',
                        'rejected' => 'danger'
                    ];
                    $statusLabels = [
                        'pending' => 'Pending',
                        'submitted' => 'Waiting Finance',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected'
                    ];
                    $color = $colors[$row->status] ?? 'secondary';
                    $label = $statusLabels[$row->status] ?? ucfirst($row->status);
                    return '<span class="badge bg-' . $color . '">' . $label . '</span>';
                })
                ->addColumn('requester_name', fn($row) => $row->sales->name ?? '-')
                ->addColumn('meeting_info', function ($row) {
                    $meeting = $row->meetingExpense?->meeting;
                    if (!$meeting) return '-';
                    $leadName = $meeting->lead->name ?? null;
                    $id = $meeting->id ?? ($row->meeting_expense_id ?? '-');
                    return $leadName ? "Meeting #{$id} - {$leadName}" : "Meeting #{$id}";
                })
                ->addColumn('original_amount', function ($row) {
                    $orig = $row->meetingExpense?->amount ?? 0;
                    return 'Rp ' . number_format($orig, 0, ',', '.');
                })
                ->addColumn('realized_amount_formatted', function ($row) {
                    return 'Rp ' . number_format($row->realized_amount ?? 0, 0, ',', '.');
                })
                ->addColumn('lead_name', function ($row) {
                    return $row->meetingExpense?->meeting?->lead?->name ?? '-';
                })
                ->addColumn('meeting_date', function ($row) {
                    return $row->meetingExpense?->meeting?->scheduled_start_at ?? null;
                })
                ->addColumn('actions', function ($row) {
                    if ($row->status === 'submitted') {
                        $financeRequest = \App\Models\Orders\FinanceRequest::where('request_type', 'expense-realization')
                            ->where('reference_id', $row->id)
                            ->first();
                        
                        if ($financeRequest) {
                            $url = route('finance-requests.form', $financeRequest->id);
                            return '<a href="'.$url.'" class="btn btn-sm btn-primary">Review</a>';
                        }
                    }
                    return '<span class="text-muted">' . ucfirst($row->status) . '</span>';
                })
                ->rawColumns(['actions', 'status_badge'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('ExpenseRealization list error: ' . $e->getMessage());

            $draw = intval($request->input('draw', 1));
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Server error: ' . $e->getMessage()
            ], 200);
        }
    }

    public function submit($id)
    {
        $realization = ExpenseRealization::findOrFail($id);

        if ($realization->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Cannot submit non-pending realization.']);
        }

        try {
            DB::beginTransaction();

            $realization->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            FinanceRequest::create([
                'request_type' => 'expense-realization',
                'reference_id' => $realization->id,
                'requester_id' => Auth::id(),
                'status' => 'pending',
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Expense realization submitted for finance approval.']);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Failed to submit: ' . $e->getMessage()]);
        }
    }

    public function create($meetingExpenseId = null)
    {
        $meetingExpense = null;
        $expenseTypes = ExpenseType::orderBy('name')->get();
        
        if ($meetingExpenseId) {
            $meetingExpense = MeetingExpense::with('meeting.lead')->findOrFail($meetingExpenseId);
            
            // Check if realization already exists
            $existing = ExpenseRealization::where('meeting_expense_id', $meetingExpenseId)->first();
            if ($existing) {
                return redirect()->route('expense-realizations.edit', $existing->id)
                    ->with('info', 'Expense realization already exists for this meeting expense.');
            }
        }

        $this->pageTitle = 'Create Expense Realization';
        return $this->render('pages.orders.expense-realizations.form', compact('meetingExpense', 'expenseTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'meeting_expense_id' => 'required|exists:meeting_expenses,id',
            'realized_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.expense_type_id' => 'required|exists:ref_expense_types,id',
            'details.*.amount' => 'required|numeric|min:0',
            'details.*.notes' => 'nullable|string',
            'details.*.receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $realization = ExpenseRealization::create([
                'meeting_expense_id' => $request->meeting_expense_id,
                'sales_id' => Auth::id(),
                'realized_amount' => $request->realized_amount,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            foreach ($request->details as $detail) {
                $receiptAttachmentId = null;
                
                if (isset($detail['receipt']) && $detail['receipt']) {
                    $attachment = $this->storeAttachment($detail['receipt'], 'expense-receipts');
                    $receiptAttachmentId = $attachment->id;
                }

                ExpenseRealizationDetail::create([
                    'expense_realization_id' => $realization->id,
                    'expense_type_id' => $detail['expense_type_id'],
                    'amount' => $detail['amount'],
                    'receipt_attachment_id' => $receiptAttachmentId,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('expense-realizations.index')
                ->with('success', 'Expense realization created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Failed to create expense realization: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $realization = ExpenseRealization::with([
            'meetingExpense.meeting.lead',
            'sales',
            'details.expenseType',
            'details.receiptAttachment'
        ])->findOrFail($id);

        $this->pageTitle = 'Expense Realization Details';
        return $this->render('pages.orders.expense-realizations.show', compact('realization'));
    }

    public function edit($id)
    {
        $realization = ExpenseRealization::with([
            'meetingExpense.meeting.lead',
            'details.expenseType',
            'details.receiptAttachment'
        ])->findOrFail($id);

        if ($realization->status !== 'pending') {
            return redirect()->route('expense-realizations.show', $id)
                ->with('error', 'Cannot edit submitted expense realization.');
        }

        $expenseTypes = ExpenseType::orderBy('name')->get();
        $this->pageTitle = 'Edit Expense Realization';
        
        return $this->render('pages.orders.expense-realizations.form', compact('realization', 'expenseTypes'));
    }

    public function update(Request $request, $id)
    {
        $realization = ExpenseRealization::findOrFail($id);

        if ($realization->status !== 'pending') {
            return back()->with('error', 'Cannot update submitted expense realization.');
        }

        $request->validate([
            'realized_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.expense_type_id' => 'required|exists:ref_expense_types,id',
            'details.*.amount' => 'required|numeric|min:0',
            'details.*.notes' => 'nullable|string',
            'details.*.receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $realization->update([
                'realized_amount' => $request->realized_amount,
                'notes' => $request->notes,
            ]);

            // Delete existing details
            $realization->details()->delete();

            // Create new details
            foreach ($request->details as $detail) {
                $receiptAttachmentId = null;
                
                if (isset($detail['receipt']) && $detail['receipt']) {
                    $attachment = $this->storeAttachment($detail['receipt'], 'expense-receipts');
                    $receiptAttachmentId = $attachment->id;
                }

                ExpenseRealizationDetail::create([
                    'expense_realization_id' => $realization->id,
                    'expense_type_id' => $detail['expense_type_id'],
                    'amount' => $detail['amount'],
                    'receipt_attachment_id' => $receiptAttachmentId,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('expense-realizations.index')
                ->with('success', 'Expense realization updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Failed to update expense realization: ' . $e->getMessage());
        }
    }

    private function storeAttachment($file, $folder)
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs($folder, $filename, 'public');

        return Attachment::create([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'uploaded_by' => Auth::id(),
        ]);
    }
}