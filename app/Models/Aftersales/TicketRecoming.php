<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketRecoming extends Model
{
    use HasFactory, SoftDeletes;

    public const REASONS = ['sparepart_unavailable', 'further_work_needed', 'site_access', 'other'];

    protected $fillable = [
        'ticket_id',
        'visit_no',
        'reason',
        'notes',
        'planned_visit_at',
        'from_step',
        'created_by_user_id',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'visit_no' => 'integer',
        'planned_visit_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
