<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRolePermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    public function role()
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(UserPermission::class, 'permission_id');
    }
}
