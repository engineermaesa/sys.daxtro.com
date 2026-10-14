<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class RefInspectionField extends Model
{
    public const TYPE_NUMBER = 'number';
    public const TYPE_CHECKLIST = 'checklist';
    public const TYPE_TEXT = 'text';

    protected $fillable = ['item_id', 'key', 'label', 'type', 'unit', 'options', 'required_when', 'sort'];

    protected $casts = ['options' => 'array'];

    public function item()
    {
        return $this->belongsTo(RefInspectionItem::class, 'item_id');
    }
}
