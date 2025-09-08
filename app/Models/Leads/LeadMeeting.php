<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadMeeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'meeting_type_id',
        'is_online',
        'online_url',
        'scheduled_start_at',
        'scheduled_end_at',
        'city',
        'address',
        'result',
        'summary',
        'attachment_id'
    ];

    protected $dates = [
        'scheduled_start_at',
        'scheduled_end_at',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function reschedules()
    {
        return $this->hasMany(LeadMeetingReschedule::class, 'meeting_id');
    }

    public function expense()
    {
        return $this->hasOne(\App\Models\Orders\MeetingExpense::class, 'meeting_id');
    }

    public function attachment()
    {
        return $this->belongsTo(\App\Models\Attachment::class, 'attachment_id');
    }

    public function meetingType()
    {
        return $this->belongsTo(\App\Models\Masters\MeetingType::class, 'meeting_type_id');
    }
}
