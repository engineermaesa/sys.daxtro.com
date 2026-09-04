<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Aftersales\ServiceCustomerProduct;
use Illuminate\Http\Request;

class ServiceCustomerController extends Controller
{
    public function page(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $this->pageTitle = 'Service Customers';

        return $this->render('pages.aftersales.customers.index');
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $query = ServiceCustomer::query()->withCount('products');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
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

        $customer->load(['products.industry']);

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
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
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $customer->update($validated);

        return $this->setJsonResponse('Customer updated successfully', ['data' => $customer->fresh()]);
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
            'machine_name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
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

        return $this->setJsonResponse('Product unit added successfully', ['data' => $product], 201);
    }

    public function updateProduct(Request $request, ServiceCustomerProduct $product)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $request->validate([
            'machine_name' => 'sometimes|required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'industry_id' => 'nullable|integer|exists:ref_industries,id',
            'warranty_period' => 'nullable|string|max:255',
            'warranty_start' => 'nullable|date',
            'warranty_end' => 'nullable|date',
            'pm_contract_status' => 'nullable|in:none,offered,active,expired',
            'pm_frequency' => 'nullable|string|max:255',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $product->update($validated);

        return $this->setJsonResponse('Product unit updated successfully', ['data' => $product->fresh()]);
    }

    public function destroyProduct(Request $request, ServiceCustomerProduct $product)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $product->update(['deleted_by' => $request->user()->id, 'is_deleted' => true]);
        $product->delete();

        return $this->setJsonResponse('Product unit deleted successfully');
    }
}
