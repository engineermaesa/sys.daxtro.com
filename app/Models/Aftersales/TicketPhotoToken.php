<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketPhotoToken extends Model
{
    public const TTL_MINUTES = 15;

    protected $fillable = ['token_hash', 'ticket_id', 'ticket_inspection_value_id', 'created_by_user_id', 'expires_at', 'used_at'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function value()
    {
        return $this->belongsTo(TicketInspectionValue::class, 'ticket_inspection_value_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
