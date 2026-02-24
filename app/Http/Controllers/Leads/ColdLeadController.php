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

        $user = $request->user();
        $roleCode = $user->role?->code;
        $perPage = 10;

        $claimsQuery = LeadClaim::with([
            'lead.status',
            'lead.segment',
            'lead.source',
            'lead.region.regional',
            'lead.meetings.expense',
            'lead.industry',
            'sales'
        ])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::COLD))
            ->whereNull('released_at');

        // Role filtering
        if ($roleCode === 'sales') {
            $claimsQuery->where('sales_id', $user->id);
        } elseif ($roleCode === 'branch_manager') {
            $claimsQuery->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        // Pagination
        $claims = $claimsQuery
            ->orderByDesc('id')
            ->paginate($perPage);

        // API Response
        if ($request->is('api/*')) {
            $data = $claims->map(function ($row) {
                $meeting = $row->lead->meetings()->latest()->first();

                return [
                    'id' => $row->id,
                    'name' => $row->lead->name,
                    'sales_name' => $row->sales->name ?? '-',
                    'phone' => $row->lead->phone,
                    'source' => $row->lead->source->name ?? '-',
                    'needs' => $row->lead->needs,
                    'segment_name' => $row->lead->segment->name ?? '-',
                    'city_name' => $row->lead->region->name ?? 'All Regions',
                    'regional_name' => $row->lead->region->regional->name ?? 'All Regions',
                    'industry' => $row->lead->industry->name ?? ($row->lead->other_industry ?? '-'),
                    'meeting_status' => $this->coldMeetingStatus($row->lead, $meeting),
                    'actions' => $this->coldActions($row),
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


    public function meeting(Request $request, $claimId)
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

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $meeting,
                'lead_id' => $claim->lead_id,
                'claim_id' => $claimId,
                'expenseTypes' => $expenseTypes,
                'meetingTypes' => $meetingTypes,
                'products' => $products,
                'regions' => $regions,
                'isViewOnly' => $isViewOnly,
                'canReschedule' => $canReschedule,
                'rescheduleCount' => $rescheduleCount,
                'cities' => config('cities'),
            ]);
        }

        return $this->render('pages.leads.cold.meeting', [
            'data'          => $meeting,
            'lead_id'       => $claim->lead_id,
            'claim_id' => $claimId,
            'expenseTypes'  => $expenseTypes,
            'cities'        => config('cities'),
            'meetingTypes'  => $meetingTypes,
            'products'      => $products,
            'regions'       => $regions,
            'isViewOnly'    => $isViewOnly,
            'canReschedule' => $canReschedule,
        ]);
    }


    public function reschedule(Request $request, $meetingId)
    {
        $meeting = LeadMeeting::with('reschedules', 'lead.segment')->findOrFail($meetingId);

        $leadClaimId = LeadClaim::where('lead_id', $meeting->lead_id)
            ->value('id');

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

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $meeting,
                'lead_id' => $meeting->lead_id,
                'claim_id' => $leadClaimId,
                'expenseTypes' => $expenseTypes,
                'meetingTypes' => $meetingTypes,
                'products' => $products,
                'regions' => $regions,
                'isReschedule' => true,
                'isViewOnly' => false,
                'canReschedule' => false,
                'cities' => config('cities'),
            ]);
        }

        return $this->render('pages.leads.cold.meeting', [
            'data' => $meeting,
            'lead_id' => $meeting->lead_id,
            'claim_id' => $leadClaimId,
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

    protected function coldActions($row)
    {
        $meeting     = $row->lead->meetings()->latest()->first();
        $leadUrl     = route('leads.my.cold.manage', $row->lead_id);
        $trashUrl    = route('leads.my.cold.trash', $row->id);
        $setMeetUrl  = route('leads.my.cold.meeting', $row->id);
        $btnId       = 'actionsDropdown' . $row->id;

        $html  = '<div class="dropdown">';
        $html .= '  <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" id="' . $btnId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '    <i class="bi bi-three-dots"></i>';
        $html .= '  </button>';
        $html .= '  <div class="dropdown-menu dropdown-menu-right rounded-lg!" aria-labelledby="' . $btnId . '">';
        $html .= '    <a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($leadUrl) . '">
            ' . view('components.icon.detail')->render() . '
            View Lead Detail</a>';
        $activityUrl = route('leads.activity.logs', $row->lead_id);
        $html .= '    <button type="button" class="dropdown-item btn-activity-log cursor-pointer flex! items-center! gap-2! text-[#1E1E1E]!" data-url="' . e($activityUrl) . '">
            ' . view('components.icon.log')->render() . '
        View / Add Activity Log</button>';

        if (! $meeting) {
            $html .= '  <a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($setMeetUrl) . '">
            ' . view('components.icon.meeting')->render() . '
            Set Meeting</a>';
        } else {
            $viewUrl       = route('leads.my.cold.meeting', $row->id);
            $rescheduleUrl = route('leads.my.cold.meeting.reschedule', $meeting->id);
            $resultUrl     = route('leads.my.cold.meeting.result', $meeting->id);
            $cancelUrl     = route('leads.my.cold.meeting.cancel', $meeting->id);

            $html .= '  <a class="dropdown-item" href="' . e($viewUrl) . '"><i class="bi bi-calendar-event mr-2"></i> View Meeting</a>';

            // Cancel condition
            if (!in_array(optional($meeting->expense)->status, ['submitted', 'canceled']) && is_null($meeting->result)) {
                $html .= '  <button class="dropdown-item text-[#900B09]! cancel-meeting cursor-pointer" data-url="' . e($cancelUrl) . '" data-online="' . ($meeting->is_online ? 1 : 0) . '" data-status="' . (optional($meeting->expense)->status ?? '') . '">'
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
                $html .= '  <a class="dropdown-item text-[#02542D]!" href="' . e($resultUrl) . '"><i class="bi bi-check2-square mr-2"></i> Set Result</a>';
            }
        }

        if (! $meeting) {
            $html .= '  <button class="dropdown-item text-danger trash-lead cursor-pointer flex! items-center! gap-2! text-[#900B09]!" data-url="' . e($trashUrl) . '">
            ' . view('components.icon.trash')->render() . '
            Move to Trash Lead</button>';
        }
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    protected function coldMeetingStatus($leadOrMeeting, $meeting = null)
    {
        // Accept either ($lead, $meeting) or ($meeting) for backward compatibility
        if ($leadOrMeeting instanceof \App\Models\Leads\Lead) {
            $lead = $leadOrMeeting;
        } else {
            $meeting = $leadOrMeeting;
            $lead = $meeting?->lead;
        }

        // No meeting -> check initiation (A01..A04) or raw
        if (! $meeting) {
            $initCodes = ['A01', 'A02', 'A03', 'A04'];

            $hasInitiation = $lead
                ? $lead->activityLogs()->whereHas('activity', function ($q) use ($initCodes) {
                    $q->whereIn('code', $initCodes);
                })->exists()
                : false;

            if ($hasInitiation) {
                return '<span class="status-grey">Initiation</span>';
            }

            return '<span class="status-grey">Raw Lead</span>';
        }

        $scheduledEnd = \Carbon\Carbon::parse($meeting->scheduled_end_at);

        $isExpired  = $scheduledEnd->copy()->addDays(30) < now();
        $isFinished = $scheduledEnd < now();

        $expiredBadge = $isExpired ? '<span class="status-expired">Expired</span>' : '';

        // Offline + expense: pending or rejected
        if ($meeting->expense && $meeting->is_online == 0) {
            if ($meeting->expense->status === 'submitted') {
                return $expiredBadge . '<span class="status-waiting">Pending Finance approval</span>';
            }

            if ($meeting->expense->status === 'rejected') {
                $note = $meeting->expense->financeRequest?->notes;
                $by = $meeting->expense->financeRequest?->rejected_by_name ?? 'Finance';

                $html = $expiredBadge . '<span class="status-expired">Rejected by ' . e($by) . '</span>';

                if ($note) {
                    $html .= '<div class="text-danger small mt-1">' . e($note) . '</div>';
                }

                return $html;
            }
        }

        // Rescheduled — only show if meeting has not finished
        $reschedulesCount = $meeting->reschedules()->count();
        if ($reschedulesCount > 0 && ! $isFinished) {
            return $expiredBadge
                . '<span class="status-waiting">Rescheduled (' . $reschedulesCount . 'x)</span>';
        }

        // Waiting for consideration (result = waiting)
        if ($meeting->result === 'waiting') {
            return $expiredBadge
                . '<span class="status-waiting">Waiting for consideration</span>';
        }

        // Meeting finished but result not set
        if ($isFinished && is_null($meeting->result)) {
            return $expiredBadge . '<span class="status-finish">Meeting Finished</span>';
        }

        // Meeting scheduled and upcoming
        if (! $isExpired && ! $isFinished) {
            return '<span class="status-finish">Meeting Scheduled</span>';
        }

        return $expiredBadge;
    }
}
