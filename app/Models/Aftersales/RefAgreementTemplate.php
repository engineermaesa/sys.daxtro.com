<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefAgreementTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['version', 'title', 'sections', 'closing_statement', 'is_active', 'status', 'lock_version', 'created_by_user_id', 'updated_by_user_id', 'activated_by_user_id', 'activated_at'];

    protected $casts = ['sections' => 'array', 'is_active' => 'boolean', 'lock_version' => 'integer', 'activated_at' => 'datetime'];

    public static function active(): self
    {
        return static::where('is_active', true)->orderByDesc('version')->firstOrFail();
    }

    public function clauseKeys(): array
    {
        return collect($this->documentSections())->flatMap(fn ($s) => collect($s['clauses'])->pluck('key'))->all();
    }

    public static function sectionCode(int $index): string
    {
        $code = '';
        do {
            $code = chr(65 + ($index % 26)).$code;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $code;
    }

    public function editableSections(): array
    {
        return collect($this->sections ?? [])->values()->map(function ($section, $index) {
            $section['section_key'] ??= 'legacy_section_'.($section['code'] ?? $index);
            $section['is_active'] ??= true;
            $section['sort_order'] = $index;
            $section['code'] = self::sectionCode($index);
            $section['clauses'] = collect($section['clauses'] ?? [])->values()->map(function ($clause, $i) {
                $clause['is_active'] ??= true;
                $clause['sort_order'] = $i;

                return $clause;
            })->all();

            return $section;
        })->all();
    }

    public function documentSections(): array
    {
        return collect($this->editableSections())->filter(fn ($s) => $s['is_active'])->values()->map(function ($s, $i) {
            $s['code'] = self::sectionCode($i);
            $s['clauses'] = array_values(array_filter($s['clauses'], fn ($c) => $c['is_active']));

            return $s;
        })->all();
    }
}
