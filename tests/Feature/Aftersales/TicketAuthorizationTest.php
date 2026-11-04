<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\User;
use App\Services\Aftersales\TicketDocuments;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TicketAuthorizationTest extends TestCase
{
    private function userWithPermissions(int $id, array $permissions): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $id]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn (string $code) => in_array($code, $permissions, true));

        return $user;
    }

    public function test_assigned_technician_is_authorized_when_database_returns_string_ids(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['id' => '3', 'assigned_technician_id' => '56', 'supervisor_id' => '55']);
        $user = $this->userWithPermissions(56, ['aftersales.tickets.view-own', 'aftersales.documents.fill']);

        $this->assertSame($user->id, $ticket->assigned_technician_id);
        $this->assertTrue(TicketDocuments::isTechnician($user, $ticket));
        TicketDocuments::authorizeView($user, $ticket);
    }

    public function test_view_only_assigned_technician_can_read_but_cannot_fill_documents(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['assigned_technician_id' => '56']);
        $user = $this->userWithPermissions(56, ['aftersales.tickets.view-own']);

        TicketDocuments::authorizeView($user, $ticket);
        $this->assertFalse(TicketDocuments::isTechnician($user, $ticket));
    }

    public function test_other_technician_cannot_read_or_fill_the_ticket(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['assigned_technician_id' => '56']);
        $user = $this->userWithPermissions(57, ['aftersales.tickets.view-own', 'aftersales.documents.fill']);

        $this->assertFalse(TicketDocuments::isTechnician($user, $ticket));
        try {
            TicketDocuments::authorizeView($user, $ticket);
            $this->fail('Another technician must be denied.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_assignment_alone_does_not_grant_document_access(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['assigned_technician_id' => '56']);
        $user = $this->userWithPermissions(56, []);

        $this->assertFalse(TicketDocuments::isTechnician($user, $ticket));
        $this->expectException(HttpException::class);
        TicketDocuments::authorizeView($user, $ticket);
    }

    public function test_supervisor_string_id_is_normalized_without_granting_other_users_access(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['supervisor_id' => '55']);
        $supervisor = $this->userWithPermissions(55, ['aftersales.documents.sign']);
        $other = $this->userWithPermissions(54, ['aftersales.documents.sign']);

        $this->assertTrue(TicketDocuments::isSupervisor($supervisor, $ticket));
        $this->assertFalse(TicketDocuments::isSupervisor($other, $ticket));
    }

    public function test_admin_who_is_ticket_supervisor_cannot_sign_without_sign_permission(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['supervisor_id' => '55']);
        $admin = $this->userWithPermissions(55, ['aftersales.tickets.manage']);

        $this->assertFalse(TicketDocuments::isSupervisor($admin, $ticket));
    }

    public function test_view_all_grants_read_access_to_any_ticket_without_manage_rights(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['assigned_technician_id' => '56']);
        $reader = $this->userWithPermissions(99, ['aftersales.tickets.view-all']);

        TicketDocuments::authorizeView($reader, $ticket);
        $this->assertTrue(TicketDocuments::canViewAll($reader));
        $this->assertFalse(TicketDocuments::isManager($reader));
        $this->assertFalse(TicketDocuments::canViewAll($this->userWithPermissions(98, ['aftersales.tickets.view-own'])));
    }

    public function test_null_assignment_and_supervisor_remain_null(): void
    {
        $ticket = (new Ticket)->setRawAttributes(['assigned_technician_id' => null, 'supervisor_id' => null]);

        $this->assertNull($ticket->assigned_technician_id);
        $this->assertNull($ticket->supervisor_id);
    }
}
