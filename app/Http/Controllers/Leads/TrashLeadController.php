<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog};
use App\Models\Masters\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TrashLeadController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $roleCode = $user->role?->code;
        $leadCounts = $this->buildTrashLeadCounts($request);

        $branches = Branch::orderBy('name')->get();
        $salesFilters = User::query()
            ->select('id', 'name', 'branch_id')
            ->with('branch:id,name')
            ->whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->when(
                in_array($roleCode, ['sales', 'branch_manager'], true) && $user->branch_id,
                fn($q) => $q->where('branch_id', $user->branch_id)
            )
            ->orderBy('name')
            ->get();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'leadCounts' => $leadCounts,
                'branches' => $branches->toArray(),
                'salesFilters' => $salesFilters->toArray(),
            ], 200);
        }

        return view('pages.trash-leads.index', [
            'leadCounts' => $leadCounts,
            'branches' => $branches,
            'salesFilters' => $salesFilters,
        ]);
    }

    private function buildTrashLeadCounts(Request $request): array
    {
        $cold = $this->buildTrashLeadCountQuery($request, [LeadStatus::TRASH_COLD])->count();
        $warm = $this->buildTrashLeadCountQuery($request, [LeadStatus::TRASH_WARM])->count();
        $hot = $this->buildTrashLeadCountQuery($request, [LeadStatus::TRASH_HOT])->count();
        $all = $this->buildTrashLeadCountQuery($request, [
            LeadStatus::TRASH_COLD,
            LeadStatus::TRASH_WARM,
            LeadStatus::TRASH_HOT,
        ])->count();

        return [
            'all' => $all,
            'cold' => $cold,
            'warm' => $warm,
            'hot' => $hot,
        ];
    }

    private function buildTrashLeadCountQuery(Request $request, array $statusIds): Builder
    {
        $claims = LeadClaim::query()
            ->whereHas('lead', fn($q) => $q->whereIn('status_id', $statusIds))
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('lead_claims as lc2')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lead_id');
            });

        $user = $request->user() ?? auth()->user();
        if ($user) {
            $this->applyTrashLeadCountScope($claims, $user, $statusIds);
        }

        $this->applyTrashLeadFilters($claims, $request, $statusIds);

        return $claims;
    }

    private function applyTrashLeadCountScope(Builder $claims, User $user, array $statusIds): void
    {
        $roleCode = $user->role?->code;
        $isHotOnly = count($statusIds) === 1 && (int) reset($statusIds) === LeadStatus::TRASH_HOT;

        if ($isHotOnly) {
            if ($roleCode === 'sales') {
                $claims->whereHas('lead', function ($q) use ($user) {
                    $q->whereNull('region_id')
                        ->orWhereHas(
                            'region',
                            fn($r) => $r->where('branch_id', $user->branch_id)
                        );
                });
            }

            return;
        }

        if ($roleCode === 'sales') {
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->where(function ($subQuery) use ($user) {
                    $subQuery->whereNull('region_id')
                        ->orWhereHas(
                            'region',
                            fn($r) => $r->where('branch_id', $user->branch_id)
                        );
                })->where('first_sales_id', $user->id);
            });
        }

        if ($roleCode === 'branch_manager') {
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    );
            });
        }
    }

    public function coldList(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        abort_if(! $user, 401);
        $roleCode = $user->role?->code;
        $perPage = $request->get('per_page', 10);

        $claims = LeadClaim::with([
                'lead.status', 
                'lead.segment', 
                'lead.source', 
                'lead.firstSales.branch',
                'lead.region.branch',
                'sales',
            ])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::TRASH_COLD))
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('lead_claims as lc2')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lead_id');
            });

        if ($roleCode === 'sales') {
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    )
                    ->where('first_sales_id', $user->id);
            });
        }

        if ($roleCode === 'branch_manager'){
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    );
            });
        }


        $this->applyTrashLeadFilters($claims, $request, [LeadStatus::TRASH_COLD]);

        $claims->addSelect([
            'trashed_at' => DB::table('lead_status_logs as lsl')
                ->selectRaw('MAX(lsl.created_at)')
                ->whereColumn('lsl.lead_id', 'lead_claims.lead_id')
                ->where('lsl.status_id', LeadStatus::TRASH_COLD),
        ]);

        $paginated = $claims
            ->orderByDesc('trashed_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function ($row) use ($user) {
            $detailUrl  = route('trash-leads.form', $row->lead_id);
            $restoreUrl = route('trash-leads.restore', $row->id);
            $btnId      = 'trashActionsDropdown' . $row->id;

            $html  = '<div class="dropdown">';
            $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
            $html .= '<i class="bi bi-three-dots"></i>';
            $html .= '</button>';
            $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg">';
            $html .= '<a class="dropdown-item flex items-center gap-2 text-[#1E1E1E]" href="' . e($detailUrl) . '"><i class="bi bi-eye"></i> View Detail</a>';

            $roleCode = $user->role?->code;
            $allowedAssign = in_array($roleCode, ['branch_manager', 'super_admin', 'sales_director', 'finance_director', 'accountant_director']);

            if (
                in_array($roleCode, ['sales', 'branch_manager', 'sales_director', 'super_admin']) &&
                ($row->lead->region?->branch_id !== null && $user->branch_id !== null && $row->lead->region->branch_id === $user->branch_id)
            ) {
                $html .= '<button class="dropdown-item restore-lead flex items-center gap-2 text-[#1E1E1E]" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>';
            }

            if ($allowedAssign) {
                $html .= '<button class="dropdown-item assign-lead flex items-center gap-2 text-[#1E1E1E]" data-claim="' . $row->id . '" data-branch="' . ($row->lead->region->branch_id ?? '') . '"><i class="bi bi-person-plus"></i> Assign</button>';
            }

            $html .= '</div></div>';

            $row->name              = $row->lead->name ?? '-';
            $row->first_sales_name  = $row->lead->firstSales->name ?? '-';
            $row->last_sales_name   = $row->sales->name ?? $row->first_sales_name;
            $row->sales_name        = $row->first_sales_name;
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? $row->lead->customer_type ?? '-';
            $row->city_name     = $row->lead->city->name ?? '-';
            $row->regional_name = $row->lead->region->regional->name ?? '-';
            
            $row->status_lead = '<span class="inline-flex items-center justify-center gap-2 px-3 py-2 status-trash">
            Trash - Cold
            <span class="dot-trash-cold"></span>
            </span>';
            
            $row->actions       = $html;

            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'total'        => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }
    public function warmList(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        abort_if(! $user, 401);
        $roleCode = $user->role?->code;
        $perPage = $request->get('per_page', 10);

        $claims = LeadClaim::with([
                'lead.status', 
                'lead.segment', 
                'lead.source', 
                'lead.firstSales.branch',
                'lead.region.branch',
                'sales',
            ])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::TRASH_WARM))
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('lead_claims as lc2')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lead_id');
            });

        if ($roleCode === 'sales') {
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    )
                    ->where('first_sales_id', $user->id);
            });
        }

        if ($roleCode === 'branch_manager'){
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    );
            });
        }

        $this->applyTrashLeadFilters($claims, $request, [LeadStatus::TRASH_WARM]);

        $claims->addSelect([
            'trashed_at' => DB::table('lead_status_logs as lsl')
                ->selectRaw('MAX(lsl.created_at)')
                ->whereColumn('lsl.lead_id', 'lead_claims.lead_id')
                ->where('lsl.status_id', LeadStatus::TRASH_WARM),
        ]);

        $paginated = $claims
            ->orderByDesc('trashed_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function ($row) use ($user) {
            $detailUrl  = route('trash-leads.form', $row->lead_id);
            $restoreUrl = route('trash-leads.restore', $row->id);
            $btnId      = 'trashActionsDropdown' . $row->id;

            $html  = '<div class="dropdown">';
            $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
            $html .= '<i class="bi bi-three-dots"></i>';
            $html .= '</button>';
            $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg">';
            $html .= '<a class="dropdown-item flex items-center gap-2 text-[#1E1E1E]" href="' . e($detailUrl) . '"><i class="bi bi-eye"></i> View Detail</a>';

            $roleCode = $user->role?->code;
            $allowedAssign = in_array($roleCode, ['branch_manager', 'super_admin', 'sales_director', 'finance_director', 'accountant_director']);

            if (
                in_array($roleCode, ['sales', 'branch_manager', 'sales_director', 'super_admin']) &&
                ($row->lead->region?->branch_id !== null && $user->branch_id !== null && $row->lead->region->branch_id === $user->branch_id)
            ) {
                $html .= '<button class="dropdown-item restore-lead flex items-center gap-2 text-[#1E1E1E]" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>';
            }

            if ($allowedAssign) {
                $html .= '<button class="dropdown-item assign-lead flex items-center gap-2 text-[#1E1E1E]" data-claim="' . $row->id . '" data-branch="' . ($row->lead->region->branch_id ?? '') . '"><i class="bi bi-person-plus"></i> Assign</button>';
            }

            $html .= '</div></div>';

            $row->name              = $row->lead->name ?? '-';
            $row->first_sales_name  = $row->lead->firstSales->name ?? '-';
            $row->last_sales_name   = $row->sales->name ?? $row->first_sales_name;
            $row->sales_name        = $row->first_sales_name;
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? $row->lead->customer_type ?? '-';
            $row->city_name     = $row->lead->city->name ?? '-';
            $row->regional_name = $row->lead->region->regional->name ?? '-';
            
            $row->status_lead = '<span class="inline-flex items-center justify-center gap-2 px-3 py-2 status-trash">
            Trash - Warm
            <span class="dot-trash-warm"></span>
            </span>';
            
            $row->actions       = $html;

            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'total'        => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    public function hotList(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 10);

        $claims = LeadClaim::with([
                'lead.status', 
                'lead.segment', 
                'lead.source', 
                'lead.firstSales',
                'sales',
            ])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::TRASH_HOT))
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('lead_claims as lc2')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lead_id');
            });

        if ($user->role?->code === 'sales') {
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    );
            });
        }

        $this->applyTrashLeadFilters($claims, $request, [LeadStatus::TRASH_HOT]);

        $claims->addSelect([
            'trashed_at' => DB::table('lead_status_logs as lsl')
                ->selectRaw('MAX(lsl.created_at)')
                ->whereColumn('lsl.lead_id', 'lead_claims.lead_id')
                ->where('lsl.status_id', LeadStatus::TRASH_HOT),
        ]);

        $paginated = $claims
            ->orderByDesc('trashed_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function ($row) use ($user) {
            $detailUrl  = route('trash-leads.form', $row->lead_id);
            $restoreUrl = route('trash-leads.restore', $row->id);
            $btnId      = 'trashActionsDropdown' . $row->id;

            $html  = '<div class="dropdown">';
            $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
            $html .= '<i class="bi bi-three-dots"></i>';
            $html .= '</button>';
            $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg">';
            $html .= '<a class="dropdown-item flex items-center gap-2 text-[#1E1E1E]" href="' . e($detailUrl) . '"><i class="bi bi-eye"></i> View Detail</a>';

            $roleCode = $user->role?->code;
            $allowedAssign = in_array($roleCode, ['branch_manager', 'super_admin', 'sales_director', 'finance_director', 'accountant_director']);

            if (
                ($roleCode === 'sales') &&
                ($row->lead->region?->branch_id !== null && $user->branch_id !== null && $row->lead->region->branch_id === $user->branch_id)
            ) {
                $html .= '<button class="dropdown-item restore-lead flex items-center gap-2 text-[#1E1E1E]" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>';
            }

            if ($allowedAssign) {
                    $html .= '<button class="dropdown-item assign-lead flex items-center gap-2 text-[#1E1E1E]" data-claim="' . $row->id . '" data-branch="' . ($row->lead->region->branch_id ?? '') . '"><i class="bi bi-person-plus"></i> Assign</button>';
            }

            $html .= '</div></div>';

            $row->name              = $row->lead->name ?? '-';
            $row->first_sales_name  = $row->lead->firstSales->name ?? '-';
            $row->last_sales_name   = $row->sales->name ?? $row->first_sales_name;
            $row->sales_name        = $row->first_sales_name;
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? $row->lead->customer_type ?? '-';
            $row->city_name     = $row->lead->city->name ?? '-';
            $row->regional_name = $row->lead->region->regional->name ?? '-';
            
            $row->status_lead = '<span class="inline-flex items-center justify-center gap-2 px-3 py-2 status-trash">
            Trash - Cold
            <span class="dot-trash-hot"></span>
            </span>';
            
            $row->actions       = $html;

            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'total'        => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    public function allList(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        abort_if(! $user, 401);
        $perPage = $request->get('per_page', 10);

        $claims = LeadClaim::with([
                'lead.status', 
                'lead.segment', 
                'lead.source', 
                'lead.firstSales.branch',
                'lead.region.branch',
                'sales',
            ])
            ->whereHas('lead', function ($q) {
                $q->whereIn('status_id', [
                    LeadStatus::TRASH_COLD, 
                    LeadStatus::TRASH_WARM, 
                    LeadStatus::TRASH_HOT
                ]);
            })
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('lead_claims as lc2')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lead_id');
            });

        $roleCode = $user->role?->code;

        if ($roleCode === 'sales') {
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    )
                    ->where('first_sales_id', $user->id);
            });
        }

        if ($roleCode === 'branch_manager'){
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    );
            });
        }

        $trashStatusIds = [
            LeadStatus::TRASH_COLD,
            LeadStatus::TRASH_WARM,
            LeadStatus::TRASH_HOT,
        ];
        $this->applyTrashLeadFilters($claims, $request, $trashStatusIds);

        $claims->addSelect([
            'trashed_at' => DB::table('lead_status_logs as lsl')
                ->selectRaw('MAX(lsl.created_at)')
                ->whereColumn('lsl.lead_id', 'lead_claims.lead_id')
                ->whereIn('lsl.status_id', $trashStatusIds),
        ]);

        $paginated = $claims
            ->orderByDesc('trashed_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function ($row) use ($user) {
            $detailUrl  = route('trash-leads.form', $row->lead_id);
            $restoreUrl = route('trash-leads.restore', $row->id);
            
            $html  = '<div class="dropdown">';
            $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
            $html .= '<i class="bi bi-three-dots"></i>';
            $html .= '</button>';
            $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg">';
            $html .= '<a class="dropdown-item flex items-center gap-2 text-[#1E1E1E]" href="' . e($detailUrl) . '"><i class="bi bi-eye"></i> View Detail</a>';
            
            $roleCode = $user->role?->code;
            $allowedAssign = in_array($roleCode, ['branch_manager', 'super_admin', 'sales_director', 'finance_director', 'accountant_director']);
            
            if (
                in_array($roleCode, ['sales', 'branch_manager']) &&
                ($row->lead->region?->branch_id !== null && $user->branch_id !== null && $row->lead->region->branch_id === $user->branch_id)
            ){
                $html .= '<button class="dropdown-item restore-lead flex items-center gap-2 text-[#1E1E1E]" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>';
            }
            
            if (in_array($roleCode, ['sales_director', 'super_admin'])){
                $html .= '<button class="dropdown-item restore-lead flex items-center gap-2 text-[#1E1E1E]" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>';
            }

            if ($allowedAssign) {
                $html .= '<button class="dropdown-item assign-lead flex items-center gap-2 text-[#1E1E1E]" data-claim="' . $row->id . '" data-branch="' . ($row->lead->region->branch_id ?? '') . '"><i class="bi bi-person-plus"></i> Assign</button>';
            }
            $html .= '</div></div>';

            $row->name              = $row->lead->name ?? '-';
            $row->first_sales_name  = $row->lead->firstSales->name ?? '-';
            $row->last_sales_name   = $row->sales->name ?? $row->first_sales_name;
            $row->sales_name        = $row->first_sales_name;
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? $row->lead->customer_type ?? '-';
            $row->city_name     = $row->lead->city->name ?? '-';
            $row->regional_name = $row->lead->region->regional->name ?? '-';
            
            $statusName = $row->lead->status->name ?? 'Trash';
            $dotClass   = match($row->lead->status_id) {
                LeadStatus::TRASH_HOT  => 'dot-trash-hot',
                LeadStatus::TRASH_WARM => 'dot-trash-warm',
                LeadStatus::TRASH_COLD => 'dot-trash-cold',
                default                => 'dot-trash-cold',
            };

            $row->status_lead = '<span class="inline-flex items-center justify-center gap-2 px-3 py-2 status-trash">
                ' . e($statusName) . '
                <span class="' . $dotClass . '"></span>
            </span>';
            
            $row->actions       = $html;
            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'total'        => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    private function applyTrashLeadFilters(Builder $claims, Request $request, array $trashStatusIds): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $claims->whereHas('lead', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch')) {
            $branchFilter = trim((string) $request->input('branch'));

            if (ctype_digit($branchFilter)) {
                $branchId = (int) $branchFilter;

                $claims->where(function (Builder $query) use ($branchId) {
                    $query->whereHas('lead', function (Builder $q) use ($branchId) {
                        $q->where('branch_id', $branchId)
                            ->orWhereHas('region', fn(Builder $subQuery) => $subQuery->where('branch_id', $branchId))
                            ->orWhereHas('firstSales', fn(Builder $subQuery) => $subQuery->where('branch_id', $branchId));
                    })->orWhereHas('sales', fn(Builder $q) => $q->where('branch_id', $branchId));
                });
            }
        }

        if ($request->filled('sales')) {
            $salesFilter = trim((string) $request->input('sales'));

            $claims->where(function (Builder $query) use ($salesFilter) {
                if (ctype_digit($salesFilter)) {
                    $salesId = (int) $salesFilter;
                    $query->where('sales_id', $salesId)
                        ->orWhereHas('lead', fn(Builder $q) => $q->where('first_sales_id', $salesId));
                    return;
                }

                $query->whereHas('sales', fn(Builder $q) => $q->where('name', 'like', "%{$salesFilter}%"))
                    ->orWhereHas('lead.firstSales', fn(Builder $q) => $q->where('name', 'like', "%{$salesFilter}%"));
            });
        }

        [$claimedStartAt, $claimedEndAt] = $this->resolveDateRange($request, 'filter_by_claimed_at');
        if ($claimedStartAt) {
            $claims->where('claimed_at', '>=', $claimedStartAt);
        }
        if ($claimedEndAt) {
            $claims->where('claimed_at', '<=', $claimedEndAt);
        }

        [$toTrashStartAt, $toTrashEndAt] = $this->resolveDateRange($request, 'filter_by_to_trash_at');
        if ($toTrashStartAt || $toTrashEndAt) {
            $trashedAtQuery = DB::table('lead_status_logs as lsl_filter')
                ->selectRaw('MAX(lsl_filter.created_at)')
                ->whereColumn('lsl_filter.lead_id', 'lead_claims.lead_id')
                ->whereIn('lsl_filter.status_id', $trashStatusIds);

            $trashedAtSql = '(' . $trashedAtQuery->toSql() . ')';
            $trashedAtBindings = $trashedAtQuery->getBindings();

            if ($toTrashStartAt) {
                $claims->whereRaw($trashedAtSql . ' >= ?', array_merge($trashedAtBindings, [$toTrashStartAt]));
            }

            if ($toTrashEndAt) {
                $claims->whereRaw($trashedAtSql . ' <= ?', array_merge($trashedAtBindings, [$toTrashEndAt]));
            }
        }
    }

    private function resolveDateRange(Request $request, string $filterKey): array
    {
        $range = $request->input($filterKey);

        $startAt = is_array($range) ? ($range['start_at'] ?? null) : null;
        $endAt = is_array($range) ? ($range['end_at'] ?? null) : null;

        $startAt = $startAt ?? $request->input("{$filterKey}.start_at") ?? $request->input("{$filterKey}_start_at");
        $endAt = $endAt ?? $request->input("{$filterKey}.end_at") ?? $request->input("{$filterKey}_end_at");

        return [
            $this->parseFilterDate($startAt, true),
            $this->parseFilterDate($endAt, false),
        ];
    }

    private function parseFilterDate($value, bool $isStartOfDay): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
            return $isStartOfDay ? $date->startOfDay() : $date->endOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function form(Request $request, $id)
    {
        $lead = \App\Models\Leads\Lead::with([
            'status',
            'source',
            'segment',
            'region',
            'firstSales',
            'meetings.expense.details.expenseType',
            'meetings.expense.financeRequest',
            'quotation.items',
            'quotation.proformas',
            'quotation.order.orderItems',
            'quotation.order.invoices',
        ])->findOrFail($id);

        $meeting = $lead->meetings->sortByDesc('scheduled_start_at')->first();

        $claim   = $lead->claims()->first();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'lead' => $lead->toArray(),
                'meeting' => $meeting ? $meeting->toArray() : null,
                'claim' => $claim ? $claim->toArray() : null,
            ], 200);
        }

        return view('pages.trash-leads.form', compact('lead', 'meeting', 'claim'));
    }
    // Tanpa Auth
    // public function restore(Request $request, $claimId = null)
    // {
    //     $user = $request->user();

    //     abort_if($user->role?->code !== 'sales', 403);

    //     // Jika kirim array claim_ids di body -> bulk restore
    //     if ($request->has('claim_ids')) {
    //         $data = $request->validate([
    //             'claim_ids'   => 'required|array',
    //             'claim_ids.*' => 'integer|exists:lead_claims,id',
    //         ]);

    //         $claims = LeadClaim::with('lead.region')
    //             ->whereIn('id', $data['claim_ids'])
    //             ->get();

    //         foreach ($claims as $claim) {
    //             abort_if($claim->lead->region->branch_id !== $user->branch_id, 403);
    //         }

    //         DB::transaction(function () use ($claims, $user) {
    //             foreach ($claims as $claim) {
    //                 $newStatus = $claim->lead->status_id == LeadStatus::TRASH_COLD
    //                     ? LeadStatus::COLD
    //                     : LeadStatus::WARM;

    //                 $claim->update([
    //                     'sales_id'    => $user->id,
    //                     'claimed_at'  => now(),
    //                     'released_at' => null,
    //                 ]);

    //                 $claim->lead->update(['status_id' => $newStatus]);

    //                 LeadStatusLog::create([
    //                     'lead_id'   => $claim->lead_id,
    //                     'status_id' => $newStatus,
    //                 ]);
    //             }
    //         });

    //         return $this->setJsonResponse('Leads restored successfully');
    //     }

    //     // Default: restore satu claim berdasarkan parameter URL
    //     $claim = LeadClaim::with('lead.region')->where('id', $claimId)
    //         ->firstOrFail();

    //     abort_if($claim->lead->region->branch_id !== $user->branch_id, 403);

    //     $newStatus = $claim->lead->status_id == LeadStatus::TRASH_COLD
    //         ? LeadStatus::COLD
    //         : LeadStatus::WARM;

    //     DB::transaction(function () use ($claim, $user, $newStatus) {
    //         $claim->update([
    //             'sales_id'   => $user->id,
    //             'claimed_at' => now(),
    //             'released_at' => null,
    //         ]);

    //         $claim->lead->update(['status_id' => $newStatus]);

    //         LeadStatusLog::create([
    //             'lead_id'   => $claim->lead_id,
    //             'status_id' => $newStatus,
    //         ]);
    //     });

    //     return $this->setJsonResponse('Lead restored successfully');
    // }

    // Dengan Auth

    public function restore(Request $request, $claimId = null)
    {
        $user = $request->user();
        $roleCode = $user->role?->code;

        // Jika kirim array claim_ids di body -> bulk restore
        if ($request->has('claim_ids')) {
            $data = $request->validate([
                'claim_ids'   => 'required|array',
                'claim_ids.*' => 'integer|exists:lead_claims,id',
            ]);

            $claims = LeadClaim::with(['lead.region', 'sales'])
                ->whereIn('id', $data['claim_ids'])
                ->get();

            foreach ($claims as $claim) {
                $leadBranchId  = optional($claim->lead->region)->branch_id;
                $salesBranchId = optional($claim->sales)->branch_id;

                if ($roleCode === 'sales') {
                    abort_if($leadBranchId !== $user->branch_id, 403);
                } elseif ($roleCode === 'branch_manager') {
                    if ($user->branch_id) {
                        abort_if(
                            $leadBranchId !== $user->branch_id &&
                            $salesBranchId !== $user->branch_id,
                            403
                        );
                    }
                }
            }

            DB::transaction(function () use ($claims) {
                foreach ($claims as $claim) {
                    $newStatus = $claim->lead->status_id == LeadStatus::TRASH_COLD
                        ? LeadStatus::COLD
                        : LeadStatus::WARM;

                    $claim->update([
                        'claimed_at'  => now(),
                        'released_at' => null,
                        'trash_note' => null,
                    ]);

                    $claim->lead->update(['status_id' => $newStatus]);

                    LeadStatusLog::create([
                        'lead_id'   => $claim->lead_id,
                        'status_id' => $newStatus,
                    ]);
                }
            });

            return $this->setJsonResponse('Leads restored successfully');
        }

        // Default: restore satu claim berdasarkan parameter URL
        $claim = LeadClaim::with(['lead.region', 'sales'])->where('id', $claimId)
            ->firstOrFail();

        $leadBranchId  = optional($claim->lead->region)->branch_id;
        $salesBranchId = optional($claim->sales)->branch_id;

        if ($roleCode === 'sales') {
            abort_if($leadBranchId !== $user->branch_id, 403);
        } elseif ($roleCode === 'branch_manager') {
            if ($user->branch_id) {
                abort_if(
                    $leadBranchId !== $user->branch_id &&
                    $salesBranchId !== $user->branch_id,
                    403
                );
            }
        }

        $newStatus = $claim->lead->status_id == LeadStatus::TRASH_COLD
            ? LeadStatus::COLD
            : LeadStatus::WARM;

        DB::transaction(function () use ($claim, $newStatus) {
            $claim->update([
                'claimed_at' => now(),
                'released_at' => null,
                'trash_note' => null,
            ]);

            $claim->lead->update(['status_id' => $newStatus]);

            LeadStatusLog::create([
                'lead_id'   => $claim->lead_id,
                'status_id' => $newStatus,
            ]);
        });

        return $this->setJsonResponse('Lead restored successfully');
    }

    public function assign(Request $request, $claimId)
    {
        $claim = LeadClaim::with('lead')->where('id', $claimId)->firstOrFail();

        $allowedRoles = [
            'super_admin',
            'branch_manager',
            'sales_director',
            'finance_director',
            'accountant_director',
        ];

        // abort_if(! in_array($request->user()->role?->code, $allowedRoles), 403);

        $request->validate([
            'sales_id' => 'required|exists:users,id',
        ]);

        $sales = User::where('id', $request->sales_id)
            ->whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->firstOrFail();

        $newStatus = $claim->lead->status_id == LeadStatus::TRASH_COLD
            ? LeadStatus::COLD
            : LeadStatus::WARM;

        DB::transaction(function () use ($claim, $sales, $newStatus) {
            $claim->update([
                'sales_id'   => $sales->id,
                'claimed_at' => now(),
                'released_at' => null,
                'trash_note' => null,
            ]);

            $claim->lead->update(['status_id' => $newStatus]);

            LeadStatusLog::create([
                'lead_id'   => $claim->lead_id,
                'status_id' => $newStatus,
            ]);
        });

        return $this->setJsonResponse('Lead assigned successfully');
    }
}
