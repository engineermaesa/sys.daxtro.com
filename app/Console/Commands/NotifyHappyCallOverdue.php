<?php

namespace App\Console\Commands;

use App\Models\Aftersales\Ticket;
use App\Services\Aftersales\TicketHappyCalls;
use Illuminate\Console\Command;

class NotifyHappyCallOverdue extends Command
{
    protected $signature = 'tickets:notify-happy-call-overdue';

    protected $description = 'Notify Head Aftersales and the ticket Admin once per overdue Happy Call cycle';

    public function handle(): int
    {
        $count = 0;
        Ticket::where('workflow_version', Ticket::WORKFLOW_V3)
            ->whereHas('currentCycle', fn ($q) => $q->whereNull('ended_at')->where('happy_call_due_at', '<', now('Asia/Jakarta')))
            ->select('tickets.id')->chunkById(100, function ($tickets) use (&$count) {
                foreach ($tickets as $ticket) {
                    $count += TicketHappyCalls::notifyOverdue($ticket->id);
                }
            });
        $this->info("Sent {$count} Happy Call overdue notification(s).");

        return self::SUCCESS;
    }
}
