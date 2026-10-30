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

    public const WORKFLOW_LEGACY = 'legacy';

    public const WORKFLOW_V3 = 'v3';

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
        'cost_display_currency',
        'assigned_technician_id',
        'supervisor_id',
        'service_type',
        'created_by',
        'updated_by',
        'happy_call_required',
        'workflow_version',
        'happy_call_due_at',
        'sla_stopped_at',
    ];

    protected $casts = [
        'sla_due_at' => 'datetime',
        'happy_call_due_at' => 'datetime',
        'sla_stopped_at' => 'datetime',
        'assigned_technician_id' => 'integer',
        'supervisor_id' => 'integer',
        'happy_call_required' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (Ticket $ticket) {
            $stored = $ticket->fresh();
            if ($stored->workflow_version !== null) {
                $ticket->workflow_version = $stored->workflow_version;
                \App\Services\Aftersales\TicketCycles::initial($ticket);
            }
        });
    }

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

    public function latestLog()
    {
        return $this->hasOne(TicketLog::class, 'ticket_id')->latestOfMany();
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
        return $this->hasOne(TicketSatisfaction::class, 'ticket_id')->latestOfMany();
    }

    public function satisfactions()
    {
        return $this->hasMany(TicketSatisfaction::class, 'ticket_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceForm()
    {
        return $this->hasOne(TicketServiceForm::class, 'ticket_id');
    }

    public function agreement()
    {
        return $this->hasOne(TicketAgreement::class, 'ticket_id');
    }

    public function surveyLink()
    {
        return $this->hasOne(SatisfactionLink::class, 'ticket_id')->latestOfMany();
    }

    public function recomings()
    {
        return $this->hasMany(TicketRecoming::class, 'ticket_id');
    }

    public function cycles()
    {
        return $this->hasMany(TicketCycle::class, 'ticket_id');
    }

    public function currentCycle()
    {
        return $this->hasOne(TicketCycle::class, 'ticket_id')->ofMany('cycle_no', 'max');
    }

    /**
     * Progress is never stored. It's the higher of two things: the latest
     * manually-logged ticket_logs step, and an "auto floor" derived from the
     * ticket's own data (technician assigned -> assigned, visit day has
     * arrived or already passed -> on_site) so a ticket never looks stuck
     * just because no one has manually logged a step yet. Manual logs
     * (repair, waiting_sparepart, documentation_published,
     * satisfaction_submitted, closed) always take precedence once they exist.
     */
    public function getProgressAttribute(): ?string
    {
        $latest = $this->relationLoaded('logs')
            ? $this->logs->sortByDesc('id')->first()
            : $this->logs()->latest('id')->first();

        $loggedStep = $latest?->step;
        $loggedOrder = $loggedStep ? array_search($loggedStep, TicketLog::STEPS, true) : -1;

        $autoFloor = TicketLog::STEP_PUBLISHED;
        if ($this->assigned_technician_id) {
            $autoFloor = TicketLog::STEP_ASSIGNED;

            if ($this->hasVisitDue()) {
                $autoFloor = TicketLog::STEP_ON_SITE;
            }
        }
        $autoFloorOrder = array_search($autoFloor, TicketLog::STEPS, true);

        return $loggedOrder >= $autoFloorOrder ? $loggedStep : $autoFloor;
    }

    /**
     * Whether a visit exists whose scheduled day has arrived or already
     * passed — used both to auto-advance progress to on_site and to gate
     * manually logging on_site (PRD: can't be on-site without a visit set).
     */
    public function hasVisitDue(): bool
    {
        if ($this->workflow_version === self::WORKFLOW_V3 && \App\Services\Aftersales\TicketRecomings::current($this)?->recoming_type === TicketRecoming::TYPE_PHONE_CALL) {
            return false;
        }
        $visits = $this->relationLoaded('visits') ? $this->visits : $this->visits()->get();
        $latestVisit = $visits->sortByDesc('scheduled_at')->first();

        if (! $latestVisit?->scheduled_at) {
            return false;
        }

        $endOfToday = Carbon::now('Asia/Jakarta')->endOfDay();

        return $latestVisit->scheduled_at->lessThanOrEqualTo($endOfToday);
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

    /**
     * SLA verdict is only decided once the ticket is actually closed —
     * an open ticket always reads as Pending, even past its due date, so
     * "Over SLA" can never appear before the ticket is really finished.
     */
    public function getSlaStatusAttribute(): ?string
    {
        if ($this->workflow_version === self::WORKFLOW_V3) {
            $summary = \App\Services\Aftersales\TicketSla::summary($this);

            return $summary && $summary['due_at'] ? ($summary['result'] ?? 'Pending') : null;
        }
        if (! $this->sla_due_at) {
            return null;
        }

        if ($this->progress !== TicketLog::STEP_CLOSED) {
            return 'Pending';
        }

        $closedAt = $this->relationLoaded('logs')
            ? $this->logs->where('step', TicketLog::STEP_CLOSED)->sortByDesc('id')->first()?->created_at
            : $this->logs()->where('step', TicketLog::STEP_CLOSED)->latest('id')->first()?->created_at;

        return ($closedAt ?? Carbon::now('Asia/Jakarta'))->lessThanOrEqualTo($this->sla_due_at) ? 'On Time' : 'Overtime';
    }

    /**
     * Sum of actual sparepart usage + operational costs (USD/CNY converted at the
     * snapshot exchange_rate). Estimated parts are excluded.
     */
    public function getTotalCostAttribute(): float
    {
        $parts = $this->relationLoaded('parts') ? $this->parts : $this->parts()->get();
        $partsTotal = $parts->where('type', 'actual')
            ->sum(fn (TicketPart $p) => (float) $p->qty * (float) $p->unit_price);

        $costs = $this->relationLoaded('costs') ? $this->costs : $this->costs()->get();
        $costsTotal = $costs->sum(function (TicketCost $c) {
            return $c->currency !== 'idr'
                ? (float) $c->amount * (float) ($c->exchange_rate ?? 1)
                : (float) $c->amount;
        });

        return round($partsTotal + $costsTotal, 2);
    }
}
