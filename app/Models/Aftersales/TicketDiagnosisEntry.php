<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketDiagnosisEntry extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['cycle_id' => 'integer', 'entry_date' => 'date', 'locked_at' => 'datetime'];

    public function cycle()
    {
        return $this->belongsTo(TicketCycle::class, 'cycle_id');
    }
}
