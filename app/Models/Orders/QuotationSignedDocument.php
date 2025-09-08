<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationSignedDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quotation_id',
        'attachment_id',
        'description',
        'signed_date',
        'uploader_id',
    ];

    protected $dates = ['signed_date'];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploader_id');
    }

    public function attachment()
    {
        return $this->belongsTo(\App\Models\Attachment::class, 'attachment_id');
    }

}
