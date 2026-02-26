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
            ->selectRaw(
                "
        lead_sources.name as source,
        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as cold,
        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as warm,
        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as hot,
        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as deal",
                [
                    LeadStatus::COLD,
                    LeadStatus::WARM,
                    LeadStatus::HOT,
                    LeadStatus::DEAL
                ]
            )
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

                    // sementara isi dulu (nanti dioverride)
                    'persen_cum' => '0,0',

                    'cold'   => $cold,
                    'persen_cold' => $total > 0 ? number_format(($cold / $total) * 100, 1, ',', '') : '0,0',

                    'warm'   => $warm,
                    'persen_warm' => $total > 0 ? number_format(($warm / $total) * 100, 1, ',', '') : '0,0',

                    'hot'    => $hot,
                    'persen_hot'  => $total > 0 ? number_format(($hot / $total) * 100, 1, ',', '') : '0,0',

                    'deal'   => $deal,
                    'persen_deal' => $total > 0 ? number_format(($deal / $total) * 100, 1, ',', '') : '0,0',

                    'total_source'  => $total,
                ];
            });

        // 🔥 GRAND TOTAL
        $grandCold = $rows->sum('cold');
        $grandWarm = $rows->sum('warm');
        $grandHot  = $rows->sum('hot');
        $grandDeal = $rows->sum('deal');
        $grandTotal = $grandCold + $grandWarm + $grandHot + $grandDeal;

        // 🔥 UPDATE persen_cum pakai GRAND TOTAL
        $rows = $rows->map(function ($row) use ($grandTotal) {

            if ($row['source'] !== 'Total') {
                $row['persen_cum'] = $grandTotal > 0
                    ? number_format(($row['total_source'] / $grandTotal) * 100, 1, ',', '')
                    : '0,0';
            }

            return $row;
        });

        $rows->push([
            'source' => 'Total',
            'cum'    => $grandTotal,
            'cold'   => $grandCold,
            'warm'   => $grandWarm,
            'hot'    => $grandHot,
            'deal'   => $grandDeal,
            'total_stage'  => $grandTotal,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $rows,
        ]);
    }

    public function SalesSegmentPerformance()
    {
        $rows = Lead::join('lead_segments', 'lead_segments.id', '=', 'leads.segment_id')
            ->selectRaw("
            lead_segments.name as segment,
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
            ->groupBy('lead_segments.id', 'lead_segments.name')
            ->orderBy('lead_segments.name')
            ->get()
            ->map(function ($row) {

                $cold = (int) $row->cold;
                $warm = (int) $row->warm;
                $hot  = (int) $row->hot;
                $deal = (int) $row->deal;

                $total = $cold + $warm + $hot + $deal;

                return [
                    'segment' => $row->segment,
                    'cum'     => $total,

                    // sementara isi dulu (dioverride nanti)
                    'persen_cum' => '0,0',

                    'cold'   => $cold,
                    'persen_cold' => $total > 0 ? number_format(($cold / $total) * 100, 1, ',', '') : '0,0',

                    'warm'   => $warm,
                    'persen_warm' => $total > 0 ? number_format(($warm / $total) * 100, 1, ',', '') : '0,0',

                    'hot'    => $hot,
                    'persen_hot'  => $total > 0 ? number_format(($hot / $total) * 100, 1, ',', '') : '0,0',

                    'deal'   => $deal,
                    'persen_deal' => $total > 0 ? number_format(($deal / $total) * 100, 1, ',', '') : '0,0',

                    'total_segment' => $total,
                ];
            });

        // 🔥 GRAND TOTAL
        $grandCold = $rows->sum('cold');
        $grandWarm = $rows->sum('warm');
        $grandHot  = $rows->sum('hot');
        $grandDeal = $rows->sum('deal');
        $grandTotal = $grandCold + $grandWarm + $grandHot + $grandDeal;

        // 🔥 UPDATE persen_cum pakai GRAND TOTAL
        $rows = $rows->map(function ($row) use ($grandTotal) {

            if ($row['segment'] !== 'Total') {
                $row['persen_cum'] = $grandTotal > 0
                    ? number_format(($row['total_segment'] / $grandTotal) * 100, 1, ',', '')
                    : '0,0';
            }

            return $row;
        });

        $rows->push([
            'segment' => 'Total',
            'cum'     => $grandTotal,
            'cold'    => $grandCold,
            'warm'    => $grandWarm,
            'hot'     => $grandHot,
            'deal'    => $grandDeal,
            'total_stage' => $grandTotal,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $rows,
        ]);
    }

    public function SourceMonitoringChart(): JsonResponse
    {
        $year  = request()->get('year', now()->year);
        $month = request()->get('month'); // optional

        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        $rawData = LeadSource::leftJoin('leads', function ($join) use ($year, $month) {
            $join->on('lead_sources.id', '=', 'leads.source_id')
                ->whereYear('leads.created_at', '=', $year);

            if ($month) {
                $join->whereMonth('leads.created_at', '=', $month);
            }
        })
            ->selectRaw("
            lead_sources.name as source,
            MONTH(leads.created_at) as month,
            COUNT(leads.id) as total
        ")
            ->groupBy('lead_sources.id', 'lead_sources.name', 'month')
            ->get();

        $sources = LeadSource::pluck('name');

        $result = [];

        foreach ($sources as $source) {

            // kalau filter month → hanya 1 bulan
            if ($month) {
                $monthlyData = [0];

                foreach ($rawData as $row) {
                    if ($row->source === $source) {
                        $monthlyData[0] = (int) $row->total;
                    }
                }
            } else {
                // full 12 bulan
                $monthlyData = array_fill(1, 12, 0);

                foreach ($rawData as $row) {
                    if ($row->source === $source && $row->month) {
                        $monthlyData[$row->month] = (int) $row->total;
                    }
                }

                $monthlyData = array_values($monthlyData);
            }

            $result[] = [
                'source' => $source,
                'data'   => $monthlyData
            ];
        }

        return response()->json([
            'status' => 'success',
            'year'   => (int) $year,
            'labels' => $month ? [$monthNames[$month]] : array_values($monthNames),
            'data'   => $result
        ]);
    }
}
