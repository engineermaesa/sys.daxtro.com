<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketSatisfaction extends Model
{
    use HasFactory, SoftDeletes;

    public const SOURCE_LINK = 'link';
    public const SOURCE_ADMIN = 'admin';

    public const REVIEW_NORMAL = 'normal';
    public const REVIEW_NEEDS_REVIEW = 'needs_review';
    public const REVIEW_REVIEWED = 'reviewed';

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
        'reviewed_by_user_id',
        'reviewed_at',
        'otp_verified_at',
        'otp_phone_masked',
        'link_created_by_user_id',
        'shared_via',
        'ip_address',
        'user_agent',
        'duration_seconds',
        'filled_by_user_id',
        'filled_at',
    ];

    protected $casts = [
        'machine_ok' => 'boolean',
        'review_reasons' => 'array',
        'filled_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function filledBy()
    {
        return $this->belongsTo(User::class, 'filled_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function linkCreatedBy()
    {
        return $this->belongsTo(User::class, 'link_created_by_user_id');
    }

    public function getAverageScoreAttribute(): ?float
    {
        $scores = collect(array_keys(self::ASPECTS))
            ->map(fn ($col) => $this->{$col})
            ->filter(fn ($v) => $v !== null);

        return $scores->isEmpty() ? null : round($scores->avg(), 1);
    }
}
