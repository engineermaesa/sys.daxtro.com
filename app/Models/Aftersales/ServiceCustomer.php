<?php

namespace App\Models\Aftersales;

use App\Models\Masters\Province;
use App\Models\Masters\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_customers';

    protected $fillable = [
        'name',
        'address',
        'ref_province_id',
        'ref_region_id',
        'pic_name',
        'email',
        'phone',
        'address_maps_url', 'source', 'created_by', 'updated_by', 'deleted_by', 'is_deleted',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class, 'ref_province_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'ref_region_id');
    }

    public function products()
    {
        return $this->hasMany(ServiceCustomerProduct::class, 'customer_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function deals()
    {
        return $this->hasMany(CustomerDeal::class, 'service_customer_id');
    }

    public function getLeadNoLongerDealAttribute(): bool
    {
        return $this->relationLoaded('deals') ? $this->deals->whereNotNull('left_deal_at')->isNotEmpty() : $this->deals()->whereNotNull('left_deal_at')->exists();
    }

    public function getLeadUpdatedAttribute(): bool
    {
        return $this->relationLoaded('deals') ? $this->deals->contains(fn ($d) => ! empty($d->pending_changes)) : $this->deals()->whereNotNull('pending_changes')->exists();
    }
}
