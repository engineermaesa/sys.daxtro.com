<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketServiceForm extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SAVED = 'saved';

    protected $fillable = ['ticket_id', 'status', 'first_draft_at', 'saved_at', 'saved_by_user_id', 'unlocked_at', 'unlocked_by_user_id'];

    protected $casts = ['first_draft_at' => 'datetime', 'saved_at' => 'datetime', 'unlocked_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function inspections()
    {
        return $this->hasMany(TicketInspection::class, 'ticket_service_form_id');
    }

    public function signatures()
    {
        return $this->morphMany(TicketSignature::class, 'signable');
    }

    public function savedBy()
    {
        return $this->belongsTo(User::class, 'saved_by_user_id');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_SAVED;
    }
}
