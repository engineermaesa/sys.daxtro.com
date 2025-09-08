<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Orders\QuotationReview;
use App\Models\Orders\QuotationSignedDocument;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\PaymentLog;

class Quotation extends Model
{
use HasFactory, SoftDeletes;

protected $fillable = [
    'lead_id',
    'quotation_no',
    'status',
    'subtotal',
    'tax_pct',
    'tax_total',
    'grand_total',
    'booking_fee',
    'expiry_date',
    'created_by',
];

protected $dates = ['expiry_date'];

protected $casts = [
    'expiry_date' => 'date',
];


public function lead()
{
    return $this->belongsTo(\App\Models\Leads\Lead::class, 'lead_id');
}

public function proformas()
{
    return $this->hasMany(Proforma::class, 'quotation_id');
}

public function paymentTerms()
{
    return $this->hasMany(QuotationPaymentTerm::class, 'quotation_id')->orderBy('term_no');
}

public function reviews()
{
    return $this->hasMany(QuotationReview::class, 'quotation_id');
}

    public function items()
    {
        return $this->hasMany(\App\Models\Orders\QuotationItems::class, 'quotation_id');
    }

    public function signedDocuments()
    {
        return $this->hasMany(QuotationSignedDocument::class, 'quotation_id');
    }

    public function logs()
    {
        return $this->hasMany(QuotationLog::class, 'quotation_id')->orderByDesc('logged_at');
    }

    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class, 'quotation_id')->orderByDesc('logged_at');
    }

/**
 * Get the order associated with the quotation through the lead.
 */
    public function order()
    {
        return $this->hasOne(Order::class, 'lead_id', 'lead_id');
    }

    /**
     * Scope quotations that have booking fee and were created within range.
     */
    public function scopeBookingFeeBetween(Builder $query, $start, $end)
    {
        if ($start && $end) {
            $query->whereNotNull('booking_fee')
                  ->whereBetween('created_at', [$start, $end]);
        }

        return $query;
    }

    /**
     * Scope quotations by first approval date range.
     */
    public function scopeFirstApprovalBetween(Builder $query, $start, $end)
    {
        if ($start && $end) {
            $query->whereRaw(
                "(select min(decided_at) from quotation_reviews where quotation_id = quotations.id and decision = 'approve') between ? and ?",
                [$start, $end]
            );
        }

        return $query;
    }

    /**
     * Scope quotations by first term payment confirmation date range.
     */
    public function scopeFirstTermPaidBetween(Builder $query, $start, $end)
    {
        if ($start && $end) {
            $subQuery = "select pc.confirmed_at from payment_confirmations pc join proformas p on p.id = pc.proforma_id where p.quotation_id = quotations.id and p.term_no = 1 and pc.confirmed_at is not null order by pc.confirmed_at asc limit 1";
            $query->whereRaw("($subQuery) between ? and ?", [$start, $end]);
        }

        return $query;
    }
}
