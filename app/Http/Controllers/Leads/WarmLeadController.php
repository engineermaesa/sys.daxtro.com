<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog, LeadSegment};
use App\Models\Orders\{Quotation, QuotationItems, QuotationPaymentTerm, PaymentConfirmation, QuotationLog};
use App\Models\Masters\Product;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarmLeadController extends Controller
{
    public function myWarmList(Request $request)
    {
        $claims = LeadClaim::with(['lead.quotation', 'lead.segment', 'lead.source'])
            ->whereHas('lead', fn ($q) => $q->where('status_id', LeadStatus::WARM))
            ->whereNull('released_at');

        if ($request->user()->role?->code === 'sales') {
            $claims->where('sales_id', $request->user()->id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claims->whereHas('lead.quotation', function ($q) use ($request) {
                $q->firstApprovalBetween($request->start_date, $request->end_date);
            });
        }

        return DataTables::of($claims)
            ->addColumn('claimed_at', fn ($row) => $row->claimed_at)
            ->addColumn('lead_name', fn ($row) => $row->lead->name)
            ->addColumn('segment_name', fn ($row) => $row->lead->segment->name ?? '-')
            ->addColumn('source_name', fn ($row) => $row->lead->source->name ?? '-')
            ->addColumn('meeting_status', function ($row) {
                $quotation = $row->lead->quotation;

                if (! $quotation) {
                    return '<span class="badge bg-secondary">No Quotation</span>';
                }

                $status = $quotation->status;
                $badgeClass = match ($status) {
                    'draft'     => 'bg-secondary',
                    'review'    => 'bg-warning',
                    'published' => 'bg-success',
                    'rejected'  => 'bg-danger',
                    default     => 'bg-light text-dark',
                };

                return '<span class="badge '.$badgeClass.'">'.ucfirst($status).'</span>';
            })
            ->addColumn('actions', function ($row) {
                $quotation = $row->lead->quotation;
                $viewUrl   = route('leads.my.warm.manage', $row->lead->id);
                $createUrl = route('leads.my.warm.quotation.create', $row->id);
                $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
                $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;
                $trashUrl   = route('leads.my.warm.trash', $row->id);

                $btnId = 'warmActionsDropdown' . $row->id;

                $html  = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle"'
                    . ' type="button" id="' . $btnId . '"'
                    . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                $html .= '    <a class="dropdown-item" href="' . e($viewUrl) . '">' 
                    . '      <i class="bi bi-eye mr-2"></i> View Lead</a>';
                $activityUrl = route('leads.activity.logs', $row->lead->id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> View / Add Activity</button>';

                if (! $quotation) {
                    $html .= '  <a class="dropdown-item" href="' . e($createUrl) . '">'
                        . '    <i class="bi bi-file-earmark-plus mr-2"></i> Generate Quotation</a>';
                } else {
                    $html .= '  <a class="dropdown-item" href="' . e($quoteUrl) . '">'
                        . '    <i class="bi bi-file-earmark-text mr-2"></i> View Quotation</a>';
                    $html .= '  <a class="dropdown-item" href="' . e($downloadUrl) . '">'
                        . '    <i class="bi bi-download mr-2"></i> Download</a>';
                    $logUrl = route('quotations.logs', $quotation->id);
                    $html .= '  <button type="button" class="dropdown-item btn-quotation-log" data-url="' . e($logUrl) . '">' .
                        '<i class="bi bi-clock-history mr-2"></i> Quotation Log</button>';
                }

                if (! $quotation || $quotation->status !== 'published') {
                    $html .= '  <button class="dropdown-item text-danger trash-lead" data-url="' . e($trashUrl) . '"><i class="bi bi-trash mr-2"></i> Trash Lead</button>';
                }
                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }

    public function trash($claimId)
    {
        $claim = LeadClaim::with('lead')->findOrFail($claimId);

        request()->validate([
            'note' => 'required|string',
        ]);

        DB::transaction(function () use ($claim) {
            $lead = $claim->lead;

            $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
            if (! $lead->first_sales_id && $firstClaim) {
                $lead->first_sales_id = $firstClaim->sales_id;
            }

            $lead->update(['status_id' => LeadStatus::TRASH_WARM]);

            $claim->update([
                'released_at' => now(),
                'trash_note'  => request('note'),
            ]);

            LeadStatusLog::create([
                'lead_id'   => $lead->id,
                'status_id' => LeadStatus::TRASH_WARM,
            ]);
        });

        return $this->setJsonResponse('Lead moved to trash');
    }

    public function createQuotation(Request $request, $claimId)
    {
        $claim = LeadClaim::with([
            'lead.quotation.items',
            'lead.quotation.paymentTerms',
            'lead.segment',
            'lead'
        ])->findOrFail($claimId);

        $segmentName = strtolower($claim->lead->segment->name ?? '');
        $priceField = match ($segmentName) {
            'fob' => 'fob_price',
            'bdi' => 'bdi_price',
            'government' => 'government_price',
            'corporate'  => 'corporate_price',
            default      => 'personal_price',
        };

        $products = Product::all()->map(function ($product) use ($priceField) {
            $product->price = $product->{$priceField};
            return $product;
        });

        $segments = LeadSegment::all();

        $quotation = $claim->lead->quotation;

        $userRole = $request->user()->role?->code;
        $isEditable = true;
        if ($quotation) {
            $bmApproved  = $quotation->reviews()->where('role', 'BM')->where('decision', 'approve')->exists();
            // $dirApproved = $quotation->reviews()->where('role', 'SD')->where('decision', 'approve')->exists();
            $allApproved = $bmApproved;

            $hasPayment = PaymentConfirmation::whereHas('proforma', function ($q) use ($quotation) {
                $q->where('quotation_id', $quotation->id);
            })->exists();

            if (! $allApproved) {
                $isEditable = $userRole === 'sales';
            } else {
                $isEditable = in_array($userRole, ['branch_manager']) && ! $hasPayment;
            }
        }

        $rejection = null;
        $approval  = null;
        if ($quotation) {
            $rejection = $quotation->reviews()
                ->where('decision', 'reject')
                ->latest('decided_at')
                ->first();

            $approval = $quotation->reviews()
                ->where('decision', 'approve')
                ->latest('decided_at')
                ->first();
        }

        return $this->render('pages.leads.warm.generate-quotation', [
            'claim'         => $claim,
            'products'      => $products,
            'quotation'     => $quotation,
            'isEditable'    => $isEditable,
            'priceField'    => $priceField,
            'segmentName'   => $segmentName,
            'segments'      => $segments,
            'defaultSegment'=> $claim->lead->segment->name ?? '',
            'rejection'     => $rejection,
            'approval'      => $approval,
        ]);
    }

    public function storeQuotation(Request $request, $claimId)
    {
        try {
            $claim = LeadClaim::with('lead.quotation.proformas.paymentConfirmation')->findOrFail($claimId);

            $quotation = $claim->lead->quotation;
            $userRole   = $request->user()->role?->code;
            $canEdit    = true;

            if ($quotation) {
                $bmApproved  = $quotation->reviews()->where('role', 'BM')->where('decision', 'approve')->exists();
                // $dirApproved = $quotation->reviews()->where('role', 'SD')->where('decision', 'approve')->exists();
                $allApproved = $bmApproved;

                $hasPayment = PaymentConfirmation::whereHas('proforma', function ($q) use ($quotation) {
                    $q->where('quotation_id', $quotation->id);
                })->exists();

                if (! $allApproved) {
                    $canEdit = $userRole === 'sales';
                } else {
                    $canEdit = in_array($userRole, ['branch_manager']) && ! $hasPayment;
                }
            } else {
                $canEdit = $userRole === 'sales';
            }

            abort_unless($canEdit, 403);

            // 1. Define rules
            $rules = [
                'product_id.*'      => 'nullable',
                'description.*'     => 'required|string',
                'qty.*'             => 'required|numeric|min:1',
                'unit_price.*'      => 'required|numeric',
                'discount_pct.*'    => 'nullable|numeric|min:0|max:100',
                'tax_pct'           => 'required|numeric',
                'term_percentage.*' => 'required|numeric|min:0|max:100',
                'term_description.*' => 'nullable|string',
                'payment_type'      => 'required|in:booking_fee,down_payment',
                'booking_fee'       => 'nullable|numeric|min:0',
            ];

            // 2. Custom messages
            $messages = [
                'description.*.required'   => 'Each line needs a description.',
                'qty.*.required'           => 'Please specify quantity for every item.',
                'qty.*.min'                => 'Quantity must be at least 1.',
                'unit_price.*.required'    => 'Unit price is required for each item.',
                'discount_pct.*.min'       => 'Discount cannot be less than 0%.',
                'discount_pct.*.max'       => 'Discount cannot exceed 100%.',
                'tax_pct.required'         => 'Tax percentage is required.',
                'tax_pct.numeric'          => 'Tax percentage must be a number.',
                'term_percentage.*.required'=> 'Each payment term needs a percentage.',
                'term_percentage.*.min'    => 'Term percentage cannot be negative.',
                'term_percentage.*.max'    => 'Term percentage cannot exceed 100%.',
                'payment_type.required'    => 'Please choose a payment type.',
                'payment_type.in'          => 'Invalid payment type selected.',
                'booking_fee.min'          => 'Booking fee cannot be negative.',
            ];
            
            // 3. Run validator
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                // grab the first error message
                $firstError = $validator->errors()->first();
                // include all errors in the payload if you want
                return $this->setJsonResponse(
                    $firstError,
                    ['errors' => $validator->errors()->toArray()],
                    422
                );
            }
            
            $totalTerm = collect($request->term_percentage)->sum();
            
            if (round($totalTerm, 2) !== 100.00) {
                return $this->setJsonResponse('Total Term of Payment must be exactly 100%', [], 422);
            }

            DB::beginTransaction();

            $items = [];
            $subtotal = 0;

            foreach ($request->qty as $idx => $qty) {
                $pidRaw = $request->product_id[$idx] ?? null;
                $pid = ($pidRaw === 'add_on' || $pidRaw === '' || $pidRaw === null) ? null : $pidRaw;
                $price = $request->unit_price[$idx] ?? 0;
                $discount = $request->discount_pct[$idx] ?? 0;
                $description = $request->description[$idx] ?? '';
                $line = ($price - ($price * $discount / 100)) * $qty;
                $subtotal += $line;

                $items[] = [
                    'product_id' => $pid,
                    'qty' => $qty,
                    'description' => $description,
                    'unit_price' => $price,
                    'discount_pct' => $discount,
                    'line_total' => $line,
                ];
            }

            $taxTotal = $subtotal * ($request->tax_pct / 100);
            $grandTotal = $subtotal + $taxTotal;
            $bookingFee = $request->payment_type === 'booking_fee'
                ? ($request->booking_fee ?? 0)
                : null;
                
            if ($bookingFee > $grandTotal) {
                return $this->setJsonResponse('Booking fee cannot be greater than Grand Total.', [], 422);
            }

            // Check for existing quotation for this lead
            $existingQuotation = Quotation::where('lead_id', $claim->lead_id)
                ->orderByDesc('id')
                ->first();

            $hadApproval = $existingQuotation
                ? $existingQuotation->reviews()->where('decision', 'approve')->exists()
                : false;

            if ($existingQuotation) {
                // Clean up previous data
                $existingQuotation->items()->delete();
                $existingQuotation->paymentTerms()->delete();

                // Reset approvals from Branch Manager and Sales Director if any
                if ($hadApproval) {
                    $existingQuotation->reviews()->delete();
                }

                $existingQuotation->update([
                    'status'      => 'review',
                    'subtotal'    => $subtotal,
                    'tax_pct'     => $request->tax_pct,
                    'tax_total'   => $taxTotal,
                    'grand_total' => $grandTotal,
                    'booking_fee' => $bookingFee,
                    'expiry_date' => now()->addDays(15),
                ]);

                $quotation = $existingQuotation;
            } else {
                // Generate quotation number
                $lastQuotation = Quotation::whereNotNull('quotation_no')
                    ->where('quotation_no', 'like', 'QT_DEAL_%')
                    ->orderByDesc('id')
                    ->first();

                $nextNumber = $lastQuotation && preg_match('/QT_DEAL_(\d+)/', $lastQuotation->quotation_no, $m)
                    ? ((int) $m[1]) + 1
                    : 1;

                $quotationNo = 'QT_DEAL_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $quotation = Quotation::create([
                    'quotation_no' => $quotationNo,
                    'lead_id' => $claim->lead_id,
                    'status' => 'review',
                    'subtotal' => $subtotal,
                    'tax_pct' => $request->tax_pct,
                    'tax_total' => $taxTotal,
                    'grand_total' => $grandTotal,
                    'booking_fee' => $bookingFee,
                    'created_by' => $request->user()->id,
                    'expiry_date' => now()->addDays(15),
                ]);
            }

            // Save items
            foreach ($items as $item) {
                QuotationItems::create(array_merge(['quotation_id' => $quotation->id], $item));
            }

            // Save payment terms
           foreach ($request->term_percentage as $idx => $pct) {
                if ($pct !== null) {
                    QuotationPaymentTerm::create([
                        'quotation_id' => $quotation->id,
                        'term_no'      => $idx + 1,
                        'percentage'   => $pct,
                        'description'  => $request->term_description[$idx] ?? null,
                    ]);
                }
            }

            $action = $existingQuotation ? 'update' : 'generate';
            QuotationLog::create([
                'quotation_id' => $quotation->id,
                'action'       => $action,
                'user_id'      => $request->user()->id,
                'logged_at'    => now(),
            ]);

            DB::commit();

            return $this->setJsonResponse('Quotation saved successfully', [
                'redirect_url' => route('leads.my')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->setJsonResponse('Failed to save quotation', [], 500, $e);
        }
    }
}
