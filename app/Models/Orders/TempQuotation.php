<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempQuotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'status',
        'subtotal',
        'tax_pct',
        'tax_total',
        'total_discount',
        'grand_total',
        'booking_fee',
        'created_by',
    ];

    public function lead()
    {
        return $this->belongsTo(\App\Models\Leads\Lead::class, 'lead_id');
    }

    public function items()
    {
        return $this->hasMany(TempQuotationItem::class, 'temp_quotation_id');
    }

    public function paymentTerms()
    {
        return $this->hasMany(TempQuotationPaymentTerm::class, 'temp_quotation_id')->orderBy('term_no');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(QuotationLog::class, 'temp_quotation_id')->orderByDesc('logged_at');
    }
}
