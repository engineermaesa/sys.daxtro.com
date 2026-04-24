<?php

namespace App\Http\Controllers\Masters;

use App\Http\Classes\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Models\Leads\LeadSource;
use App\Models\Masters\Agent;
use App\Models\Masters\Branch;
use App\Models\Masters\CustomerType;
use App\Models\Masters\Jabatan;
use App\Models\Masters\Province;
use App\Models\Masters\Region;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Agents';

        return $this->render('pages.masters.agents.index', [
            'branches' => Branch::all(),
            'regions' => Region::all(),
            'provinces' => Province::all(),
        ]);
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id
            ? Agent::with(['branch', 'jabatan', 'source', 'customerType', 'region.province'])
                ->where('id', $id)
                ->firstOrFail()
            : new Agent();

        $branches = Branch::orderBy('name')->get();
        $regions = Region::with('province:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'province_id', 'branch_id']);
        $provinces = Province::orderBy('name')->pluck('name');
        $sources = LeadSource::orderBy('name')->get();
        $customerTypes = CustomerType::orderBy('name')->get();
        $jabatans = Jabatan::orderBy('name')->get();
        $saveUrl = url('/api/masters/agents/save' . ($id ? '/' . $id : ''));
        $backUrl = route('masters.agents.index');

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                    'branches' => $branches,
                    'regions' => $regions,
                    'provinces' => $provinces,
                    'sources' => $sources,
                    'customerTypes' => $customerTypes,
                    'jabatans' => $jabatans,
                ],
            ]);
        }

        return $this->render('pages.masters.agents.form', compact(
            'form_data',
            'branches',
            'regions',
            'provinces',
            'sources',
            'customerTypes',
            'jabatans',
            'saveUrl',
            'backUrl'
        ));
    }

    public function list(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;
        $page = max((int) $request->input('page', 1), 1);

        $filteredQuery = $this->agentListQuery($request);

        $counts = [
            'all' => (clone $filteredQuery)->count(),
            'active' => (clone $filteredQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $filteredQuery)->where('is_active', 0)->count(),
        ];

        $query = clone $filteredQuery;

        switch ($request->input('status', 'all')) {
            case 'active':
                $query->where('is_active', 1);
                break;

            case 'inactive':
                $query->where('is_active', 0);
                break;

            default:
                break;
        }

        $agents = $query
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $agents->getCollection()->transform(function ($row) {
            switch ((string) $row->is_active) {
                case '1':
                    $actions = $this->activeActions($row);
                    break;

                default:
                    $actions = $this->inactiveActions($row);
                    break;
            }

            return [
                'id' => $row->id,
                'name' => $row->name,
                'phone' => $row->phone,
                'email' => $row->email,
                'branch_name' => $row->branch->name ?? '-',
                'region_name' => $row->region->name ?? '-',
                'province' => $row->province ?? '-',
                'company_name' => $row->company_name,
                'company_address' => $row->company_address,
                'source_name' => $row->source->name ?? '-',
                'created_at' => optional($row->created_at)->format('d M Y'),
                'is_active' => (int) $row->is_active,
                'status_name' => $row->is_active ? 'Active' : 'Inactive',
                'actions' => $actions,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $agents->items(),
            'counts' => $counts,
            'pagination' => [
                'current_page' => $agents->currentPage(),
                'last_page' => $agents->lastPage(),
                'per_page' => $agents->perPage(),
                'total' => $agents->total(),
                'from' => $agents->firstItem() ?? 0,
                'to' => $agents->lastItem() ?? 0,
            ],
        ]);
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'title' => 'nullable|in:Mr,Mrs',
            'name' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:ref_jabatans,id',
            'source_id' => 'required|exists:lead_sources,id',
            'customer_type_id' => 'required|exists:ref_customer_types,id',
            'region_id' => 'required|exists:ref_regions,id',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string',
            'is_active' => 'nullable|boolean',
            'assignment_branch' => 'nullable|exists:ref_branches,id',
            'branch_id' => 'nullable|exists:ref_branches,id',
        ]);

        $user = auth()->user();
        $roleCode = $user?->role?->code;

        if ($roleCode === 'branch_manager') {
            $branchId = $user->branch_id;
        } elseif (in_array($roleCode, ['super_admin', 'sales_director'], true)) {
            $branchId = $id ? $request->assignment_branch : null;
        } else {
            $branchId = $request->branch_id;
        }

        $agent = $id ? Agent::where('id', $id)->firstOrFail() : new Agent();
        $before = $id ? $agent->toArray() : null;
        $name = trim($request->name);

        if ($request->filled('title') && ! str_starts_with($name, $request->title . ' ')) {
            $name = trim($request->title . ' ' . $name);
        }

        $agent->branch_id = $branchId;
        $agent->jabatan_id = $request->jabatan_id;
        $agent->source_id = $request->source_id;
        $agent->customer_type_id = $request->customer_type_id;
        $agent->region_id = $request->region_id;
        $agent->province = $request->province;
        $agent->name = $name;
        $agent->phone = $request->phone;
        $agent->email = $request->email;
        $agent->company_name = $request->company_name;
        $agent->company_address = $request->company_address;
        $agent->is_active = $request->boolean('is_active', true);
        $agent->save();

        $after = $agent->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_agent' : 'create_agent',
            $id ? 'Updated agent' : 'Created new agent',
            $agent,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Agent saved successfully');
    }

    private function agentListQuery(Request $request)
    {
        $query = Agent::with(['branch', 'region', 'source', 'customerType', 'jabatan']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('company_address', 'like', "%{$search}%")
                    ->orWhereHas('branch', function ($branchQuery) use ($search) {
                        $branchQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('region', function ($regionQuery) use ($search) {
                        $regionQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('source', function ($sourceQuery) use ($search) {
                        $sourceQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return $query;
    }

    private function activeActions($row)
    {
        $formUrl = route('masters.agents.form', $row->id);

        $btnId = 'actionsDropdown' . $row->id;
        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right rounded-lg!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($formUrl) . '">
            ' . view('components.icon.detail')->render() . '
            View Agent Detail</a>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    private function inactiveActions($row)
    {
        $formUrl = route('masters.agents.form', $row->id);

        $btnId = 'actionsDropdown' . $row->id;
        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right rounded-lg!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($formUrl) . '">
            ' . view('components.icon.detail')->render() . '
            View Agent Detail</a>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }
}
