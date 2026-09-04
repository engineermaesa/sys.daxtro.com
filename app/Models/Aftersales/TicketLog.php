<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketLog extends Model
{
    use HasFactory, SoftDeletes;

    public const STEP_PUBLISHED = 'published';
    public const STEP_ASSIGNED = 'assigned';
    public const STEP_ON_SITE = 'on_site';
    public const STEP_REPAIR = 'repair';
    public const STEP_WAITING_SPAREPART = 'waiting_sparepart';
    public const STEP_DOCUMENTATION_PUBLISHED = 'documentation_published';
    public const STEP_SATISFACTION_SUBMITTED = 'satisfaction_submitted';
    public const STEP_CLOSED = 'closed';

    public const STEPS = [
        self::STEP_PUBLISHED,
        self::STEP_ASSIGNED,
        self::STEP_ON_SITE,
        self::STEP_REPAIR,
        self::STEP_WAITING_SPAREPART,
        self::STEP_DOCUMENTATION_PUBLISHED,
        self::STEP_SATISFACTION_SUBMITTED,
        self::STEP_CLOSED,
    ];

    protected $fillable = [
        'ticket_id',
        'step',
        'actor_user_id',
        'note',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
