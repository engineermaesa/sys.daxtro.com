<?php

namespace App\Models\Aftersales;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerDeal extends Model
{
    public const REVIEW_PENDING = 'pending_review';

    public const REVIEW_RESOLVED = 'resolved';

    protected $guarded = ['id'];

    protected $casts = ['candidates' => 'array', 'lead_snapshot' => 'array', 'pending_changes' => 'array', 'left_deal_at' => 'datetime'];

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', self::REVIEW_PENDING)->whereNull('service_customer_id')->whereNull('left_deal_at')
            ->whereHas('lead', fn ($lead) => $lead->where('status_id', LeadStatus::DEAL)->whereNotNull('deal_at'));
    }

    public function scopeLeadUpdates(Builder $query): Builder
    {
        return $query->whereNotNull('service_customer_id')->whereNotNull('pending_changes');
    }

    public function customer()
    {
        return $this->belongsTo(ServiceCustomer::class, 'service_customer_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
