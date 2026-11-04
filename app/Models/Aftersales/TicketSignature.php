<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketSignature extends Model
{
    public const ROLES = ['technician', 'supervisor', 'customer'];

    protected $fillable = [
        'signable_type', 'signable_id', 'role', 'signer_name', 'signed_by_user_id', 'captured_by_user_id',
        'file_path', 'signed_at', 'ip_address', 'user_agent',
    ];

    protected $casts = ['signed_at' => 'datetime'];

    protected $hidden = ['file_path'];

    public function signable()
    {
        return $this->morphTo();
    }
}
