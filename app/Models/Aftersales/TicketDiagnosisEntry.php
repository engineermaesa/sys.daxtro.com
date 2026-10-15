<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketDiagnosisEntry extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['entry_date' => 'date', 'locked_at' => 'datetime'];
}
