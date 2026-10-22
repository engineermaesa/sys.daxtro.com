<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Aftersales\ServiceCustomerPhoneChange;
use App\Models\Aftersales\ServiceCustomerProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceCustomerController extends Controller
{
    public function page(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $this->pageTitle = 'Service Customers';

        return $this->render('pages.aftersales.customers.index');
    }

    public function machinesPage(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $this->pageTitle = 'Machine — ' . $customer->name;
        return $this->render('pages.aftersales.customers.machines', [
            'customer' => $customer,
        ]);
    }

    public function ticketsPage(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        $this->pageTitle = 'Tickets — ' . $customer->name;
        return $this->render('pages.aftersales.customers.tickets', [
            'customer' => $customer,
        ]);
    }

    public function createPage(Request $request, ?ServiceCustomer $customer = null)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $customer?->load(['province', 'region']);

        $this->pageTitle = ($customer ? 'Edit' : 'Add') . ' Customer';

        return $this->render('pages.aftersales.customers.create', [
            'customer' => $customer,
        ]);
    }

    public function createMachinePage(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $product = null;
        if ($request->filled('product')) {
            $product = ServiceCustomerProduct::where('customer_id', $customer->id)
                ->findOrFail($request->input('product'));
        }
        $this->pageTitle = ($product ? 'Edit' : 'Add') . ' Machine — ' . $customer->name;
        return $this->render('pages.aftersales.customers.create-machine', [
            'customer' => $customer,
            'product' => $product,
            'products' => \App\Models\Masters\Product::orderBy('name')->get(['id', 'name']),
            'productTypes' => \App\Models\Masters\ProductType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $query = ServiceCustomer::query()->with(['province', 'region'])->withCount('products');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('region', fn ($r) => $r->where('name', 'like', "%{$search}%"))
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $customer->load(['province', 'region', 'products.industry', 'products.product', 'products.productType']);

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'maps_link' => 'required|url|max:2048',
            'ref_province_id' => 'required|integer|exists:ref_provinces,id',
            'ref_region_id' => 'required|integer|exists:ref_regions,id',
            'pic_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
        ]);

        $validated['created_by'] = $request->user()->id;

        $customer = ServiceCustomer::create($validated);

        return $this->setJsonResponse('Customer created successfully', ['data' => $customer], 201);
    }

    public function update(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'required|string',
            'maps_link' => 'required|url|max:2048',
            'ref_province_id' => 'required|integer|exists:ref_provinces,id',
            'ref_region_id' => 'required|integer|exists:ref_regions,id',
            'pic_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $digits = fn ($p) => preg_replace('/\D+/', '', (string) $p);
        $phoneChanged = array_key_exists('phone', $validated) && $digits($validated['phone']) !== $digits($customer->phone);
        if ($phoneChanged) {
            abort_unless($request->user()->hasPermission('aftersales.customer-phone.change'), 403, 'Only admin can change the customer WhatsApp number.');
        }

        $oldPhone = $customer->phone;
        $customer->update($validated);

        if ($phoneChanged) {
            ServiceCustomerPhoneChange::create([
                'customer_id' => $customer->id,
                'old_phone' => $oldPhone,
                'new_phone' => $validated['phone'],
                'changed_by_user_id' => $request->user()->id,
                'changed_at' => Carbon::now('Asia/Jakarta'),
            ]);
        }

        return $this->setJsonResponse('Customer updated successfully', ['data' => $customer->fresh(['province', 'region'])]);
    }

    public function phoneChanges(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        return response()->json([
            'status' => 'success',
            'data' => ServiceCustomerPhoneChange::with('changedBy:id,name')
                ->where('customer_id', $customer->id)->latest('id')->get()
                ->map(fn ($c) => [
                    'old_phone' => $c->old_phone,
                    'new_phone' => $c->new_phone,
                    'changed_by' => $c->changedBy?->name,
                    'changed_at' => $c->changed_at,
                ]),
        ]);
    }

    public function provinces(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        return response()->json([
            'status' => 'success',
            'data' => \App\Models\Masters\Province::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function regions(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $request->validate(['province_id' => 'required|integer|exists:ref_provinces,id']);

        return response()->json([
            'status' => 'success',
            'data' => \App\Models\Masters\Region::where('province_id', $request->input('province_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function destroy(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $customer->update(['deleted_by' => $request->user()->id, 'is_deleted' => true]);
        $customer->delete();

        return $this->setJsonResponse('Customer deleted successfully');
    }

    public function storeProduct(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:ref_products,id',
            'serial_number' => 'nullable|string|max:255',
            'ref_product_type_id' => 'nullable|integer|exists:ref_product_types,id',
            'industry_id' => 'nullable|integer|exists:ref_industries,id',
            'warranty_period' => 'nullable|string|max:255',
            'warranty_start' => 'nullable|date',
            'warranty_end' => 'nullable|date',
            'pm_contract_status' => 'nullable|in:none,offered,active,expired',
            'pm_frequency' => 'nullable|string|max:255',
        ]);

        $validated['customer_id'] = $customer->id;
        $validated['created_by'] = $request->user()->id;

        $product = ServiceCustomerProduct::create($validated);

        return $this->setJsonResponse('Product unit added successfully', ['data' => $product->load(['product', 'productType'])], 201);
    }

    public function updateProduct(Request $request, ServiceCustomerProduct $product)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $request->validate([
            'product_id' => 'sometimes|required|integer|exists:ref_products,id',
            'serial_number' => 'nullable|string|max:255',
            'ref_product_type_id' => 'nullable|integer|exists:ref_product_types,id',
            'industry_id' => 'nullable|integer|exists:ref_industries,id',
            'warranty_period' => 'nullable|string|max:255',
            'warranty_start' => 'nullable|date',
            'warranty_end' => 'nullable|date',
            'pm_contract_status' => 'nullable|in:none,offered,active,expired',
            'pm_frequency' => 'nullable|string|max:255',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $product->update($validated);

        return $this->setJsonResponse('Product unit updated successfully', ['data' => $product->fresh(['product', 'productType'])]);
    }

    public function destroyProduct(Request $request, ServiceCustomerProduct $product)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $product->update(['deleted_by' => $request->user()->id, 'is_deleted' => true]);
        $product->delete();

        return $this->setJsonResponse('Product unit deleted successfully');
    }
}
