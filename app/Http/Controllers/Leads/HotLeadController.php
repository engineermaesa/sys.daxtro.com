<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;

class HotLeadController extends Controller
{
    public function myHotList(Request $request)
    {
        $claims = LeadClaim::with([
                'lead.status',
                'lead.statusLogs' => fn($q) => $q->where('status_id', LeadStatus::HOT)->orderByDesc('created_at'),
                'lead.segment',
                'lead.source',
                'lead.industry',
                'sales',
                'lead.region.regional'
            ])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::HOT))
            ->whereNull('released_at');

        $roleCode = $request->user()->role?->code;

        if ($roleCode === 'sales') {
            $claims->where('sales_id', $request->user()->id);
        } elseif ($roleCode === 'branch_manager') {
            $claims->whereHas('sales', function ($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            });
        }


        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claims->whereHas('lead.quotation', function ($q) use ($request) {
                $q->bookingFeeBetween($request->start_date, $request->end_date);
            });
        }

        $page = $request->input('page', 1);
        $perPage = 10;
        // If the request is an API call, return a plain JSON payload.
        if ($request->is('api/*')) {
            $items = $claims->get()->map(function ($row) {
                // Prefer the time when lead moved to HOT (from status logs). Fallback to claimed_at.
                $hotLog = $row->lead->statusLogs->first() ?? null;
                $hotAt = $hotLog && $hotLog->created_at ? Carbon::parse($hotLog->created_at) : null;
                $claimedAt = $row->claimed_at ? Carbon::parse($row->claimed_at) : null;
                // Prefer the claimed_at as the base for expiry. Fallback to HOT status timestamp.
                $baseAt = $claimedAt ?? $hotAt;
                $expireAt = $baseAt ? $baseAt->copy()->startOfDay()->addDays(30) : null;
                $daysLeft = null;
                if ($expireAt) {
                    // If the claim date is in the future, show full 30 days until it starts.
                    if ($baseAt->gt(Carbon::now())) {
                        $daysLeft = 30;
                    } else {
                        // compute days remaining until expiry, clamp at 0
                        $daysLeft = Carbon::now()->startOfDay()->diffInDays($expireAt, false);
                        if ($daysLeft < 0) {
                            $daysLeft = 0;
                        }
                    }
                }

                // determine meeting status label
                $meetingStatus = 'Hot';
                if ($daysLeft !== null) {
                    if ($daysLeft > 0) {
                        $meetingStatus = 'Hot';
                    } elseif ($daysLeft === 0) {
                        $meetingStatus = 'Today';
                    } else {
                        $meetingStatus = '<span class="status-expired">Hot</span>';
                    }
                }

                return [
                    'id' => $row->id,
                    'claimed_at' => $row->claimed_at,
                    'lead_id' => $row->lead->id,
                    'lead_name' => $row->lead->name,
                    'sales_id' => $row->sales->id ?? null,
                    'sales_name' => $row->sales->name ?? null,
                    'phone' => $row->lead->phone,
                    'needs' => $row->lead->needs,
                    'segment_name' => $row->lead->segment->name ?? null,
                    'source_name' => $row->lead->source->name ?? null,
                    'city_name' => $row->lead->region->name ?? 'All Regions',
                    'regional_name' => $row->lead->region->regional->name ?? 'All Regions',
                    'meeting_status' => $meetingStatus,
                    'expire_in' => $daysLeft,
                    'expire_at' => $expireAt ? $expireAt->toDateString() : null,
                    'industry' => $row->lead->industry->name ?? ($row->lead->other_industry ?? '-'),
                    'quotation' => $row->lead->quotation ? [
                        'id' => $row->lead->quotation->id,
                    ] : null,
                ];
            });

            $claims = $claims->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

            $data = $claims->map(function ($row) {

                // Prefer HOT status timestamp from logs; fallback to claimed_at
                $hotLog = $row->lead->statusLogs->first() ?? null;
                $hotAt = $hotLog && $hotLog->created_at ? Carbon::parse($hotLog->created_at) : null;
                $claimedAt = $row->claimed_at ? Carbon::parse($row->claimed_at) : null;
                // Prefer the claimed_at as the base for expiry. Fallback to HOT status timestamp.
                $baseAt = $claimedAt ?? $hotAt;
                $expireAt = $baseAt ? $baseAt->copy()->startOfDay()->addDays(30) : null;
                $daysLeft = null;
                if ($expireAt) {
                    if ($baseAt->gt(Carbon::now())) {
                        $daysLeft = 30;
                    } else {
                        $daysLeft = Carbon::now()->startOfDay()->diffInDays($expireAt, false);
                        if ($daysLeft < 0) {
                            $daysLeft = 0;
                        }
                    }
                }

                // determine meeting status label for paginated response
                $meetingStatus = '<span class="status-expired">Hot</span>';
                if ($daysLeft !== null) {
                    if ($daysLeft > 0) {
                        $meetingStatus = 'Hot';
                    } elseif ($daysLeft === 0) {
                        $meetingStatus = 'Today';
                    } else {
                        $meetingStatus = '<span class="status-expired">Hot</span>';
                    }
                }

                return [
                    'id' => $row->id,
                    'claimed_at' => $row->claimed_at,
                    'lead_name' => $row->lead->name ?? '',
                    'sales_name' => $row->sales->name ?? '-',
                    'phone' => $row->lead->phone ?? '-',
                    'segment_name' => $row->lead->segment->name ?? '',
                    'industry_name' => $row->lead->industry->name ?? null,
                    'other_industry' => $row->lead->other_industry ?? null,

                    // helper
                    'expire_in' => $daysLeft,
                    'expire_at' => $expireAt ? $expireAt->toDateString() : null,
                    'meeting_status' => $meetingStatus,
                    'actions' => $this->hotActions($row),
                ];
            });

            return response()->json([
                'data' => $data,
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'total' => $claims->total(),
            ]);
        }
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
                <i class="bi bi-file-earmark-plus mr-2"></i> Generate Quotation</a>';
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
