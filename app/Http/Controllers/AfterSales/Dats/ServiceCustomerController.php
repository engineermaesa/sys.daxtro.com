<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Aftersales\ServiceCustomerPhoneChange;
use App\Models\Aftersales\ServiceCustomerProduct;
use App\Services\Aftersales\MachineNames;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $this->pageTitle = 'Machine — '.$customer->name;

        return $this->render('pages.aftersales.customers.machines', [
            'customer' => $customer,
        ]);
    }

    public function ticketsPage(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('aftersales.tickets.manage'), 403);
        $this->pageTitle = 'Tickets — '.$customer->name;

        return $this->render('pages.aftersales.customers.tickets', [
            'customer' => $customer,
        ]);
    }

    public function createPage(Request $request, ?ServiceCustomer $customer = null)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $customer?->load(['province', 'region']);

        $this->pageTitle = ($customer ? 'Edit' : 'Add').' Customer';

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
        $this->pageTitle = ($product ? 'Edit' : 'Add').' Machine — '.$customer->name;

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

        $query = ServiceCustomer::query()->with(['province', 'region', 'deals'])->withCount('products');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('region', fn ($r) => $r->where('name', 'like', "%{$search}%"))
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginated = $query->orderByDesc('id')->paginate($perPage);
        $paginated->getCollection()->each->append(['lead_no_longer_deal', 'lead_updated', 'ticket_creation']);

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

        $customer->load(['province', 'region', 'deals.lead.quotation', 'products.industry', 'products.product', 'products.productType']);
        $customer->append(['lead_no_longer_deal', 'lead_updated', 'ticket_creation']);

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $this->normalizeMapsInput($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'address_maps_url' => ['nullable', 'string', 'max:2048', new \App\Rules\GoogleMapsUrl],
            'ref_province_id' => 'nullable|integer|exists:ref_provinces,id',
            'ref_region_id' => 'nullable|integer|exists:ref_regions,id',
            'pic_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated = $this->customerLocation($validated);

        $customer = \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            \App\Services\Aftersales\CustomerMutationLocks::acquire(\App\Services\Aftersales\CustomerMutationLocks::customerKeys($validated));

            return ServiceCustomer::create($validated);
        }, 3);

        return $this->setJsonResponse('Customer created successfully', ['data' => $customer], 201);
    }

    public function update(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $this->normalizeMapsInput($request);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'address_maps_url' => ['nullable', 'string', 'max:2048', new \App\Rules\GoogleMapsUrl],
            'ref_province_id' => 'nullable|integer|exists:ref_provinces,id',
            'ref_region_id' => 'nullable|integer|exists:ref_regions,id',
            'pic_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $validated['updated_by'] = $request->user()->id;
        $validated = $this->customerLocation($validated, $customer);

        $original = $customer->getAttributes();
        $customer = \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $validated, $request, $original) {
            $oldKeys = \App\Services\Aftersales\CustomerMutationLocks::customerKeys($original);
            $newKeys = \App\Services\Aftersales\CustomerMutationLocks::customerKeys(array_replace($original, $validated));
            \App\Services\Aftersales\CustomerMutationLocks::acquire(array_merge($oldKeys, $newKeys));
            $customer = ServiceCustomer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            abort_unless(\App\Services\Aftersales\CustomerMutationLocks::customerKeys($customer->getAttributes()) === $oldKeys, 409, 'Customer changed. Reload before saving.');
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

            return $customer;
        }, 3);

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

        \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $request) {
            $keys = \App\Services\Aftersales\CustomerMutationLocks::customerKeys($customer->getAttributes());
            \App\Services\Aftersales\CustomerMutationLocks::acquire($keys);
            $locked = ServiceCustomer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            abort_unless(\App\Services\Aftersales\CustomerMutationLocks::customerKeys($locked->getAttributes()) === $keys, 409, 'Customer changed. Reload before deleting.');
            $locked->update(['deleted_by' => $request->user()->id, 'is_deleted' => true]);
            $locked->delete();
        }, 3);

        return $this->setJsonResponse('Customer deleted successfully');
    }

    public function storeProduct(Request $request, ServiceCustomer $customer)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $this->validateMachine($request);

        $validated['customer_id'] = $customer->id;
        $validated['created_by'] = $request->user()->id;

        $product = $this->saveMachine($validated);

        return $this->setJsonResponse('Product unit added successfully', ['data' => $product->load(['product', 'productType'])], 201);
    }

    public function updateProduct(Request $request, ServiceCustomerProduct $product)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $validated = $this->validateMachine($request, $product);

        $validated['updated_by'] = $request->user()->id;

        $product = $this->saveMachine($validated, $product);

        return $this->setJsonResponse('Product unit updated successfully', ['data' => $product->fresh(['product', 'productType'])]);
    }

    public function destroyProduct(Request $request, ServiceCustomerProduct $product)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        $product->update(['deleted_by' => $request->user()->id, 'is_deleted' => true]);
        $product->delete();

        return $this->setJsonResponse('Product unit deleted successfully');
    }

    public function moveProduct(Request $request, ServiceCustomerProduct $product, \App\Services\Aftersales\MachineTransfer $service)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $input = $request->validate(['customer_id' => 'required|integer|exists:service_customers,id', 'note' => 'nullable|string|max:10000']);

        return response()->json(['data' => $service->move($product, $input['customer_id'], $input['note'] ?? null, $request->user()->id)->load(['customer', 'productType'])]);
    }

    public function machineReferences(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);

        return response()->json(['data' => ['categories' => \App\Models\Masters\ProductType::orderBy('name')->get(['id', 'name', 'code_prefix']), 'industries' => \App\Models\Masters\Industry::orderBy('name')->get(['id', 'name']), 'pm_contracts' => ['none' => null, 'basic' => '6 months', 'premium' => '3 months']]]);
    }

    public function machineNamePreview(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        $data = $request->validate([
            'ref_product_type_id' => 'required|integer|exists:ref_product_types,id',
            'capacity' => 'required|numeric|min:0',
            'compressor' => 'nullable|string|max:255',
            'refrigerant' => 'nullable|string|max:255',
        ]);
        $name = MachineNames::generate(
            \App\Models\Masters\ProductType::findOrFail($data['ref_product_type_id'])->code_prefix,
            $data['capacity'], $data['compressor'] ?? null, $data['refrigerant'] ?? null,
        );

        return response()->json(['data' => ['machine_name' => $name, 'machine_name_mode' => 'auto']]);
    }

    private function normalizeMapsInput(Request $request): void
    {
        if (! $request->exists('maps_link')) {
            return;
        }

        if ($request->exists('address_maps_url') && $request->input('address_maps_url') !== $request->input('maps_link')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'maps_link' => 'Google Maps fields must contain the same URL.',
            ]);
        }

        $request->merge(['address_maps_url' => $request->input('maps_link')]);
    }

    private function customerLocation(array $data, ?ServiceCustomer $customer = null): array
    {
        $regionId = $data['ref_region_id'] ?? $customer?->ref_region_id;
        if ($regionId) {
            $data['ref_province_id'] = \App\Models\Masters\Region::findOrFail($regionId)->province_id;
        }

        return $data;
    }

    private function validateMachine(Request $request, ?ServiceCustomerProduct $machine = null): array
    {
        $isDaxtro = $request->exists('is_daxtro') ? $request->boolean('is_daxtro') : ($machine?->is_daxtro ?? true);
        $validated = $request->validate([
            'is_daxtro' => 'sometimes|required|boolean',
            'product_id' => 'nullable|integer|exists:ref_products,id',
            'machine_name' => [Rule::requiredIf(! $isDaxtro), 'nullable', 'string', 'max:255'],
            'machine_name_mode' => 'sometimes|required|in:auto,manual',
            'serial_number' => [Rule::excludeIf(! $isDaxtro), 'required', 'string', 'max:255'],
            'brand' => [Rule::excludeIf($isDaxtro), 'required', 'string', 'max:255'],
            'compressor' => 'nullable|string|max:255',
            'capacity' => 'nullable|numeric|min:0',
            'refrigerant' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:10000',
            'ref_product_type_id' => 'nullable|integer|exists:ref_product_types,id',
            'industry_id' => 'nullable|integer|exists:ref_industries,id',
            'warranty_start' => [Rule::excludeIf(! $isDaxtro), 'nullable', 'date'],
            'warranty_end' => [Rule::excludeIf(! $isDaxtro), 'nullable', 'date'],
            'pm_contract_status' => [Rule::excludeIf(! $isDaxtro), 'sometimes', 'required', 'in:none,basic,premium'],
        ]);
        $validated['is_daxtro'] = $isDaxtro;
        if ($isDaxtro) {
            $validated['brand'] = null;
        }

        return $validated;
    }

    private function saveMachine(array $data, ?ServiceCustomerProduct $machine = null): ServiceCustomerProduct
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $machine) {
            $serial = $data['is_daxtro'] ? \App\Services\Aftersales\CustomerMatching::serial($data['serial_number']) : null;
            \App\Services\Aftersales\CustomerMutationLocks::acquire(\App\Services\Aftersales\CustomerMutationLocks::serialKey($serial));
            $duplicate = $serial && \App\Services\Aftersales\MachineSerials::exists($serial, $machine?->id);
            if (($data['is_daxtro'] && ! $serial) || $duplicate) {
                throw \Illuminate\Validation\ValidationException::withMessages(['serial_number' => $serial ? 'This serial number already exists.' : 'Serial Number is required.']);
            }
            $data['serial_number'] = $serial;
            if ($machine) {
                $machine = ServiceCustomerProduct::whereKey($machine->id)->lockForUpdate()->firstOrFail();
            }
            $machine ??= new ServiceCustomerProduct;
            MachineNames::apply($machine, $data);
            $machine->save();

            return $machine;
        }, 3);
    }
}
