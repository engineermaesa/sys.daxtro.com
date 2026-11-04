<?php

namespace App\Console\Commands;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Services\Aftersales\TicketDocumentWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResumeRecomingTickets extends Command
{
    protected $signature = 'tickets:resume-recoming';

    protected $description = 'Resume legacy Recoming tickets on the visit day; v3 waits for the first technician edit';

    public function handle(): int
    {
        $count = 0;

        Ticket::with(['logs', 'visits', 'recomings'])
            ->whereHas('logs', fn ($q) => $q->where('step', TicketLog::STEP_RECOMING))
            ->each(function (Ticket $ticket) use (&$count) {
                if (TicketDocumentWorkflow::enabled($ticket)) {
                    return;
                }
                DB::transaction(function () use ($ticket, &$count) {
                    $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
                    if (TicketDocumentWorkflow::enabled($ticket) || $ticket->progress !== TicketLog::STEP_RECOMING || ! $ticket->hasVisitDue()) {
                        return;
                    }
                    $ticket->logs()->create(['step' => TicketLog::STEP_ON_SITE, 'actor_user_id' => null, 'note' => 'Auto: follow-up visit day arrived']);
                    $count++;
                });
            });

        $this->info("Resumed {$count} recoming ticket(s).");

        return self::SUCCESS;
    }
}
