<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Orders\Invoice;

class Proforma extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quotation_id',
        'term_no',
        'proforma_type',
        'proforma_no',
        'amount',
        'status',
        'issued_by',
        'issued_at',
        'attachment_id',
    ];
    
    protected $casts = [
        'issued_at' => 'datetime',
    ];


    protected $dates = ['issued_at'];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function paymentConfirmation()
    {
        return $this->hasOne(PaymentConfirmation::class, 'proforma_id');
    }

    public function attachment()
    {
        return $this->belongsTo(\App\Models\Attachment::class, 'attachment_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'proforma_id');
    }
}
