<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use App\Models\Orders\{Quotation, QuotationReview, Proforma, QuotationPaymentTerm, QuotationSignedDocument, QuotationLog};
use App\Models\Attachment;
use App\Models\Leads\LeadClaim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class QuotationController extends Controller
{
        public function download(Request $request, $id)
    {
        // 1. Authorize
        $allowed = ['branch_manager','super_admin','sales'];
        abort_if(! in_array($request->user()->role?->code, $allowed), 403);

        // 2. Load the quotation with its relations
        $quotation = Quotation::with(['lead','items','paymentTerms'])->findOrFail($id);

        // 3. Ensure temp dir exists
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        // 4. Render page-2 (body)
        $bodyPath = "{$tempDir}/body_{$quotation->id}.pdf";
        Pdf::loadView('pdfs.quotation_body', compact('quotation'))
            ->setPaper('A4','portrait')
            ->save($bodyPath);

        if (! File::exists($bodyPath)) {
            throw new \RuntimeException("Body PDF not found at {$bodyPath}");
        }

        // 4.1 Load the active LeadClaim to get Sales info
        $claim = LeadClaim::where('lead_id', $quotation->lead_id)
                    ->whereNull('released_at')
                    ->with('sales.role')
                    ->latest('claimed_at')
                    ->first();

        // 5. Render page-3 (terms & conditions)
        $termsPath = "{$tempDir}/terms_{$quotation->id}.pdf";
        Pdf::loadView('pdfs.quotation_terms', compact('quotation','claim'))
            ->setPaper('A4','portrait')
            ->save($termsPath);

        if (! File::exists($termsPath)) {
            throw new \RuntimeException("Terms PDF not found at {$termsPath}");
        }

        // 6. Paths for the static pages
        $coverPath      = resource_path('pdf/quotation/cover.pdf');
        // $componentsPath = resource_path('pdf/quotation/components.pdf');

        // foreach ([$coverPath, $componentsPath] as $static) {
        //     if (! File::exists($static)) {
        //         throw new \RuntimeException("Static PDF missing at {$static}");
        //     }
        // }

        // 7. Merge: cover → body → terms → components
        $merger = PDFMerger::init();
        $merger->addPDF($coverPath,       'all');
        $merger->addPDF($bodyPath,        'all');
        $merger->addPDF($termsPath,       'all');
        // $merger->addPDF($componentsPath,  'all');

        // 8. Save merged file
        $fileName  = $quotation->quotation_no
                        ? "{$quotation->quotation_no}.pdf"
                        : "quotation_{$quotation->id}.pdf";
        $finalPath = "{$tempDir}/{$fileName}";

        $merger->merge();
        $merger->save($finalPath);

        if (! File::exists($finalPath)) {
            throw new \RuntimeException("Merged PDF not found at {$finalPath}");
        }

        // 9. Clean up temp PDFs
        File::delete([$bodyPath, $termsPath]);

        // 10. Return download (auto-deletes the merged file)
        return response()
            ->download($finalPath)
            ->deleteFileAfterSend(true);
    }



    public function downloadOld(Request $request, $id)
    {
        $allowed = ['branch_manager', 'super_admin', 'sales'];

        if (!in_array($request->user()->role?->code, $allowed)) {
            abort(403);
        }

        $quotation = Quotation::with(['lead', 'items', 'paymentTerms'])
            ->findOrFail($id);

        $html = view('pdfs.quotation', compact('quotation'))->render();
        $pdf  = Pdf::loadHTML($html)->setPaper('A4', 'portrait');

        $fileName = $quotation->quotation_no ? $quotation->quotation_no.'.pdf' : 'quotation_'.$quotation->id.'.pdf';

        return $pdf->download($fileName);
    }
    
    public function show(Request $request, $id)
    {
        $allowed = ['branch_manager', 'sales'];
        if (!in_array($request->user()->role?->code, $allowed)) {
            abort(403);
        }

        $quotation = Quotation::with([
            'lead',
            'items',
            'paymentTerms',
            'reviews',
            'proformas.invoice',
            'proformas.paymentConfirmation.attachment',
            'proformas.paymentConfirmation.financeRequest',
            'signedDocuments.uploader',
            'signedDocuments.attachment',
            'paymentLogs.user',
            'paymentLogs.proforma',
            'paymentLogs.invoice',
        ])
            ->findOrFail($id);

        $rejection = $quotation->reviews()
            ->where('decision', 'reject')
            ->latest('decided_at')
            ->first();

        $claimQuery = LeadClaim::where('lead_id', $quotation->lead_id)
            ->whereNull('released_at');

        if ($request->user()->role?->code === 'sales') {
            $claimQuery->where('sales_id', $request->user()->id);
        }

        $claim = $claimQuery->first();

        return $this->render('pages.orders.quotation-show', compact('quotation', 'claim', 'rejection'));
    }

    public function approve(Request $request, $id)
    {        
        $incentive = env('INCENTIVE_AMOUNT_RATE', 100000);
        $quotation = Quotation::with('paymentTerms', 'lead', 'items', 'reviews')->findOrFail($id);

        $request->validate([
            'notes' => 'required|string',
        ]);

        $userRole = $request->user()->role?->code;
        $bmApproved  = $quotation->reviews()->where('role', 'BM')->where('decision', 'approve')->exists();
        // $dirApproved = $quotation->reviews()->where('role', 'SD')->where('decision', 'approve')->exists();

        // if ($userRole === 'sales_director' && ! $bmApproved) {
        //     abort(403, 'Branch Manager approval is required before Sales Director.');
        // }

        if ($userRole === 'branch_manager' && $bmApproved) {
            abort(403, 'Branch Manager has already approved this quotation.');
        }

        // if ($userRole === 'sales_director' && $dirApproved) {
        //     abort(403, 'Sales Director has already approved this quotation.');
        // }
        DB::transaction(function () use ($quotation, $request, $incentive, $userRole) {            
            $role = $userRole === 'branch_manager' ? 'BM' : $userRole;            
            
            QuotationReview::create([
                'quotation_id'      => $quotation->id,
                'reviewer_id'       => $request->user()->id,
                'role'              => $role,
                'decision'          => 'approve',
                'notes'             => $request->input('notes'),
                'incentive_nominal' => $incentive,
                'decided_at'        => now(),
            ]);

            QuotationLog::create([
                'quotation_id' => $quotation->id,
                'action'       => 'approve',
                'user_id'      => $request->user()->id,
                'logged_at'    => now(),
            ]);

            $bmApproved  = $quotation->reviews()->where('role', 'BM')->where('decision', 'approve')->exists();
            // $dirApproved = $quotation->reviews()->where('role', 'SD')->where('decision', 'approve')->exists();

            if ($bmApproved && $quotation->status !== 'published') {
                $quotation->update([
                    'status' => 'published',
                    'expiry_date' => now()->addDays(30),
                ]);            

                if ($quotation->booking_fee) {
                    $proforma = $quotation->proformas()->firstOrCreate(
                        ['proforma_type' => 'booking_fee'],
                        ['term_no' => null]
                    );

                    $proforma->fill([
                        'amount'      => $quotation->booking_fee,
                        'status'      => 'confirmed',
                        'proforma_no' => 'PROFORMA_'.$proforma->id,
                        'issued_at'   => now(),
                        'issued_by'   => $request->user()->id,
                    ])->save();

                    $html = view('pdfs.proforma', compact('proforma', 'quotation'))->render();
                    $pdf  = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
                    $fileName = $proforma->proforma_no.'.pdf';

                    $storagePath = storage_path('app/public/proformas');
                    if (!File::exists($storagePath)) {
                        File::makeDirectory($storagePath, 0755, true);
                    }

                    $filePath = 'proformas/'.$fileName;
                    Storage::disk('public')->put($filePath, $pdf->output());

                    $attachment = Attachment::create([
                        'type'        => 'proforma_pdf',
                        // Use consistent path in database
                        'file_path'   => 'proformas/'.$fileName,  
                        'mime_type'   => 'application/pdf',
                        'size'        => Storage::disk('public')->size($filePath),
                        'uploaded_by' => $request->user()->id,
                    ]);

                    $proforma->update(['attachment_id' => $attachment->id]);
                }            

                foreach ($quotation->paymentTerms as $term) {
                    $amount = $quotation->grand_total * ($term->percentage / 100);
                    $type   = $term->term_no == 1 ? 'down_payment' : 'term_payment';

                    $proforma = $quotation->proformas()->firstOrCreate(
                        ['term_no' => $term->term_no],
                        ['proforma_type' => $type]
                    );

                    $proforma->fill([
                        'amount'      => $amount,
                        'status'      => 'confirmed',
                        'proforma_no' => 'PROFORMA_'.$proforma->id,
                        'issued_at'   => now(),
                    ])->save();

                    $storagePath = storage_path('app/public/proformas');
                    if (!File::exists($storagePath)) {
                        File::makeDirectory($storagePath, 0755, true);
                    }

                    $html = view('pdfs.proforma', compact('proforma', 'quotation'))->render();
                    $pdf  = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
                    $fileName = $proforma->proforma_no.'.pdf';

                    $filePath = 'proformas/'.$fileName;
                    Storage::disk('public')->put($filePath, $pdf->output());

                    $attachment = Attachment::create([
                        'type'        => 'proforma_pdf',
                        'file_path'   => 'storage/proformas/'.$fileName,
                        'mime_type'   => 'application/pdf',
                        'size'        => Storage::disk('public')->size($filePath),
                        'uploaded_by' => $request->user()->id,
                    ]);

                    $proforma->update(['attachment_id' => $attachment->id]);             
                }

                \App\Models\UserBalanceLog::create([
                    'user_id'       => $quotation->created_by,
                    'amount'        => $incentive,
                    'quotation_id'  => $quotation->id,
                    'description'   => 'Incentive for quotation ' . $quotation->quotation_no,
                    'status'        => 'pending',
                    'created_at'    => now(),
                ]);

            }
        });

        return back()->with('status', 'Quotation approved');
    }

    public function reject(Request $request, $id)
    {
        $quotation = Quotation::findOrFail($id);

        DB::transaction(function () use ($quotation, $request) {
            $role = $request->user()->role?->code === 'branch_manager' ? 'BM' : $request->user()->role?->code;            

            $quotation->update(['status' => 'rejected']);

            QuotationReview::create([
                'quotation_id' => $quotation->id,
                'reviewer_id'  => $request->user()->id,
                'role'        => $role,
                'decision'    => 'reject',
                'notes'       => $request->input('notes'),
                'decided_at'  => now(),
            ]);

            QuotationLog::create([
                'quotation_id' => $quotation->id,
                'action'       => 'reject',
                'user_id'      => $request->user()->id,
                'logged_at'    => now(),
            ]);
        });

        return back()->with('status', 'Quotation rejected');
    }

    public function uploadSignedDocument(Request $request, $id)
    {
        abort_if($request->user()->role?->code !== 'sales', 403);

        $quotation = Quotation::findOrFail($id);

        $data = $request->validate([
            'file'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'description'  => 'nullable|string|max:255',
            'signed_date'  => 'required|date',
        ]);

        $file = $request->file('file');
        $filename = time().'_'.$file->getClientOriginalName();
        $path = $file->storeAs('signed-quotations', $filename, 'local');

        $attachment = Attachment::create([
            'type'        => 'quotation_signed',
            'file_path'   => 'storage/'.$path,
            'mime_type'   => $file->getClientMimeType(),
            'size'        => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        $quotation->signedDocuments()->create([
            'attachment_id' => $attachment->id,
            'description'   => $data['description'] ?? null,
            'signed_date'   => $data['signed_date'],
            'uploader_id'   => $request->user()->id,
        ]);

        return back()->with('status', 'Signed document uploaded');
    }

    public function logs($id)
    {
        $quotation = Quotation::with(['logs.user'])->findOrFail($id);

        $logs = $quotation->logs->map(function ($log) {
            return [
                'logged_at' => $log->logged_at ? $log->logged_at->format('d M Y H:i') : '-',
                'action'    => $log->action,
                'user'      => $log->user->name ?? '-',
            ];
        });

        return response()->json($logs);
    }
}
