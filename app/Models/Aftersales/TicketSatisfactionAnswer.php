<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketSatisfactionAnswer extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['value_number' => 'integer', 'value_options' => 'array'];
}
