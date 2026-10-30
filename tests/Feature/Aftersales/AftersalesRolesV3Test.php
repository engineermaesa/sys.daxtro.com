<?php

namespace Tests\Feature\Aftersales;

use App\Http\Controllers\AfterSales\Dats\TicketController;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketAgreement;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AftersalesV3RoleSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AftersalesRolesV3Test extends TestCase
{
    private function createRoleTables(): void
    {
        foreach (['user_roles', 'user_permissions'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code');
                $t->string('description')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('user_role_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
        });
    }

    private function codes(string $role): array
    {
        return DB::table('user_role_permissions')
            ->join('user_roles', 'user_roles.id', '=', 'user_role_permissions.role_id')
            ->join('user_permissions', 'user_permissions.id', '=', 'user_role_permissions.permission_id')
            ->where('user_roles.code', $role)->pluck('user_permissions.code')->sort()->values()->all();
    }

    public function test_seeder_builds_the_four_aftersales_roles_and_is_idempotent(): void
    {
        $this->createRoleTables();
        (new \Database\Seeders\PermissionSeeder)->run();
        foreach (['after_sales' => 'After Sales', 'technician' => 'Technician', 'supervisor_after_sales' => 'Supervisor After Sales'] as $code => $name) {
            UserRole::create(['code' => $code, 'name' => $name]);
        }
        $id = fn (string $code) => DB::table('user_permissions')->where('code', $code)->value('id');
        $grant = fn (string $role, array $codes) => array_map(fn ($c) => DB::table('user_role_permissions')->insert(['role_id' => UserRole::where('code', $role)->value('id'), 'permission_id' => $id($c)]), $codes);
        $grant('after_sales', ['aftersales.tickets.manage', 'aftersales.tickets.close']);
        $grant('technician', ['aftersales.documents.fill', 'aftersales.tickets.recoming']);
        $grant('supervisor_after_sales', ['aftersales.tickets.manage', 'aftersales.tickets.assign']);

        (new AftersalesV3RoleSeeder)->run();
        (new AftersalesV3RoleSeeder)->run();

        $this->assertSame(['aftersales.tickets.manage'], $this->codes('after_sales'));
        $this->assertSame(['aftersales.documents.fill'], $this->codes('technician'));
        $this->assertSame(['aftersales.documents.sign', 'aftersales.tickets.view-all', 'customers.view', 'dashboard', 'satisfaction-survey.view'], $this->codes('supervisor_after_sales'));
        $this->assertSame(['aftersales.tickets.close', 'aftersales.tickets.view-all', 'customers.view', 'dashboard', 'satisfaction-survey.view'], $this->codes('head_after_sales'));
        $this->assertSame('SPV Technician', UserRole::where('code', 'supervisor_after_sales')->value('name'));
        $this->assertSame(1, UserRole::where('code', 'head_after_sales')->count());
        $this->assertSame(1, DB::table('user_permissions')->where('code', 'aftersales.tickets.view-all')->count());
        // Only Head Aftersales holds close; nobody else may keep it.
        $this->assertSame(1, DB::table('user_role_permissions')->where('permission_id', $id('aftersales.tickets.close'))->count());
    }

    private function capabilities(array $permissions, int $userId, array $ticketAttributes, string $step, array $docStatus = ['draft', 'draft']): array
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $userId]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($code) => in_array($code, $permissions, true));

        $ticket = (new Ticket)->setRawAttributes($ticketAttributes + ['id' => 1]);
        $ticket->setRelation('logs', collect([(new TicketLog)->forceFill(['id' => 1, 'step' => $step])]));
        $ticket->setRelation('visits', collect());
        $ticket->setRelation('recomings', collect());
        $ticket->setRelation('serviceForm', (new TicketServiceForm)->forceFill(['status' => $docStatus[0]]));
        $ticket->setRelation('agreement', (new TicketAgreement)->forceFill(['status' => $docStatus[1]]));

        $method = new \ReflectionMethod(TicketController::class, 'capabilities');

        return $method->invoke(new TicketController, $user, $ticket);
    }

    public function test_capabilities_per_role(): void
    {
        $ticket = ['assigned_technician_id' => 10, 'supervisor_id' => 20];
        $admin = $this->capabilities(['aftersales.tickets.manage', 'aftersales.tickets.assign'], 1, $ticket, TicketLog::STEP_REPAIR);
        $this->assertTrue($admin['can_edit'] && $admin['can_assign'] && $admin['can_schedule'] && $admin['can_trigger_recoming'] && $admin['can_record_happy_call']);
        $this->assertFalse($admin['can_close'] || $admin['can_fill_service_form'] || $admin['can_sign_service_form'] || $admin['can_sign_agreement']);

        $technician = $this->capabilities(['aftersales.tickets.view-own', 'aftersales.documents.fill'], 10, $ticket, TicketLog::STEP_REPAIR);
        $this->assertTrue($technician['can_fill_service_form']);
        $this->assertSame([], array_keys(array_filter(array_diff_key($technician, ['can_fill_service_form' => 1]))));

        $spv = $this->capabilities(['aftersales.documents.sign', 'aftersales.tickets.view-all'], 20, $ticket, TicketLog::STEP_REPAIR);
        $this->assertTrue($spv['can_sign_service_form'] && $spv['can_sign_agreement']);
        $this->assertSame(['can_sign_service_form', 'can_sign_agreement'], array_keys(array_filter($spv)));
        $otherSpv = $this->capabilities(['aftersales.documents.sign'], 21, $ticket, TicketLog::STEP_REPAIR);
        $this->assertSame([], array_keys(array_filter($otherSpv)));

        $head = $this->capabilities(['aftersales.tickets.close', 'aftersales.tickets.view-all'], 30, $ticket, TicketLog::STEP_DOCUMENTATION_PUBLISHED);
        $this->assertSame(['can_close'], array_keys(array_filter($head)));
        $closed = $this->capabilities(['aftersales.tickets.close'], 30, $ticket, TicketLog::STEP_CLOSED);
        $this->assertFalse($closed['can_close']);
    }

    public function test_locked_documents_enable_unlock_for_admin_and_disable_signing(): void
    {
        $ticket = ['assigned_technician_id' => 10, 'supervisor_id' => 20];
        $admin = $this->capabilities(['aftersales.tickets.manage'], 1, $ticket, TicketLog::STEP_DOCUMENTATION_PUBLISHED, ['saved', 'saved']);
        $this->assertTrue($admin['can_unlock_service_form']);
        $spv = $this->capabilities(['aftersales.documents.sign'], 20, $ticket, TicketLog::STEP_DOCUMENTATION_PUBLISHED, ['saved', 'saved']);
        $this->assertFalse($spv['can_sign_service_form'] || $spv['can_unlock_service_form']);
    }
}
