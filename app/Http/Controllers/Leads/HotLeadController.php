<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog};
use App\Services\AutoTrashService;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;

class HotLeadController extends Controller
{
    public function myHotList(Request $request)
    {
        AutoTrashService::triggerIfNeeded();

        $user     = $request->user();
        $roleCode = $user->role?->code;
        $perPage  = $request->get('per_page', 10);
        
        $claimsQuery = LeadClaim::with([
            'lead.statusLogs' => fn($q) => $q->where('status_id', LeadStatus::HOT)
                                            ->orderByDesc('created_at'),
            'lead.segment',
            'lead.source',
            'lead.industry',
            'lead.region.regional',
            'lead.quotation',
            'sales'
        ])
        ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::HOT))
        ->whereNull('released_at');

        $roleCode = $request->user()->role?->code;

        if ($roleCode === 'sales') {
        $claimsQuery->where('sales_id', $user->id);

        } elseif ($roleCode === 'branch_manager') {
            $claimsQuery->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $claimsQuery->where(function ($query) use ($search) {
                // Lead basic fields + needs + customer type
                $query->whereHas('lead', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('needs', 'like', "%{$search}%")
                            ->orWhere('customer_type', 'like', "%{$search}%");
                    });
                })
                // Sales name
                ->orWhereHas('sales', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                // Source name
                ->orWhereHas('lead.source', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                // City name
                ->orWhereHas('lead.region', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                // Regional name
                ->orWhereHas('lead.region.regional', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }


        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claimsQuery->whereHas('lead.quotation', function ($q) use ($request) {
                $q->bookingFeeBetween($request->start_date, $request->end_date);
            });
        }

        $paginated = $claimsQuery
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function ($row) {

            $lead = $row->lead;

            // Ambil waktu HOT dari status log
            $hotLog    = $lead->statusLogs->first();
            $hotAt     = $hotLog?->created_at ? \Carbon\Carbon::parse($hotLog->created_at) : null;
            $claimedAt = $row->claimed_at ? \Carbon\Carbon::parse($row->claimed_at) : null;

            $baseAt   = $claimedAt ?? $hotAt;
            $expireAt = $baseAt ? $baseAt->copy()->startOfDay()->addDays(30) : null;

            $daysLeft = null;

            if ($expireAt) {
                if ($baseAt->gt(now())) {
                    $daysLeft = 30;
                } else {
                    $daysLeft = now()->startOfDay()->diffInDays($expireAt, false);
                    if ($daysLeft < 0) {
                        $daysLeft = 0;
                    }
                }
            }

            // Meeting status
            if ($daysLeft === null) {
                $meetingStatus = 'Hot';
            } elseif ($daysLeft > 0) {
                $meetingStatus = 'Hot';
            } elseif ($daysLeft === 0) {
                $meetingStatus = 'Today';
            } else {
                $meetingStatus = '<span class="status-expired">Hot</span>';
            }

            $row->name          = $lead->name ?? '-';
            $row->sales_name    = $row->sales->name ?? '-';
            $row->phone         = $lead->phone ?? '-';
            $row->source        = $lead->source->name ?? '-';
            $row->needs         = $lead->needs ?? '-';
            $row->segment_name  = $lead->segment->name ?? '-';
            $row->city_name     = $lead->region->name ?? 'All Regions';
            $row->regional_name = $lead->region->regional->name ?? 'All Regions';
            $row->industry      = $lead->industry->name ?? ($lead->other_industry ?? '-');

            $row->expire_in     = $daysLeft;
            $row->expire_at     = $expireAt ? $expireAt->toDateString() : null;
            $row->meeting_status = $meetingStatus;
            $row->actions        = $this->hotActions($row);

            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
        ]);
    }

    protected function hotActions($row)
    {
        $quotation = $row->lead->quotation;
        $viewUrl   = route('leads.manage.form', $row->lead->id);
        $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
        $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;

        $btnId = 'hotActionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"'
            . ' type="button" id="' . $btnId . '"'
            . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right text-[#1E1E1E]!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2!" href="' . e($viewUrl) . '">'
            . '
            ' . view('components.icon.detail')->render() . '
            View Lead</a>';
        $activityUrl = route('leads.activity.logs', $row->lead->id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log cursor-pointer flex! items-center! gap-2!" data-url="' . e($activityUrl) . '">
        ' . view('components.icon.log')->render() . ' 
        View / Add Activity Log</button>';

        if (! $quotation) {
            $html .= '  <a class="dropdown-item" href="' . route('leads.my.warm.quotation.create', $row->id) . '">'
                . '
                '.view('components.icon.generate-quotation')->render().'
                Generate Quotation</a>';
        } else {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                . '    
                ' . view('components.icon.view-quotation')->render() . '
                View Quotation</a>';
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($downloadUrl) . '">'
                . '    
                ' . view('components.icon.download') . ' 
                Download</a>';
        }

        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }
}
