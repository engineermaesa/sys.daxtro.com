<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class CustomerImport extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['applied_at' => 'datetime', 'lock_version' => 'integer'];

    public function rows()
    {
        return $this->hasMany(CustomerImportRow::class);
    }
}
