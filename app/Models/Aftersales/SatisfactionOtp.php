<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class SatisfactionOtp extends Model
{
    public const CODE_TTL_MINUTES = 5;
    public const MAX_ATTEMPTS = 5;
    public const LOCK_MINUTES = 15;
    public const RESEND_GAP_SECONDS = 60;
    public const MAX_SENDS_PER_HOUR = 3;
    public const SESSION_MINUTES = 30;

    protected $fillable = [
        'link_id',
        'phone_masked',
        'code_hash',
        'sent_at',
        'expires_at',
        'attempts',
        'verified_at',
        'locked_until',
        'session_hash',
        'session_expires_at',
    ];

    protected $hidden = ['code_hash', 'session_hash'];

    protected $casts = [
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'session_expires_at' => 'datetime',
    ];

    public function link()
    {
        return $this->belongsTo(SatisfactionLink::class, 'link_id');
    }
}
