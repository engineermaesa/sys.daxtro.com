<?php

namespace App\Jobs;

use App\Mail\TicketDocumentMail;
use App\Models\Aftersales\TicketDocumentDispatch;
use App\Services\Aftersales\DocumentPdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendTicketDocumentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public int $dispatchId) {}

    public function handle(): void
    {
        $dispatch = TicketDocumentDispatch::with('ticket')->findOrFail($this->dispatchId);

        try {
            $pdf = DocumentPdf::make($dispatch->ticket, $dispatch->document_type, $dispatch->copy_label)->output();

            Mail::to($dispatch->recipient_email)->send(new TicketDocumentMail($dispatch, $pdf));

            $dispatch->update(['status' => 'sent', 'sent_at' => Carbon::now('Asia/Jakarta'), 'error' => null]);
        } catch (\Throwable $e) {
            $dispatch->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }
}
