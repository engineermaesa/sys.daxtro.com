<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = ['electrical', 'mechanical', 'refrigeration_system', 'production'];
    public const PRIORITIES = ['low', 'medium', 'high'];

    protected $fillable = [
        'ticket_code',
        'customer_id',
        'customer_product_id',
        'category',
        'priority',
        'description',
        'sla_value',
        'sla_unit',
        'sla_due_at',
        'assigned_technician_id',
        'supervisor_id',
    ];

    protected $casts = [
        'sla_due_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(ServiceCustomer::class, 'customer_id');
    }

    public function customerProduct()
    {
        return $this->belongsTo(ServiceCustomerProduct::class, 'customer_product_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function logs()
    {
        return $this->hasMany(TicketLog::class, 'ticket_id');
    }

    public function visits()
    {
        return $this->hasMany(TicketVisit::class, 'ticket_id');
    }

    public function workOrders()
    {
        return $this->hasMany(TicketWorkOrder::class, 'ticket_id');
    }

    public function parts()
    {
        return $this->hasMany(TicketPart::class, 'ticket_id');
    }

    public function costs()
    {
        return $this->hasMany(TicketCost::class, 'ticket_id');
    }

    public function photos()
    {
        return $this->hasMany(TicketPhoto::class, 'ticket_id');
    }

    public function satisfaction()
    {
        return $this->hasOne(TicketSatisfaction::class, 'ticket_id');
    }

    /**
     * Progress is never stored — it is the step of the latest ticket_logs
     * row, per PRD §5.1.
     */
    public function getProgressAttribute(): ?string
    {
        $latest = $this->relationLoaded('logs')
            ? $this->logs->sortByDesc('id')->first()
            : $this->logs()->latest('id')->first();

        return $latest?->step;
    }

    public function getAgingDaysAttribute(): int
    {
        $end = $this->progress === TicketLog::STEP_CLOSED
            ? ($this->relationLoaded('logs')
                ? $this->logs->where('step', TicketLog::STEP_CLOSED)->sortByDesc('id')->first()?->created_at
                : $this->logs()->where('step', TicketLog::STEP_CLOSED)->latest('id')->first()?->created_at)
            : Carbon::now('Asia/Jakarta');

        return (int) $this->created_at->diffInDays($end ?? Carbon::now('Asia/Jakarta'));
    }

    public function getSlaStatusAttribute(): ?string
    {
        if (! $this->sla_due_at) {
            return null;
        }

        $reference = $this->progress === TicketLog::STEP_CLOSED
            ? ($this->relationLoaded('logs')
                ? $this->logs->where('step', TicketLog::STEP_CLOSED)->sortByDesc('id')->first()?->created_at
                : $this->logs()->where('step', TicketLog::STEP_CLOSED)->latest('id')->first()?->created_at)
            : Carbon::now('Asia/Jakarta');

        return ($reference ?? Carbon::now('Asia/Jakarta'))->lessThanOrEqualTo($this->sla_due_at) ? 'On Time' : 'Over SLA';
    }

    /**
     * Sum of actual sparepart usage + operational costs (USD converted at the
     * snapshot exchange_rate). Estimated parts are excluded.
     */
    public function getTotalCostAttribute(): float
    {
        $parts = $this->relationLoaded('parts') ? $this->parts : $this->parts()->get();
        $partsTotal = $parts->where('type', 'actual')
            ->sum(fn (TicketPart $p) => (float) $p->qty * (float) $p->unit_price);

        $costs = $this->relationLoaded('costs') ? $this->costs : $this->costs()->get();
        $costsTotal = $costs->sum(function (TicketCost $c) {
            return $c->currency === 'usd'
                ? (float) $c->amount * (float) ($c->exchange_rate ?? 1)
                : (float) $c->amount;
        });

        return round($partsTotal + $costsTotal, 2);
    }
}
