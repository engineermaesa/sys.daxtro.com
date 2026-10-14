<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DocumentPdf
{
    public static function make(Ticket $ticket, string $type, string $copyLabel)
    {
        $ticket->load(['customer', 'customerProduct.productType', 'customerProduct.product', 'technician', 'supervisor', 'visits']);
        $doc = TicketDocuments::document($ticket, $type);

        $signatures = collect(TicketDocuments::signatureSummary($doc, $ticket))->map(function ($summary, $role) use ($doc) {
            if (! $summary) {
                return null;
            }
            $sig = $doc->signatures()->where('role', $role)->first();

            return $summary + ['image' => self::dataUri($sig?->file_path, 'image/png')];
        })->all();

        $base = [
            'ticket' => $ticket,
            'copyLabel' => $copyLabel,
            'docNumber' => $ticket->ticket_code,
            'signatures' => $signatures,
            'doc' => $doc,
        ];

        if ($type === TicketDocuments::TYPE_AGREEMENT) {
            return Pdf::loadView('pdfs.aftersales.agreement', $base + [
                'sections' => $doc->template->documentSections(),
                'closing' => $doc->template->closing_statement,
                'accepted' => $doc->accepted_clauses ?? [],
            ])->setPaper('A4', 'portrait');
        }

        $groups = TicketDocuments::formStructure($ticket, $doc);
        $photos = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                foreach ($item['fields'] as $field) {
                    if ($field['photo']) {
                        $photo = \App\Models\Aftersales\TicketPhoto::find($field['photo']['id']);
                        $photos[] = [
                            'caption' => "#{$item['number']} {$item['name']} · {$field['label']} · {$field['value']} {$field['unit']}",
                            'image' => self::dataUri($photo->thumbnail_path ?: $photo->file_path, 'image/jpeg'),
                        ];
                    }
                }
            }
        }

        return Pdf::loadView('pdfs.aftersales.service_form', $base + ['groups' => $groups, 'photos' => $photos])->setPaper('A4', 'portrait');
    }

    private static function dataUri(?string $path, string $mime): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('local')->get($path));
    }
}
