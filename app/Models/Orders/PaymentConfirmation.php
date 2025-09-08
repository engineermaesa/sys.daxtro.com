<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Models\Orders\FinanceRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentConfirmation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payment_confirmations';

    protected $casts = [
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected $fillable = [
        'proforma_id',
        'payer_name',
        'payer_bank',
        'payer_account_number',
        'paid_at',
        'amount',
        'attachment_id',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $dates = ['paid_at', 'confirmed_at'];

    public function proforma()
    {
        return $this->belongsTo(Proforma::class, 'proforma_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function attachment()
    {
        return $this->belongsTo(\App\Models\Attachment::class, 'attachment_id');
    }

    public function financeRequest()
    {
        return $this->hasOne(FinanceRequest::class, 'reference_id')
            ->where('request_type', 'payment-confirmation');
    }
}
