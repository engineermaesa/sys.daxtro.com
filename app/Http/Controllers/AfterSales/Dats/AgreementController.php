<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\Ticket;
use App\Services\Aftersales\TicketDocuments;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function show(Request $request, Ticket $ticket)
    {
        TicketDocuments::authorizeView($request->user(), $ticket);

        $agreement = TicketDocuments::document($ticket, TicketDocuments::TYPE_AGREEMENT);
        $agreement->load('template');

        return response()->json(['status' => 'success', 'data' => [
            'ticket_id' => $ticket->id,
            'number' => $ticket->ticket_code,
            'status' => $agreement->status,
            'locked' => $agreement->isLocked(),
            'saved_at' => $agreement->saved_at,
            'template' => [
                'version' => $agreement->template->version,
                'title' => $agreement->template->title,
                'sections' => $agreement->template->sections,
                'closing_statement' => $agreement->template->closing_statement,
            ],
            'accepted_clauses' => $agreement->accepted_clauses ?? [],
            'customer_name' => $agreement->customer_name,
            'signatures' => TicketDocuments::signatureSummary($agreement, $ticket),
            'completeness' => TicketDocuments::completeness($ticket, TicketDocuments::TYPE_AGREEMENT),
            'can' => TicketDocuments::can($request->user(), $ticket, $agreement),
        ]]);
    }

    public function saveDraft(Request $request, Ticket $ticket)
    {
        abort_unless(TicketDocuments::isTechnician($request->user(), $ticket), 403, 'Only the assigned technician can fill the Service Agreement.');

        $validated = $request->validate([
            'accepted_clauses' => 'nullable|array',
            'accepted_clauses.*' => 'string|max:10',
            'customer_name' => 'nullable|string|max:255',
        ]);

        $agreement = TicketDocuments::document($ticket, TicketDocuments::TYPE_AGREEMENT);
        $agreement->load('template');
        TicketDocuments::saveAgreementDraft($ticket, $agreement, $validated);

        return $this->show($request, $ticket->refresh());
    }
}
