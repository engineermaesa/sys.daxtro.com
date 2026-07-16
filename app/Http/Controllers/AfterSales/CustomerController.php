<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use App\Models\Leads\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess($request);

        return view('pages.dashboard.after-sales.customer');
    }

    public function list(Request $request)
    {
        $this->authorizeAccess($request);

        $query = Customer::query()->with(['lead.product.type']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($outer) use ($search) {
                $outer->where('electricity', 'like', "%{$search}%")
                    ->orWhere('building_area', 'like', "%{$search}%")
                    ->orWhere('access_road_width', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($leadQuery) use ($search) {
                        $leadQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('needs', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 10);

        $paginated = $query->orderByDesc('customers.created_at')->paginate($perPage);

        $data = collect($paginated->items())->map(function (Customer $customer) {
            return [
                'id' => $customer->id,
                'customer_name' => $customer->lead->name ?? '-',
                'telephone' => $customer->lead->phone ?? '-',
                'machine_type' => $customer->lead->product?->type?->name ?? $customer->lead->needs ?? '-',
                'power_watts' => $customer->electricity,
                'room_area_m2' => $customer->building_area,
                'road_width_m' => $customer->access_road_width,
                'actions' => $this->actionsHtml($customer),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(Request $request, Customer $customer)
    {
        $this->authorizeAccess($request);

        $customer->load(['lead.product.type']);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'contact_person' => $customer->lead->name ?? '-',
                'phone_number' => $customer->lead->phone ?? '-',
                'site_address' => $customer->lead->company_address ?? '-',
                'machine_type' => $customer->lead->product?->type?->name ?? $customer->lead->needs ?? '-',
                'power_watts' => $customer->electricity,
                'building_area_m2' => $customer->building_area,
                'road_width_m' => $customer->access_road_width,
                'location_link' => $customer->location_link,
                'file_cad' => collect($customer->file_cad ?? [])->map(function ($file) {
                    $path = is_array($file) ? ($file['path'] ?? '') : $file;
                    $originalName = is_array($file) ? ($file['original_name'] ?? basename($path)) : basename($path);

                    return [
                        'path' => $path,
                        'name' => $originalName,
                        'url' => Storage::disk('public')->url($path),
                        'size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
                    ];
                })->values(),
            ],
            'lead_id' => $customer->leads_id,
        ]);
    }

    public function uploadCad(Request $request, Customer $customer)
    {
        $this->authorizeAccess($request);

        $validator = Validator::make($request->all(), [
            'file_cad' => 'nullable|array',
            'file_cad.*' => 'file|extensions:dwg,dxf,zip|max:10240',
            'remove_file_cad' => 'nullable|array',
            'remove_file_cad.*' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->setJsonResponse(
                $validator->errors()->first(),
                ['errors' => $validator->errors()->toArray()],
                422
            );
        }

        $existingFiles = $customer->file_cad ?? [];

        if ($request->filled('remove_file_cad')) {
            $filesToRemove = $request->input('remove_file_cad');

            $pathsToDelete = collect($existingFiles)
                ->map(fn ($file) => is_array($file) ? ($file['path'] ?? '') : $file)
                ->intersect($filesToRemove)
                ->values()
                ->all();

            Storage::disk('public')->delete($pathsToDelete);

            $existingFiles = collect($existingFiles)
                ->reject(fn ($file) => in_array(is_array($file) ? ($file['path'] ?? '') : $file, $filesToRemove))
                ->values()
                ->all();
        }

        if ($request->hasFile('file_cad')) {
            foreach ($request->file('file_cad') as $file) {
                $existingFiles[] = [
                    'path' => $file->store('cad', 'public'),
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        $customer->update(['file_cad' => $existingFiles]);

        return $this->setJsonResponse(
            'CAD file(s) updated successfully.',
            ['customer' => $customer->fresh()]
        );
    }

    protected function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('customers.view'), 403);
    }

    protected function actionsHtml(Customer $customer): string
    {
        $btnId = 'customerDetailDropdown' . $customer->id;

        $html = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"'
            . ' type="button" id="' . $btnId . '"'
            . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item btn-customer-detail flex! items-center! gap-2!"'
            . ' data-url="' . route('after-sales.customers.show', $customer->id) . '"'
            . ' data-upload-url="' . route('after-sales.customers.cad.upload', $customer->id) . '">'
            . 'View Detail</a>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }
}
