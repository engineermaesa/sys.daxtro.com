<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadMeetingReschedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'meeting_id',
        'old_scheduled_start_at',
        'old_scheduled_end_at',
        'new_scheduled_start_at',
        'new_scheduled_end_at',
        'old_location',
        'new_location',
        'reason',
        'rescheduled_by',
        'rescheduled_at',
        'old_online_url',  
        'new_online_url',  
    ];

    protected $dates = [
        'old_scheduled_start_at',
        'old_scheduled_end_at',
        'new_scheduled_start_at',
        'new_scheduled_end_at',
        'rescheduled_at',
    ];

    public function rescheduler()
    {
        return $this->belongsTo(\App\Models\User::class, 'rescheduled_by');
    }

    public function meeting()
    {
        return $this->belongsTo(LeadMeeting::class, 'meeting_id');
    }
}
