<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Services\AutoTrashService;
use App\Services\MyLeadQueryService;
use App\Services\Orders\QuotationNumberGenerator;
use App\Services\Orders\QuotationRevisions;
use App\Services\Orders\QuotationConcurrency;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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

        $needsDraftFlow = ! $quotation || in_array($quotation->status, ['rejected', 'approved_by_bm'], true);
        if ($needsDraftFlow) {
            abort_unless(in_array($userRole, ['sales', 'branch_manager'], true), 403);
        } elseif ($quotation) {
            app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);
        }

        if (! $needsDraftFlow) {
            $isEditable = true;

            $bmApproved = $quotation->currentReviews()->where('role', 'BM')->where('decision', 'approve')->exists();
            $financeApproved = $quotation->currentReviews()->where('role', 'FIN')->where('decision', 'approve')->exists();
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

            $rejection = $quotation->currentReviews()
                ->where('decision', 'reject')
                ->latest('decided_at')
                ->first();

            $approval = $quotation->currentReviews()
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

        $draft = $claim->lead->tempQuotation;
        if ($quotation) {
            $draft = app(QuotationRevisions::class)->startRevision($quotation, $request->user()->id);
            $quotation->refresh();
        } elseif (! $draft) {
            $draft = QuotationConcurrency::transaction(function () use ($claim, $request) {
                QuotationConcurrency::lockLead($claim->lead_id);
                abort_if(Quotation::where('lead_id', $claim->lead_id)->exists(), 409, QuotationConcurrency::MESSAGE);
                $existingDraft = TempQuotation::where('lead_id', $claim->lead_id)->where('status', 'draft')->lockForUpdate()->first();
                if ($existingDraft) {
                    return $existingDraft;
                }
                $seed = [
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

                QuotationLog::create([
                    'temp_quotation_id' => $newDraft->id,
                    'quotation_id' => null,
                    'action' => 'draft_created',
                    'user_id' => $request->user()->id,
                    'logged_at' => Carbon::now('Asia/Jakarta'),
                ]);

                return $newDraft;
            });
            $draft->load('items', 'paymentTerms');
        }

        $isEditable = in_array($userRole, ['sales', 'branch_manager']);

        $rejection = $quotation ? $quotation->currentReviews()
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
        $transactionLevel = DB::transactionLevel();
        $claim = LeadClaim::with('lead.quotation')->findOrFail($claimId);
        $quotation = $claim->lead->quotation;
        $hasOpenDraft = TempQuotation::where('lead_id', $claim->lead_id)->where('status', 'draft')->exists();
        abort_if($request->filled('revision_no') && !$request->filled('draft_id') && $hasOpenDraft,
            409, 'An open revision exists. Continue that revision before editing the quotation.');
        $needsDraftFlow = ! $quotation || in_array($quotation->status, ['rejected', 'approved_by_bm'], true)
            || $hasOpenDraft || $request->filled('draft_id');

        if ($needsDraftFlow) {
            return $this->storeQuotationDraft($request, $claim);
        }

        try {
            $userRole   = $request->user()->role?->code;
            abort_unless($this->canEditQuotationDirectly($quotation, $userRole), 403);

            // 1. Define rules
            $rules = [
                'revision_no'       => 'nullable|integer|min:0',
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

            DB::beginTransaction();
            QuotationConcurrency::lockLead($claim->lead_id);

            $existingQuotation = Quotation::where('lead_id', $claim->lead_id)
                ->orderByDesc('id')->lockForUpdate()
                ->first();
            abort_unless($existingQuotation
                && $existingQuotation->id === $quotation->id
                && $existingQuotation->revision_no === (int) $request->input('revision_no', $quotation->revision_no)
                && $existingQuotation->status === $quotation->status,
                409, 'The quotation has changed. Reload it before saving your edits.');
            abort_unless($this->canEditQuotationDirectly($existingQuotation, $userRole), 403);
            abort_if(TempQuotation::where('lead_id', $claim->lead_id)->where('status', 'draft')->exists(),
                409, 'An open revision exists. Continue that revision before editing the quotation.');

            app(QuotationRevisions::class)->ensureInitialSnapshot($existingQuotation);
            $revisionNo = $existingQuotation->revision_no + 1;
            $revisionAction = $existingQuotation->status === 'published' ? 'bm_published_revision' : 'revision_in_place';
            $existingQuotation->items()->delete();
            $existingQuotation->paymentTerms()->delete();

            // Archive the decisions against the previous revision before restarting review.
            $existingQuotation->reviews()->delete();

            $existingQuotation->update([
                'revision_no' => $revisionNo,
                'revision_code' => $existingQuotation->quotation_no.'_R'.$revisionNo,
                'status' => 'review',
                'subtotal' => $subtotal,
                'tax_pct' => $request->tax_pct,
                'tax_total' => $taxTotal,
                'total_discount' => $totalDiscount,
                'grand_total' => $grandTotal,
                'booking_fee' => $bookingFee,
                'expiry_date' => Carbon::now('Asia/Jakarta')->addDays(15),
            ]);

            $quotation = $existingQuotation;

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

            $snapshot = app(QuotationRevisions::class)->snapshotCurrentRevision($quotation, $request->user()->id);
            QuotationLog::create([
                'quotation_id' => $quotation->id,
                'temp_quotation_id' => $snapshot->id,
                'action'       => $revisionAction,
                'user_id'      => $request->user()->id,
                'logged_at'    => Carbon::now('Asia/Jakarta'),
            ]);

            DB::commit();

            // Notify all BMs in this branch that a quotation is waiting for review
            $sales = $request->user();
            QuotationConcurrency::afterCommit(function () use ($quotation, $sales) {
                \App\Models\User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                    ->where('branch_id', $quotation->lead->branch_id)
                    ->each(fn($bm) => $bm->notify(
                        new \App\Notifications\Orders\QuotationSubmittedNotification($quotation->load('lead'), $sales)
                    ));
            });
            return $this->setJsonResponse('Quotation saved successfully', [
                'redirect_url' => route('leads.my')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack($transactionLevel);
            if (QuotationConcurrency::isConflict($e)) {
                return $this->setJsonResponse(QuotationConcurrency::MESSAGE, [], 409);
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->setJsonResponse($e->getMessage(), [], $e->getStatusCode());
            }
            return $this->setJsonResponse('Failed to save quotation', [], 500, $e);
        }
    }

    private function canEditQuotationDirectly(Quotation $quotation, ?string $role): bool
    {
        if ($quotation->status === 'published') {
            return $role === 'branch_manager' && ! PaymentConfirmation::whereHas('proforma',
                fn ($query) => $query->where('quotation_id', $quotation->id))->exists();
        }

        return in_array($role, ['sales', 'branch_manager'], true)
            && in_array($quotation->status, ['draft', 'review', 'pending_finance'], true);
    }

    /**
     * Save progress into the active temp_quotation draft (no official
     * Quotation exists yet, or the existing one was rejected). Never
     * generates a quotation_no, never touches Lead/Quotation state.
     */
    protected function storeQuotationDraft(Request $request, LeadClaim $claim)
    {
        $transactionLevel = DB::transactionLevel();
        try {
            $userRole = $request->user()->role?->code;
            abort_unless(in_array($userRole, ['sales', 'branch_manager']), 403);

            $rules = [
                'draft_id'         => 'nullable|integer|min:1',
                'draft_version'    => 'nullable|integer|min:0',
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

            $observedDraft = TempQuotation::where('lead_id', $claim->lead_id)->where('status', 'draft')->first();
            DB::beginTransaction();
            QuotationConcurrency::lockLead($claim->lead_id);

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

            $current = Quotation::where('lead_id', $claim->lead_id)->lockForUpdate()->first();
            if ($current) {
                abort_unless(in_array($current->status, ['rejected', 'approved_by_bm'], true), 409,
                    'This quotation is no longer available for revision. Reload the quotation.');
            }
            $draft = TempQuotation::where('lead_id', $claim->lead_id)
                ->where('status', 'draft')->lockForUpdate()->first();
            abort_unless($draft, 409, 'No open revision is available. Reload the quotation.');
            abort_if($request->filled('draft_id') && (int) $request->input('draft_id') !== $draft->id,
                409, 'This draft is no longer active. Reload the quotation.');
            QuotationConcurrency::checkDraft($draft, $observedDraft, $request);
            if ($current) {
                app(QuotationRevisions::class)->validateRevisionDraft($current, $draft);
            }

            $draft->items()->delete();
            $draft->paymentTerms()->delete();

            $draft->update([
                'subtotal' => $subtotal,
                'lock_version' => ($draft->lock_version ?? 0) + 1,
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

            return $this->setJsonResponse('Changes saved', [
                'draft_version' => $draft->lock_version,
                'redirect_url' => route('leads.my.warm.quotation.create', $claim->id)
            ]);
        } catch (\Throwable $e) {
            DB::rollBack($transactionLevel);
            if (QuotationConcurrency::isConflict($e)) {
                return $this->setJsonResponse(QuotationConcurrency::MESSAGE, [], 409);
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->setJsonResponse($e->getMessage(), [], $e->getStatusCode());
            }
            return $this->setJsonResponse('Failed to save quotation', [], 500, $e);
        }
    }

    public function finalSubmitQuotation(Request $request, $claimId)
    {
        $transactionLevel = DB::transactionLevel();
        $request->validate(['draft_id' => 'nullable|integer|min:1', 'draft_version' => 'nullable|integer|min:0']);
        try {
            $claim = LeadClaim::with('lead.quotation')->findOrFail($claimId);
            $userRole = $request->user()->role?->code;
            abort_unless(in_array($userRole, ['sales', 'branch_manager']), 403);

            $observedDraft = TempQuotation::where('lead_id', $claim->lead_id)->where('status', 'draft')->first();
            DB::beginTransaction();
            QuotationConcurrency::lockLead($claim->lead_id);

            $existingQuotation = Quotation::where('lead_id', $claim->lead_id)->lockForUpdate()->first();
            abort_if($existingQuotation && ! in_array($existingQuotation->status, ['rejected', 'approved_by_bm'], true),
                409, 'This lead already has an active official quotation.');

            $draft = TempQuotation::where('lead_id', $claim->lead_id)
                ->where('status', 'draft')
                ->lockForUpdate()->first();
            abort_unless($draft, 409, 'No open revision is available. Reload the quotation.');
            abort_if($request->filled('draft_id') && (int) $request->input('draft_id') !== $draft->id,
                409, 'This draft is no longer active. Reload the quotation.');

            $draft->load('items', 'paymentTerms');
            QuotationConcurrency::checkDraft($draft, $observedDraft, $request);

            if ($existingQuotation) {
                app(QuotationRevisions::class)->validateRevisionDraft($existingQuotation, $draft);
                app(QuotationRevisions::class)->ensureInitialSnapshot($existingQuotation);
                $existingQuotation->items()->delete();
                $existingQuotation->paymentTerms()->delete();
                $existingQuotation->reviews()->delete();

                $revisionNo = $existingQuotation->revision_no + 1;
                $existingQuotation->update([
                    'revision_no' => $revisionNo,
                    'revision_code' => $existingQuotation->quotation_no.'_R'.$revisionNo,
                    'status' => 'review',
                    'subtotal' => $draft->subtotal,
                    'tax_pct' => $draft->tax_pct,
                    'tax_total' => $draft->tax_total,
                    'total_discount' => $draft->total_discount,
                    'grand_total' => $draft->grand_total,
                    'booking_fee' => $draft->booking_fee,
                    'expiry_date' => Carbon::now('Asia/Jakarta')->addDays(15),
                ]);

                $quotation = $existingQuotation;
            } else {
                $quotationNo = app(QuotationNumberGenerator::class)->next();
                $quotation = Quotation::create([
                    'quotation_no' => $quotationNo,
                    'revision_no' => 0,
                    'revision_code' => $quotationNo.'_R0',
                    'lead_id' => $claim->lead_id,
                    'status' => 'review',
                    'subtotal' => $draft->subtotal,
                    'tax_pct' => $draft->tax_pct,
                    'tax_total' => $draft->tax_total,
                    'total_discount' => $draft->total_discount,
                    'grand_total' => $draft->grand_total,
                    'booking_fee' => $draft->booking_fee,
                    'created_by' => $request->user()->id,
                    'expiry_date' => Carbon::now('Asia/Jakarta')->addDays(15),
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

            $draft->update([
                'status' => 'submitted',
                'lock_version' => ($draft->lock_version ?? 0) + 1,
                'quotation_id' => $quotation->id,
                'revision_no' => $quotation->revision_no,
                'revision_code' => $quotation->revision_code,
            ]);

            $log = QuotationLog::where('temp_quotation_id', $draft->id)
                ->where('action', 'draft_created')
                ->latest('logged_at')
                ->first();

            if ($log) {
                $log->update([
                    'quotation_id' => $quotation->id,
                    'revision_no' => $draft->revision_no,
                    'revision_code' => $draft->revision_code,
                ]);
            }
            QuotationLog::create([
                'quotation_id' => $quotation->id,
                'temp_quotation_id' => $draft->id,
                'action' => 'submitted',
                'user_id' => $request->user()->id,
                'logged_at' => Carbon::now('Asia/Jakarta'),
            ]);

            DB::commit();

            $sales = $request->user();
            QuotationConcurrency::afterCommit(function () use ($quotation, $sales) {
                User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                    ->where('branch_id', $quotation->lead->branch_id)
                    ->each(fn($bm) => $bm->notify(
                        new \App\Notifications\Orders\QuotationSubmittedNotification($quotation->load('lead'), $sales)
                    ));
            });
            return $this->setJsonResponse('Quotation submitted successfully', [
                'redirect_url' => route('leads.my')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack($transactionLevel);
            if (QuotationConcurrency::isConflict($e)) {
                return $this->setJsonResponse(QuotationConcurrency::MESSAGE, [], 409);
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->setJsonResponse($e->getMessage(), [], $e->getStatusCode());
            }
            return $this->setJsonResponse('Failed to submit quotation', [], 500, $e);
        }
    }

    protected function warmMeetingStatus($quotation, $draft = null)
    {
        if (! $quotation) {
            return '<span class="status-grey">' . ($draft ? 'Not Submitted (R0)' : 'No Quotation') . '</span>';
        }

        $status = $quotation->status;
        $label = $quotation->status_label;
        if ($status === 'rejected') {
            $review = $quotation->currentReviews()->where('decision', 'reject')->latest('decided_at')->first();
            $role = $review?->role;

            if ($role) {
                $by = match (strtoupper($role)) {
                    'BM' => 'BM',
                    'FIN', 'FINANCE' => 'Finance',
                    default => ucfirst(strtolower($role)),
                };
            } else {
                $by = 'BM/Finance';
            }

            $label = 'Rejected by ' . $by;
        }

        $class = match ($status) {
            'review', 'approved_by_bm', 'pending_finance' => 'status-waiting',
            'published' => 'status-finish',
            'rejected', 'expired', 'cancelled' => 'status-expired',
            default => 'status-grey',
        };
        $html = '<span class="' . $class . '">' . e($label) . '</span>';
        if ($draft) {
            $revision = (int) ($draft->revision_no ?? ($quotation->revision_no + 1));
            $html .= ' <span class="status-grey">Revision in Progress (R' . $revision . ')</span>';
        }

        return $html;
    }

    protected function warmActions($row)
    {
        $quotation = $row->lead->quotation;
        $draft     = $row->lead->tempQuotation;
        $viewUrl   = route('leads.my.warm.manage', $row->lead->id);
        $createUrl = route('leads.my.warm.quotation.create', $row->id);
        $quoteUrl  = $quotation ? route('quotations.revisions.index', $quotation->id) : null;
        $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;
        $trashUrl   = route('leads.my.warm.trash', $row->id);
        $role = auth()->user()->role?->code;
        $canManage = in_array($role, ['sales', 'branch_manager'], true);

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

        if ($draft && $canManage) {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($createUrl) . '">'
                . '
                ' . view('components.icon.generate-quotation')->render() . '
                ' . ($quotation ? 'Continue Revision (R' . (int) $draft->revision_no . ')' : 'Continue Quotation (R0)') . '</a>';
            if (auth()->user()->role?->code === 'sales') {
                $cancelLabel = $quotation ? 'Cancel Revision' : 'Discard Draft';
                $cancelText = $quotation
                    ? 'Discard saved changes for this revision and keep the current submitted quotation?'
                    : 'Discard this quotation draft and its saved changes?';
                $html .= '<form method="POST" action="' . e(route('quotations.drafts.cancel', $draft->id))
                    . '" require-confirmation="true" data-confirm-title="' . e($cancelLabel) . '?" data-confirm-text="' . e($cancelText) . '">'
                    . csrf_field() . '<input type="hidden" name="draft_version" value="' . (int) ($draft->lock_version ?? 0) . '">'
                    . '<button type="submit" class="dropdown-item cursor-pointer text-[#900B09]! flex! items-center! gap-2!">'
                    . view('components.icon.trash')->render() . ' ' . e($cancelLabel) . '</button></form>';
            }
        } elseif ($quotation && ! $draft) {
            // Only Sales sees this action; keep the existing BM endpoint capability intact.
            if ($quotation->status === 'approved_by_bm' && $role === 'sales') {
                $html .= '<form method="POST" action="' . e(route('quotations.submit-to-finance', $quotation->id))
                    . '" require-confirmation="true" data-confirm-title="Submit quotation to Finance?"'
                    . ' data-confirm-text="Confirm the customer has accepted this revision before submitting to Finance.">'
                    . csrf_field() . '<input type="hidden" name="revision_no" value="' . (int) $quotation->revision_no . '">'
                    . '<button type="submit" class="dropdown-item cursor-pointer flex! items-center! gap-2!">'
                    . view('components.icon.export')->render() . ' Submit to Finance</button></form>';
            }
            if (in_array($quotation->status, ['rejected', 'approved_by_bm'], true)
                && $canManage) {
                $html .= '<a class="dropdown-item flex! items-center! gap-2!" href="' . e($createUrl) . '">'
                    . view('components.icon.generate-quotation')->render() . ' Revise Quotation</a>';
            } elseif ($canManage && $this->canEditQuotationDirectly($quotation, $role)) {
                $html .= '<a class="dropdown-item flex! items-center! gap-2!" href="' . e($createUrl) . '">'
                    . view('components.icon.generate-quotation')->render() . ' Edit Quotation</a>';
            }
        } elseif (! $quotation && ! $draft && $canManage) {
            $html .= '<a class="dropdown-item flex! items-center! gap-2!" href="' . e($createUrl) . '">'
                . view('components.icon.generate-quotation')->render() . ' Create Quotation (R0)</a>';
        }

        if ($quotation) {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                . '
                ' . view('components.icon.view-quotation')->render() . '
                View Quotation</a>';
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($downloadUrl) . '">'
                . '
                ' . view('components.icon.download')->render() . '
                Download Quotation</a>';
            $logUrl = route('quotations.logs', $quotation->id);
            $html .= '  <button type="button" class="dropdown-item btn-quotation-log cursor-pointer flex! items-center! gap-2!" data-url="' . e($logUrl) . '">
            ' . view('components.icon.quotation-log')->render() . '
            Quotation Log</button>';
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
