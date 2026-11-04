<?php

namespace App\Mail;

use App\Models\Aftersales\TicketDocumentDispatch;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketDocumentMail extends Mailable
{
    public function __construct(public TicketDocumentDispatch $dispatch, public string $pdf) {}

    private function title(): string
    {
        return $this->dispatch->document_type === 'agreement' ? 'Service / Repair Work Agreement' : 'Service Form';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[Daxtro] {$this->title()} {$this->dispatch->ticket->ticket_code} ({$this->dispatch->copy_label})");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.aftersales.document', with: [
            'title' => $this->title(),
            'ticket' => $this->dispatch->ticket,
            'copyLabel' => $this->dispatch->copy_label,
        ]);
    }

    public function attachments(): array
    {
        $name = ($this->dispatch->document_type === 'agreement' ? 'Agreement-' : 'ServiceForm-') . $this->dispatch->ticket->ticket_code . '.pdf';

        return [Attachment::fromData(fn () => $this->pdf, $name)->withMime('application/pdf')];
    }
}
