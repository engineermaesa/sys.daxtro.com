<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketSatisfaction extends Model
{
    use Concerns\BelongsToTicketCycle, HasFactory, SoftDeletes;

    public const SOURCE_LINK = 'link';

    public const SOURCE_ADMIN = 'admin';

    public const REVIEW_NORMAL = 'normal';

    public const REVIEW_NEEDS_REVIEW = 'needs_review';

    public const REVIEW_REVIEWED = 'reviewed';

    public const REVIEW_REJECTED = 'rejected';

    public const ASPECTS = [
        'timeliness' => 'Arrival punctuality',
        'technician_attitude' => 'Technician attitude',
        'technical_knowledge' => 'Technical knowledge',
        'work_speed' => 'Work speed',
        'solution_quality' => 'Service quality',
        'work_neatness' => 'Work cleanliness',
        'explanation_quality' => 'Work explanation',
        'overall_satisfaction' => 'Overall satisfaction',
        'grooming' => 'Technician appearance',
    ];

    protected $fillable = [
        'ticket_id',
        'cycle_id',
        'timeliness',
        'technician_attitude',
        'technical_knowledge',
        'work_neatness',
        'solution_quality',
        'work_speed',
        'explanation_quality',
        'overall_satisfaction',
        'grooming',
        'machine_ok',
        'machine_issue_note',
        'feedback',
        'signer_name',
        'signer_position',
        'signature_path',
        'source',
        'review_status',
        'review_reasons',
        'admin_input_reason',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
        'otp_verified_at',
        'otp_phone_masked',
        'link_created_by_user_id',
        'shared_via',
        'ip_address',
        'user_agent',
        'duration_seconds',
        'filled_by_user_id',
        'filled_at',
        'ref_survey_template_id',
        'score_original', 'scale_version',
    ];

    protected $casts = [
        'machine_ok' => 'boolean',
        'score_original' => 'array',
        'review_reasons' => 'array',
        'filled_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $response) => \App\Services\Aftersales\TicketSla::responseChanged($response));
        static::updated(function (self $response) {
            if ($response->wasChanged(['review_status', 'filled_at'])) {
                \App\Services\Aftersales\TicketSla::responseChanged($response);
            }
        });
        static::deleted(fn (self $response) => \App\Services\Aftersales\TicketSla::responseChanged($response));
        static::restored(fn (self $response) => \App\Services\Aftersales\TicketSla::responseChanged($response));
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function template()
    {
        return $this->belongsTo(RefSurveyTemplate::class, 'ref_survey_template_id');
    }

    public function answers()
    {
        return $this->hasMany(TicketSatisfactionAnswer::class, 'ticket_satisfaction_id');
    }

    public function filledBy()
    {
        return $this->belongsTo(User::class, 'filled_by_user_id');
    }

    public static function storeSignature(int $ticketId, string $dataUri, string $dir = 'satisfaction-signatures'): string
    {
        abort_unless(preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUri, $m), 422, 'Invalid signature.');
        $png = base64_decode($m[1], true);
        abort_if($png === false || strlen($png) > 512 * 1024 || ! str_starts_with($png, "\x89PNG"), 422, 'Invalid signature.');

        $path = $dir.'/'.$ticketId.'/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $png);

        return $path;
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function linkCreatedBy()
    {
        return $this->belongsTo(User::class, 'link_created_by_user_id');
    }

    public function ratingItems(): array
    {
        if ($this->ref_survey_template_id && $this->template) {
            $answers = $this->answers->keyBy('question_key');

            return collect($this->template->questions())->where('type', 'rating')
                ->map(fn ($q) => ['key' => $q['question_key'], 'label' => $q['label'], 'score' => $answers->get($q['question_key'])?->value_number])
                ->filter(fn ($r) => $r['score'] !== null)->values()->all();
        }

        return collect(self::ASPECTS)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'score' => $this->{$key} === null ? null : (int) $this->{$key}])
            ->filter(fn ($r) => $r['score'] !== null)->values()->all();
    }

    public function getAverageScoreAttribute(): ?float
    {
        if ($this->ref_survey_template_id) {
            $scores = $this->answers->where('type', 'rating')->whereNotNull('value_number');

            return $scores->isEmpty() ? null : round($scores->avg('value_number'), 1);
        }
        $scores = collect(array_keys(self::ASPECTS))
            ->map(fn ($col) => $this->{$col})
            ->filter(fn ($v) => $v !== null);

        return $scores->isEmpty() ? null : round($scores->avg(), 1);
    }
}
