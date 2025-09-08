<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProgressLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'progress_step',
        'note',
        'attachment_id',
        'logged_at',
        'user_id',
    ];
    
    protected $casts = [
        'logged_at' => 'datetime',
    ];


    protected $dates = ['logged_at'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function attachment()
    {
        return $this->belongsTo(\App\Models\Attachment::class, 'attachment_id');
    }
}