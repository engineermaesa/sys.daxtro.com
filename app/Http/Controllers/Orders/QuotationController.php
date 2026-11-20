<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use App\Models\Orders\{Quotation, QuotationReview, Proforma, QuotationPaymentTerm, QuotationSignedDocument, QuotationLog, TempQuotation};
use App\Models\Attachment;
use App\Models\Leads\LeadActivityList;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadSource;
use App\Models\Leads\LeadStatus;
use App\Models\Masters\Branch;
use App\Models\User;
use App\Services\AutoTrashService;
use App\Services\MyLeadQueryService;
use App\Services\Orders\QuotationRevisions;
use App\Notifications\Orders\QuotationPendingFinanceNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// duplicate imports removed
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        AutoTrashService::triggerIfNeeded();

        $user = $request->user();
        $selfScopedRoles = ['sales', 'branch_manager', 'sales_director'];

        $claims = LeadClaim::whereNull('released_at')
            ->with('lead');

        if (in_array($user->role?->code, $selfScopedRoles, true)) {
            $claims->where('sales_id', $user->id);
        }

        $counts = $claims->get()
            ->groupBy(fn($claim) => $claim->lead->status_id)
            ->map->count();

        $cold = $counts[LeadStatus::COLD] ?? 0;
        $warm = $counts[LeadStatus::WARM] ?? 0;
        $hot  = $counts[LeadStatus::HOT] ?? 0;
        $deal = $counts[LeadStatus::DEAL] ?? 0;

        $all = $cold + $warm + $hot + $deal;

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'leadCounts' => [
                    'all'  => $all,
                    'cold' => $counts[LeadStatus::COLD] ?? 0,
                    'warm' => $counts[LeadStatus::WARM] ?? 0,
                    'hot'  => $counts[LeadStatus::HOT] ?? 0,
                    'deal' => $counts[LeadStatus::DEAL] ?? 0,
                ],
                'activities' => LeadActivityList::all(),
            ]);
        }

        // Diakalin, ini index buat finance. Jadi ambil leads warm & hot & deal aja, end point API pake my leads di api.php. view nya pasti sama kayak my leads.
        $branches = Branch::orderBy('name')->get(['id', 'name', 'code']);
        $sales = User::query()
            ->whereHas('role', fn($query) => $query->whereIn('code', ['sales', 'branch_manager']))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id', 'role_id']);

        return view('pages.orders.quotation-index', [
            'leadCounts' => [
                'all'  => $all,
                'cold' => $counts[LeadStatus::COLD] ?? 0,
                'warm' => $counts[LeadStatus::WARM] ?? 0,
                'hot'  => $counts[LeadStatus::HOT] ?? 0,
                'deal' => $counts[LeadStatus::DEAL] ?? 0,
            ],
            'activities' => LeadActivityList::all(),
            'leadSources' => LeadSource::orderBy('name')->get(),
            'branches' => $branches,
            'sales' => $sales,
        ]);
           
    }
    public function revisionHistory(Request $request, $id)
    {
        $this->authorizeRevisionAccess($request);
        $quotation = Quotation::with(['lead', 'createdBy' => fn ($query) => $query->withTrashed()])->findOrFail($id);
        app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);
        $quotation->refresh();
        $revisions = $quotation->revisions()->where('status', 'submitted')
            ->with(['createdBy' => fn ($query) => $query->withTrashed()])->orderByDesc('id')->get();
        $reviewsByRevision = $quotation->reviewHistory()
            ->with(['reviewer' => fn ($query) => $query->withTrashed()])->get()->groupBy('revision_no');
        $claim = LeadClaim::where('lead_id', $quotation->lead_id)->whereNull('released_at')->whereNull('trash_note')
            ->with('sales')->latest('claimed_at')->latest('id')->first();

        return $this->render('pages.orders.quotation-revisions', compact('quotation', 'revisions', 'reviewsByRevision', 'claim'));
    }

    public function showRevision(Request $request, $id, $revisionNo)
    {
        $this->authorizeRevisionAccess($request);
        $quotation = Quotation::with('lead')->findOrFail($id);
        $revision = $this->submittedRevision($quotation, (int) $revisionNo);
        if ($revision->revision_no === $quotation->revision_no) {
            return redirect()->route('quotations.show', $quotation->id);
        }
        $reviews = $quotation->reviewHistory()->where('revision_no', $revision->revision_no)
            ->with(['reviewer' => fn ($query) => $query->withTrashed()])->get();

        return $this->render('pages.orders.quotation-revision', compact('quotation', 'revision', 'reviews'));
    }

    private function authorizeRevisionAccess(Request $request): void
    {
        abort_unless(in_array($request->user()->role?->code,
            ['sales', 'branch_manager', 'finance', 'finance_director', 'super_admin'], true), 403);
    }

    private function submittedRevision(Quotation $quotation, int $revisionNo): TempQuotation
    {
        app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);

        return $quotation->revisions()->where('status', 'submitted')->where('revision_no', $revisionNo)
            ->with(['items', 'paymentTerms', 'createdBy' => fn ($query) => $query->withTrashed()])->firstOrFail();
    }

    public function download(Request $request, $id, $revisionNo = null)
    {
        // 1. Authorize
        $allowed = ['branch_manager','super_admin','sales', 'finance', 'finance_director'];
        abort_if(! in_array($request->user()->role?->code, $allowed), 403);

        // 2. Load the quotation with its relations
        $quotation = Quotation::with(['lead','items','paymentTerms'])->findOrFail($id);

        app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);

        if ($revisionNo !== null) {
            $revision = $this->submittedRevision($quotation, (int) $revisionNo);
            // Build an in-memory document from the selected snapshot; never save it to the official header.
            $quotation->forceFill($revision->only([
                'revision_no', 'revision_code', 'subtotal', 'tax_pct', 'tax_total',
                'total_discount', 'grand_total', 'booking_fee',
            ]));
            $quotation->setRelation('items', $revision->items);
            $quotation->setRelation('paymentTerms', $revision->paymentTerms);
            $quotation->setRelation('createdBy', $revision->createdBy);
            $quotation->quotation_no = $revision->revision_code;
        }

        // Get the user who created the quotation or current user
        $user = $quotation->createdBy ?? auth()->user();

        // 3. Ensure temp dir exists
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        // 4.1 Load the active LeadClaim to get Sales info
        $claim = LeadClaim::where('lead_id', $quotation->lead_id)
                    ->whereNull('released_at')
                    ->with('sales.role')
                    ->latest('claimed_at')
                    ->first();

        // 4. Render page-2 (body) - ADD $user and $claim variables
        $documentSuffix = isset($revision) ? '_R'.$revision->revision_no.'_'.\Illuminate\Support\Str::uuid() : '';
        $bodyPath = "{$tempDir}/body_{$quotation->id}{$documentSuffix}.pdf";
        Pdf::loadView('pdfs.quotation_body', compact('quotation', 'user', 'claim'))
            ->setPaper('A4','portrait')
            ->save($bodyPath);

        if (! File::exists($bodyPath)) {
            throw new \RuntimeException("Body PDF not found at {$bodyPath}");
        }

        // // 5. Render page-3 (terms & conditions) - ADD $user variable
        // $termsPath = "{$tempDir}/terms_{$quotation->id}.pdf";
        // Pdf::loadView('pdfs.quotation_terms', compact('quotation','claim', 'user'))
        //     ->setPaper('A4','portrait')
        //     ->save($termsPath);

        // if (! File::exists($termsPath)) {
        //     throw new \RuntimeException("Terms PDF not found at {$termsPath}");
        // }

        // 6. Paths for the static pages
        // $coverPath      = resource_path('pdf/quotation/cover.pdf');
        // $componentsPath = resource_path('pdf/quotation/components.pdf');

        // foreach ([$coverPath, $componentsPath] as $static) {
        //     if (! File::exists($static)) {
        //         throw new \RuntimeException("Static PDF missing at {$static}");
        //     }
        // }

        // 7. Merge: cover → body → terms → components
        $merger = PDFMerger::init();
        // $merger->addPDF($coverPath,       'all');
        $merger->addPDF($bodyPath,        'all');
        // $merger->addPDF($termsPath,       'all');
        // $merger->addPDF($componentsPath,  'all');

        // 8. Save merged file
        $fileName  = isset($revision) ? $revision->revision_code.'.pdf' : ($quotation->quotation_no
                        ? "{$quotation->quotation_no}.pdf"
                        : "quotation_{$quotation->id}.pdf");
        $finalPath = isset($revision) ? "{$tempDir}/{$documentSuffix}.pdf" : "{$tempDir}/{$fileName}";

        $merger->merge();
        $merger->save($finalPath);

        if (! File::exists($finalPath)) {
            throw new \RuntimeException("Merged PDF not found at {$finalPath}");
        }

        if (isset($revision)) {
            File::delete($bodyPath);
        }

        // 9. Clean up temp PDFs
        // File::delete([$bodyPath, $termsPath]);

        // 10. Return download (auto-deletes the merged file)
        return response()
            ->download($finalPath, $fileName)
            ->deleteFileAfterSend(true);
    }

    public function downloadOld(Request $request, $id)
    {
        $allowed = ['branch_manager', 'super_admin', 'sales', 'finance', 'finance_director'];

        if (!in_array($request->user()->role?->code, $allowed)) {
            abort(403);
        }

        $quotation = Quotation::with(['lead', 'items', 'paymentTerms'])
            ->findOrFail($id);

        app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);

        $html = view('pdfs.quotation', compact('quotation'))->render();
        $pdf  = Pdf::loadHTML($html)->setPaper('A4', 'portrait');

        $fileName = $quotation->quotation_no ? $quotation->quotation_no.'.pdf' : 'quotation_'.$quotation->id.'.pdf';

        return $pdf->download($fileName);
    }
    
    public function show(Request $request, $id)
    {
        $allowed = ['branch_manager', 'sales', 'finance', 'finance_director', 'super_admin'];
        if (!in_array($request->user()->role?->code, $allowed)) {
            abort(403);
        }

        $quotation = Quotation::with([
            'lead',
            'items',
            'paymentTerms',
            'currentReviews',
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

        app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);

        $rejection = $quotation->currentReviews()
            ->where('decision', 'reject')
            ->latest('decided_at')
            ->first();

        $claimQuery = LeadClaim::where('lead_id', $quotation->lead_id)
            ->whereNull('released_at');

        if ($request->user()->role?->code === 'sales') {
            $claimQuery->where('sales_id', $request->user()->id);
        }

        $claim = $claimQuery->first();

        \Log::info('Quotation Show Debug', [
            'quotation_id' => $id,
            'user_role' => $request->user()->role?->code,
            'user_id' => $request->user()->id,
            'quotation_status' => $quotation->status,
            'has_claim' => $claim !== null,
            'claim_id' => $claim?->id,
        ]);

        return $this->render('pages.orders.quotation-show', compact('quotation', 'claim', 'rejection'));
    }

    public function cancelDraft(Request $request, $draftId)
    {
        abort_unless($request->user()->role?->code === 'sales', 403);
        $target = TempQuotation::withTrashed()->findOrFail($draftId);

        $isRevision = DB::transaction(function () use ($request, $target) {
            
            $parentQuery = Quotation::where('lead_id', $target->lead_id);
            if ($target->quotation_id !== null) {
                $parentQuery->withTrashed()->whereKey($target->quotation_id);
            } else {
                $parentQuery->orderByDesc('id');
            }
            $quotation = $parentQuery->lockForUpdate()->first();
            abort_unless(MyLeadQueryService::baseClaimsQuery($request)->where('lead_id', $target->lead_id)
                ->lockForUpdate()->first(), 403);
            $draft = TempQuotation::withTrashed()->whereKey($target->id)->lockForUpdate()->firstOrFail();
            abort_unless($draft->lead_id === $target->lead_id && ! $draft->trashed() && $draft->status === 'draft',
                409, 'Only an active, unsubmitted draft can be cancelled. Reload the quotation.');

            $draft->delete();
            QuotationLog::create([
                'quotation_id' => $quotation?->id, 'temp_quotation_id' => $draft->id,
                'action' => 'draft_cancelled', 'user_id' => $request->user()->id,
                'logged_at' => Carbon::now('Asia/Jakarta'),
            ]);

            return $quotation !== null || $draft->quotation_id !== null;
        }, 3);

        $message = $isRevision ? 'Revision cancelled successfully' : 'Draft discarded successfully';
        if ($request->expectsJson() || $request->ajax()) {
            return $this->setJsonResponse($message, ['redirect_url' => route('leads.my')]);
        }

        return redirect()->route('leads.my')->with('status', $message);
    }

    public function submitToFinance(Request $request, $id)
    {
        $user = $request->user();
        $role = $user->role?->code;
        abort_unless(in_array($role, ['sales', 'branch_manager'], true), 403);
        $data = $request->validate(['revision_no' => 'required|integer|min:0']);

        [$quotation, $sales, $bm, $approval] = DB::transaction(function () use ($request, $id, $data, $user, $role) {
            $quotation = Quotation::with('lead')->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($role === 'sales') {
                abort_unless(MyLeadQueryService::baseClaimsQuery($request)
                    ->where('lead_id', $quotation->lead_id)->exists(), 403);
            } else {
                abort_unless($user->branch_id && (int) $user->branch_id === (int) $quotation->lead?->branch_id, 403);
            }
            abort_unless($quotation->status === 'approved_by_bm', 409,
                'Only a quotation approved by BM can be submitted to Finance.');
            abort_unless($quotation->revision_no === (int) $data['revision_no'], 409,
                'The quotation revision has changed. Reload it before submitting to Finance.');
            abort_if(TempQuotation::where('lead_id', $quotation->lead_id)->where('status', 'draft')->exists(),
                409, 'An open revision exists. Submit or cancel that revision before submitting to Finance.');

            $approval = $quotation->currentReviews()->where('role', 'BM')->where('decision', 'approve')
                ->orderByDesc('decided_at')->orderByDesc('id')->first();
            abort_unless($approval, 409, 'BM approval is required for the current revision.');
            app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);
            $snapshot = $quotation->revisions()->where('revision_no', $quotation->revision_no)
                ->where('revision_code', $quotation->revision_code)->where('status', 'submitted')->first();
            abort_unless($snapshot, 409, 'The current revision snapshot is unavailable. Reload the quotation.');
            $sales = $quotation->createdBy()->withTrashed()->first();
            $bm = $approval->reviewer()->withTrashed()->first();
            abort_unless($sales && $bm, 409, 'The quotation creator or BM reviewer is unavailable.');

            $quotation->update(['status' => 'pending_finance']);
            QuotationLog::create([
                'quotation_id' => $quotation->id, 'temp_quotation_id' => $snapshot->id,
                'action' => 'submit_to_finance', 'user_id' => $user->id,
                'logged_at' => Carbon::now('Asia/Jakarta'),
            ]);

            return [$quotation, $sales, $bm, $approval];
        }, 3);

        $notification = new QuotationPendingFinanceNotification($quotation, $sales, $bm, $approval->notes, $user, $approval);
        DB::afterCommit(function () use ($notification) {
            User::whereHas('role', fn ($query) => $query->whereIn('code', ['finance', 'finance_director']))
                ->get()->each->notify($notification);
        });

        if ($request->expectsJson() || $request->ajax()) {
            return $this->setJsonResponse('Quotation submitted to Finance successfully', [
                'status' => $quotation->status, 'revision_no' => $quotation->revision_no,
                'revision_code' => $quotation->revision_code,
            ]);
        }

        return back()->with('status', 'Quotation submitted to Finance successfully');
    }

    public function approve(Request $request, $id)
    {
        $this->validateReviewRequest($request);
        $userRole = $request->user()->role?->code;
        $quotation = Quotation::findOrFail($id);

        DB::transaction(function () use (&$quotation, $request, $userRole) {
            $quotation = $this->lockQuotationForReview($quotation, $request);
            $totalIncentive = $this->calculateQuotationIncentive($quotation);
            $bmApproved = $quotation->currentReviews()->where('role', 'BM')->where('decision', 'approve')->exists();
            // Map user role to review role
            $roleMapping = [
                'branch_manager' => 'BM',
                'finance' => 'FIN',
                'finance_director' => 'FIN',
            ];
            
            $role = $roleMapping[$userRole] ?? $userRole;
            
            // Prepare review data
            $reviewData = [
                'quotation_id'      => $quotation->id,
                'revision_no'       => $quotation->revision_no,
                'reviewer_id'       => $request->user()->id,
                'role'              => $role,
                'decision'          => 'approve',
                'notes'             => $request->input('notes'),
                'decided_at'        => Carbon::now('Asia/Jakarta'),
            ];

            // Only add incentive_nominal if it's not null (for Finance approvals)
            if ($userRole === 'FIN') {
                $reviewData['incentive_nominal'] = $totalIncentive;
            } else {
                $reviewData['incentive_nominal'] = 0; // Set to 0 instead of null
            }
            
            QuotationReview::create($reviewData);

            QuotationLog::create([
                'quotation_id' => $quotation->id,
                'temp_quotation_id' => $quotation->revisions()->where('revision_no', $quotation->revision_no)
                    ->where('revision_code', $quotation->revision_code)->where('status', 'submitted')->value('id'),
                'action'       => 'approve',
                'user_id'      => $request->user()->id,
                'logged_at'    => Carbon::now('Asia/Jakarta'),
            ]);

            // Check approval status after this approval
            $bmApproved = $quotation->currentReviews()->where('role', 'BM')->where('decision', 'approve')->exists();
            $financeApproved = $quotation->currentReviews()->where('role', 'FIN')->where('decision', 'approve')->exists();

            // Update quotation status based on approvals
            if ($userRole === 'branch_manager') {
                $quotation->update(['status' => 'approved_by_bm']);
            } elseif (in_array($userRole, ['finance', 'finance_director'], true) && $bmApproved) {
                // Both BM and Finance approved - publish the quotation
                $quotation->update([
                    'status' => 'published',
                    'expiry_date' => Carbon::now('Asia/Jakarta')->addDays(30),
                ]);

                // Only create proformas when both approvals are complete
                $this->createProformas($quotation, $request->user());

                // Create incentive log
                \App\Models\UserBalanceLog::create([
                    'user_id'       => $quotation->created_by,
                    'amount'        => $totalIncentive,
                    'quotation_id'  => $quotation->id,
                    'description'   => 'Incentive for quotation ' . $quotation->quotation_no,
                    'status'        => 'pending',
                    'created_at'    => Carbon::now('Asia/Jakarta'),
                ]);
            }
        });

        // Notify sales about the approval decision
        $salesUser = \App\Models\User::find($quotation->created_by);
        if ($salesUser) {
            $reviewerRole = $userRole === 'branch_manager' ? 'BM' : 'FIN';
            $salesUser->notify(
                new \App\Notifications\Orders\QuotationReviewedNotification(
                    $quotation->load('lead'), 'approve', $reviewerRole, $request->input('notes')
                )
            );
        }

        // Jika Finance yang approve, notify juga semua BM di branch agar tahu keputusan akhir
        if (in_array($userRole, ['finance', 'finance_director'])) {
            $branchId = $quotation->lead?->branch_id;
            if ($branchId) {
                User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                    ->where('branch_id', $branchId)
                    ->get()
                    ->each->notify(
                        new \App\Notifications\Orders\QuotationReviewedNotification(
                            $quotation->fresh()->load('lead'), 'approve', 'FIN', $request->input('notes')
                        )
                    );
            }
        }

        return back()->with('status', 'Quotation approved');
    }

    // Extract proforma creation to separate method for cleaner code
    private function createProformas($quotation, $user)
    {
        // Create booking fee proforma if exists
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
                'issued_by'   => $user->id,
            ])->save();

            $this->generateProformaPDF($proforma, $quotation, $user);
        }

        // Create payment term proformas
        foreach ($quotation->paymentTerms as $term) {
            $base = $quotation->grand_total;
            $amount = $base * ($term->percentage / 100);
            $type   = $term->term_no == 1 ? 'down_payment' : 'term_payment';

            // Subtract booking fee from term 1 so proforma reflects remaining amount
            if ($term->term_no == 1 && ! empty($quotation->booking_fee)) {
                $amount = $amount - $quotation->booking_fee;
                if ($amount < 0) {
                    $amount = 0;
                }
            }

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

            $this->generateProformaPDF($proforma, $quotation, $user);
        }
    }

    private function generateProformaPDF($proforma, $quotation, $user)
    {
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
            'file_path'   => 'proformas/'.$fileName,
            'mime_type'   => 'application/pdf',
            'size'        => Storage::disk('public')->size($filePath),
            'uploaded_by' => $user->id,
        ]);

        $proforma->update(['attachment_id' => $attachment->id]);
    }
    public function reject(Request $request, $id)
    {
        $this->validateReviewRequest($request);
        $quotation = Quotation::findOrFail($id);
        $userRole = $request->user()->role?->code;

        DB::transaction(function () use (&$quotation, $request, $userRole) {
            $quotation = $this->lockQuotationForReview($quotation, $request);
            // Map user role to review role
            $roleMapping = [
                'branch_manager' => 'BM',
                'finance' => 'FIN',
                'finance_director' => 'FIN',
            ];

            $role = $roleMapping[$userRole] ?? $userRole;

            $quotation->update(['status' => 'rejected']);

            QuotationReview::create([
                'quotation_id' => $quotation->id,
                'revision_no' => $quotation->revision_no,
                'reviewer_id'  => $request->user()->id,
                'role'        => $role,
                'decision'    => 'reject',
                'notes'       => $request->input('notes'),
                'incentive_nominal' => 0, // Add this to prevent NULL issues
                'decided_at'  => Carbon::now('Asia/Jakarta'),
            ]);

            QuotationLog::create([
                'quotation_id' => $quotation->id,
                'temp_quotation_id' => $quotation->revisions()->where('revision_no', $quotation->revision_no)
                    ->where('revision_code', $quotation->revision_code)->where('status', 'submitted')->value('id'),
                'action'       => 'reject',
                'user_id'      => $request->user()->id,
                'logged_at'    => Carbon::now('Asia/Jakarta'),
            ]);
        });

        // Notify sales about the rejection decision
        $salesUser = \App\Models\User::find($quotation->created_by);
        if ($salesUser) {
            $reviewerRole = $userRole === 'branch_manager' ? 'BM' : 'FIN';
            $salesUser->notify(
                new \App\Notifications\Orders\QuotationReviewedNotification(
                    $quotation->fresh()->load('lead'), 'reject', $reviewerRole, $request->input('notes')
                )
            );
        }

        // Jika Finance yang reject, notify juga semua BM di branch agar tahu keputusan akhir
        if (in_array($userRole, ['finance', 'finance_director'])) {
            $branchId = $quotation->lead?->branch_id;
            if ($branchId) {
                User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                    ->where('branch_id', $branchId)
                    ->get()
                    ->each->notify(
                        new \App\Notifications\Orders\QuotationReviewedNotification(
                            $quotation->fresh()->load('lead'), 'reject', 'FIN', $request->input('notes')
                        )
                    );
            }
        }

        return back()->with('status', 'Quotation rejected');
    }

    private function validateReviewRequest(Request $request): void
    {
        abort_unless(in_array($request->user()->role?->code, ['branch_manager', 'finance', 'finance_director'], true), 403);
        $request->validate([
            'revision_no' => 'required|integer|min:0',
            'notes' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                if (! preg_match('/[^\s\p{Z}\x{200B}\x{FEFF}]/u', $value)) {
                    $fail('Notes must contain a reason and cannot be blank.');
                }
            }],
        ]);
    }

    private function lockQuotationForReview(Quotation $quotation, Request $request): Quotation
    {
        $role = $request->user()->role?->code;
        abort_unless(in_array($role, ['branch_manager', 'finance', 'finance_director'], true), 403);
        $current = Quotation::with(['lead', 'items', 'paymentTerms'])
            ->whereKey($quotation->id)->lockForUpdate()->firstOrFail();
        if ($role === 'branch_manager') {
            abort_unless($request->user()->branch_id
                && (int) $request->user()->branch_id === (int) $current->lead?->branch_id, 403);
        }
        $expected = (int) $request->input('revision_no');
        abort_unless($current->revision_no === $expected, 409,
            'The quotation revision has changed. Reload it before reviewing.');
        abort_unless($current->status === ($role === 'branch_manager' ? 'review' : 'pending_finance'),
            409, 'This revision is no longer awaiting your review.');
        $reviewRole = $role === 'branch_manager' ? 'BM' : 'FIN';
        abort_if($current->currentReviews()->where('role', $reviewRole)->exists(), 409,
            'This revision has already been reviewed.');
        if ($reviewRole === 'FIN') {
            abort_unless($current->currentReviews()->where('role', 'BM')->where('decision', 'approve')->exists(),
                403, 'Branch Manager approval is required for this revision before Finance review.');
        }
        app(QuotationRevisions::class)->ensureInitialSnapshot($current);
        abort_unless($current->revisions()->where('revision_no', $current->revision_no)
            ->where('revision_code', $current->revision_code)->where('status', 'submitted')->exists(),
            409, 'The current revision snapshot is unavailable. Reload the quotation.');

        return $current;
    }

    private function calculateQuotationIncentive(Quotation $quotation)
    {
        $baseIncentive = env('INCENTIVE_AMOUNT_RATE', 100000);
        $incentive = 0;
        foreach ($quotation->items as $item) {
            if ($item->discount_pct > 100 || $item->discount_pct < 0) {
                abort(400, 'Invalid discount percentage on item ID: ' . $item->id);
            }
            if ($item->discount_pct === null) {
                abort(400, 'Discount percentage cannot be null on item ID: ' . $item->id);
            }
            $itemPrice = $item->price;
            $itemQty = $item->qty;
            $itemDiscount = $item->discount_pct;
            $basePricePerUnit = $item->base_price ?? $item->product->base_price ?? $itemPrice;
            if ($itemDiscount < 5) {
                if ($itemPrice > $basePricePerUnit) {
                    $markupAmount = ($itemPrice - $basePricePerUnit) * $itemQty;
                    $incentive += 0.3 * $markupAmount;
                }
                if ($itemDiscount < 1) {
                    $markupAmount = ($itemPrice * $itemDiscount / 100) * $itemQty;
                    $incentive += 0.5 * $markupAmount;
                }
                $incentive += 0.01 * $itemPrice * $itemQty;
            }
        }

        return $baseIncentive + $incentive;
    }

    public function uploadSignedDocument(Request $request, $id)
    {
        abort_if($request->user()->role?->code !== 'sales', 403);

        $quotation = Quotation::findOrFail($id);

        $data = $request->validate([
            'file'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'description'  => 'nullable|string|max:255',
            'signed_date'  => 'required|date_format:d/m/Y',
        ]);

        $signedDate = Carbon::createFromFormat('d/m/Y', $data['signed_date'])->format('Y-m-d');

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
            'signed_date'   => $signedDate,
            'uploader_id'   => $request->user()->id,
        ]);

        return back()->with('status', 'Signed document uploaded');
    }

    public function signedDocuments(Request $request, $id)
    {
        return redirect()->route('quotations.show', $id);
    }

    public function updateProformaIssuedAt(Request $request, $id)
    {
        $data = $request->validate([
            'issued_at' => 'required|date_format:Y-m-d',
        ]);

        $proforma = Proforma::findOrFail($id);
        $proforma->update(['issued_at' => $data['issued_at']]);

        // Regenerate stored PDF for archival consistency
        try {
            $this->generateProformaPDF($proforma, $proforma->quotation, $request->user());
        } catch (\Throwable $e) {
            // ignore regeneration errors for now
        }

        return response()->json([
            'status' => 'ok',
            'issued_at_display' => $proforma->issued_at ? $proforma->issued_at->format('d M Y') : null,
        ]);
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
