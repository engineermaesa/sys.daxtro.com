<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempQuotationPaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'temp_quotation_id',
        'term_no',
        'percentage',
        'description',
    ];

    public function tempQuotation()
    {
        return $this->belongsTo(TempQuotation::class, 'temp_quotation_id');
    }
}
