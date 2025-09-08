<?php
namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadActivityList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'description'];

    public function logs()
    {
        return $this->hasMany(LeadActivityLog::class, 'activity_id');
    }
}
