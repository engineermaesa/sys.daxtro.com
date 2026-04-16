<?php

namespace App\Services;

use App\Models\Leads\{LeadClaim, LeadStatus};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MyLeadQueryService
{
    public static function allowedStatusIds(): array
    {
        return [
            LeadStatus::COLD,
            LeadStatus::WARM,
            LeadStatus::HOT,
            LeadStatus::DEAL,
        ];
    }

    public static function baseClaimsQuery(Request $request, int|array|null $statusIds = null, array $with = []): Builder
    {
        $query = LeadClaim::query()
            ->with($with)
            ->whereNull('lead_claims.released_at');

        self::applyRoleFilter($query, $request);

        if ($statusIds !== null) {
            self::applyStatusFilter($query, (array) $statusIds);
        }

        return self::applyCommonFilters($query, $request);
    }

    public static function applyRoleFilter(Builder $query, Request $request): Builder
    {
        $user = $request->user();
        $roleCode = $user?->role?->code;

        if ($roleCode === 'sales') {
            $query->where('lead_claims.sales_id', $user->id);
        } elseif ($roleCode === 'branch_manager') {
            $query->whereHas('sales', function ($salesQuery) use ($user) {
                $salesQuery->where('branch_id', $user->branch_id);
            });
        }

        return $query;
    }

    public static function applyStatusFilter(Builder $query, array $statusIds): Builder
    {
        $statusIds = array_values(array_unique(array_map('intval', $statusIds)));

        return $query->whereHas('lead', function ($leadQuery) use ($statusIds) {
            $leadQuery->whereIn('status_id', $statusIds);
        });
    }

    public static function applyCommonFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($nestedQuery) use ($search) {
                $nestedQuery->whereHas('lead', function ($leadQuery) use ($search) {
                    $leadQuery->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('needs', 'like', "%{$search}%")
                            ->orWhere('customer_type', 'like', "%{$search}%");
                    });
                })
                ->orWhereHas('sales', function ($salesQuery) use ($search) {
                    $salesQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('lead.source', function ($sourceQuery) use ($search) {
                    $sourceQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('lead.region', function ($regionQuery) use ($search) {
                    $regionQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('lead.region.regional', function ($regionalQuery) use ($search) {
                    $regionalQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        self::applyDateFilter(
            $query,
            $request->input('start_date'),
            $request->input('end_date')
        );

        self::applySourceFilter($query, $request->input('sources'));

        return $query;
    }

    public static function applyDateFilter(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate && $endDate) {
            $query->whereDate('lead_claims.claimed_at', '>=', $startDate)
                ->whereDate('lead_claims.claimed_at', '<=', $endDate);
        } elseif ($startDate) {
            $query->whereDate('lead_claims.claimed_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('lead_claims.claimed_at', '<=', $endDate);
        }

        return $query;
    }

    public static function applySourceFilter(Builder $query, string|array|null $sources): Builder
    {
        if ($sources === null || $sources === '') {
            return $query;
        }

        if (is_string($sources) && str_contains($sources, ',')) {
            $sources = array_filter(array_map('trim', explode(',', $sources)));
        }

        $sourceIds = is_array($sources) ? $sources : [$sources];
        $sourceIds = array_values(array_filter($sourceIds, function ($sourceId) {
            return $sourceId !== null && $sourceId !== '';
        }));

        if ($sourceIds === []) {
            return $query;
        }

        return $query->whereHas('lead', function ($leadQuery) use ($sourceIds) {
            if (count($sourceIds) === 1) {
                $leadQuery->where('source_id', $sourceIds[0]);
                return;
            }

            $leadQuery->whereIn('source_id', $sourceIds);
        });
    }

    public static function getLeadCounts(Request $request): array
    {
        $baseQuery = self::baseClaimsQuery($request, self::allowedStatusIds());

        $cold = (clone $baseQuery)
            ->whereHas('lead', fn($leadQuery) => $leadQuery->where('status_id', LeadStatus::COLD))
            ->count();

        $warm = (clone $baseQuery)
            ->whereHas('lead', fn($leadQuery) => $leadQuery->where('status_id', LeadStatus::WARM))
            ->count();

        $hot = (clone $baseQuery)
            ->whereHas('lead', fn($leadQuery) => $leadQuery->where('status_id', LeadStatus::HOT))
            ->count();

        $deal = (clone $baseQuery)
            ->whereHas('lead', fn($leadQuery) => $leadQuery->where('status_id', LeadStatus::DEAL))
            ->count();

        return [
            'all' => $cold + $warm + $hot + $deal,
            'cold' => $cold,
            'warm' => $warm,
            'hot' => $hot,
            'deal' => $deal,
        ];
    }

    public static function getSummary(Request $request): array
    {
        $leadCounts = self::getLeadCounts($request);

        $initiationCodes = ['A01', 'A02', 'A03', 'A04'];

        $coldBase = self::baseClaimsQuery($request, LeadStatus::COLD);
        $coldInitiation = (clone $coldBase)
            ->whereHas('lead.activityLogs.activity', fn($query) => $query->whereIn('code', $initiationCodes))
            ->whereDoesntHave('lead.meetings')
            ->count();
        $coldRaw = (clone $coldBase)
            ->whereDoesntHave('lead.activityLogs.activity', fn($query) => $query->whereIn('code', $initiationCodes))
            ->whereDoesntHave('lead.meetings')
            ->count();
        $coldPending = (clone $coldBase)
            ->whereHas('lead.meetings', fn($query) => $query->where('is_online', 0)
                ->whereHas('expense', fn($expenseQuery) => $expenseQuery->where('status', 'submitted')))
            ->count();
        $coldRejected = (clone $coldBase)
            ->whereHas('lead.meetings', fn($query) => $query->where('is_online', 0)
                ->whereHas('expense', fn($expenseQuery) => $expenseQuery->where('status', 'rejected')))
            ->count();
        $coldMeetOnline = (clone $coldBase)
            ->whereHas('lead.meetings', fn($query) => $query->where('scheduled_end_at', '>', Carbon::now())
                ->where('is_online', 1))
            ->count();
        $coldMeetOffline = (clone $coldBase)
            ->whereHas('lead.meetings', fn($query) => $query->where('scheduled_end_at', '>', Carbon::now())
                ->where('is_online', 0))
            ->count();

        $warmBase = self::baseClaimsQuery($request, LeadStatus::WARM);
        $warmPending = (clone $warmBase)
            ->whereHas('lead.quotation', fn($query) => $query->whereIn('status', ['review', 'pending_finance']))
            ->count();
        $warmRejected = (clone $warmBase)
            ->whereHas('lead.quotation', fn($query) => $query->where('status', 'rejected'))
            ->count();
        $warmNoQuotation = (clone $warmBase)
            ->whereDoesntHave('lead.quotation')
            ->count();
        $warmPublished = (clone $warmBase)
            ->whereHas('lead.quotation', fn($query) => $query->where('status', 'published'))
            ->count();

        $hotBase = self::baseClaimsQuery($request, LeadStatus::HOT, [
            'lead.statusLogs' => fn($query) => $query->where('status_id', LeadStatus::HOT)->orderByDesc('created_at'),
        ]);
        $hotClaims = $hotBase->get();
        $hotExpiringSoon = 0;
        $hotExpiringLater = 0;

        foreach ($hotClaims as $claim) {
            $hotLog = $claim->lead->statusLogs->first();
            $claimedAt = $claim->claimed_at ? Carbon::parse($claim->claimed_at) : null;
            $hotAt = $hotLog?->created_at ? Carbon::parse($hotLog->created_at) : null;
            $baseAt = $claimedAt ?? $hotAt;

            if (! $baseAt) {
                continue;
            }

            $daysLeft = Carbon::now()->startOfDay()->diffInDays(
                $baseAt->copy()->startOfDay()->addDays(30),
                false
            );

            if ($daysLeft < 0) {
                $daysLeft = 0;
            }

            if ($daysLeft <= 7) {
                $hotExpiringSoon++;
            } else {
                $hotExpiringLater++;
            }
        }

        return [
            'leadCounts' => $leadCounts,
            'cold' => [
                'total' => $leadCounts['cold'],
                'initiation' => $coldInitiation,
                'raw' => $coldRaw,
                'approval_status' => $coldPending + $coldRejected,
                'pending' => $coldPending,
                'rejected' => $coldRejected,
                'meet_online' => $coldMeetOnline,
                'meet_offline' => $coldMeetOffline,
                'meeting_scheduled' => $coldMeetOnline + $coldMeetOffline,
            ],
            'warm' => [
                'total' => $leadCounts['warm'],
                'approval_status' => $warmPending + $warmRejected,
                'pending' => $warmPending,
                'rejected' => $warmRejected,
                'no_quotation' => $warmNoQuotation,
                'quotation_published' => $warmPublished,
            ],
            'hot' => [
                'total' => $leadCounts['hot'],
                'expiring_7_days' => $hotExpiringSoon,
                'expiring_8_plus_days' => $hotExpiringLater,
            ],
            'deal' => [
                'total' => $leadCounts['deal'],
            ],
        ];
    }
}
