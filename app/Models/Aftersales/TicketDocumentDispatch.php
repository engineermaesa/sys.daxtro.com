<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketDocumentDispatch extends Model
{
    public const COPY_LABELS = [
        'customer' => 'COPY FOR CUSTOMER',
        'aftersales' => 'COPY FOR AFTERSALES',
        'technician' => 'COPY FOR TECHNICIAN',
    ];

    protected $fillable = ['ticket_id', 'publication_id', 'document_type', 'recipient_role', 'recipient_email', 'copy_label', 'status', 'error', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
