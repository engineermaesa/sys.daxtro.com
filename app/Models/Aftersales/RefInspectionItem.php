<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefInspectionItem extends Model
{
    use SoftDeletes;

    public const GROUPS = [
        'A' => 'Electrical & Control Automation',
        'B' => 'Refrigeration & Compressor',
        'C' => 'Water & Ice Making',
        'D' => 'Mechanical, Bin Storage & Structure',
        'E' => 'Machine Cleanliness',
    ];

    protected $fillable = ['group_code', 'number', 'name', 'standard', 'only_for_types', 'ref_inspection_template_id', 'ref_inspection_group_id', 'item_key', 'machine_types', 'types_restricted'];

    protected $casts = ['only_for_types' => 'array', 'machine_types' => 'array', 'types_restricted' => 'boolean'];

    public function group()
    {
        return $this->belongsTo(RefInspectionGroup::class, 'ref_inspection_group_id');
    }

    public function appliesToType(?int $typeId): bool
    {
        return ! $this->types_restricted || in_array($typeId, $this->machine_types ?? [], true);
    }

    public function fields()
    {
        return $this->hasMany(RefInspectionField::class, 'item_id')->orderBy('sort');
    }

    public function appliesTo(?string $productTypeName): bool
    {
        if (empty($this->only_for_types) || blank($productTypeName)) {
            return true;
        }

        foreach ($this->only_for_types as $keyword) {
            if (stripos($productTypeName, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
