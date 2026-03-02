<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog};
use App\Models\Masters\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrashLeadController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();

        $leadIds = LeadClaim::select(DB::raw('MAX(id) as id'))
            ->whereHas(
                'lead',
                fn($q) =>
                $q->whereIn('status_id', [LeadStatus::TRASH_COLD, LeadStatus::TRASH_WARM, LeadStatus::TRASH_HOT])
            )
            ->when(
                $user->role?->code === 'sales',
                fn($q) =>
                $q->whereHas('lead', function ($q) use ($user) {
                    $q->whereNull('region_id')
                        ->orWhereHas(
                            'region',
                            fn($r) =>
                            $r->where('branch_id', $user->branch_id)
                        );
                })
            )
            ->groupBy('lead_id')
            ->pluck('id');

        $counts = LeadClaim::whereIn('id', $leadIds)
            ->with('lead')
            ->get()
            ->groupBy(fn($claim) => $claim->lead->status_id)
            ->map(fn($group) => $group->count());

        $branches = Branch::all();

        $cold = $counts[LeadStatus::TRASH_COLD] ?? 0;
        $warm = $counts[LeadStatus::TRASH_WARM] ?? 0;
        // TRASH HOT
        $hot = $counts[LeadStatus::TRASH_HOT] ?? 0;

        $all = $cold + $warm + $hot;
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'leadCounts' => [
                    'cold' => $counts[LeadStatus::TRASH_COLD] ?? 0,
                    'warm' => $counts[LeadStatus::TRASH_WARM] ?? 0,
                    'hot' => $counts[LeadStatus::TRASH_HOT] ?? 0,
                    'all'  => $all,
                ],
                'branches' => $branches->toArray(),
            ], 200);
        }

        return view('pages.trash-leads.index', [
            'leadCounts' => [
                'cold' => $counts[LeadStatus::TRASH_COLD] ?? 0,
                'warm' => $counts[LeadStatus::TRASH_WARM] ?? 0,
                'hot' => $counts[LeadStatus::TRASH_HOT] ?? 0,
                'all'  => $all,
            ],
            'branches' => $branches,
        ]);
    }

    public function coldList(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 10);

        $claims = LeadClaim::with([
                'lead.status', 
                'lead.segment', 
                'lead.source', 
                'lead.firstSales'
            ])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::TRASH_COLD))
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

        if ($request->filled('search')) {
            $search = $request->search;
            $claims->whereHas('lead', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $paginated = $claims
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

            $row->name          = $row->lead->name ?? '-';
            $row->sales_name    = $row->lead->firstSales->name ?? '-';
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? '-';
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
        $user = $request->user();
        $perPage = $request->get('per_page', 10);

        $claims = LeadClaim::with([
                'lead.status', 
                'lead.segment', 
                'lead.source', 
                'lead.firstSales'
            ])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::TRASH_WARM))
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

        if ($request->filled('search')) {
            $search = $request->search;
            $claims->whereHas('lead', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $paginated = $claims
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

            $row->name          = $row->lead->name ?? '-';
            $row->sales_name    = $row->lead->firstSales->name ?? '-';
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? '-';
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
                'lead.firstSales'
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

        if ($request->filled('search')) {
            $search = $request->search;
            $claims->whereHas('lead', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $paginated = $claims
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

            $row->name          = $row->lead->name ?? '-';
            $row->sales_name    = $row->lead->firstSales->name ?? '-';
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? '-';
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
        $user = $request->user();
        $perPage = $request->get('per_page', 10);

        $claims = LeadClaim::with([
                'lead.status', 
                'lead.segment', 
                'lead.source', 
                'lead.firstSales'
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

        if ($user->role?->code === 'sales') {
            $claims->whereHas('lead', function ($q) use ($user) {
                $q->whereNull('region_id')
                    ->orWhereHas(
                        'region',
                        fn($r) => $r->where('branch_id', $user->branch_id)
                    );
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $claims->whereHas('lead', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $paginated = $claims
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
                ($roleCode === 'sales') &&
                ($row->lead->region?->branch_id !== null && $user->branch_id !== null && $row->lead->region->branch_id === $user->branch_id)
            ) {
                $html .= '<button class="dropdown-item restore-lead flex items-center gap-2 text-[#1E1E1E]" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>';
            }
            
            if ($allowedAssign) {
                $html .= '<button class="dropdown-item assign-lead flex items-center gap-2 text-[#1E1E1E]" data-claim="' . $row->id . '" data-branch="' . ($row->lead->region->branch_id ?? '') . '"><i class="bi bi-person-plus"></i> Assign</button>';
            }
            $html .= '</div></div>';

            $row->name          = $row->lead->name ?? '-';
            $row->sales_name    = $row->lead->firstSales->name ?? '-';
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? '-';
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

    public function restore($claimId)
    {
        $claim = LeadClaim::with('lead')->where('id', $claimId)
            ->firstOrFail();

        $user = request()->user();

        abort_if($user->role?->code !== 'sales', 403);
        abort_if($claim->lead->region->branch_id !== $user->branch_id, 403);

        $newStatus = $claim->lead->status_id == LeadStatus::TRASH_COLD
            ? LeadStatus::COLD
            : LeadStatus::WARM;

        DB::transaction(function () use ($claim, $user, $newStatus) {
            $claim->update([
                'sales_id'   => $user->id,
                'claimed_at' => now(),
                'released_at' => null,
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

        abort_if(! in_array($request->user()->role?->code, $allowedRoles), 403);

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
