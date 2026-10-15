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
    public const CATEGORY_INSPECTION = 'inspection';
    public const CATEGORY_DIAGNOSIS = 'diagnosis';

    protected $fillable = [
        'ticket_id',
        'ticket_inspection_value_id',
        'category',
        'sequence',
        'file_path',
        'file_name',
        'captured_at',
        'thumbnail_path',
        'ticket_service_form_id', 'diagnosis_field', 'caption', 'locked_at',
    ];

    protected $casts = ['captured_at' => 'datetime', 'locked_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
