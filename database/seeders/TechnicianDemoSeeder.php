<?php

namespace Database\Seeders;

use App\Models\Aftersales\TechnicianProfile;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;
use App\Models\UserRolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TechnicianDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo technicians may only be seeded locally or in testing.');
        }

        DB::transaction(function () {
            $role = UserRole::firstOrCreate(['code' => 'technician'], ['name' => 'Technician']);
            foreach (['dashboard', 'aftersales.tickets.view-own', 'aftersales.documents.fill', 'aftersales.survey-link.share', 'aftersales.documentation.submit', 'aftersales.tickets.recoming'] as $code) {
                $permission = UserPermission::firstOrCreate(['code' => $code], ['name' => $code]);
                $pivot = UserRolePermission::withTrashed()->firstOrCreate(['role_id' => $role->id, 'permission_id' => $permission->id]);
                if ($pivot->trashed()) {
                    $pivot->restore();
                }
            }

            foreach (['Dimas', 'Raka', 'Andi'] as $index => $name) {
                $email = 'technician'.($index + 1).'@example.test';
                $user = User::firstOrCreate(['email' => $email], [
                    'name' => $name.' (Demo Technician)', 'role_id' => $role->id,
                    'password' => Hash::make('Testing123!'), 'grade' => 'Demo',
                ]);
                if ((int) $user->role_id !== (int) $role->id) {
                    throw new \RuntimeException("{$email} already belongs to a different role; the account was left unchanged.");
                }
                TechnicianProfile::firstOrCreate(['user_id' => $user->id], ['certification' => 'Local testing account']);
                $this->command?->info($user->name.' | '.$email.' | '.($user->wasRecentlyCreated ? 'Created; password: Testing123!' : 'Existing account; password unchanged'));
                if (! $user->hasPermission('aftersales.documents.fill')) {
                    throw new \RuntimeException('Technician document permission verification failed.');
                }
            }
        });
    }
}
