<?php
namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'activity_id',
        'note',
        'attachment_id',
        'logged_at',
        'user_id',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function activity()
    {
        return $this->belongsTo(LeadActivityList::class, 'activity_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function attachment()
    {
        return $this->belongsTo(\App\Models\Attachment::class, 'attachment_id');
    }
}
