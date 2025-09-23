<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Masters\Branch;
use App\Models\Leads\LeadSource;
use App\Models\Leads\LeadSegment;
use App\Models\Orders\Quotation;
use App\Models\User;
use App\Models\Leads\LeadPicExtension;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'segment_id',
        'region_id',
        'branch_id',
        'first_sales_id',
        'industry_id',
        'industry_remark',
        'factory_city_id',
        'factory_province',
        'factory_industry_id',
        'other_industry',
        'jabatan_id',
        'province',
        'status_id',
        'product_id',
        'company',
        'customer_type',
        'contact_reason',
        'business_reason',
        'competitor_offer',
        'name',
        'phone',
        'email',
        'needs',
        'tonase',
        'tonage_remark',
        'published_at',
    ];

    protected $dates = [
        'published_at',
    ];

    public function status()
    {
        return $this->belongsTo(LeadStatus::class, 'status_id');
    }

    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function segment()
    {
        return $this->belongsTo(LeadSegment::class, 'segment_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Masters\Product::class, 'product_id');
    }

    public function industry()
    {
        return $this->belongsTo(\App\Models\Masters\Industry::class, 'industry_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(\App\Models\Masters\Jabatan::class, 'jabatan_id');
    }

    public function quotation()
    {
        return $this->hasOne(Quotation::class, 'lead_id');
    }


    public function region()
    {
        return $this->belongsTo(\App\Models\Masters\Region::class, 'region_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function factoryCity()
    {
        \Log::debug('Accessing factoryCity relation');
        return $this->belongsTo(\App\Models\Masters\Region::class, 'factory_city_id');
    }

    public function factoryIndustry()
    {
        return $this->belongsTo(\App\Models\Masters\Industry::class, 'factory_industry_id');
    }

    public function firstSales()
    {
        return $this->belongsTo(User::class, 'first_sales_id');
    }

    public function claims()
    {
        return $this->hasMany(LeadClaim::class, 'lead_id');
    }

    public function meetings()
    {
        return $this->hasMany(LeadMeeting::class, 'lead_id');
    }

    public function picExtensions()
    {
        return $this->hasMany(LeadPicExtension::class, 'lead_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(LeadStatusLog::class);
    }

    public function latestStatusLog()
    {
        // grabs the single most‐recent log by created_at
        return $this->hasOne(LeadStatusLog::class)
                    ->latest('created_at');
    }

    public function activityLogs()
    {
        return $this->hasMany(LeadActivityLog::class, 'lead_id');
    }
}
