<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TicketAssignment
{
    private const TECHNICIAN_ROLES = ['technician', 'supervisor_after_sales'];

    public static function rules(): array
    {
        return [
            'technician_ids' => 'sometimes|required|array|list|min:1|prohibits:assigned_technician_id',
            'technician_ids.*' => 'required|integer|distinct|exists:users,id',
            'assigned_technician_id' => 'nullable|integer|exists:users,id',
            'supervisor_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public static function technicians(): Builder
    {
        return User::whereHas('role', fn ($query) => $query->whereIn('code', self::TECHNICIAN_ROLES))
            ->with('role:id,code')->orderBy('name')->orderBy('id');
    }

    public static function technicianIds(array $input, ?Ticket $ticket = null): array
    {
        if (array_key_exists('technician_ids', $input)) {
            return array_map('intval', $input['technician_ids']);
        }
        if (array_key_exists('assigned_technician_id', $input) && $input['assigned_technician_id'] !== null) {
            $id = (int) $input['assigned_technician_id'];

            return $id === $ticket?->assigned_technician_id ? $ticket->technicianIds() : [$id];
        }

        return $ticket?->technicianIds() ?? [];
    }

    public static function supervisors(array $technicianIds, bool $validateTechnicians = true): array
    {
        $roles = User::with('role:id,code')->whereKey($technicianIds)->get()
            ->map(fn (User $user) => $user->role?->code);
        if ($validateTechnicians && (count($technicianIds) !== $roles->count() || $roles->diff(self::TECHNICIAN_ROLES)->isNotEmpty())) {
            throw ValidationException::withMessages(['technician_ids' => 'Select Technician or SPV Technician users.']);
        }

        $headRequired = $roles->contains('supervisor_after_sales');
        $role = $headRequired ? 'head_after_sales' : 'supervisor_after_sales';

        $options = User::whereHas('role', fn ($query) => $query->where('code', $role))
            ->when($headRequired, fn ($query) => $query->orderBy('id')->limit(1), fn ($query) => $query->orderBy('name')->orderBy('id'))
            ->get(['id', 'name']);

        return ['options' => $options, 'head_required' => $headRequired];
    }

    public static function supervisorId(array $input, array $technicianIds, ?Ticket $ticket = null): ?int
    {
        $hasSelection = array_key_exists('technician_ids', $input) || isset($input['assigned_technician_id']);
        if (! $hasSelection && ! array_key_exists('supervisor_id', $input)) {
            return $ticket?->supervisor_id;
        }

        ['options' => $options, 'head_required' => $headRequired] = self::supervisors($technicianIds, $hasSelection);
        $requested = isset($input['supervisor_id']) ? (int) $input['supervisor_id'] : null;
        if ($headRequired) {
            $headId = $options->first()?->id;
            if ($headId === null || ($requested !== null && $requested !== $headId)) {
                throw ValidationException::withMessages(['supervisor_id' => 'The selected technicians require the Head Aftersales supervisor.']);
            }

            return $headId;
        }

        if (array_key_exists('supervisor_id', $input)) {
            if ($requested !== null && ! $options->contains('id', $requested)) {
                throw ValidationException::withMessages(['supervisor_id' => 'Select a user with the SPV Technician role.']);
            }
        } else {
            $requested = $options->contains('id', $ticket?->supervisor_id) ? $ticket->supervisor_id : null;
        }
        if ($requested === null && array_key_exists('technician_ids', $input)) {
            throw ValidationException::withMessages(['supervisor_id' => 'Select an SPV Technician as the ticket supervisor.']);
        }

        return $requested;
    }

    public static function sync(Ticket $ticket, array $ids): void
    {
        if ($ticket->technicians->modelKeys() === $ids) {
            return;
        }

        $ticket->technicians()->sync(collect($ids)->mapWithKeys(fn (int $id, int $position) => [$id => ['position' => $position]])->all());
        $ticket->unsetRelation('technicians');
        if (TicketDocumentWorkflow::enabled($ticket) && ! $ticket->wasChanged(['assigned_technician_id', 'supervisor_id'])) {
            ServiceFormRevisions::advance($ticket);
        }
    }
}
