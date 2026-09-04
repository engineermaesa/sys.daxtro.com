<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketSatisfaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'timeliness',
        'technician_attitude',
        'technical_knowledge',
        'work_neatness',
        'solution_quality',
        'filled_by_user_id',
        'filled_at',
    ];

    protected $casts = [
        'filled_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function filledBy()
    {
        return $this->belongsTo(User::class, 'filled_by_user_id');
    }

    public function getAverageScoreAttribute(): float
    {
        return round((
            $this->timeliness
            + $this->technician_attitude
            + $this->technical_knowledge
            + $this->work_neatness
            + $this->solution_quality
        ) / 5, 1);
    }
}
