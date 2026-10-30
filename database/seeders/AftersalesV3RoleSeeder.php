<?php

namespace Database\Seeders;

use App\Models\UserPermission;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class AftersalesV3RoleSeeder extends Seeder
{
    private const SPV = ['dashboard', 'customers.view', 'satisfaction-survey.view', 'aftersales.tickets.view-all', 'aftersales.documents.sign'];

    private const HEAD = ['dashboard', 'customers.view', 'satisfaction-survey.view', 'aftersales.tickets.view-all', 'aftersales.tickets.close'];

    public function run(): void
    {
        (new PermissionSeeder)->run();

        $spv = UserRole::firstOrCreate(['code' => 'supervisor_after_sales'], ['name' => 'SPV Technician']);
        $spv->update(['name' => 'SPV Technician']);
        $head = UserRole::firstOrCreate(['code' => 'head_after_sales'], ['name' => 'Head Aftersales']);

        $ids = fn (array $codes) => UserPermission::whereIn('code', $codes)->pluck('id')->all();
        $spv->permissions()->sync($ids(self::SPV));
        $head->permissions()->sync($ids(self::HEAD));

        UserRole::where('code', '!=', 'head_after_sales')->get()
            ->each(fn (UserRole $r) => $r->permissions()->detach($ids(['aftersales.tickets.close'])));
        UserRole::where('code', 'technician')->get()
            ->each(fn (UserRole $r) => $r->permissions()->detach($ids(['aftersales.tickets.recoming'])));
    }
}
