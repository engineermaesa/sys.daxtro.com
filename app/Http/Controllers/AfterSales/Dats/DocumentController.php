<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketDocumentDispatch;
use App\Models\Aftersales\TicketSignature;
use App\Services\Aftersales\DocumentPdf;
use App\Services\Aftersales\TicketDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        TicketDocuments::authorizeView($request->user(), $ticket);

        $docs = [];
        foreach (TicketDocuments::TYPES as $type) {
            $doc = TicketDocuments::document($ticket, $type);
            $docs[$type] = $this->summary($request, $ticket, $type, $doc);
        }

        return response()->json(['status' => 'success', 'data' => [
            'ticket_id' => $ticket->id,
            'progress' => $ticket->progress,
            'supervisor' => $ticket->supervisor ? ['id' => $ticket->supervisor->id, 'name' => $ticket->supervisor->name] : null,
            'supervisor_missing' => ! $ticket->supervisor_id,
            'documents' => $docs,
        ]]);
    }

    public function sign(Request $request, Ticket $ticket, string $type)
    {
        $validated = $request->validate([
            'role' => 'required|in:' . implode(',', TicketSignature::ROLES),
            'signature' => 'required|string',
            'signer_name' => 'nullable|string|max:255',
        ]);

        $doc = TicketDocuments::document($ticket, $type);
        TicketDocuments::sign($ticket, $doc, $request->user(), $validated['role'], $validated['signature'], $validated['signer_name'] ?? null, $request);

        return response()->json(['status' => 'success', 'message' => 'Signature saved', 'data' => $this->summary($request, $ticket, $type, $doc->refresh())], 201);
    }

    public function signatureImage(Request $request, Ticket $ticket, string $type, string $role)
    {
        TicketDocuments::authorizeView($request->user(), $ticket);
        $doc = TicketDocuments::document($ticket, $type);
        $sig = $doc->signatures()->where('role', $role)->first();
        abort_if(! $sig || ! Storage::disk('local')->exists($sig->file_path), 404, 'No signature.');

        return Storage::disk('local')->response($sig->file_path, null, ['Content-Type' => 'image/png']);
    }

    public function save(Request $request, Ticket $ticket, string $type)
    {
        abort_unless(in_array($type, TicketDocuments::TYPES, true), 404);

        $result = TicketDocuments::save($ticket, $type, $request->user());
        $ticket->refresh();

        $response = [
            'status' => 'success',
            'message' => 'Document saved and locked',
            'data' => $this->summary($request, $ticket, $type, $result['document']) + [
                'warnings' => $result['warnings'],
                'documentation_published' => $ticket->progress === \App\Models\Aftersales\TicketLog::STEP_DOCUMENTATION_PUBLISHED,
            ],
        ];

        if ($type === TicketDocuments::TYPE_AGREEMENT) {
            $response['title'] = 'Saved!';
            $response['message'] = 'Service Agreement for ticket '.$ticket->ticket_code.' has been saved successfully.';
            $response['data'] += [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'status_label' => 'Saved',
            ];
        }

        return response()->json($response);
    }

    public function unlock(Request $request, Ticket $ticket, string $type)
    {
        $doc = TicketDocuments::unlock($ticket, $type, $request->user());

        return response()->json(['status' => 'success', 'message' => 'Document unlocked', 'data' => $this->summary($request, $ticket, $type, $doc)]);
    }

    public function pdf(Request $request, Ticket $ticket, string $type)
    {
        $validated = $request->validate(['copy' => 'required|in:' . implode(',', array_keys(TicketDocumentDispatch::COPY_LABELS))]);

        TicketDocuments::authorizeView($request->user(), $ticket);
        $doc = TicketDocuments::document($ticket, $type);
        $allowed = TicketDocuments::can($request->user(), $ticket, $doc)['download_copies'];
        abort_unless(in_array($validated['copy'], $allowed, true), 403, 'You cannot download this copy.');
        abort_if(! $doc->isLocked(), 422, 'Save the document first to generate the PDF.');

        $name = ($type === TicketDocuments::TYPE_FORM ? 'ServiceForm-' : 'Agreement-') . $ticket->ticket_code . '-' . $validated['copy'] . '.pdf';

        return DocumentPdf::make($ticket, $type, TicketDocumentDispatch::COPY_LABELS[$validated['copy']])->download($name);
    }

    public function dispatches(Request $request, Ticket $ticket, string $type)
    {
        abort_unless(TicketDocuments::isManager($request->user()), 403);
        abort_unless(in_array($type, TicketDocuments::TYPES, true), 404);

        return response()->json(['status' => 'success', 'data' => $this->dispatchRows($ticket, $type)]);
    }

    public function resend(Request $request, Ticket $ticket, string $type, TicketDocumentDispatch $dispatch)
    {
        abort_unless(TicketDocuments::isManager($request->user()), 403);
        abort_unless($dispatch->ticket_id === $ticket->id && $dispatch->document_type === $type, 404);

        TicketDocuments::resend($dispatch);

        return response()->json(['status' => 'success', 'message' => 'Email queued', 'data' => $this->dispatchRows($ticket, $type)], 201);
    }

    private function summary(Request $request, Ticket $ticket, string $type, $doc): array
    {
        return [
            'type' => $type,
            'status' => $doc->status,
            'locked' => $doc->isLocked(),
            'saved_at' => $doc->saved_at,
            'template_version' => $doc->template_version,
            'signatures' => TicketDocuments::signatureSummary($doc, $ticket),
            'completeness' => TicketDocuments::completeness($ticket, $type),
            'can' => TicketDocuments::can($request->user(), $ticket, $doc),
            'dispatches' => TicketDocuments::isManager($request->user()) || TicketDocuments::isSupervisor($request->user(), $ticket) ?$this->dispatchRows($ticket, $type) : [],
        ];
    }

    private function dispatchRows(Ticket $ticket, string $type): array
    {
        return TicketDocumentDispatch::where('ticket_id', $ticket->id)->where('document_type', $type)
            ->latest('id')->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'recipient_role' => $d->recipient_role,
                'recipient_email' => $d->recipient_email,
                'copy_label' => $d->copy_label,
                'status' => $d->status,
                'error' => $d->error,
                'sent_at' => $d->sent_at,
                'created_at' => $d->created_at,
            ])->all();
    }
}
