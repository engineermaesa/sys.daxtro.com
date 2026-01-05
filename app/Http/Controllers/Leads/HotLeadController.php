<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus};
use Yajra\DataTables\Facades\DataTables;

class HotLeadController extends Controller
{
    public function myHotList(Request $request)
    {
        $claims = LeadClaim::with(['lead.status', 'lead.segment', 'lead.source', 'lead.industry'])
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

        return DataTables::of($claims)
            ->addColumn('claimed_at', fn($row) => $row->claimed_at)
            ->addColumn('lead_name', fn($row) => $row->lead->name)
            ->addColumn('segment_name', fn($row) => $row->lead->segment->name ?? '-')
            ->addColumn('source_name', fn($row) => $row->lead->source->name ?? '-')
            ->addColumn('meeting_status', fn() => '<span class="badge bg-danger">Hot</span>')
            ->addColumn('industry', function ($row) {
                return $row->lead->industry->name ?? ($row->lead->other_industry ?? '-');
            })
            ->addColumn('actions', function ($row) {
                $quotation = $row->lead->quotation;
                $viewUrl   = route('leads.manage.form', $row->lead->id);
                $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
                $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;

                $btnId = 'hotActionsDropdown' . $row->id;

                $html  = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle"'
                    . ' type="button" id="' . $btnId . '"'
                    . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                $html .= '    <a class="dropdown-item" href="' . e($viewUrl) . '">'
                    . '      <i class="bi bi-eye mr-2"></i> View Lead</a>';
                $activityUrl = route('leads.activity.logs', $row->lead->id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> View / Add Activity</button>';

                if (! $quotation) {
                    $html .= '  <a class="dropdown-item" href="' . route('leads.my.warm.quotation.create', $row->id) . '">'
                        . '    <i class="bi bi-file-earmark-plus mr-2"></i> Generate Quotation</a>';
                } else {
                    $html .= '  <a class="dropdown-item" href="' . e($quoteUrl) . '">'
                        . '    <i class="bi bi-file-earmark-text mr-2"></i> View Quotation</a>';
                    $html .= '  <a class="dropdown-item" href="' . e($downloadUrl) . '">'
                        . '    <i class="bi bi-download mr-2"></i> Download</a>';
                }

                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->make(true);
    }
}
