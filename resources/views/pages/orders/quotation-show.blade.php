@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="card mb-4">
            <div class="card-body">
                @if ($quotation->status === 'rejected' && isset($rejection))
                    <div class="alert alert-danger">
                        Quotation rejected by <b>{{ $rejection->reviewer->name ?? $rejection->role }}</b> on
                        {{ $rejection->decided_at ? \Carbon\Carbon::parse($rejection->decided_at)->format('d M Y') : '' }}
                        <strong>Notes:</strong> {{ $rejection->notes }}
                    </div>
                @elseif(in_array($quotation->status, ['review', 'pending_finance']))
                    @php
                        $roleCode = auth()->user()->role?->code;
                        $bmReview = $quotation->reviews->where('role', 'BM')->sortByDesc('decided_at')->first();
                        $financeReview = $quotation->reviews->where('role', 'finance')->sortByDesc('decided_at')->first();
                    @endphp
                    <div class="alert alert-warning">
                        This quotation is currently under review.<br>
                        Branch Manager: <strong>{{ $bmReview ? ucfirst($bmReview->decision) : 'Pending' }}</strong><br>
                        Finance: <strong>{{ $financeReview ? ucfirst($financeReview->decision) : 'Pending' }}</strong><br>
                        
                        @if ($roleCode === 'branch_manager' && !$bmReview && $quotation->status === 'review')
                            You can <strong>approve</strong> or <strong>reject</strong> this quotation using the buttons at the bottom of the page.
                        @elseif ($roleCode === 'finance' && $bmReview && $bmReview->decision === 'approve' && !$financeReview && $quotation->status === 'pending_finance')
                            You can <strong>approve</strong> or <strong>reject</strong> this quotation using the buttons at the bottom of the page.
                        @elseif ($quotation->status === 'review')
                            Please wait for Branch Manager approval.
                        @elseif ($quotation->status === 'pending_finance')
                            Please wait for Finance approval.
                        @endif
                    </div>
                @elseif($quotation && $quotation->status === 'published' && $quotation->reviews->count())
                    @php
                        $approval = $quotation->reviews
                            ->where('decision', 'approve')
                            ->sortByDesc('decided_at')
                            ->first();
                    @endphp
                    @if ($approval)
                        <div class="alert alert-success">
                            Quotation published on
                            {{ $approval->decided_at ? \Carbon\Carbon::parse($approval->decided_at)->format('d M Y H:i:s') : '-' }}
                            WIB<br>
                            <strong>Notes:</strong> {{ $approval->notes }}
                        </div>
                    @endif
                @endif
                <h5>Quotation Detail</h5>
                <table class="table table-sm">
                    <tr>
                        <th>No</th>
                        <td>{{ $quotation->quotation_no }}</td>
                    <tr>
                        <th>Status</th>
                        <td>
                            @php
                                $statusClass = [
                                    'draft' => 'secondary',
                                    'review' => 'warning',
                                    'pending_finance' => 'info',
                                    'published' => 'success',
                                    'rejected' => 'danger',
                                ][$quotation->status] ?? 'light';
                                
                                $statusLabel = [
                                    'pending_finance' => 'Pending Finance',
                                ][$quotation->status] ?? ucfirst($quotation->status);
                            @endphp
                            <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <td>{{ $quotation->lead->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Sub Total</th>
                        <td>Rp{{ number_format($quotation->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Tax ({{ $quotation->tax_pct }}%)</th>
                        <td>Rp{{ number_format($quotation->tax_total, 0, ',', '.') }}</td>
                    </tr>
                    @if (!empty($quotation->discount))
                        <tr>
                            <th>Discount</th>
                            <td class="text-danger">- Rp{{ number_format($quotation->discount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Grand Total</th>
                        <td class="fw-bold">Rp{{ number_format($quotation->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    {{-- Baris Payment Type --}}
                    @if (!empty($quotation->booking_fee))
                        <tr>
                            <th>Payment Type</th>
                            <td>Booking Fee | Rp{{ number_format($quotation->booking_fee, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr>
                            <th>Payment Type</th>
                            <td>Direct Down Payment</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Expiry Date</th>
                        <td class="fw-bold">{{ $quotation->expiry_date ? date('d M Y', strtotime($quotation->expiry_date)) : '-' }}</td>
                    </tr>
                </table>
                <h6 class="mt-4">Items</h6>
                @php
                    $isSales = auth()->user()->role?->code === 'sales';
                    $orderId = $quotation->order->id ?? null;
                @endphp


                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Disc %</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotation->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td>{{ $item->discount_pct }}</td>
                                <td>Rp{{ number_format($item->line_total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Sub Total</th>
                            <th class="text-end">Rp{{ number_format($quotation->subtotal, 0, ',', '.') }}</th>
                        </tr>
                        {{-- <tr>
                            <th colspan="4" class="text-end">Discount</th>
                            <th class="text-end">{{ number_format($quotation->discount, 0, ',', '.') }}</th>
                        </tr> --}}
                        <tr>
                            <th colspan="4" class="text-end">Tax ({{ $quotation->tax_pct }}%)</th>
                            <th class="text-end">Rp{{ number_format($quotation->tax_total, 0, ',', '.') }}</th>
                        </tr>
                        @if (!empty($quotation->discount))
                            <tr>
                                <th colspan="4" class="text-end">Discount</th>
                                <th class="text-end text-danger">- Rp{{ number_format($quotation->discount, 0, ',', '.') }}
                                </th>
                            </tr>
                        @endif
                        <tr>
                            <th colspan="4" class="text-end">Grand Total</th>
                            <th class="text-end fw-bold">Rp{{ number_format($quotation->grand_total, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>


                <h6 class="mt-4">Payment Terms</h6>
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Term</th>
                            <th>Percentage</th>
                            <th>Total (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotation->paymentTerms as $term)
                            <tr>
                                <td>{{ $term->term_no }}</td>
                                <td>{{ $term->percentage }}%</td>
                                @if($quotation->booking_fee)
                                    @if( $term->term_no === 1 )
                                        <td>
                                            Rp{{ number_format(((($quotation->grand_total * $term->percentage)  / 100) - $quotation->booking_fee), 0, ',', '.') }}
                                        </td>
                                    @else
                                        <td>
                                            Rp{{ number_format(($quotation->grand_total * $term->percentage) / 100, 0, ',', '.') }}
                                        </td>
                                    @endif
                                @else
                                    <td>
                                        Rp{{ number_format(($quotation->grand_total * $term->percentage) / 100, 0, ',', '.') }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($quotation->proformas->count())
                    <h6 class="mt-4">Proformas</h6>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Term</th>
                                <th>No</th>
                                <th>Status</th>
                                <th>Issued</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quotation->proformas as $pf)
                                <tr>
                                    <td>{{ $pf->term_no ?? 'Booking Fee' }}</td>
                                    <td>{{ $pf->proforma_no ?? '-' }}</td>
                                    <td>{{ ucfirst($pf->status) }}</td>
                                    <td>{{ $pf->issued_at ? date('d M Y', strtotime($pf->issued_at)) : '-' }}</td>
                                    <td>Rp{{ number_format($pf->amount, 0, ',', '.') }}

                                        @if ($pf->paymentConfirmation)
                                            @php $fr = $pf->paymentConfirmation->financeRequest; @endphp
                                            @if ($fr && in_array($fr->status, ['approved','rejected']))
                                                <div class="small text-{{ $fr->status === 'approved' ? 'success' : 'danger' }} mt-1">
                                                    {{ ucfirst($fr->status) }}: {{ $fr->notes }}
                                                </div>
                                            @elseif($fr)
                                                <div class="small text-warning mt-1">Awaiting Finance</div>
                                            @endif
                                            @if ($fr && $fr->status !== 'rejected')
                                                <div class="small text-success mt-1">
                                                    Paid at: {{ $pf->paymentConfirmation->paid_at->format('d M Y') }}<br>
                                                    Confirmed by: {{ $pf->paymentConfirmation->confirmedBy?->name ?? '-' }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if ($pf->attachment_id)
                                            <a href="{{ route('attachments.download', $pf->attachment_id) }}"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-download"></i> Download Proforma
                                            </a>

                                            @if ($pf->invoice && $pf->invoice->attachment_id)
                                                <a href="{{ route('attachments.download', $pf->invoice->attachment_id) }}"
                                                    class="btn btn-sm btn-outline-secondary ms-1">
                                                    <i class="bi bi-download"></i> Download Invoice
                                                </a>
                                            @endif

                                            @if ($pf->status === 'confirmed')
                                                @php $fr = $pf->paymentConfirmation?->financeRequest; @endphp
                                                @if (!$pf->paymentConfirmation && $isSales)
                                                    <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$quotation->lead_id, $pf->term_no ?? 'bf']) }}"
                                                        class="btn btn-sm btn-outline-primary ml-1">
                                                        <i class="bi bi-cash-coin"></i> Confirm Payment
                                                    </a>
                                                @endif

                                                @if ($pf->paymentConfirmation)
                                                    <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$quotation->lead_id, $pf->term_no ?? 'bf']) }}"
                                                        class="btn btn-sm {{ $fr && $fr->status === 'rejected' ? 'btn-outline-danger' : 'btn-outline-success' }} ml-1">
                                                        <i
                                                            class="bi {{ $fr && $fr->status === 'rejected' ? 'bi-pencil-square' : 'bi-eye' }}"></i>
                                                        {{ $fr && $fr->status === 'rejected' ? 'Edit Payment' : 'View Payment' }}
                                                    </a>
                                                @endif
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($quotation->signedDocuments->count())
                    <h6 class="mt-4">Signed Documents</h6>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>File</th>
                                <th>Description</th>
                                <th>Signed Date</th>
                                <th>Uploaded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quotation->signedDocuments as $doc)
                                <tr>
                                    <td>
                                        <a href="{{ route('attachments.download', $doc->attachment_id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-download"></i> {{ basename($doc->attachment?->file_path ?? '') }}
                                        </a>
                                    </td>
                                    <td>{{ $doc->description }}</td>
                                    <td>{{ $doc->signed_date ? date('d M Y', strtotime($doc->signed_date)) : '-' }}</td>
                                    <td>{{ $doc->uploader?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($quotation->paymentLogs->count())
                    <h6 class="mt-4">Payment Logs</h6>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Detail</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quotation->paymentLogs as $log)
                                <tr>
                                    <td>{{ $log->logged_at ? \Carbon\Carbon::parse($log->logged_at)->format('d M Y H:i') : '-' }}</td>
                                    <td>{{ ucfirst($log->type) }}</td>
                                    <td>
                                        @if($log->type === 'proforma')
                                            {{ $log->proforma?->proforma_no }}
                                        @elseif($log->type === 'invoice')
                                            {{ $log->invoice?->invoice_no }}
                                        @elseif($log->type === 'confirmation')
                                            {{ $log->proforma?->term_no ? 'Term ' . $log->proforma->term_no : 'Booking Fee' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $log->user->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if(auth()->user()->role?->code === 'sales' && isset($claim))
                    <h6 class="mt-4">Upload Signed Document</h6>
                    <form action="{{ route('quotations.signed-documents.upload', $quotation->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="signed_file" class="form-label">Signed File <i class="required">*</i></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('file') is-invalid @enderror" id="signed_file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="signed_file">Choose file...</label>
                                @error('file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="signed_date" class="form-label">Signed Date <i class="required">*</i></label>
                            <input type="date" name="signed_date" id="signed_date" class="form-control form-control-sm @error('signed_date') is-invalid @enderror" required value="{{ old('signed_date') }}" onfocus="this.showPicker()">
                            @error('signed_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" name="description" id="description" class="form-control form-control-sm" value="{{ old('description') }}">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                @endif

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="{{ url()->previous() }}" class="btn btn-light">Back</a>
                        <a href="{{ route('quotations.download', $quotation->id) }}" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-download"></i> Download Quotation
                        </a>
                        @php
                            // $userRole   = auth()->user()->role?->code;
                            // $bmApproved = $quotation->reviews->where('role', 'BM')->where('decision', 'approve')->isNotEmpty();
                            // // $dirApproved = $quotation->reviews->where('role', 'SD')->where('decision', 'approve')->isNotEmpty();
                            // $financeApproved = $quotation->reviews->where('role', 'finance')->where('decision', 'approve')->isNotEmpty();
                            // $allApproved = $bmApproved && $financeApproved;
                            // $hasPayment = $quotation->proformas->contains(function ($p) {
                            //     return $p->paymentConfirmation !== null;
                            // });

                            // if ($quotation->status === 'published') {
                            //     $canEdit = in_array($userRole, ['branch_manager']) && !$hasPayment;
                            // } else {
                            //     $canEdit = $userRole === 'sales' && isset($claim) && in_array($quotation->status, ['draft', 'review', 'pending_finance']);
                            // }

                            // dd([
                            //     'userRole' => $userRole,
                            //     'hasClaim' => isset($claim),
                            //     'claimData' => $claim,
                            //     'quotationStatus' => $quotation->status,
                            //     'canEdit' => $canEdit,
                            //     'statusCheck' => in_array($quotation->status, ['draft', 'review', 'pending_finance']),
                            // ]);

                            $userRole = auth()->user()->role?->code;
                            $bmApproved = $quotation->reviews->where('role', 'BM')->where('decision', 'approve')->isNotEmpty();
                            $financeApproved = $quotation->reviews->where('role', 'finance')->where('decision', 'approve')->isNotEmpty();
                            $allApproved = $bmApproved && $financeApproved;
                            $hasPayment = $quotation->proformas->contains(function ($p) {
                                return $p->paymentConfirmation !== null;
                            });

                            // Explicit editability rules - allow both sales and BM to edit before finance approval
                            $canEdit = false;
                            
                            if (in_array($userRole, ['sales', 'branch_manager']) && isset($claim)) {
                                // Sales OR BM can edit if quotation is not yet fully approved by finance
                                $editableStatuses = ['draft', 'review', 'pending_finance'];
                                $canEdit = in_array($quotation->status, $editableStatuses);
                            } elseif ($userRole === 'branch_manager' && $quotation->status === 'published' && !$hasPayment) {
                                // BM can edit published quotations if no payments exist
                                $canEdit = true;
                            }
                        @endphp

                        {{-- <div class="alert alert-info">
                            <strong>Debug Info:</strong><br>
                            User Role: {{ $userRole }}<br>
                            Has Claim: {{ isset($claim) ? 'Yes' : 'No' }}<br>
                            Quotation Status: {{ $quotation->status }}<br>
                            Can Edit: {{ $canEdit ? 'Yes' : 'No' }}<br>
                            BM Approved: {{ $bmApproved ? 'Yes' : 'No' }}<br>
                            Finance Approved: {{ $financeApproved ? 'Yes' : 'No' }}
                        </div> --}}

                        @if ($canEdit && isset($claim))
                            <a href="{{ route('leads.my.warm.quotation.create', $claim->id) }}" class="btn btn-primary ms-2">Edit Quotation</a>
                        @endif
                    </div>
                    @php
                        // $userRole   = auth()->user()->role?->code;
                        // $bmApproved = $quotation->reviews
                        //     ->where('role', 'BM')
                        //     ->where('decision', 'approve')
                        //     ->isNotEmpty();
                        // $reviewed = $userRole === 'branch_manager'
                        //     ? $quotation->reviews->where('role', 'BM')->isNotEmpty()
                        //     : ($userRole === 'sales_director' ? $quotation->reviews->where('role', 'SD')->isNotEmpty() : false);
                        // $canReview = ($userRole === 'branch_manager' && !$reviewed) ||
                        //     ($userRole === 'sales_director' && $bmApproved && !$reviewed);
                        // $userRole   = auth()->user()->role?->code;
                        // $bmApproved = $quotation->reviews
                        //     ->where('role', 'BM')
                        //     ->where('decision', 'approve')
                        //     ->isNotEmpty();
                        // $reviewed = $userRole === 'branch_manager'
                        //     ? $quotation->reviews->where('role', 'BM')->isNotEmpty()
                        //     : false;
                        // $canReview = ($userRole === 'branch_manager' && !$reviewed);
                        $userRole = auth()->user()->role?->code;
                        $bmReview = $quotation->reviews->where('role', 'BM')->first();
                        $financeReview = $quotation->reviews->where('role', 'finance')->first();
                        
                        // Determine who can review based on current status and role
                        $canReview = false;
                        
                        if ($quotation->status === 'review') {
                            // BM can review if they haven't reviewed yet
                            $canReview = ($userRole === 'branch_manager' && !$bmReview);
                        } elseif ($quotation->status === 'pending_finance') {
                            // Finance can review if BM approved and finance hasn't reviewed yet
                            $canReview = ($userRole === 'finance' && $bmReview && $bmReview->decision === 'approve' && !$financeReview);
                        }
                    @endphp
                    @if($canReview)
                        <div class="d-flex flex-column align-items-end" style="gap: 0.5rem;">
                            <div class="d-flex align-items-end" style="gap: 0.5rem;">
                                <form method="POST" id="approve"
                                    action="{{ route('quotations.approve', $quotation->id) }}" require-confirmation="true"
                                    class="d-flex align-items-end" style="gap: 0.5rem;">
                                    @csrf
                                    <textarea name="notes" class="form-control form-control-sm" rows="1" placeholder="Enter notes..." required style="min-width: 220px;"></textarea>
                                    <button class="btn btn-success">
                                        Approve{{ $userRole === 'finance' ? ' (Finance)' : ($userRole === 'branch_manager' ? ' (BM)' : '') }}
                                    </button>
                                </form>

                                <form method="POST" id="reject"
                                    action="{{ route('quotations.reject', $quotation->id) }}" require-confirmation="true"
                                    class="d-flex align-items-end" style="gap: 0.5rem;">
                                    @csrf
                                    <textarea name="notes" class="form-control form-control-sm" rows="1" placeholder="Enter notes..." required style="min-width: 220px;"></textarea>
                                    <button class="btn btn-danger">
                                        Reject{{ $userRole === 'finance' ? ' (Finance)' : ($userRole === 'branch_manager' ? ' (BM)' : '') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        const observer = new MutationObserver(() => {
            const confirmBtn = document.querySelector('.swal2-confirm');
            if (confirmBtn && !confirmBtn.dataset.bound) {
                confirmBtn.dataset.bound = 'true';
                confirmBtn.addEventListener('click', function() {
                    if (typeof loading === 'function') loading();
                });
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        const fileInput = document.getElementById('signed_file');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const name = e.target.files[0] ? e.target.files[0].name : 'Choose file...';
                e.target.nextElementSibling.innerText = name;
            });
        }
    </script>
@endsection
