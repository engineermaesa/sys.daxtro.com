<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ServiceCustomerPhoneChange extends Model
{
    protected $fillable = ['customer_id', 'old_phone', 'new_phone', 'changed_by_user_id', 'changed_at'];

    protected $casts = ['changed_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(ServiceCustomer::class, 'customer_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
