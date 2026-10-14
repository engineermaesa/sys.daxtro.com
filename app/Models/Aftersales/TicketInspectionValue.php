<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketInspectionValue extends Model
{
    protected $fillable = ['ticket_inspection_id', 'ref_inspection_field_id', 'value'];

    public function inspection()
    {
        return $this->belongsTo(TicketInspection::class, 'ticket_inspection_id');
    }

    public function field()
    {
        return $this->belongsTo(RefInspectionField::class, 'ref_inspection_field_id');
    }

    public function photo()
    {
        return $this->hasOne(TicketPhoto::class, 'ticket_inspection_value_id')->latestOfMany();
    }
}
