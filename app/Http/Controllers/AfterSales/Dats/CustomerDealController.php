<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\CustomerDeal;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Leads\Lead;
use App\Services\Aftersales\CustomerMatching;
use App\Services\Aftersales\CustomerMutationLocks;
use App\Services\Aftersales\DealConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDealController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $input = $request->validate(['queue' => 'sometimes|in:all,pending,lead_updates', 'per_page' => 'sometimes|integer|min:1|max:100']);
        $query = CustomerDeal::with(['lead.quotation', 'lead.factoryCity', 'customer']);
        match ($input['queue'] ?? 'all') {
            'pending' => $query->pendingReview(),
            'lead_updates' => $query->leadUpdates(),
            default => $query->where(fn ($q) => $q->where(fn ($pending) => $pending->pendingReview())->orWhere(fn ($updates) => $updates->leadUpdates())),
        };

        return response()->json($query->orderBy('id')->paginate($input['per_page'] ?? 25)->toArray() + $this->pendingSummary());
    }

    public function pendingCount(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        return response()->json(['data' => $this->pendingSummary()]);
    }

    public function show(Request $request, CustomerDeal $deal, DealConversion $service)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $deal->load(['lead.quotation.items.product', 'customer.province', 'customer.region']);
        abort_unless($deal->lead, 404);
        $snapshot = $service->snapshot($deal->lead);
        $matches = app(CustomerMatching::class)->candidates($snapshot, false);
        $customers = ServiceCustomer::with(['province', 'region'])->withCount('products')->whereIn('id', array_column($matches, 'id'))->get()->keyBy('id');
        $candidates = collect($matches)->map(fn ($match) => ['match_status' => $match['status'], 'customer' => $customers->get($match['id'])])->filter(fn ($match) => $match['customer'])->values();
        $machines = $deal->lead->quotation?->items->filter(fn ($item) => $item->product?->product_type_id && ! $item->merge_into_item_id)
            ->map(fn ($item) => ['quotation_item_id' => $item->id, 'product_id' => $item->product_id, 'machine_name' => $item->product->name, 'ref_product_type_id' => $item->product->product_type_id, 'quantity' => max(0, (int) $item->qty)])->values() ?? collect();
        $pending = CustomerDeal::whereKey($deal->id)->pendingReview()->exists();

        return response()->json(['data' => [
            'deal' => $deal->withoutRelations(),
            'from_deal' => $snapshot + ['lead_id' => $deal->lead_id, 'quotation_no' => $deal->lead->quotation?->quotation_no, 'deal_at' => $deal->lead->deal_at, 'region' => \App\Models\Masters\Region::find($snapshot['ref_region_id']), 'province' => \App\Models\Masters\Province::find($snapshot['ref_province_id']), 'machine_count' => $machines->sum('quantity'), 'machines' => $machines],
            'candidates' => $candidates,
            'customer' => $deal->customer,
            'allowed_actions' => $pending ? ['link', 'create_new'] : ($deal->service_customer_id && $deal->pending_changes ? ['apply', 'ignore'] : []),
        ]] + $this->pendingSummary());
    }

    private function pendingSummary(): array
    {
        $count = CustomerDeal::pendingReview()->count();

        return ['pending_count' => $count, 'badge_label' => $count > 99 ? '99+' : ($count > 0 ? (string) $count : null), 'has_pending' => $count > 0];
    }

    public function resolve(Request $request, CustomerDeal $deal, DealConversion $service)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $input = $request->validate(['action' => 'required|in:link,create_new,apply,ignore', 'customer_id' => 'required_if:action,link|integer|exists:service_customers,id', 'updated_at' => 'required|string']);
        $initialLead = $deal->lead;
        abort_unless($initialLead, 404);
        $identityKeys = CustomerMutationLocks::customerKeys(['name' => $initialLead->name, 'phone' => $initialLead->phone]);
        $target = $input['action'] === 'link' ? ServiceCustomer::findOrFail($input['customer_id']) : $deal->customer;
        $customerKeys = $target ? CustomerMutationLocks::customerKeys($target->getAttributes()) : [];
        $data = DB::transaction(function () use ($deal, $input, $service, $request, $identityKeys, $customerKeys) {
            CustomerMutationLocks::acquire(array_merge($identityKeys, $customerKeys));
            $lead = Lead::whereKey($deal->lead_id)->lockForUpdate()->firstOrFail();
            abort_unless(CustomerMutationLocks::customerKeys(['name' => $lead->name, 'phone' => $lead->phone]) === $identityKeys, 409, 'Deal review changed. Reload before saving.');
            $link = CustomerDeal::whereKey($deal->id)->lockForUpdate()->firstOrFail();
            $link->setRelation('lead', $lead);
            abort_unless($link->updated_at->toJSON() === $input['updated_at'], 409, 'Deal review changed. Reload before saving.');
            $before = $link->getAttributes();
            if (in_array($input['action'], ['link', 'create_new'], true)) {
                abort_unless($link->status === 'needs_review' && $link->review_status === CustomerDeal::REVIEW_PENDING && ! $link->service_customer_id, 409, 'This Deal is already resolved.');
                abort_unless(CustomerDeal::whereKey($link->id)->pendingReview()->exists(), 409, 'This Lead is no longer an active Deal.');
                if ($input['action'] === 'link') {
                    $customer = ServiceCustomer::whereKey($input['customer_id'])->lockForUpdate()->firstOrFail();
                    abort_unless(CustomerMutationLocks::customerKeys($customer->getAttributes()) === $customerKeys, 409, 'Customer changed. Reload the Deal review.');
                } else {
                    $customer = ServiceCustomer::create($service->snapshot($link->lead) + ['source' => 'deal_conversion', 'created_by' => $request->user()->id]);
                }
                $link = $service->link($link->lead, $customer);
            } else {
                abort_unless($link->service_customer_id && $link->pending_changes, 409, 'There are no pending Lead changes.');
                $snapshot = $service->snapshot($link->lead);
                if ($input['action'] === 'apply') {
                    $customer = ServiceCustomer::whereKey($link->service_customer_id)->lockForUpdate()->firstOrFail();
                    abort_unless(CustomerMutationLocks::customerKeys($customer->getAttributes()) === $customerKeys, 409, 'Customer changed. Reload the Deal review.');
                    if (($snapshot['phone'] ?? null) !== $customer->phone) {
                        abort_unless($request->user()->hasPermission('aftersales.customer-phone.change'), 403);
                        \App\Models\Aftersales\ServiceCustomerPhoneChange::create(['customer_id' => $customer->id, 'old_phone' => $customer->phone, 'new_phone' => $snapshot['phone'], 'changed_by_user_id' => $request->user()->id, 'changed_at' => now()]);
                    }
                    $customer->update($snapshot + ['updated_by' => $request->user()->id]);
                }
                $link->update(['lead_snapshot' => $snapshot, 'pending_changes' => null]);
            }
            DB::table('aftersales_customer_audits')->insert(['customer_id' => $link->service_customer_id, 'user_id' => $request->user()->id, 'action' => 'lead_'.$input['action'], 'changes' => json_encode(['before' => $before, 'after' => $link->getAttributes()]), 'created_at' => now()]);

            return $link;
        }, 3);

        return response()->json(['data' => $data] + $this->pendingSummary());
    }
}
