<?php

namespace App\Console\Commands;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use Illuminate\Console\Command;

class ResumeRecomingTickets extends Command
{
    protected $signature = 'tickets:resume-recoming';

    protected $description = 'Log On Site for Recoming tickets whose follow-up visit day has arrived';

    public function handle(): int
    {
        $count = 0;

        Ticket::with(['logs', 'visits'])
            ->whereHas('logs', fn ($q) => $q->where('step', TicketLog::STEP_RECOMING))
            ->each(function (Ticket $ticket) use (&$count) {
                if ($ticket->progress !== TicketLog::STEP_RECOMING || ! $ticket->hasVisitDue()) {
                    return;
                }

                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'step' => TicketLog::STEP_ON_SITE,
                    'actor_user_id' => null,
                    'note' => 'Auto: follow-up visit day arrived',
                ]);
                $count++;
            });

        $this->info("Resumed {$count} recoming ticket(s).");

        return self::SUCCESS;
    }
}
