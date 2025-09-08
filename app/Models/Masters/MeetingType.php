<?php
namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function meetings()
    {
        return $this->hasMany(\App\Models\Leads\LeadMeeting::class, 'meeting_type_id');
    }
}
