<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_permissions';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(UserRole::class, 'user_role_permissions', 'permission_id', 'role_id');
    }
}
