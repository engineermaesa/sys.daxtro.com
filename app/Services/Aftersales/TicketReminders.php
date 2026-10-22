<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;

class TicketReminders
{
    public static function for(Ticket $ticket): array
    {
        $saved = $ticket->serviceForm?->saved_at;
        $lastCycle = $ticket->logs->where('step', TicketLog::STEP_RECOMING)->max('id') ?? 0;
        $logs = $ticket->logs->where('id', '>', $lastCycle);
        $happy = $logs->contains('step', TicketLog::STEP_HAPPY_CALL_COMPLETED);
        $response = $ticket->satisfactions->sortByDesc('id')->first();
        $currentResponse = $response && (! $lastCycle || $response->created_at >= $ticket->logs->firstWhere('id', $lastCycle)->created_at);
        $shared = $ticket->surveyLink?->shared_at;
        $closed = $ticket->progress === TicketLog::STEP_CLOSED;

        return ['survey_due_at' => $saved?->copy()->addDays(3), 'survey_due' => ! $closed && $saved && now()->gte($saved->copy()->addDays(3)) && (! $shared || $shared->lt($saved)) && ! $currentResponse,
            'happy_call_due' => ! $closed && $currentResponse && ! $happy && ($response->filled_at ?? $response->created_at)->lte(now()->subDay()),
            'happy_call_completed' => $happy, 'happy_call_required' => (bool) $ticket->happy_call_required, 'rating_scale_max' => 3];
    }
}
