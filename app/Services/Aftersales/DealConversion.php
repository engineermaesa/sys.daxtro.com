<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\CustomerDeal;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Models\Masters\Region;
use Illuminate\Support\Facades\DB;

class DealConversion
{
    public function snapshot(Lead $lead): array
    {
        try {
            $phone = CustomerMatching::phone($lead->phone);
        } catch (\InvalidArgumentException) {
            $phone = null;
        }
        $region = $lead->factory_city_id ? Region::find($lead->factory_city_id) : null;
        $province = $region?->province_id ?? \App\Models\Masters\Province::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($lead->factory_province ?? ''))])->value('id');

        return ['name' => $lead->name, 'phone' => $phone, 'email' => $lead->email, 'address' => $lead->company_address, 'ref_region_id' => $region?->id, 'ref_province_id' => $province];
    }

    public function sync(Lead $lead): ?CustomerDeal
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            if ($attempt > 0) {
                $lead = Lead::findOrFail($lead->id);
            }
            $keys = CustomerMutationLocks::customerKeys(['name' => $lead->name, 'phone' => $lead->phone]);
            try {
                return $this->syncWithIdentity($lead, $keys);
            } catch (CustomerIdentityChanged) {

            }
        }
        abort(409, 'The Lead changed during conversion. Retry from its current data.');
    }

    private function syncWithIdentity(Lead $lead, array $keys): ?CustomerDeal
    {
        return DB::transaction(function () use ($lead, $keys) {
            CustomerMutationLocks::acquire($keys);
            $lead = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();
            if (CustomerMutationLocks::customerKeys(['name' => $lead->name, 'phone' => $lead->phone]) !== $keys) {
                throw new CustomerIdentityChanged;
            }
            $link = CustomerDeal::where('lead_id', $lead->id)->lockForUpdate()->first();
            if ((int) $lead->status_id !== LeadStatus::DEAL) {
                $link?->update(['left_deal_at' => $link->left_deal_at ?? now()]);

                return $link;
            }
            if (! $lead->deal_at) {
                return $link;
            }
            $data = $this->snapshot($lead);
            if ($link) {
                if ($link->review_status === CustomerDeal::REVIEW_PENDING && ! $link->service_customer_id) {
                    $link->update(['left_deal_at' => null, 'lead_snapshot' => $data, 'pending_changes' => null, 'candidates' => app(CustomerMatching::class)->candidates($data, false)]);

                    return $link;
                }
                $pending = $data !== $link->lead_snapshot ? $data : null;
                $link->update(['left_deal_at' => null, 'pending_changes' => $pending]);

                return $link;
            }
            $matches = app(CustomerMatching::class)->candidates($data, false);
            $exact = collect($matches)->where('status', 'exact');
            if ($exact->count() === 1) {
                $customer = ServiceCustomer::findOrFail($exact->first()['id']);
            } elseif ($matches) {
                return CustomerDeal::create(['lead_id' => $lead->id, 'status' => 'needs_review', 'review_status' => CustomerDeal::REVIEW_PENDING, 'candidates' => $matches, 'lead_snapshot' => $data]);
            } else {
                $customer = ServiceCustomer::create($data + ['source' => 'deal_conversion']);
            }

            return $this->link($lead, $customer);
        }, 3);
    }

    public function link(Lead $lead, ServiceCustomer $customer): CustomerDeal
    {
        $lead = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();
        abort_unless((int) $lead->status_id === LeadStatus::DEAL && $lead->deal_at, 409, 'The selected Lead is no longer a Deal.');
        $link = CustomerDeal::firstOrNew(['lead_id' => $lead->id]);
        abort_if($link->exists && $link->service_customer_id && (int) $link->service_customer_id !== $customer->id, 409, 'This Deal is already linked to another customer.');
        $alreadyLinked = $link->exists && $link->service_customer_id;
        $link->fill(['service_customer_id' => $customer->id, 'status' => 'linked', 'review_status' => CustomerDeal::REVIEW_RESOLVED, 'lead_snapshot' => $this->snapshot($lead), 'pending_changes' => null, 'candidates' => null, 'left_deal_at' => (int) $lead->status_id === LeadStatus::DEAL ? null : now()])->save();
        if (! $alreadyLinked) {
            $lead->load('quotation.items.product');
            foreach ($lead->quotation?->items ?? [] as $item) {
                $product = $item->product;
                if (! $product?->product_type_id || $item->merge_into_item_id) {
                    continue;
                }
                for ($unit = 0; $unit < (int) $item->qty; $unit++) {
                    $customer->products()->create(['product_id' => $product->id, 'ref_product_type_id' => $product->product_type_id, 'industry_id' => $lead->industry_id, 'machine_name' => $product->name, 'capacity' => $lead->tonase, 'warranty_period' => $product->warranty_available ? $product->warranty_time_month.' Months' : null]);
                }
            }
        }

        return $link;
    }
}
