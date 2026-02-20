<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus};
use Carbon\Carbon;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $roleCode = $user?->role?->code;

        // helper to apply role filters similar to other controllers
        $applyRoleFilter = function ($query) use ($roleCode, $user) {
            if ($roleCode === 'sales') {
                $query->where('sales_id', $user->id);
            } elseif ($roleCode === 'branch_manager') {
                $query->whereHas('sales', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                });
            }
        };

        // -- COLD --
        $coldBase = LeadClaim::whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::COLD))
            ->whereNull('released_at');
        $applyRoleFilter($coldBase);

        $totalCold = $coldBase->count();

        $initCodes = ['A01', 'A02', 'A03', 'A04'];
        $initiation = (clone $coldBase)
            ->whereHas('lead.activityLogs.activity', fn($q) => $q->whereIn('code', $initCodes))
            ->whereDoesntHave('lead.meetings')
            ->count();

        // Raw: no initiation and no meetings
        $rawCold = (clone $coldBase)
            ->whereDoesntHave('lead.activityLogs.activity', fn($q) => $q->whereIn('code', $initCodes))
            ->whereDoesntHave('lead.meetings')
            ->count();

        $pendingApprovalCold = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('is_online', 0)
                ->whereHas('expense', fn($qe) => $qe->where('status', 'submitted')))
            ->count();

        $pendingCold = $pendingApprovalCold;
        $rejectedCold = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('is_online', 0)
                ->whereHas('expense', fn($qe) => $qe->where('status', 'rejected')))
            ->count();

        // approval_status is the sum of pending + rejected
        $approvalStatusCold = $pendingCold + $rejectedCold;

        // Meeting scheduled split by online/offline
        $meetOnlineCold = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('scheduled_end_at', '>', Carbon::now())
                ->where('is_online', 1))
            ->count();

        $meetOfflineCold = (clone $coldBase)
            ->whereHas('lead.meetings', fn($q) => $q->where('scheduled_end_at', '>', Carbon::now())
                ->where('is_online', 0))
            ->count();

        $meetingScheduledCold = $meetOnlineCold + $meetOfflineCold;

        // -- WARM --
        $warmBase = LeadClaim::whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::WARM))
            ->whereNull('released_at');
        $applyRoleFilter($warmBase);

        $totalWarm = $warmBase->count();

        $warmPending = (clone $warmBase)
            ->whereHas('lead.quotation', fn($q) => $q->whereIn('status', ['review', 'pending_finance']))
            ->count();

        $warmRejected = (clone $warmBase)
            ->whereHas('lead.quotation', fn($q) => $q->where('status', 'rejected'))
            ->count();

        // approval_status for warm is pending (review|pending_finance) + rejected
        $approvalStatusWarm = $warmPending + $warmRejected;

        $warmPublished = (clone $warmBase)
            ->whereHas('lead.quotation', fn($q) => $q->where('status', 'published'))
            ->count();

        // -- HOT -- (we compute days-left per claim similar to HotLeadController)
        $hotBase = LeadClaim::with(['lead.statusLogs'])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::HOT))
            ->whereNull('released_at');
        $applyRoleFilter($hotBase);

        $hotClaims = $hotBase->get();
        $totalHot = $hotClaims->count();
        $expiring7 = 0;
        $expiring8plus = 0;

        foreach ($hotClaims as $claim) {
            $hotLog = $claim->lead->statusLogs->first() ?? null;
            $hotAt = $hotLog?->created_at ? Carbon::parse($hotLog->created_at) : null;
            $claimedAt = $claim->claimed_at ? Carbon::parse($claim->claimed_at) : null;
            $baseAt = $claimedAt ?? $hotAt;
            if (! $baseAt) {
                continue;
            }
            $expireAt = $baseAt->copy()->startOfDay()->addDays(30);
            $daysLeft = Carbon::now()->startOfDay()->diffInDays($expireAt, false);
            if ($daysLeft < 0) {
                $daysLeft = 0;
            }

            if ($daysLeft <= 7) {
                $expiring7++;
            } else {
                $expiring8plus++;
            }
        }

        // -- DEAL --
        $dealBase = LeadClaim::whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL))
            ->whereNull('released_at');
        $applyRoleFilter($dealBase);
        $totalDeal = $dealBase->count();

        return response()->json([
            'cold' => [
                'total' => $totalCold,
                'initiation' => $initiation,
                'raw' => $rawCold,
                'approval_status' => $approvalStatusCold,
                'pending' => $pendingCold,
                'rejected' => $rejectedCold,
                'meet_online' => $meetOnlineCold,
                'meet_offline' => $meetOfflineCold,
                'meeting_scheduled' => $meetingScheduledCold,
            ],
            'warm' => [
                'total' => $totalWarm,
                'approval_status' => $approvalStatusWarm,
                'pending' => $warmPending,
                'rejected' => $warmRejected,
                'quotation_published' => $warmPublished,
            ],
            'hot' => [
                'total' => $totalHot,
                'expiring_7_days' => $expiring7,
                'expiring_8_plus_days' => $expiring8plus,
            ],
            'deal' => [
                'total' => $totalDeal,
            ],
        ]);
    }
}
