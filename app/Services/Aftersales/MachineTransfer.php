<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\ServiceCustomer;
use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Aftersales\TicketLog;
use Illuminate\Support\Facades\DB;

class MachineTransfer
{
    public function move(ServiceCustomerProduct $machine, int $destination, ?string $note, int $userId): ServiceCustomerProduct
    {
        return DB::transaction(function () use ($machine, $destination, $note, $userId) {
            $machine = ServiceCustomerProduct::whereKey($machine->id)->lockForUpdate()->firstOrFail();
            $customer = ServiceCustomer::findOrFail($destination);
            abort_if($machine->customer_id === $customer->id, 422, 'Select a different destination customer.');
            $hasOpenTickets = $machine->tickets()->whereDoesntHave('latestLog', fn ($q) => $q->where('step', TicketLog::STEP_CLOSED))->exists();
            abort_if($hasOpenTickets, 422, 'This machine has open tickets. Close them first.');
            $from = $machine->customer_id;
            $note = trim($note ?? '') ?: 'Previously owned by '.$machine->customer->name;
            $machine->update(['customer_id' => $customer->id, 'note' => trim(implode("\n", array_filter([$machine->note, $note]))), 'updated_by' => $userId]);
            DB::table('aftersales_customer_audits')->insert(['customer_id' => $customer->id, 'machine_id' => $machine->id, 'user_id' => $userId, 'action' => 'machine_moved', 'changes' => json_encode(['from_customer_id' => $from, 'to_customer_id' => $customer->id, 'note' => $note]), 'created_at' => now()]);

            return $machine;
        }, 3);
    }
}
