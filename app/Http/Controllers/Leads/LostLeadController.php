<?php

namespace App\Http\Controllers\leads;
use App\Http\Controllers\Controller;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Masters\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class LostLeadController extends Controller
{
    public function index()
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
        $salesFilters = User::query()
            ->select('id', 'name', 'branch_id')
            ->with('branch:id,name')
            ->whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->when(
                $user->role?->code === 'sales' && $user->branch_id,
                fn($q) => $q->where('branch_id', $user->branch_id)
            )
            ->orderBy('name')
            ->get();

        $hot = $counts[LeadStatus::TRASH_HOT] ?? 0;
        
         return view('pages.lost-leads.index', [
            'leadCounts' => [
                'hot' => $counts[LeadStatus::TRASH_HOT] ?? 0
            ],
            'branches' => $branches,
            'salesFilters' => $salesFilters,
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

            $html  = '<div class="dropdown">';
            $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
            $html .= '<i class="bi bi-three-dots"></i>';
            $html .= '</button>';
            $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg">';
            $html .= '<a class="dropdown-item flex items-center gap-2 text-[#1E1E1E]" href="' . e($detailUrl) . '"><i class="bi bi-eye"></i> View Detail</a>';
            $html .= '</div></div>';

            $row->name          = $row->lead->name ?? '-';
            $row->sales_name    = $row->lead->firstSales->name ?? '-';
            $row->phone         = $row->lead->phone ?? '-';
            $row->source        = $row->lead->source->name ?? '-';
            $row->needs         = $row->lead->needs ?? '-';
            $row->segment_name  = $row->lead->segment->name ?? $row->lead->customer_type ?? '-';
            $row->city_name     = $row->lead->city->name ?? '-';
            $row->regional_name = $row->lead->region->regional->name ?? '-';
            
            $row->status_lead = '<span class="inline-flex items-center justify-center gap-2 px-3 py-2 status-trash"> Trash - Hot <span class="dot-trash-hot"></span>
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
}
