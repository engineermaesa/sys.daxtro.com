<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketRecoming extends Model
{
    use HasFactory, SoftDeletes;

    public const REASONS = ['sparepart_unavailable', 'further_work_needed', 'site_access', 'unfinished_previous_work'];

    public const TYPE_PHONE_CALL = 'phone_call';

    public const TYPE_VISIT_SITE = 'visit_site';

    public const TYPES = [self::TYPE_PHONE_CALL, self::TYPE_VISIT_SITE];

    protected $fillable = [
        'ticket_id',
        'cycle_id',
        'recoming_type',
        'planned_call_at',
        'ticket_visit_id',
        'visit_no',
        'reason',
        'notes',
        'planned_visit_at',
        'from_step',
        'created_by_user_id',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'cycle_id' => 'integer',
        'visit_no' => 'integer',
        'planned_visit_at' => 'datetime',
        'planned_call_at' => 'datetime',
        'ticket_visit_id' => 'integer',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function cycle()
    {
        return $this->belongsTo(TicketCycle::class, 'cycle_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function visit()
    {
        return $this->belongsTo(TicketVisit::class, 'ticket_visit_id');
    }

    public function scheduledAt(): ?\Carbon\Carbon
    {
        return $this->recoming_type === self::TYPE_PHONE_CALL ? $this->planned_call_at : $this->planned_visit_at;
    }
}
