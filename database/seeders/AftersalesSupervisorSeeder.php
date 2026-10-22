<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;

class AftersalesSupervisorSeeder extends Seeder
{
    public function run(): void
    {
        $role = UserRole::firstOrCreate(['code' => 'supervisor_after_sales'], ['name' => 'Supervisor After Sales']);
        $source = UserRole::where('code', 'after_sales')->first();
        if ($source) {
            $role->permissions()->syncWithoutDetaching($source->permissions()->pluck('user_permissions.id')->all());
        }
    }
}
