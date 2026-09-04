<?php

namespace App\Models\Aftersales;

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
        'city',
        'province',
        'pic_name',
        'email',
        'phone',
    ];

    public function products()
    {
        return $this->hasMany(ServiceCustomerProduct::class, 'customer_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }
}
