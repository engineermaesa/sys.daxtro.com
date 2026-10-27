<?php

namespace App\Models\Aftersales;

use App\Models\Masters\Industry;
use App\Models\Masters\Product;
use App\Models\Masters\ProductType;
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
        'product_id',
        'serial_number',
        'ref_product_type_id',
        'industry_id',
        'warranty_period',
        'warranty_start',
        'warranty_end',
        'pm_contract_status',
        'pm_frequency',
        'capacity',
        'refrigerant',
        'is_daxtro',
        'compressor',
        'condenser_type',
        'voltage',
        'phase',
        'hz',
        'machine_name', 'brand', 'note', 'created_by', 'updated_by', 'deleted_by', 'is_deleted',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_daxtro' => 'boolean',
        'warranty_start' => 'date',
        'warranty_end' => 'date',
    ];

    protected $appends = ['machine_code', 'warranty_active'];

    protected $hidden = ['normalized_serial'];

    protected static function booted(): void
    {
        static::saving(function (self $machine) {
            if (! $machine->is_daxtro) {
                $machine->serial_number = null;
                $machine->normalized_serial = null;
                $machine->warranty_period = null;
                $machine->warranty_start = null;
                $machine->warranty_end = null;
                $machine->pm_contract_status = 'none';
                $machine->pm_frequency = null;

                return;
            }
            if ($machine->isDirty(['is_daxtro', 'brand'])) {
                $machine->brand = null;
            }
            if (! $machine->exists || $machine->isDirty(['serial_number', 'is_daxtro'])) {
                $machine->normalized_serial = \App\Services\Aftersales\CustomerMatching::serial($machine->serial_number);
            }
            if ($machine->isDirty('pm_contract_status') || ! $machine->exists) {
                $machine->pm_frequency = match ($machine->pm_contract_status ?? 'none') {
                    'basic' => '6 months', 'premium' => '3 months', 'none' => null,
                    default => $machine->pm_frequency,
                };
            }
            if ($machine->warranty_start && (! $machine->warranty_end || ($machine->isDirty('warranty_start') && ! $machine->isDirty('warranty_end')))) {
                $product = $machine->product;
                $months = $product?->warranty_available && $product->warranty_time_month ? (int) $product->warranty_time_month : 12;
                $machine->warranty_end = $machine->warranty_start->copy()->addMonthsNoOverflow($months);
            }
            if ($machine->warranty_start && $machine->warranty_end && (! $machine->exists || $machine->isDirty(['warranty_start', 'warranty_end']))) {
                if ($machine->warranty_end->lt($machine->warranty_start)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['warranty_end' => 'Warranty End must be on or after Warranty Start.']);
                }
                $months = (int) $machine->warranty_start->diffInMonths($machine->warranty_end);
                $machine->warranty_period = $months > 0 && $months % 12 === 0 ? ($months / 12).' '.($months === 12 ? 'Year' : 'Years') : $months.' Months';
            }
        });
    }

    public function getMachineCodeAttribute(): ?string
    {
        $prefix = $this->productType?->code_prefix;

        return $prefix && $this->capacity !== null && $this->capacity !== '' ? $prefix.' '.$this->capacity : null;
    }

    public function getMachineNameAttribute(?string $value): ?string
    {
        return $value ?: $this->product?->name;
    }

    public function customer()
    {
        return $this->belongsTo(ServiceCustomer::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'ref_product_type_id');
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_product_id');
    }

    public function getIsDaxtroAttribute(mixed $value): bool
    {
        return $value === null ? true : (bool) $value;
    }

    public function getWarrantyActiveAttribute(): ?bool
    {
        if (! $this->is_daxtro) {
            return null;
        }

        return $this->warranty_end !== null && $this->warranty_end->greaterThanOrEqualTo(Carbon::today());
    }
}
