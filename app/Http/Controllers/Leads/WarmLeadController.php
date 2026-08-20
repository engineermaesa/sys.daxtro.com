<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Services\AutoTrashService;
use App\Services\MyLeadQueryService;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog, LeadSegment};
use App\Models\Orders\{Quotation, QuotationItems, QuotationPaymentTerm, PaymentConfirmation, QuotationLog, TempQuotation, TempQuotationItem, TempQuotationPaymentTerm};
use App\Models\Masters\Product;
use App\Models\User;
use App\Notifications\Leads\LeadTrashedNotification;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarmLeadController extends Controller
{
    public function myWarmList(Request $request)
    {
        AutoTrashService::triggerIfNeeded();

        $perPage  = $request->get('per_page', 10);

        $claimsQuery = MyLeadQueryService::baseClaimsQuery($request, LeadStatus::WARM, [
            'lead.branch',
            'lead.quotation',
            'lead.tempQuotation',
            'lead.segment',
            'lead.source',
            'lead.industry',
            'lead.region.regional',
            'sales'
        ]);

        $paginated = $claimsQuery
            ->orderByDesc('lead_claims.claimed_at')
            ->orderByDesc('lead_claims.id')
            ->paginate($perPage);

        $cityIds = $paginated->getCollection()
            ->pluck('lead.factory_city_id')
            ->filter()
            ->unique()
            ->values();

        $cities = collect();
        $regionals = collect();
        $provinces = collect();

        if ($cityIds->isNotEmpty()) {
            $cities = DB::table('ref_regions')
                ->whereIn('id', $cityIds)
                ->select('id', 'name', 'regional_id', 'province_id')
                ->get()
                ->keyBy('id');

            $regionalIds = $cities->pluck('regional_id')->filter()->unique()->values();
            $provinceIds = $cities->pluck('province_id')->filter()->unique()->values();

            if ($regionalIds->isNotEmpty()) {
                $regionals = DB::table('ref_regionals')
                    ->whereIn('id', $regionalIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }

            if ($provinceIds->isNotEmpty()) {
                $provinces = DB::table('ref_provinces')
                    ->whereIn('id', $provinceIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }
        }

        $paginated->getCollection()->transform(function ($row) use ($cities, $regionals, $provinces){

            $lead      = $row->lead;
            $quotation = $lead->quotation;
            $city = $lead ? $cities->get($lead->factory_city_id) : null;

            if ($lead) {
                $lead->alternate_location = $city ? [
                    'region_id' => $city->id,
                    'region_name' => $city->name,
                    'regional_id' => $city->regional_id,
                    'regional_name' => optional($regionals->get($city->regional_id))->name,
                    'province_id' => $city->province_id,
                    'province_name' => optional($provinces->get($city->province_id))->name,
                ] : null;
            }


            $row->name          = $lead->name ?? '-';
            $row->sales_name    = $row->sales->name ?? '-';
            $row->phone         = $lead->phone ?? '-';
            $row->source        = $lead->source->name ?? '-';
            $row->needs         = $lead->needs ?? '-';
            $row->segment_name  = $lead->segment->name ?? '-';
            $row->city_name     = $lead->region->name ?? 'All Regions';
            $row->regional_name = $lead->region->regional->name ?? 'All Regions';
            $row->industry      = $lead->industry->name ?? ($lead->other_industry ?? '-');

            $row->meeting_status = $this->warmMeetingStatus($quotation, $lead->tempQuotation);
            $row->actions        = $this->warmActions($row);

            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
        ]);
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

        $lead = $claim->lead;
        if ($lead->branch_id) {
            User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                ->where('branch_id', $lead->branch_id)
                ->get()
                ->each->notify(new LeadTrashedNotification(
                    lead: $lead,
                    sales: request()->user(),
                    trashNote: request('note'),
                    isAutoTrash: false
                ));
        }

        return $this->setJsonResponse('Lead moved to trash');
    }

    public function createQuotation(Request $request, $claimId)
    {
        $claim = LeadClaim::with([
            'lead.quotation.items',
            'lead.quotation.paymentTerms',
            'lead.tempQuotation.items',
            'lead.tempQuotation.paymentTerms',
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

        // Draft flow applies when the lead has never had an official quotation
        // yet, OR its official quotation was rejected (needs a redraft before
        // resubmission). Every other official-quotation status keeps the
        // pre-existing behavior below untouched.
        $needsDraftFlow = ! $quotation || $quotation->status === 'rejected';

        if (! $needsDraftFlow) {
            $isEditable = true;

            $bmApproved = $quotation->reviews()->where('role', 'BM')->where('decision', 'approve')->exists();
            $financeApproved = $quotation->reviews()->where('role', 'FIN')->where('decision', 'approve')->exists();
            $allApproved = $bmApproved && $financeApproved; // Both must approve

            $hasPayment = PaymentConfirmation::whereHas('proforma', function ($q) use ($quotation) {
                $q->where('quotation_id', $quotation->id);
            })->exists();

            // Updated editability logic for BM → Finance workflow
            if ($quotation->status === 'published') {
                // Published quotations can only be edited by BM if no payments exist
                $isEditable = in_array($userRole, ['branch_manager']) && !$hasPayment;
            } else {
                // Draft, review, or pending_finance can be edited by sales
                $editableStatuses = ['draft', 'review', 'pending_finance'];
                $isEditable = in_array($userRole, ['sales', 'branch_manager']) && in_array($quotation->status, $editableStatuses);
            }

            // Allow sales to re-edit when quotation was rejected by BM/Finance
            if ($quotation->status === 'rejected' && $userRole === 'sales') {
                $isEditable = true;
            }

            $rejection = $quotation->reviews()
                ->where('decision', 'reject')
                ->latest('decided_at')
                ->first();

            $approval = $quotation->reviews()
                ->where('decision', 'approve')
                ->latest('decided_at')
                ->first();

            if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'claim' => $claim,
                    'products' => $products,
                    'quotation' => $quotation,
                    'draft' => null,
                    'isEditable' => $isEditable,
                    'isDraftMode' => false,
                    'priceField' => $priceField,
                    'segmentName' => $segmentName,
                    'segments' => $segments,
                    'defaultSegment' => $claim->lead->segment->name ?? '',
                    'rejection' => $rejection,
                    'approval' => $approval,
                ]);
            }

            return $this->render('pages.leads.warm.generate-quotation', [
                'claim'         => $claim,
                'products'      => $products,
                'quotation'     => $quotation,
                'draft'         => null,
                'isEditable'    => $isEditable,
                'isDraftMode'   => false,
                'priceField'    => $priceField,
                'segmentName'   => $segmentName,
                'segments'      => $segments,
                'defaultSegment' => $claim->lead->segment->name ?? '',
                'rejection'     => $rejection,
                'approval'      => $approval,
            ]);
        }

        // ---- Draft flow: no official quotation yet, or the official quotation was rejected ----
        $draft = $claim->lead->tempQuotation;
        if (! $draft) {
            $draft = DB::transaction(function () use ($claim, $quotation, $request) {
                $seed = $quotation ? [
                    'subtotal' => $quotation->subtotal,
                    'tax_pct' => $quotation->tax_pct,
                    'tax_total' => $quotation->tax_total,
                    'total_discount' => $quotation->total_discount,
                    'grand_total' => $quotation->grand_total,
                    'booking_fee' => $quotation->booking_fee,
                ] : [
                    'subtotal' => 0,
                    'tax_pct' => 11,
                    'tax_total' => 0,
                    'total_discount' => 0,
                    'grand_total' => 0,
                    'booking_fee' => null,
                ];

                $newDraft = TempQuotation::create(array_merge($seed, [
                    'lead_id' => $claim->lead_id,
                    'status' => 'draft',
                    'created_by' => $request->user()->id,
                ]));

                // If the lead had a rejected quotation, seed the draft with its
                // current items/payment terms as the starting point for revision.
                if ($quotation) {
                    $quotation->load('items', 'paymentTerms');

                    $savedTempItems = [];
                    foreach ($quotation->items as $item) {
                        $savedTempItems[$item->id] = TempQuotationItem::create([
                            'temp_quotation_id' => $newDraft->id,
                            'product_id' => $item->product_id,
                            'qty' => $item->qty,
                            'description' => $item->description,
                            'unit_price' => $item->unit_price,
                            'discount_pct' => $item->discount_pct,
                            'line_total' => $item->line_total,
                            'is_visible_pdf' => $item->is_visible_pdf,
                        ]);
                    }
                    foreach ($quotation->items as $item) {
                        if ($item->merge_into_item_id && isset($savedTempItems[$item->merge_into_item_id], $savedTempItems[$item->id])) {
                            $savedTempItems[$item->id]->update([
                                'merge_into_item_id' => $savedTempItems[$item->merge_into_item_id]->id,
                            ]);
                        }
                    }
                    foreach ($quotation->paymentTerms as $term) {
                        TempQuotationPaymentTerm::create([
                            'temp_quotation_id' => $newDraft->id,
                            'term_no' => $term->term_no,
                            'percentage' => $term->percentage,
                            'description' => $term->description,
                        ]);
                    }
                }

                QuotationLog::create([
                    'temp_quotation_id' => $newDraft->id,
                    'quotation_id' => null,
                    'action' => 'draft_created',
                    'user_id' => $request->user()->id,
                    'logged_at' => now(),
                ]);

                return $newDraft;
            });
            $draft->load('items', 'paymentTerms');
        }

        $isEditable = in_array($userRole, ['sales', 'branch_manager']);

        // The (rejected) official quotation, if any, is still passed through
        // for context banners (rejection reason) — form prefill uses $draft.
        $rejection = $quotation ? $quotation->reviews()
            ->where('decision', 'reject')
            ->latest('decided_at')
            ->first() : null;

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'claim' => $claim,
                'products' => $products,
                'quotation' => $quotation,
                'draft' => $draft,
                'isEditable' => $isEditable,
                'isDraftMode' => true,
                'priceField' => $priceField,
                'segmentName' => $segmentName,
                'segments' => $segments,
                'defaultSegment' => $claim->lead->segment->name ?? '',
                'rejection' => $rejection,
                'approval' => null,
            ]);
        }

        return $this->render('pages.leads.warm.generate-quotation', [
            'claim'         => $claim,
            'products'      => $products,
            'quotation'     => $quotation,
            'draft'         => $draft,
            'isEditable'    => $isEditable,
            'isDraftMode'   => true,
            'priceField'    => $priceField,
            'segmentName'   => $segmentName,
            'segments'      => $segments,
            'defaultSegment' => $claim->lead->segment->name ?? '',
            'rejection'     => $rejection,
            'approval'      => null,
        ]);
    }

    public function storeQuotation(Request $request, $claimId)
    {
        $claim = LeadClaim::with('lead.quotation.proformas.paymentConfirmation')->findOrFail($claimId);
        $quotation = $claim->lead->quotation;
        $needsDraftFlow = ! $quotation || $quotation->status === 'rejected';

        if ($needsDraftFlow) {
            return $this->storeQuotationDraft($request, $claim);
        }

        try {
            $userRole   = $request->user()->role?->code;
            $canEdit    = true;
            if ($quotation) {
                $bmApproved = $quotation->reviews()->where('role', 'BM')->where('decision', 'approve')->exists();
                $financeApproved = $quotation->reviews()->where('role', 'finance')->where('decision', 'approve')->exists();
                $allApproved = $bmApproved && $financeApproved; // Both must approve

                $hasPayment = PaymentConfirmation::whereHas('proforma', function ($q) use ($quotation) {
                    $q->where('quotation_id', $quotation->id);
                })->exists();

                if ($quotation->status === 'published') {
                    $canEdit = in_array($userRole, ['branch_manager']) && !$hasPayment;
                } else {
                    $editableStatuses = ['draft', 'review', 'pending_finance'];
                    $canEdit = in_array($userRole, ['sales', 'branch_manager']) && in_array($quotation->status, $editableStatuses);
                }
            } else {
                $canEdit = in_array($userRole, ['sales', 'branch_manager']);
            }

            // Allow sales to save edits when quotation was rejected
            if ($quotation && $quotation->status === 'rejected' && $userRole === 'sales') {
                $canEdit = true;
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
                'total_discount'    => 'nullable|numeric|min:0',
                'term_percentage.*' => 'required|numeric|min:0|max:100',
                'term_description.*' => 'nullable|string',
                'payment_type'      => 'required|in:booking_fee,down_payment',
                'booking_fee'       => 'nullable|numeric|min:0',
                'is_visible_pdf.*'  => 'nullable|boolean',
                'merge_into_item_id.*' => 'nullable',
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
                'term_percentage.*.required' => 'Each payment term needs a percentage.',
                'term_percentage.*.min'    => 'Term percentage cannot be negative.',
                'term_percentage.*.max'    => 'Term percentage cannot exceed 100%.',
                'payment_type.required'    => 'Please choose a payment type.',
                'payment_type.in'          => 'Invalid payment type selected.',
                'booking_fee.min'          => 'Booking fee cannot be negative.',
                'is_visible_pdf.*.boolean' => 'is_visible_pdf must be true or false.',
                'merge_into_item_id.*.exists' => 'Selected merge item does not exist.',
            ];

            // 3. Run validator
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $firstError = $validator->errors()->first();
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
            $totalDiscount = 0;

            foreach ($request->qty as $idx => $qty) {
                $pidRaw = $request->product_id[$idx] ?? null;
                $pid = ($pidRaw === 'add_on' || $pidRaw === '' || $pidRaw === null) ? null : $pidRaw;
                $price = $request->unit_price[$idx] ?? 0;
                $discount = $request->discount_pct[$idx] ?? 0;
                $description = $request->description[$idx] ?? '';
                $line = ($price - ($price * $discount / 100)) * $qty;
                $subtotal += $line;
                $totalDiscount += ($price * $discount / 100) * $qty;

                $isVisible = isset($request->is_visible_pdf[$idx]) ?
                    (($request->is_visible_pdf[$idx] === '1') || ($request->is_visible_pdf[$idx] === 1) || ($request->is_visible_pdf[$idx] === true)) : true;

                $mergeIntoIndex = isset($request->merge_into_item_id[$idx]) && $request->merge_into_item_id[$idx] !== '' ?
                    (int)$request->merge_into_item_id[$idx] : null;

                $items[] = [
                    'product_id' => $pid,
                    'qty' => $qty,
                    'description' => $description,
                    'unit_price' => $price,
                    'discount_pct' => $discount,
                    'line_total' => $line,
                    'is_visible_pdf' => $isVisible,
                    'merge_into_index' => $mergeIntoIndex,
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

                // Clear previous reviews (approve/reject) so new review cycle can start
                $existingQuotation->reviews()->delete();

                $existingQuotation->update([
                    'status'      => 'review',
                    'subtotal'    => $subtotal,
                    'tax_pct'     => $request->tax_pct,
                    'tax_total'   => $taxTotal,
                    'total_discount' => $totalDiscount,
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
                    'total_discount' => $totalDiscount,
                    'grand_total' => $grandTotal,
                    'booking_fee' => $bookingFee,
                    'created_by' => $request->user()->id,
                    'expiry_date' => now()->addDays(15),
                ]);
            }

            $savedItems = [];
            foreach ($items as $index => $itemData) {
                $mergeIndex = $itemData['merge_into_index'] ?? null;
                unset($itemData['merge_into_index']);

                $savedItem = QuotationItems::create(array_merge(['quotation_id' => $quotation->id], $itemData));
                $savedItems[$index] = $savedItem;
            }

            // Second pass: update merge relationships
            foreach ($items as $index => $itemData) {
                $mergeIndex = $itemData['merge_into_index'] ?? null;
                if ($mergeIndex !== null && isset($savedItems[$mergeIndex]) && isset($savedItems[$index])) {
                    $savedItems[$index]->update(['merge_into_item_id' => $savedItems[$mergeIndex]->id]);
                }
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

            // Notify all BMs in this branch that a quotation is waiting for review
            $sales = $request->user();
            \App\Models\User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                ->where('branch_id', $quotation->lead->branch_id)
                ->each(fn($bm) => $bm->notify(
                    new \App\Notifications\Orders\QuotationSubmittedNotification($quotation->load('lead'), $sales)
                ));

            return $this->setJsonResponse('Quotation saved successfully', [
                'redirect_url' => route('leads.my')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->setJsonResponse('Failed to save quotation', [], 500, $e);
        }
    }

    /**
     * Save progress into the active temp_quotation draft (no official
     * Quotation exists yet, or the existing one was rejected). Never
     * generates a quotation_no, never touches Lead/Quotation state.
     */
    protected function storeQuotationDraft(Request $request, LeadClaim $claim)
    {
        try {
            $userRole = $request->user()->role?->code;
            abort_unless(in_array($userRole, ['sales', 'branch_manager']), 403);

            $rules = [
                'product_id.*'      => 'nullable',
                'description.*'     => 'required|string',
                'qty.*'             => 'required|numeric|min:1',
                'unit_price.*'      => 'required|numeric',
                'discount_pct.*'    => 'nullable|numeric|min:0|max:100',
                'tax_pct'           => 'required|numeric',
                'total_discount'    => 'nullable|numeric|min:0',
                'term_percentage.*' => 'required|numeric|min:0|max:100',
                'term_description.*' => 'nullable|string',
                'payment_type'      => 'required|in:booking_fee,down_payment',
                'booking_fee'       => 'nullable|numeric|min:0',
                'is_visible_pdf.*'  => 'nullable|boolean',
                'merge_into_item_id.*' => 'nullable',
            ];

            $messages = [
                'description.*.required'   => 'Each line needs a description.',
                'qty.*.required'           => 'Please specify quantity for every item.',
                'qty.*.min'                => 'Quantity must be at least 1.',
                'unit_price.*.required'    => 'Unit price is required for each item.',
                'discount_pct.*.min'       => 'Discount cannot be less than 0%.',
                'discount_pct.*.max'       => 'Discount cannot exceed 100%.',
                'tax_pct.required'         => 'Tax percentage is required.',
                'tax_pct.numeric'          => 'Tax percentage must be a number.',
                'term_percentage.*.required' => 'Each payment term needs a percentage.',
                'term_percentage.*.min'    => 'Term percentage cannot be negative.',
                'term_percentage.*.max'    => 'Term percentage cannot exceed 100%.',
                'payment_type.required'    => 'Please choose a payment type.',
                'payment_type.in'          => 'Invalid payment type selected.',
                'booking_fee.min'          => 'Booking fee cannot be negative.',
                'is_visible_pdf.*.boolean' => 'is_visible_pdf must be true or false.',
                'merge_into_item_id.*.exists' => 'Selected merge item does not exist.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $firstError = $validator->errors()->first();
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
            $totalDiscount = 0;

            foreach ($request->qty as $idx => $qty) {
                $pidRaw = $request->product_id[$idx] ?? null;
                $pid = ($pidRaw === 'add_on' || $pidRaw === '' || $pidRaw === null) ? null : $pidRaw;
                $price = $request->unit_price[$idx] ?? 0;
                $discount = $request->discount_pct[$idx] ?? 0;
                $description = $request->description[$idx] ?? '';
                $line = ($price - ($price * $discount / 100)) * $qty;
                $subtotal += $line;
                $totalDiscount += ($price * $discount / 100) * $qty;

                $isVisible = isset($request->is_visible_pdf[$idx]) ?
                    (($request->is_visible_pdf[$idx] === '1') || ($request->is_visible_pdf[$idx] === 1) || ($request->is_visible_pdf[$idx] === true)) : true;

                $mergeIntoIndex = isset($request->merge_into_item_id[$idx]) && $request->merge_into_item_id[$idx] !== '' ?
                    (int)$request->merge_into_item_id[$idx] : null;

                $items[] = [
                    'product_id' => $pid,
                    'qty' => $qty,
                    'description' => $description,
                    'unit_price' => $price,
                    'discount_pct' => $discount,
                    'line_total' => $line,
                    'is_visible_pdf' => $isVisible,
                    'merge_into_index' => $mergeIntoIndex,
                ];
            }

            $taxTotal = $subtotal * ($request->tax_pct / 100);
            $grandTotal = $subtotal + $taxTotal;
            $bookingFee = $request->payment_type === 'booking_fee'
                ? ($request->booking_fee ?? 0)
                : null;

            if ($bookingFee > $grandTotal) {
                DB::rollBack();
                return $this->setJsonResponse('Booking fee cannot be greater than Grand Total.', [], 422);
            }

            $draft = TempQuotation::where('lead_id', $claim->lead_id)
                ->where('status', 'draft')
                ->firstOrFail();

            $draft->items()->delete();
            $draft->paymentTerms()->delete();

            $draft->update([
                'subtotal' => $subtotal,
                'tax_pct' => $request->tax_pct,
                'tax_total' => $taxTotal,
                'total_discount' => $totalDiscount,
                'grand_total' => $grandTotal,
                'booking_fee' => $bookingFee,
            ]);

            $savedItems = [];
            foreach ($items as $index => $itemData) {
                $mergeIndex = $itemData['merge_into_index'] ?? null;
                unset($itemData['merge_into_index']);

                $savedItem = TempQuotationItem::create(array_merge(['temp_quotation_id' => $draft->id], $itemData));
                $savedItems[$index] = $savedItem;
            }

            foreach ($items as $index => $itemData) {
                $mergeIndex = $itemData['merge_into_index'] ?? null;
                if ($mergeIndex !== null && isset($savedItems[$mergeIndex]) && isset($savedItems[$index])) {
                    $savedItems[$index]->update(['merge_into_item_id' => $savedItems[$mergeIndex]->id]);
                }
            }

            foreach ($request->term_percentage as $idx => $pct) {
                if ($pct !== null) {
                    TempQuotationPaymentTerm::create([
                        'temp_quotation_id' => $draft->id,
                        'term_no'      => $idx + 1,
                        'percentage'   => $pct,
                        'description'  => $request->term_description[$idx] ?? null,
                    ]);
                }
            }

            // Draft edits are not individually logged — only draft_created and
            // the eventual submitted update exist (see finalSubmitQuotation).
            DB::commit();

            return $this->setJsonResponse('Draft tersimpan', [
                'redirect_url' => route('leads.my.warm.quotation.create', $claim->id)
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->setJsonResponse('Failed to save quotation', [], 500, $e);
        }
    }

    public function finalSubmitQuotation(Request $request, $claimId)
    {
        try {
            $claim = LeadClaim::with('lead.quotation')->findOrFail($claimId);
            $userRole = $request->user()->role?->code;
            abort_unless(in_array($userRole, ['sales', 'branch_manager']), 403);

            $existingQuotation = $claim->lead->quotation;

            // Final submit is only valid when there's no official quotation at
            // all, or the official quotation was rejected (the only two states
            // that can have an active draft, per the guiding rule above).
            abort_if($existingQuotation && $existingQuotation->status !== 'rejected', 409, 'This lead already has an active official quotation.');

            DB::beginTransaction();

            $draft = TempQuotation::where('lead_id', $claim->lead_id)
                ->where('status', 'draft')
                ->lockForUpdate()
                ->firstOrFail();

            $draft->load('items', 'paymentTerms');

            if ($existingQuotation) {
                // Redraft of a rejected quotation: update the same official
                // record in place, keep the same quotation_no.
                $existingQuotation->items()->delete();
                $existingQuotation->paymentTerms()->delete();
                $existingQuotation->reviews()->delete();

                $existingQuotation->update([
                    'status' => 'review',
                    'subtotal' => $draft->subtotal,
                    'tax_pct' => $draft->tax_pct,
                    'tax_total' => $draft->tax_total,
                    'total_discount' => $draft->total_discount,
                    'grand_total' => $draft->grand_total,
                    'booking_fee' => $draft->booking_fee,
                    'expiry_date' => now()->addDays(15),
                ]);

                $quotation = $existingQuotation;
            } else {
                $quotation = Quotation::create([
                    'quotation_no' => $this->generateNextQuotationNo(),
                    'lead_id' => $claim->lead_id,
                    'status' => 'review',
                    'subtotal' => $draft->subtotal,
                    'tax_pct' => $draft->tax_pct,
                    'tax_total' => $draft->tax_total,
                    'total_discount' => $draft->total_discount,
                    'grand_total' => $draft->grand_total,
                    'booking_fee' => $draft->booking_fee,
                    'created_by' => $request->user()->id,
                    'expiry_date' => now()->addDays(15),
                ]);
            }

            $savedItems = [];
            foreach ($draft->items as $tempItem) {
                $savedItems[$tempItem->id] = QuotationItems::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $tempItem->product_id,
                    'qty' => $tempItem->qty,
                    'description' => $tempItem->description,
                    'unit_price' => $tempItem->unit_price,
                    'discount_pct' => $tempItem->discount_pct,
                    'line_total' => $tempItem->line_total,
                    'is_visible_pdf' => $tempItem->is_visible_pdf,
                ]);
            }
            foreach ($draft->items as $tempItem) {
                if ($tempItem->merge_into_item_id && isset($savedItems[$tempItem->merge_into_item_id], $savedItems[$tempItem->id])) {
                    $savedItems[$tempItem->id]->update([
                        'merge_into_item_id' => $savedItems[$tempItem->merge_into_item_id]->id,
                    ]);
                }
            }
            foreach ($draft->paymentTerms as $term) {
                QuotationPaymentTerm::create([
                    'quotation_id' => $quotation->id,
                    'term_no' => $term->term_no,
                    'percentage' => $term->percentage,
                    'description' => $term->description,
                ]);
            }

            $draft->update(['status' => 'submitted']);

            $log = QuotationLog::where('temp_quotation_id', $draft->id)
                ->where('action', 'draft_created')
                ->latest('logged_at')
                ->first();

            if ($log) {
                $log->update([
                    'quotation_id' => $quotation->id,
                    'action' => 'submitted',
                    'logged_at' => now(),
                ]);
            } else {
                QuotationLog::create([
                    'quotation_id' => $quotation->id,
                    'temp_quotation_id' => $draft->id,
                    'action' => 'submitted',
                    'user_id' => $request->user()->id,
                    'logged_at' => now(),
                ]);
            }

            DB::commit();

            $sales = $request->user();
            User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                ->where('branch_id', $quotation->lead->branch_id)
                ->each(fn($bm) => $bm->notify(
                    new \App\Notifications\Orders\QuotationSubmittedNotification($quotation->load('lead'), $sales)
                ));

            return $this->setJsonResponse('Quotation submitted successfully', [
                'redirect_url' => route('leads.my')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->setJsonResponse('Failed to submit quotation', [], 500, $e);
        }
    }

    /**
     * Generate the next sequential QT_DEAL_### quotation number.
     * Extracted from the original inline logic in storeQuotation().
     */
    private function generateNextQuotationNo(): string
    {
        $lastQuotation = Quotation::whereNotNull('quotation_no')
            ->where('quotation_no', 'like', 'QT_DEAL_%')
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastQuotation && preg_match('/QT_DEAL_(\d+)/', $lastQuotation->quotation_no, $m)
            ? ((int) $m[1]) + 1
            : 1;

        return 'QT_DEAL_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    protected function warmMeetingStatus($quotation, $draft = null)
    {
        if ($draft) {
            return '<span class="status-grey">Draft</span>';
        }

        if (! $quotation) {
            return '<span class="status-grey">No Quotation</span>';
        }

        $status = $quotation->status;
        if ($status === 'draft') {
            return '<span class="status-grey">Draft</span>';
        }

        if (in_array($status, ['review', 'pending_finance'])) {
            $bmApproved = $quotation->reviews()->where('role', 'BM')->where('decision', 'approve')->exists();
            $financeApproved = $quotation->reviews()->where('role', 'finance')->where('decision', 'approve')->exists();

            if (! $bmApproved) {
                return '<span class="status-waiting">Pending BM Approval</span>';
            }

            if ($bmApproved && ! $financeApproved) {
                return '<span class="status-waiting">Pending Finance Approval</span>';
            }

            return '<span class="status-waiting">Pending Approval</span>';
        }

        if ($status === 'published') {
            return '<span class="status-finish">Quotation Published</span>';
        }

        if ($status === 'rejected') {
            $review = $quotation->reviews()->where('decision', 'reject')->latest('decided_at')->first();
            $role = $review?->role;

            if ($role) {
                $by = strtolower($role) === 'bm' ? 'BM' : (strtolower($role) === 'finance' ? 'Finance' : ucfirst($role));
            } else {
                $by = 'BM/Finance';
            }

            return '<span class="status-expired">Rejected by ' . e($by) . '</span>';
        }

        return '<span class="bg-light text-dark">' . ucfirst($status) . '</span>';
    }

    protected function warmActions($row)
    {
        $quotation = $row->lead->quotation;
        $draft     = $row->lead->tempQuotation;
        $viewUrl   = route('leads.my.warm.manage', $row->lead->id);
        $createUrl = route('leads.my.warm.quotation.create', $row->id);
        $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
        $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;
        $trashUrl   = route('leads.my.warm.trash', $row->id);

        $btnId = 'warmActionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"'
            . ' type="button" id="' . $btnId . '"'
            . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right text-[#1E1E1E]!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2!" href="' . e($viewUrl) . '">'
            . '
            ' . view('components.icon.detail')->render() . ' 
            View Lead</a>';
        $activityUrl = route('leads.activity.logs', $row->lead->id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log cursor-pointer flex! items-center! gap-2!" data-url="' . e($activityUrl) . '">
        ' . view('components.icon.log')->render() . ' 
        View / Add Activity</button>';

        if ($draft) {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($createUrl) . '">'
                . '
                ' . view('components.icon.generate-quotation')->render() . '
                Edit Draft</a>';
            if ($quotation) {
                // Rejected quotation being redrafted — keep a read-only link
                // to the rejected record for context (rejection reason).
                $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                    . '
                    ' . view('components.icon.view-quotation')->render() . '
                    View Quotation</a>';
            }
        } elseif ($quotation) {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                . '
                ' . view('components.icon.view-quotation')->render() . '
                View Quotation</a>';
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($downloadUrl) . '">'
                . '
                ' . view('components.icon.download')->render() . '
                Download</a>';
            $logUrl = route('quotations.logs', $quotation->id);
            $html .= '  <button type="button" class="dropdown-item btn-quotation-log cursor-pointer flex! items-center! gap-2!" data-url="' . e($logUrl) . '">
            ' . view('components.icon.quotation-log')->render() . '
            Quotation Log</button>';
        } else {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($createUrl) . '">'
                . '
                ' . view('components.icon.generate-quotation')->render() . '
                Generate Quotation</a>';
        }

        if (! $quotation || $quotation->status !== 'published') {
            $html .= '  <button class="dropdown-item text-[#900B09]! cursor-pointer trash-lead flex! items-center! gap-2!" data-url="' . e($trashUrl) . '">
            ' . view('components.icon.trash')->render() . '
            Trash Lead</button>';
        }
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }
}
