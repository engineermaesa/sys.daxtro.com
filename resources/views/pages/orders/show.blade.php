@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card mb-4">
    <div class="card-body">
      <h5>Order Info</h5>      
      <table class="table table-sm">
        <tr><th>Order No</th><td>{{ $order->order_no }}</td></tr>
        <tr><th>Customer</th><td>{{ $order->lead->name ?? '-' }}</td></tr>
        <tr><th>Quotation No</th><td>{{ $quotation->quotation_no ?? '-' }}</td></tr>
        <tr><th>Total Billing</th><td>Rp {{ number_format($order->total_billing, 0, ',', '.') }}</td></tr>
        <tr><th>Status</th><td>{{ $order->order_status }}</td></tr>
        <tr><th>Created At</th><td>{{ $order->created_at->format('d M Y H:i') }}</td></tr>
      </table>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <h5>Order Items</h5>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Description</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Disc (%)</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            @php
              $subtotal = 0;
            @endphp

            @foreach ($quotation->items as $i => $item)
              @php
                $itemTotal = $item->line_total;
                $subtotal += $itemTotal;
              @endphp
              <tr>
                <td>{{ $i + 1 }} - {{$quotation->id}}</td>
                <td>{{ $item->description }}</td>
                <td class="text-end">{{ $item->qty }}</td>
                <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-end">{{ $item->discount_pct }}</td>
                <td class="text-end">Rp {{ number_format($itemTotal, 0, ',', '.') }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <th colspan="5" class="text-end">Subtotal</th>
              <th class="text-end">Rp {{ number_format($subtotal, 0, ',', '.') }}</th>
            </tr>
            @if ($quotation->discount)
            <tr>
              <th colspan="5" class="text-end">Discount</th>
              <th class="text-end">Rp {{ number_format($quotation->discount ?? 0, 0, ',', '.') }}</th>
            </tr>
            @endif
            <tr>
              <th colspan="5" class="text-end">Tax ({{$quotation->tax_pct}}%)</th>
              <th class="text-end">Rp {{ number_format($quotation->tax_total ?? 0, 0, ',', '.') }}</th>
            </tr>
            <tr class="table-success">
              <th colspan="5" class="text-end">Grand Total</th>
              <th class="text-end">Rp {{ number_format($quotation->grand_total, 0, ',', '.') }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>


  <div class="card">
    <div class="card-body">
      <h5 class="mb-4">Transactions</h5>

      <div class="row g-4">
        @foreach($terms as $idx => $term)
          @php $i = $idx + 1; @endphp
          <div class="col-md-4 col-12 mb-4">
            <div class="card h-100 shadow-sm">
              <div class="card-header bg-light fw-bold">
                Term {{ $i }} ({{ $term['percentage'] ?? '-' }}%)
              </div>
              <div class="card-body p-3">
                {{-- PROFORMA --}}
                <h2 class="text-primary mb-3">Proforma</h2>
                <div class="mb-2 text-muted small">No:</div>
                <div class="fw-semibold mb-2">{{ $term['proforma']?->proforma_no ?? '-' }}</div>

                <div class="mb-2 text-muted small">Status:</div>
                <div class="mb-2">{{ ucfirst($term['proforma']?->status ?? '-') }}</div>

                <div class="mb-2 text-muted small">Issued At:</div>
                <div class="mb-2">{{ $term['proforma']?->issued_at ? \Carbon\Carbon::parse($term['proforma']->issued_at)->format('d M Y') : '-' }}</div>

                <div class="mb-3">
                  <div class="text-muted small mb-1">File:</div>
                  @if($term['proforma']?->attachment_id)
                    <a href="{{ route('attachments.download', $term['proforma']->attachment_id) }}" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-download"></i> Download
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </div>

                @if($term['proforma_note'])
                  <div class="alert alert-{{ $term['proforma_note_status'] === 'approved' ? 'success' : 'danger' }} py-2">
                    <strong>Note:</strong> {{ $term['proforma_note'] }}
                  </div>
                @endif

                {{-- PAYMENT --}}
                <h2 class="text-success mt-4 mb-3">Payment</h2>
                <div class="mb-2 text-muted small">Payer:</div>
                <div class="mb-2">{{ $term['payment']?->payer_name ?? '-' }}</div>

                <div class="mb-2 text-muted small">Amount:</div>
                <div class="mb-2">{{ $term['payment'] ? 'Rp' . number_format($term['payment']->amount, 0, ',', '.') : '-' }}</div>

                <div class="mb-2 text-muted small">Paid At:</div>
                <div class="mb-2">{{ $term['payment']?->paid_at ? \Carbon\Carbon::parse($term['payment']->paid_at)->format('d M Y') : '-' }}</div>

                <div class="mb-2 text-muted small">Proof:</div>
                <div class="mb-3">
                  @if($term['payment']?->attachment_id)
                    <a href="{{ route('attachments.download', $term['payment']->attachment_id) }}" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-download"></i> Download Proof
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif

                </div>

                <div class="mb-2 text-muted small">Confirmed:</div>
                <div class="mb-2">
                  @if($term['payment']?->confirmed_at)
                    <span class="badge bg-success">Yes ({{ $term['payment']->confirmedBy?->name ?? 'Finance' }})</span>
                  @elseif($term['payment'])
                    <span class="badge bg-warning">Awaiting Finance</span>
                  @else
                    -
                  @endif
                </div>

                @if($term['payment_note'])
                  <div class="alert alert-{{ $term['payment_note_status'] === 'approved' ? 'success' : 'danger' }} py-2">
                    <strong>Note:</strong> {{ $term['payment_note'] }}
                  </div>
                @endif

                {{-- INVOICE --}}
                <h2 class="text-dark mt-4 mb-3">Invoice</h2>
                <div class="mb-2 text-muted small">No:</div>
                <div class="mb-2">{{ $term['invoice']?->invoice_no ?? '-' }}</div>

                <div class="mb-2 text-muted small">Status:</div>
                <div class="mb-2">{{ ucfirst($term['invoice']?->status ?? '-') }}</div>

                <div class="mb-2 text-muted small">Issued At:</div>
                <div class="mb-2">{{ $term['invoice']?->issued_at ? \Carbon\Carbon::parse($term['invoice']->issued_at)->format('d M Y') : '-' }}</div>

                <div class="mb-2 text-muted small">File:</div>
                <div class="mb-2">
                  @if($term['invoice']?->attachment_id)
                    <a href="{{ route('attachments.download', $term['invoice']->attachment_id) }}" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-download"></i> Invoice
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </div>

                @if($term['invoice_note'])
                  <div class="alert alert-{{ $term['invoice_note_status'] === 'approved' ? 'success' : 'danger' }} py-2">
                    <strong>Note:</strong> {{ $term['invoice_note'] }}
                  </div>
                @endif

              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header">
      <h5>Progress Logs</h5>
    </div>
    <div class="card-body">
      <div class="text-end mb-2">
        @if(auth()->user()->role?->code === 'purchasing')
          <a href="{{ route('orders.progress.form', $order->id) }}" class="btn btn-sm btn-primary">Update Progress</a>
        @endif
      </div>
      
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Step</th>
              <th>Note</th>
              <th>User</th>
              <th>Attachment</th>
            </tr>
          </thead>
          <tbody>
            @forelse($order->progressLogs as $log)
              @php
                $stepLabels = [
                  1 => 'Order Publish',
                  2 => 'On Production',
                  3 => 'Running Test',
                  4 => 'Delivery to Indonesia',
                  5 => 'Legal Confirmation',
                  6 => 'Delivery to Customer Location',
                  7 => 'Installation',
                  8 => 'BAST',
                ];
              @endphp
              <tr>
                <td>{{ $log->logged_at ? \Carbon\Carbon::parse($log->logged_at)->format('d M Y') : '-' }}</td>
                <td>{{ $log->progress_step }} - {{ $stepLabels[$log->progress_step] ?? '-' }}</td>
                <td>{{ $log->note }}</td>
                <td>{{ $log->user->name ?? '-' }}</td>
                <td>
                  @if($log->attachment_id)
                    <a href="{{ route('attachments.download', $log->attachment_id) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                  @else
                    -
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center">No logs</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection
