<?php

namespace App\Models\Aftersales;

use App\Models\Masters\Industry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCustomerProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_customer_products';

    protected $fillable = [
        'customer_id',
        'machine_name',
        'serial_number',
        'model',
        'industry_id',
        'warranty_period',
        'warranty_start',
        'warranty_end',
        'pm_contract_status',
        'pm_frequency',
    ];

    protected $casts = [
        'warranty_start' => 'date',
        'warranty_end' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(ServiceCustomer::class, 'customer_id');
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_product_id');
    }

    public function getWarrantyActiveAttribute(): bool
    {
        return $this->warranty_end !== null && $this->warranty_end->greaterThanOrEqualTo(Carbon::today());
    }
}
