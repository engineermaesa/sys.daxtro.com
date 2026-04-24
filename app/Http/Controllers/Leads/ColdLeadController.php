<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Classes\ActivityLogger;
use App\Services\AutoTrashService;
use App\Services\MyLeadQueryService;
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

        $perPage  = $request->get('per_page', 10);

        $claimsQuery = MyLeadQueryService::baseClaimsQuery($request, LeadStatus::COLD, [
            'lead.status',
            'lead.segment',
            'lead.source',
            'lead.region.regional',
            'lead.meetings.expense',
            'lead.industry',
            'sales'
        ]);

        $paginated = $claimsQuery
            ->orderByDesc('lead_claims.claimed_at')
            ->orderByDesc('lead_claims.id')
            ->paginate($perPage);
        
        $cityIds = $paginated->getCollection()
            ->pluck('lead.factory_city_id')
            ->filter()
            ->unique()
            ->values();

        $cities = collect();
        $regionals = collect();
        $provinces = collect();

        if ($cityIds->isNotEmpty()) {
            $cities = DB::table('ref_regions')
                ->whereIn('id', $cityIds)
                ->select('id', 'name', 'regional_id', 'province_id')
                ->get()
                ->keyBy('id');

            $regionalIds = $cities->pluck('regional_id')->filter()->unique()->values();
            $provinceIds = $cities->pluck('province_id')->filter()->unique()->values();

            if ($regionalIds->isNotEmpty()) {
                $regionals = DB::table('ref_regionals')
                    ->whereIn('id', $regionalIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }

            if ($provinceIds->isNotEmpty()) {
                $provinces = DB::table('ref_provinces')
                    ->whereIn('id', $provinceIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
            }
        }

        $paginated->getCollection()->transform(function ($row) use ($cities, $regionals, $provinces){

            $lead    = $row->lead;
            $meeting = $lead->meetings()->latest()->first();
            $city = $lead ? $cities->get($lead->factory_city_id) : null;

            if ($lead) {
                $lead->alternate_location = $city ? [
                    'region_id' => $city->id,
                    'region_name' => $city->name,
                    'regional_id' => $city->regional_id,
                    'regional_name' => optional($regionals->get($city->regional_id))->name,
                    'province_id' => $city->province_id,
                    'province_name' => optional($provinces->get($city->province_id))->name,
                ] : null;
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

            $row->meeting_status = $this->coldMeetingStatus($lead, $meeting);
            $row->actions        = $this->coldActions($row);

            return $row;
        });

        return response()->json([
            'data'         => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
        ]);
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
