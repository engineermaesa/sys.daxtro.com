<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPermissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserPermission $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserPermission $model): bool
    {
        return true;
    }

    public function delete(User $user, UserPermission $model): bool
    {
        return true;
    }
}
