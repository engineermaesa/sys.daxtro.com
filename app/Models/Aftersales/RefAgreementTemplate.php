<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefAgreementTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['version', 'title', 'sections', 'closing_statement', 'is_active'];

    protected $casts = ['sections' => 'array', 'is_active' => 'boolean'];

    public static function active(): self
    {
        return static::where('is_active', true)->orderByDesc('version')->firstOrFail();
    }

    public function clauseKeys(): array
    {
        return collect($this->sections)->flatMap(fn ($s) => collect($s['clauses'])->pluck('key'))->all();
    }
}
