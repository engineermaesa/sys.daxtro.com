<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Models\Orders\Quotation;
use App\Models\Orders\Invoice;
use App\Models\Leads\LeadSource;

class DashSummaryController extends Controller
{
    public function grid(): JsonResponse
    {
        $totalLeads = Lead::count();
        $totalAcquisition = Lead::where('status_id', LeadStatus::DEAL)->count();
        $totalMeeting = Lead::has('meetings')->count();
        $totalQuotation = Quotation::count();
        $totalInvoice = Invoice::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'all' => $totalLeads,
                'acquisition' => $totalAcquisition,
                'meeting' => $totalMeeting,
                'quotation' => $totalQuotation,
                'invoice' => $totalInvoice,
            ],
        ]);
    }

    public function SourceConversionLists()
    {
        $rows = LeadSource::leftJoin('leads', 'lead_sources.id', '=', 'leads.source_id')
            ->selectRaw("
            lead_sources.name as source,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as cold,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as warm,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as hot,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as deal
        ", [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL
            ])
            ->groupBy('lead_sources.id', 'lead_sources.name')
            ->orderBy('lead_sources.name')
            ->get()
            ->map(function ($row) {

                $cold = (int) $row->cold;
                $warm = (int) $row->warm;
                $hot  = (int) $row->hot;
                $deal = (int) $row->deal;

                $total = $cold + $warm + $hot + $deal;

                return [
                    'source' => $row->source,
                    'cum'    => $total,
                    'cold'   => $cold,
                    'warm'   => $warm,
                    'hot'    => $hot,
                    'deal'   => $deal,
                    'total'  => $total,
                ];
            });

        // 🔥 GRAND TOTAL
        $grandCold = $rows->sum('cold');
        $grandWarm = $rows->sum('warm');
        $grandHot  = $rows->sum('hot');
        $grandDeal = $rows->sum('deal');

        $rows->push([
            'source' => 'Total',
            'cum'    => $grandCold + $grandWarm + $grandHot + $grandDeal,
            'cold'   => $grandCold,
            'warm'   => $grandWarm,
            'hot'    => $grandHot,
            'deal'   => $grandDeal,
            'total'  => $grandCold + $grandWarm + $grandHot + $grandDeal,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $rows,
        ]);
    }
}
