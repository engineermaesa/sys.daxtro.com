<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class RefInspectionTemplate extends Model
{
    protected $fillable = ['version', 'status', 'title', 'groups', 'diagnosis_section_title', 'diagnosis_fields', 'lock_version', 'created_by_user_id', 'updated_by_user_id', 'activated_by_user_id', 'activated_at'];

    protected $casts = ['groups' => 'array', 'diagnosis_fields' => 'array', 'lock_version' => 'integer', 'activated_at' => 'datetime'];

    public static function active(): self
    {
        return static::where('status', 'active')->firstOrFail();
    }

    public function items()
    {
        return $this->hasMany(RefInspectionItem::class, 'ref_inspection_template_id')->orderBy('number');
    }

    public function documentGroups(): array
    {
        $number = 0;

        return collect($this->groups)->filter(fn ($g) => $g['is_active'])->values()->map(function ($g, $i) use (&$number) {
            $g['code'] = RefAgreementTemplate::sectionCode($i);
            $g['items'] = collect($g['items'])->filter(fn ($item) => $item['is_active'])->values()->map(function ($item) use (&$number) {
                $item['number'] = ++$number;
                $item['fields'] = array_values(array_filter($item['fields'], fn ($f) => $f['is_active']));

                return $item;
            })->all();

            return $g;
        })->all();
    }
}
