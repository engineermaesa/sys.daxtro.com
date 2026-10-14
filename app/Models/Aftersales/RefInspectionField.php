<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class RefInspectionField extends Model
{
    public const TYPE_NUMBER = 'number';
    public const TYPE_CHECKLIST = 'checklist';
    public const TYPE_TEXT = 'text';

    protected $fillable = ['item_id', 'key', 'label', 'type', 'unit', 'options', 'required_when', 'sort', 'field_key', 'units', 'is_multiple', 'min_value', 'max_value'];

    protected $casts = ['options' => 'array', 'units' => 'array', 'is_multiple' => 'boolean'];

    public function item()
    {
        return $this->belongsTo(RefInspectionItem::class, 'item_id');
    }
}
