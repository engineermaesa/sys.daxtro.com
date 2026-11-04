<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CustomerImport extends Model
{
    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_SPAREPART = 'sparepart';

    public const TYPES = [self::TYPE_CUSTOMER, self::TYPE_SPAREPART];

    protected $attributes = ['type' => self::TYPE_CUSTOMER];

    protected $guarded = ['id'];

    protected $casts = ['applied_at' => 'datetime', 'lock_version' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (self $batch) {
            if (! in_array($batch->type, self::TYPES, true)) {
                throw ValidationException::withMessages(['type' => 'Import type must be customer or sparepart.']);
            }
            if ($batch->exists && $batch->isDirty('type')) {
                throw ValidationException::withMessages(['type' => 'The type of an existing import cannot be changed.']);
            }
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function assertType(string $type): void
    {
        abort_unless($this->type === $type, 404, 'Import not found for this type.');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function rows()
    {
        return $this->hasMany(CustomerImportRow::class);
    }
}
