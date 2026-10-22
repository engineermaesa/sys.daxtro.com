<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\CustomerDeal;
use App\Models\Aftersales\ServiceCustomer;
use App\Services\Aftersales\DealConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDealController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        return response()->json(CustomerDeal::with(['lead.quotation', 'customer'])->where(function ($q) {
            $q->where('status', 'needs_review')->orWhereNotNull('pending_changes');
        })->orderBy('id')->paginate(25));
    }

    public function resolve(Request $request, CustomerDeal $deal, DealConversion $service)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $input = $request->validate(['action' => 'required|in:link,create_new,apply,ignore', 'customer_id' => 'required_if:action,link|integer|exists:service_customers,id', 'updated_at' => 'required|string']);
        $data = DB::transaction(function () use ($deal, $input, $service, $request) {
            $link = CustomerDeal::whereKey($deal->id)->lockForUpdate()->firstOrFail();
            abort_unless($link->updated_at->toJSON() === $input['updated_at'], 409, 'Deal review changed. Reload before saving.');
            $before = $link->getAttributes();
            if (in_array($input['action'], ['link', 'create_new'], true)) {
                abort_unless($link->status === 'needs_review', 409, 'This Deal is already resolved.');
                $customer = $input['action'] === 'link' ? ServiceCustomer::findOrFail($input['customer_id']) : ServiceCustomer::create($service->snapshot($link->lead) + ['source' => 'deal_conversion', 'created_by' => $request->user()->id]);
                $link = $service->link($link->lead, $customer);
            } else {
                abort_unless($link->service_customer_id && $link->pending_changes, 409, 'There are no pending Lead changes.');
                $snapshot = $service->snapshot($link->lead);
                if ($input['action'] === 'apply') {
                    $customer = $link->customer;
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

        return response()->json(['data' => $data]);
    }
}
