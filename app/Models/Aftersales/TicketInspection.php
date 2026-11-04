<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketInspection extends Model
{
    public const STATUSES = ['ok', 'rep', 'na'];

    protected $fillable = ['ticket_service_form_id', 'ref_inspection_item_id', 'status'];

    public function form()
    {
        return $this->belongsTo(TicketServiceForm::class, 'ticket_service_form_id');
    }

    public function item()
    {
        return $this->belongsTo(RefInspectionItem::class, 'ref_inspection_item_id');
    }

    public function values()
    {
        return $this->hasMany(TicketInspectionValue::class, 'ticket_inspection_id');
    }
}
