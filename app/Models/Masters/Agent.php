<?php

namespace App\Models\Masters;

use App\Models\Leads\LeadSource;
use App\Models\Masters\Branch;
use App\Models\Masters\CustomerType;
use App\Models\Masters\Jabatan;
use App\Models\Masters\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_agents';

    protected $fillable = [
        'branch_id',
        'jabatan_id',
        'source_id',
        'customer_type_id',
        'region_id',
        'province',
        'name',
        'phone',
        'email',
        'company_name',
        'company_address',
        'is_active',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
