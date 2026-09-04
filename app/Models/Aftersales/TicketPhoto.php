<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketPhoto extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORY_PROBLEM = 'problem';
    public const CATEGORY_ANALYSIS = 'analysis';
    public const CATEGORY_REPAIR = 'repair';

    protected $fillable = [
        'ticket_id',
        'category',
        'sequence',
        'file_path',
        'file_name',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
