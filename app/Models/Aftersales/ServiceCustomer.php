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
}
