<?php

namespace App\Models\Aftersales;

use App\Models\Leads\Lead;
use Illuminate\Database\Eloquent\Model;

class CustomerDeal extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['candidates' => 'array', 'lead_snapshot' => 'array', 'pending_changes' => 'array', 'left_deal_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(ServiceCustomer::class, 'service_customer_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
