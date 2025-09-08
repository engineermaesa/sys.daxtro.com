<?php

use App\Models\{User, UserRole, UserPermission};

if (! function_exists('hasRole')) {
    function hasRole(int|User $user, string $roleCode): bool
    {
        if (! $user instanceof User) {
            $user = User::find($user);
        }

        return $user?->role?->code === $roleCode;
    }
}

if (! function_exists('hasPermission')) {
    function hasPermission(int|User $user, string $permissionCode): bool
    {
        if (! $user instanceof User) {
            $user = User::find($user);
        }

        if (! $user) {
            return false;
        }

        return $user->permissions()->where('code', $permissionCode)->exists();
    }
}
