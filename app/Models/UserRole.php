<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_roles';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    

    public function permissions()
    {
        return $this->belongsToMany(UserPermission::class, 'user_role_permissions', 'role_id', 'permission_id');
    }
}
