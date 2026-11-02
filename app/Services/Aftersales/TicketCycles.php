<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCycle;
use Illuminate\Support\Facades\DB;

class TicketCycles
{
    public static function initial(Ticket $ticket): TicketCycle
    {
        $cycle = DB::transaction(function () use ($ticket) {
            $locked = Ticket::withTrashed()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            return $locked->cycles()->firstOrCreate(['cycle_no' => 1], [
                'started_at' => $locked->created_at ?? now(),
                'sla_value' => $locked->sla_value, 'sla_unit' => $locked->sla_unit,
                'sla_due_at' => $locked->sla_due_at,
            ]);
        });

        $ticket->unsetRelation('currentCycle')->unsetRelation('cycles');

        return $cycle;
    }

    public static function startRecoming(Ticket $ticket): TicketCycle
    {
        $cycle = DB::transaction(function () use ($ticket) {
            $locked = Ticket::withTrashed()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            self::initial($locked);
            $previous = $locked->cycles()->orderByDesc('cycle_no')->firstOrFail();
            $now = now();
            $previous->update(['ended_at' => $now]);
            if (TicketDocumentWorkflow::enabled($locked)) {
                $locked->update(['happy_call_due_at' => null]);
            }

            return $locked->cycles()->create([
                'cycle_no' => $previous->cycle_no + 1, 'started_at' => $now,
            ]);
        });

        $ticket->unsetRelation('currentCycle')->unsetRelation('cycles');

        return $cycle;
    }
}
