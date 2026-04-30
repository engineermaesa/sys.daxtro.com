<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use App\Models\Orders\Proforma;
use App\Models\Orders\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class AttachmentController extends Controller
{

    public function download($id)
    {
        $attachment = Attachment::findOrFail($id);

        if (empty($attachment->file_path)) {
            return response()->json(['message' => 'File path not set in attachment record.'], 400);
        }

        $filePath = str_replace('\\', '/', $attachment->file_path);
        $filePath = ltrim($filePath, '/');

        $candidatePaths = [];

        // Normalise common "storage/" prefix used in DB
        if (str_starts_with($filePath, 'storage/')) {
            $relativePath = ltrim(substr($filePath, strlen('storage/')), '/');

            foreach (['public', 'local'] as $disk) {
                try {
                    $candidatePaths[] = Storage::disk($disk)->path($relativePath);
                } catch (\Throwable $e) {
                }
            }
        } else {
            foreach (['public', 'local'] as $disk) {
                try {
                    $candidatePaths[] = Storage::disk($disk)->path($filePath);
                } catch (\Throwable $e) {
                }
            }
        }

        // Remove duplicates
        $candidatePaths = array_values(array_unique($candidatePaths));

        // If this is a proforma PDF, prefer rendering dynamically from the proforma
        if ($attachment->type === 'proforma_pdf') {
            $proforma = Proforma::where('attachment_id', $attachment->id)->first();
            if ($proforma) {
                $quotation = $proforma->quotation;
                $pdf = Pdf::loadView('pdfs.proforma', compact('proforma', 'quotation'))
                    ->setPaper('A4', 'portrait');

                $fileName = ($proforma->proforma_no ?? 'proforma_' . $proforma->id) . '.pdf';
                return $pdf->download($fileName);
            }
        }

        // If this is an invoice PDF, prefer rendering dynamically from the invoice
        if ($attachment->type === 'invoice_pdf') {
            $invoice = Invoice::where('attachment_id', $attachment->id)->with(['proforma.quotation.items', 'proforma.quotation.paymentTerms', 'proforma.quotation.lead', 'proforma.attachment'])->first();
            if ($invoice) {
                $proforma = $invoice->proforma;
                $quotation = $proforma->quotation;
                $pdf = Pdf::loadView('pdfs.invoice', compact('invoice', 'proforma', 'quotation'))
                    ->setPaper('A4', 'portrait');

                $fileName = ($invoice->invoice_no ?? 'invoice_' . $invoice->id) . '.pdf';
                return $pdf->download($fileName);
            }
        }

        // Try each possible path for stored file
        foreach ($candidatePaths as $path) {
            if (is_file($path)) {
                return response()->download($path, basename($path));
            }
        }

        // Fallback for old proforma files that may have inconsistent paths
        if (str_contains($filePath, 'PROFORMA_')) {
            $fileName = basename($filePath);

            foreach (['public', 'local'] as $disk) {
                try {
                    $fixedPath = Storage::disk($disk)->path('proformas/' . $fileName);
                } catch (\Throwable $e) {
                    continue;
                }

                if (is_file($fixedPath)) {
                    return response()->download($fixedPath, $fileName);
                }
            }
        }

        return response()->json([
            'message' => 'File not found',
            'path' => $filePath,
            'tried_paths' => $candidatePaths,
        ], 404);
    }
}
