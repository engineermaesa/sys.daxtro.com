<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SatisfactionLink extends Model
{
    use HasFactory, SoftDeletes;

    public const TTL_DAYS = 7;

    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_REPLACED = 'replaced';

    protected $fillable = [
        'ticket_id',
        'token_hash',
        'token_encrypted',
        'status',
        'expires_at',
        'created_by_user_id',
        'used_at',
        'replaced_by_id',
        'regenerate_reason',
        'shared_by_user_id',
        'shared_via',
        'shared_at',
        'ref_survey_template_id',
    ];

    protected $hidden = ['token_hash', 'token_encrypted'];

    protected $casts = [
        'ticket_id' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'shared_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function template()
    {
        return $this->belongsTo(RefSurveyTemplate::class, 'ref_survey_template_id');
    }

    public static function issue(Ticket $ticket, ?int $userId = null, ?RefSurveyTemplate $template = null): self
    {
        $token = Str::random(64);

        return static::create([
            'ref_survey_template_id' => ($template ?? RefSurveyTemplate::active())->id,
            'ticket_id' => $ticket->id,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => Crypt::encryptString($token),
            'status' => self::STATUS_ACTIVE,
            'expires_at' => Carbon::now('Asia/Jakarta')->addDays(self::TTL_DAYS),
            'created_by_user_id' => $userId,
        ]);
    }

    public static function deactivateFor(Ticket $ticket): void
    {
        static::where('ticket_id', $ticket->id)->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_INACTIVE]);
    }

    public static function markUsedFor(Ticket $ticket): void
    {
        static::where('ticket_id', $ticket->id)->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_USED, 'used_at' => Carbon::now('Asia/Jakarta')]);
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->expires_at->lt(Carbon::now('Asia/Jakarta'));
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->isExpired() ? 'expired' : $this->status;
    }

    public function getUrlAttribute(): string
    {
        return url('/survey/' . Crypt::decryptString($this->token_encrypted));
    }
}
