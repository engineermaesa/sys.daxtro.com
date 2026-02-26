<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus};
use Yajra\DataTables\Facades\DataTables;

class DealLeadController extends Controller
{
    public function myDealList(Request $request)
    {
        $claims = LeadClaim::with(['lead.status', 'lead.segment', 'lead.source', 'lead.industry', 'sales', 'lead.region.regional', 'lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL))
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
                $q->firstTermPaidBetween($request->start_date, $request->end_date);
            });
        }

        $perPage = $request->get('per_page', 10);

        $paginated = $claims->paginate($perPage);

        $items = collect($paginated->items())->map(function ($row) {
            $quotation = $row->lead->quotation;
            $totalPayments = $quotation?->proformas?->count() ?? 0;
            $approvedPayments = 0;
            if ($quotation && $quotation->proformas) {
                $approvedPayments = collect($quotation->proformas)->filter(function ($p) {
                    return $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at;
                })->count();
            }
            return [
                'id' => $row->id,
                'claimed_at' => $row->claimed_at,
                'lead_id' => $row->lead->id,
                'lead_name' => $row->lead->name,
                'sales_name' => $row->sales->name ?? null,
                'phone' => $row->lead->phone,
                'needs' => $row->lead->needs,
                'segment_name' => $row->lead->segment->name ?? null,
                'source_name' => $row->lead->source->name ?? null,
                'city_name' => $row->lead->region->name ?? 'All Regions',
                'regional_name' => $row->lead->region->regional->name ?? 'All Regions',
                'meeting_status' => '<span class="status-finish">Deal</span>',
                'industry' => $row->lead->industry->name ?? ($row->lead->other_industry ?? '-'),
                'payments' => $approvedPayments . ' / ' . $totalPayments,
                'actions' => $this->dealActions($row),
            ];
        });

        return response()->json([
            'data' => $items,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    protected function dealActions($row)
    {
        $quotation = $row->lead->quotation;
        $viewUrl   = route('leads.manage.form', $row->lead->id);
        $quoteUrl  = $quotation ? route('quotations.show', $quotation->id) : null;
        $downloadUrl = $quotation ? route('quotations.download', $quotation->id) : null;

        $btnId = 'dealActionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"'
            . ' type="button" id="' . $btnId . '"'
            . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2!" href="' . e($viewUrl) . '">'
            . '
            '.view('components.icon.detail')->render().'
            View Lead</a>';
        $activityUrl = route('leads.activity.logs', $row->lead->id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log flex! items-center! gap-2!" data-url="' . e($activityUrl) . '">
        '.view('components.icon.log')->render().'
        View / Add Activity</button>';

        if (! $quotation) {
            $html .= '  <a class="dropdown-item" href="' . route('leads.my.warm.quotation.create', $row->id) . '">'
                . '    <i class="bi bi-file-earmark-plus mr-2"></i> Generate Quotation</a>';
        } else {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($quoteUrl) . '">'
                . '    
                '.view('components.icon.view-quotation')->render().'
                View Quotation</a>';
            $html .= '  <a class="dropdown-item flex! items-center! gap-2!" href="' . e($downloadUrl) . '">'
                . '    
                '.view('components.icon.download').' 
                Download</a>';
        }

        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }
}
