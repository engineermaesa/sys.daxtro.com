<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;

class TicketCycleEdits
{
    public static function record(Ticket $ticket, int $actorId): void
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return;
        }
        $cycle = $ticket->currentCycle()->lockForUpdate()->firstOrFail();
        if ($cycle->service_form_first_edited_at) {
            return;
        }
        $cycle->update(['service_form_first_edited_at' => now('Asia/Jakarta'), 'service_form_first_edited_by' => $actorId]);
        if ($cycle->cycle_no > 1 && ! $cycle->logs()->where('step', TicketLog::STEP_REPAIR)->exists()) {
            $cycle->logs()->create([
                'ticket_id' => $ticket->id, 'step' => TicketLog::STEP_REPAIR, 'actor_user_id' => $actorId,
                'note' => 'Auto: first Service Form edit in Recoming cycle '.$cycle->cycle_no,
            ]);
        }
        $ticket->unsetRelation('currentCycle')->unsetRelation('cycles')->unsetRelation('logs');
    }
}
