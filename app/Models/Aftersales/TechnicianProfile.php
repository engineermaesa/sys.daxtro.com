<?php

namespace App\Models\Aftersales;

use App\Models\Masters\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechnicianProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'region_id',
        'certification',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function skills()
    {
        return $this->hasMany(TechnicianSkill::class, 'technician_profile_id');
    }

    /**
     * On Duty = has a ticket currently assigned whose latest progress step
     * is not yet closed. Never stored — always derived, per PRD §8.
     */
    public function getStatusAttribute(): string
    {
        $hasActiveTicket = Ticket::query()
            ->where('assigned_technician_id', $this->user_id)
            ->whereDoesntHave('logs', function ($q) {
                $q->where('step', TicketLog::STEP_CLOSED);
            })
            ->exists();

        return $hasActiveTicket ? 'On Duty' : 'Standby';
    }
}
