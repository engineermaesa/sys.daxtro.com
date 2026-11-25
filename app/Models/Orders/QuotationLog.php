<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quotation_id',
        'temp_quotation_id',
        'revision_no',
        'revision_code',
        'action',
        'user_id',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'revision_no' => 'integer',
    ];

    protected $dates = ['logged_at'];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $snapshot = $log->temp_quotation_id ? TempQuotation::withTrashed()->find($log->temp_quotation_id) : null;
            if ($snapshot) {
                $log->revision_no = $snapshot->revision_no ?? 0;
                $log->revision_code = $snapshot->revision_code;
            }
        });
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'draft_created' => ($this->revision_no ?? $this->tempQuotation?->revision_no ?? 0) === 0 ? 'Quotation Started' : 'Revision Started',
            'submitted' => 'Submitted for BM Review',
            'submit_to_finance' => 'Submitted to Finance',
            'draft_cancelled' => ($this->revision_no ?? $this->tempQuotation?->revision_no ?? 0) === 0 ? 'Draft Discarded' : 'Revision Cancelled',
            'revision_in_place' => 'Revision Created (Direct Edit)',
            'bm_published_revision' => 'Published Quotation Revised by BM',
            'approve' => 'Approved',
            'reject' => 'Rejected',
            'update' => 'Quotation Updated',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function tempQuotation()
    {
        return $this->belongsTo(TempQuotation::class, 'temp_quotation_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id')->withTrashed();
    }
}
