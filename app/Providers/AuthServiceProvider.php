<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\{User, UserRole, UserPermission};
use App\Policies\{UserPolicy, UserRolePolicy, UserPermissionPolicy};

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        UserRole::class => UserRolePolicy::class,
        UserPermission::class => UserPermissionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
