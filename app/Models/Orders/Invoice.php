<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Orders\Proforma;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'proforma_id',
        'invoice_no',
        'invoice_type',
        'attachment_id',
        'amount',
        'due_date',
        'status',
        'issued_at',
    ];

    protected $dates = ['due_date', 'issued_at'];

    public function proforma()
    {
        return $this->belongsTo(Proforma::class, 'proforma_id');
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }
    
}
