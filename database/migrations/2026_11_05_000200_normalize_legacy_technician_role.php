<?php

use App\Models\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $legacyRoles = UserRole::whereRaw('LOWER(TRIM(name)) = ?', ['technician'])
                ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
                ->get();

            if ($legacyRoles->isEmpty()) {
                return;
            }

            $canonical = UserRole::firstOrCreate(['code' => 'technician'], ['name' => 'Technician']);
            foreach ($legacyRoles as $legacy) {
                // Keep existing role grants when consolidating duplicate technician roles.
                $canonical->permissions()->syncWithoutDetaching($legacy->permissions()->pluck('user_permissions.id')->all());
                DB::table('users')->where('role_id', $legacy->id)->update(['role_id' => $canonical->id]);
            }
        });
    }

    // Keep normalized user assignments and existing permissions when rolling back.
    public function down(): void {}
};
