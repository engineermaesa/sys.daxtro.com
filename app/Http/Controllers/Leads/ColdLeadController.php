<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Classes\ActivityLogger;
use App\Services\AutoTrashService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Leads\{Lead, LeadClaim, LeadMeeting, LeadStatus, LeadStatusLog, LeadSource, LeadSegment};
use App\Models\Masters\{Branch, Region, ExpenseType, Product};
use Illuminate\Support\Facades\DB;

class ColdLeadController extends Controller
{ 
    public function myColdList(Request $request)
    {
        // Trigger auto-trash if needed (non-blocking)
        AutoTrashService::triggerIfNeeded();
        
        $tenDaysAgo = now()->subDays(10); // Tanggal 10 hari yang lalu

        $claims = LeadClaim::with([
                'lead.status',
                'lead.industry',
                'lead.segment',
                'lead.source',
                'lead.region.regional',
                'lead.meetings.expense',
                'sales'
            ])
            ->whereHas('lead', fn ($q) => $q->where('status_id', LeadStatus::COLD))
            ->whereNull('released_at')
            ->where('claimed_at', '>=', $tenDaysAgo); // Filter: claimed_at tidak lebih dari 10 hari

        $roleCode = $request->user()->role?->code;

        if ($roleCode === 'sales') {
            $claims->where('sales_id', $request->user()->id);
        } elseif ($roleCode === 'branch_manager') {
            $claims->whereHas('sales', function ($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            });
        }


        return DataTables::of($claims)
            ->addColumn('name', fn ($row) => $row->lead->name)
            ->addColumn('sales_name', fn ($row) => $row->sales->name ?? '-')
            ->addColumn('phone', fn ($row) => $row->lead->phone)
            ->addColumn('source', fn ($row) => $row->lead->source->name ?? '-')
            ->addColumn('needs', fn ($row) => $row->lead->needs)
            ->addColumn('industry_name', fn ($row) => $row->lead->industry->name ?? null)
            ->addColumn('other_industry_name', fn ($row) => $row->lead->other_industry->name ?? null)
            ->addColumn('segment_name', fn ($row) => $row->lead->segment->name ?? '')
            ->addColumn('city_name', fn ($row) => $row->lead->region->name ?? 'All Regions')
            ->addColumn('regional_name', fn ($row) => $row->lead->region->regional->name ?? 'All Regions')
            ->addColumn('meeting_status', function ($row) {
                $meeting = $row->lead->meetings()->latest()->first();

                if (!$meeting) {
                    return '<span class="badge bg-secondary">Not Scheduled</span>';
                }

                $scheduledEnd = \Carbon\Carbon::parse($meeting->scheduled_end_at);
                $isExpired = $scheduledEnd->copy()->addDays(30) < now();
                $isFinished = $scheduledEnd < now();

                $expiredBadge = $isExpired ? '<span class="badge bg-dark mr-1">Expired</span>' : '';
                $finishedBadge = (!$isExpired && $isFinished) ? '<span class="badge bg-success mr-1">Finished</span>' : '';

                // If offline + ada expense
                if ($meeting->expense && $meeting->is_online == 0) {
                    if ($meeting->expense->status === 'submitted') {
                        return $expiredBadge . '<span class="badge bg-warning">Awaiting Finance Approval</span>';
                    } elseif ($meeting->expense->status === 'rejected') {
                        $note = $meeting->expense->financeRequest?->notes;
                        $html = $expiredBadge . '<span class="badge bg-danger">Rejected by Finance</span>';
                        if ($note) {
                            $html .= '<div class="text-danger small mt-1"><i class="bi bi-info-circle-fill"></i> ' . e($note) . '</div>';
                        }
                        return $html;
                    }
                }

                // Jika pernah dijadwal ulang
                if ($meeting->reschedules()->count() > 0) {
                    return $expiredBadge . $finishedBadge . '<span class="badge bg-info">Rescheduled (' . $meeting->reschedules->count() . 'x)</span>';
                }

                if ($meeting->result === 'waiting') {
                    return $expiredBadge . $finishedBadge . '<span class="badge bg-info">Waiting for Consideration</span>';
                }

                // Tampilkan "Meeting Set" hanya jika belum expired
                if (!$isExpired && !$isFinished) {
                    return '<span class="badge bg-info">Meeting Set</span>';
                }

                // Jika expired atau selesai tapi tidak punya status khusus
                return $expiredBadge . $finishedBadge;
            })
           ->addColumn('actions', function ($row) {
                $meeting     = $row->lead->meetings()->latest()->first();
                $leadUrl     = route('leads.my.cold.manage', $row->lead_id);
                $trashUrl    = route('leads.my.cold.trash', $row->id);
                $setMeetUrl  = route('leads.my.cold.meeting', $row->id);
                $btnId       = 'actionsDropdown' . $row->id;

                $html  = '<div class="dropdown">';
                $html .= '  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $html .= '    <i class="bi bi-three-dots-vertical"></i> Actions';
                $html .= '  </button>';
                $html .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="' . $btnId . '">';
                $html .= '    <a class="dropdown-item" href="' . e($leadUrl) . '"><i class="bi bi-eye mr-2"></i> View Lead</a>';
                $activityUrl = route('leads.activity.logs', $row->lead_id);
                $html .= '    <button type="button" class="dropdown-item btn-activity-log" data-url="' . e($activityUrl) . '"><i class="bi bi-list-check mr-2"></i> View / Add Activity</button>';

                if (! $meeting) {
                    $html .= '  <a class="dropdown-item" href="' . e($setMeetUrl) . '"><i class="bi bi-calendar-plus mr-2"></i> Set Meeting</a>';
                } else {
                    $viewUrl       = route('leads.my.cold.meeting', $row->id);
                    $rescheduleUrl = route('leads.my.cold.meeting.reschedule', $meeting->id);
                    $resultUrl     = route('leads.my.cold.meeting.result', $meeting->id);
                    $cancelUrl     = route('leads.my.cold.meeting.cancel', $meeting->id);

                    $html .= '  <a class="dropdown-item" href="' . e($viewUrl) . '"><i class="bi bi-calendar-event mr-2"></i> View Meeting</a>';

                    // Cancel condition
                    if (!in_array(optional($meeting->expense)->status, ['submitted', 'canceled']) && is_null($meeting->result)) {
                        $html .= '  <button class="dropdown-item text-warning cancel-meeting" data-url="' . e($cancelUrl) . '" data-online="' . ($meeting->is_online ? 1 : 0) . '" data-status="' . (optional($meeting->expense)->status ?? '') . '">'
                            . '    <i class="bi bi-x-circle mr-2"></i> Cancel Meeting</button>';
                    }

                    // Reschedule condition
                    $canSetResult = $meeting->is_online || ($meeting->expense && $meeting->expense->status === 'approved');
                    $canReschedule = !$canSetResult
                        && optional($meeting->expense)->status !== 'submitted';

                    if ($canReschedule) {
                        $html .= '  <a class="dropdown-item" href="' . e($rescheduleUrl) . '"><i class="bi bi-arrow-repeat mr-2"></i> Reschedule</a>';
                    }

                    // Set Result condition
                    if (now()->gt($meeting->scheduled_end_at) && ($meeting->result === null || $meeting->result === 'waiting') && $canSetResult) {
                        $html .= '  <a class="dropdown-item text-success" href="' . e($resultUrl) . '"><i class="bi bi-check2-square mr-2"></i> Set Result</a>';
                    }
                }

                if (! $meeting) {
                    $html .= '  <button class="dropdown-item text-danger trash-lead" data-url="' . e($trashUrl) . '"><i class="bi bi-trash mr-2"></i> Trash Lead</button>';
                }
                $html .= '  </div>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['meeting_status', 'actions'])
            ->orderColumn('id', 'id $1')
            ->make(true);
    }

    public function meeting($claimId)
    {
        $claim = LeadClaim::with('lead')->findOrFail($claimId);
        $meeting = $claim->lead->meetings()->latest()->first();
        $expenseTypes = ExpenseType::all();
        $meetingTypes = \App\Models\Masters\MeetingType::all();
        
        // Get products with default segment pricing
        $segmentName = strtolower($claim->lead->segment->name ?? '');
        $priceField = match ($segmentName) {
            'fob' => 'fob_price',
            'bdi' => 'bdi_price',
            'government' => 'government_price',
            'corporate'  => 'corporate_price',
            default      => 'personal_price',
        };

        $products = Product::all()->map(function ($product) use ($priceField) {
            $product->price = $product->{$priceField};
            return $product;
        });

        $regions = Region::with('province:id,name')
            ->get(['id', 'name', 'province_id', 'branch_id']);

        $rescheduleCount = $meeting?->reschedules()->count() ?? 0;

        $isViewOnly = $meeting !== null;
        $canReschedule = (bool) $meeting;

        if ($meeting && $meeting->expense && $meeting->expense->status === 'submitted') {
            $canReschedule = false;
        }

        return $this->render('pages.leads.cold.meeting', [
            'data'          => $meeting,
            'lead_id'       => $claim->lead_id,
            'expenseTypes'  => $expenseTypes,
            'cities'        => config('cities'),
            'meetingTypes'  => $meetingTypes,
            'products'      => $products,
            'regions'       => $regions,
            'isViewOnly'    => $isViewOnly,
            'canReschedule' => $canReschedule,
        ]);
    }


    public function reschedule($meetingId)
    {
        $meeting = LeadMeeting::with('reschedules', 'lead.segment')->findOrFail($meetingId);
        $expenseTypes = ExpenseType::all();
        $meetingTypes = \App\Models\Masters\MeetingType::all();
        
        // Get products with default segment pricing
        $segmentName = strtolower($meeting->lead->segment->name ?? '');
        $priceField = match ($segmentName) {
            'fob' => 'fob_price',
            'bdi' => 'bdi_price',
            'government' => 'government_price',
            'corporate'  => 'corporate_price',
            default      => 'personal_price',
        };

        $products = Product::all()->map(function ($product) use ($priceField) {
            $product->price = $product->{$priceField};
            return $product;
        });

        $regions = Region::with('province:id,name')
            ->get(['id', 'name', 'province_id', 'branch_id']);

        return $this->render('pages.leads.cold.meeting', [
            'data' => $meeting,
            'lead_id' => $meeting->lead_id,
            'expenseTypes' => $expenseTypes,
            'cities' => config('cities'),
            'meetingTypes' => $meetingTypes,
            'products' => $products,
            'regions' => $regions,
            'isReschedule' => true,
            'isViewOnly' => false,
            'canReschedule' => false,
        ]);
    }

    public function trash($claimId)
    {
        $claim = LeadClaim::with('lead')->findOrFail($claimId);

        request()->validate([
            'note' => 'required|string',
        ]);

        DB::transaction(function () use ($claim) {
            $lead = $claim->lead;

            $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
            if (! $lead->first_sales_id && $firstClaim) {
                $lead->first_sales_id = $firstClaim->sales_id;
            }

            $lead->update(['status_id' => LeadStatus::TRASH_COLD]);

            $claim->update([
                'released_at' => now(),
                'trash_note'  => request('note'),
            ]);

            LeadStatusLog::create([
                'lead_id'   => $lead->id,
                'status_id' => LeadStatus::TRASH_COLD,
            ]);
        });

        return $this->setJsonResponse('Lead moved to trash');
    }


    public function save(Request $request, $id = null)
    {
        $request->validate([
            'source_id'     => 'required',
            'segment_id'    => 'required',
            'region_id'     => 'required',
            'name'          => 'required',
            'phone'         => 'required',
            'email'         => 'required',
        ]);

        $lead = $id ? Lead::findOrFail($id) : new Lead();
        $before = $id ? $lead->toArray() : null;

        $lead->source_id    = $request->source_id;
        $lead->segment_id   = $request->segment_id;
        $lead->region_id    = $request->region_id;
        $lead->status_id    = LeadStatus::PUBLISHED;
        $lead->name         = $request->name;
        $lead->phone        = $request->phone;
        $lead->email        = $request->email;
        $lead->published_at = $id ? $lead->published_at : now();
        $lead->save();

        $after = $lead->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_lead' : 'create_lead',
            $id ? 'Updated lead' : 'Created new lead',
            $lead,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Lead saved successfully');
    }
}
