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
    public function index()
    {
        $user  = auth()->user();

        $leadIds = LeadClaim::select(DB::raw('MAX(id) as id'))
            ->whereHas('lead', fn ($q) => 
                $q->whereIn('status_id', [LeadStatus::TRASH_COLD, LeadStatus::TRASH_WARM])
            )
            ->when($user->role?->code === 'sales', fn ($q) => 
                $q->whereHas('lead', function($q) use ($user) {
                    $q->whereNull('region_id')
                    ->orWhereHas('region', fn($r) =>
                        $r->where('branch_id', $user->branch_id)
                    );
                })
            )
            ->groupBy('lead_id')
            ->pluck('id');

        $counts = LeadClaim::whereIn('id', $leadIds)
            ->with('lead')
            ->get()
            ->groupBy(fn ($claim) => $claim->lead->status_id)
            ->map(fn ($group) => $group->count());

        $branches = Branch::all();

        $cold = $counts[LeadStatus::TRASH_COLD] ?? 0;
        $warm = $counts[LeadStatus::TRASH_WARM] ?? 0;
        // NANTI JADI TRASH HOT
        $hot = $counts[LeadStatus::HOT] ?? 0;

        $all = $cold + $warm + $hot;

        return view('pages.trash-leads.index', [
            'leadCounts' => [
                'cold' => $counts[LeadStatus::TRASH_COLD] ?? 0,
                'warm' => $counts[LeadStatus::TRASH_WARM] ?? 0,
                'hot' => $counts[LeadStatus::HOT] ?? 0,
                'all'  => $all,
            ],
            'branches' => $branches,
        ]);
    }

    public function coldList(Request $request)
    {
        $claims = LeadClaim::with(['lead.status', 'lead.segment', 'lead.source', 'lead.firstSales'])
            ->whereHas('lead', fn ($q) => $q->where('status_id', LeadStatus::TRASH_COLD))
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('lead_claims as lc2')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lead_id');
            });

        if ($request->user()->role?->code === 'sales') {
            $claims->whereHas('lead', function($q) use ($request) {
                $q->whereNull('region_id')
                ->orWhereHas('region', fn($r) =>
                    $r->where('branch_id', $request->user()->branch_id)
                );
            });
        }

        return DataTables::of($claims)
            ->addColumn('claimed_at', fn ($row) => $row->claimed_at)
            ->addColumn('lead_name', fn ($row) => $row->lead->name)
            ->addColumn('segment_name', fn ($row) => $row->lead->segment->name ?? '-')
            ->addColumn('source_name', fn ($row) => $row->lead->source->name ?? '-')
            ->addColumn('first_sales_name', fn ($row) => $row->lead->firstSales->name ?? '-')
            ->addColumn('meeting_status', fn () => '<span class="badge bg-secondary">Trash Cold</span>')
            ->addColumn('actions', function ($row) use ($request) {
                $detailUrl  = route('trash-leads.form', $row->lead_id);
                $restoreUrl = route('trash-leads.restore', $row->id);
                $btnId      = 'trashActionsDropdown' . $row->id;

                $html  = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                $html .= '    <a class="dropdown-item" href="' . e($detailUrl) . '"><i class="bi bi-eye mr-2"></i> View Detail</a>';

                $roleCode = $request->user()->role?->code;
                $allowedAssign = in_array($roleCode, ['branch_manager', 'super_admin', 'sales_director', 'finance_director', 'accountant_director']);

                if (
                    ($roleCode === 'sales') &&
                    ($row->lead->region?->branch_id !== null && $request->user()->branch_id !== null && $row->lead->region->branch_id === $request->user()->branch_id)
                ) {
                    $html .= '  <button class="dropdown-item restore-lead" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise mr-2"></i> Restore</button>';
                }

                if ($allowedAssign) {
                    $html .= '  <button class="dropdown-item assign-lead" data-claim="' . $row->id . '" data-branch="' . ($row->lead->region->branch_id ?? '') . '"><i class="bi bi-person-plus mr-2"></i> Assign</button>';
                }

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }

    public function warmList(Request $request)
    {
        $claims = LeadClaim::with(['lead.status', 'lead.segment', 'lead.source', 'lead.firstSales'])
            ->whereHas('lead', fn ($q) => $q->where('status_id', LeadStatus::TRASH_WARM))
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('lead_claims as lc2')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lead_id');
            });

        if ($request->user()->role?->code === 'sales') {
            $claims->whereHas('lead', function($q) use ($request) {
                $q->whereNull('region_id')
                ->orWhereHas('region', fn($r) => 
                    $r->where('branch_id', $request->user()->branch_id)
                );
            });
        }

        return DataTables::of($claims)
            ->addColumn('claimed_at', fn ($row) => $row->claimed_at)
            ->addColumn('lead_name', fn ($row) => $row->lead->name)
            ->addColumn('segment_name', fn ($row) => $row->lead->segment->name ?? '-')
            ->addColumn('source_name', fn ($row) => $row->lead->source->name ?? '-')
            ->addColumn('first_sales_name', fn ($row) => $row->lead->firstSales->name ?? '-')
            ->addColumn('meeting_status', fn () => '<span class="badge bg-secondary">Trash Warm</span>')
            ->addColumn('actions', function ($row) use ($request) {
                $detailUrl  = route('trash-leads.form', $row->lead_id);
                $restoreUrl = route('trash-leads.restore', $row->id);
                $btnId      = 'trashActionsDropdown' . $row->id;

                $html  = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                $html .= '    <a class="dropdown-item" href="' . e($detailUrl) . '"><i class="bi bi-eye mr-2"></i> View Detail</a>';

                $roleCode = $request->user()->role?->code;
                $allowedAssign = in_array($roleCode, ['branch_manager', 'super_admin', 'sales_director', 'finance_director', 'accountant_director']);

                if (
                    ($roleCode === 'sales') &&
                    ($row->lead->region?->branch_id !== null && $request->user()->branch_id !== null && $row->lead->region->branch_id === $request->user()->branch_id)
                ) {
                    $html .= '  <button class="dropdown-item restore-lead" data-url="' . e($restoreUrl) . '"><i class="bi bi-arrow-counterclockwise mr-2"></i> Restore</button>';
                }

                if ($allowedAssign) {
                    $html .= '  <button class="dropdown-item assign-lead" data-claim="' . $row->id . '" data-branch="' . ($row->lead->region->branch_id ?? '') . '"><i class="bi bi-person-plus mr-2"></i> Assign</button>';
                }

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }

    public function form($id)
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
            ->whereHas('role', fn ($q) => $q->where('code', 'sales'))
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
