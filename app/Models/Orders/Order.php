<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Orders\OrderItems;
use App\Models\Orders\Invoice;
use App\Models\Orders\Proforma;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'order_no',
        'total_billing',
        'order_status',
    ];

    public function lead()
    {
        return $this->belongsTo(\App\Models\Leads\Lead::class, 'lead_id');
    }

    public function invoices()
    {
        return $this->hasManyThrough(
            Invoice::class,
            Proforma::class,
            'quotation_id',
            'proforma_id',
            'lead_id',
            'id'
        )->join('quotations', 'proformas.quotation_id', '=', 'quotations.id')
         ->whereColumn('quotations.lead_id', 'orders.lead_id')
         ->select('invoices.*');
    }

    public function paymentTerms()
    {
        return $this->hasMany(OrderPaymentTerm::class, 'order_id')->orderBy('term_no');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class, 'order_id');
    }

    public function progressLogs()
    {
        return $this->hasMany(OrderProgressLog::class, 'order_id')->orderByDesc('logged_at');
    }
}
