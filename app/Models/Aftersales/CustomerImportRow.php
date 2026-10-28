<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class CustomerImportRow extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['input' => 'array', 'review' => 'array', 'decision' => 'array', 'result' => 'array'];

    public function batch()
    {
        return $this->belongsTo(CustomerImport::class, 'customer_import_id');
    }
}
