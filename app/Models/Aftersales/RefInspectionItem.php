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

    protected $fillable = ['group_code', 'number', 'name', 'standard', 'only_for_types'];

    protected $casts = ['only_for_types' => 'array'];

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
