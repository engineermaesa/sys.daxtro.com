@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card mb-4">
    <div class="card-header">
      <strong>Finance Request Detail</strong>
    </div>
    <div class="card-body">
      @if(session('error'))
        <div class="alert alert-danger">
          {{ session('error') }}
        </div>
      @endif

      @if(session('status'))
        <div class="alert alert-success">
          {{ session('status') }}
        </div>
      @endif

      @php
        $typeColors = [
          'proforma' => 'primary',
          'invoice' => 'success',
          'payment-confirmation' => 'warning',
          'meeting-expense' => 'info',
          'meeting-realization' => 'secondary',
        ];
        $statusColors = [
          'pending' => 'warning',
          'approved' => 'success',
          'rejected' => 'danger',
        ];
      @endphp
      <table class="table table-sm">
        <tr><th>Type</th><td><span class="badge bg-{{ $typeColors[$financeRequest->request_type] ?? 'secondary' }}">{{ ucwords(str_replace('-', ' ', $financeRequest->request_type)) }}</span></td></tr>
        <tr><th>Status</th><td><span class="badge bg-{{ $statusColors[$financeRequest->status] ?? 'secondary' }}">{{ ucwords(str_replace('-', ' ', $financeRequest->status)) }}</span></td></tr>
        <tr><th>Requester</th><td>{{ $financeRequest->requester->name ?? '-' }}</td></tr>        
        <tr><th>Requested At</th><td>{{ date('d M Y, H:i', strtotime($financeRequest->created_at)) }}</td></tr>
        <tr><th>Decided At</th><td>{{ $financeRequest->decided_at ? date('d M Y, H:i', strtotime($financeRequest->decided_at)) : '-' }}</td></tr>
        <tr><th>Notes</th><td>{{ $financeRequest->notes }}</td></tr>
      </table>
    </div>
  </div>

  @if($order)
  <div class="card mb-4">
    <div class="card-header">
      <strong>Order Information</strong>
    </div>
    <div class="card-body">
      <table class="table table-sm">
        <tr><th>Order No</th><td>{{ $order->order_no }}</td></tr>
        <tr><th>Customer</th><td>{{ $order->lead->name ?? '-' }}</td></tr>
        <tr><th>Total Billing</th><td>{{ $order->total_billing }}</td></tr>
        <tr><th>Status</th><td>{{ $order->order_status }}</td></tr>
      </table>

      <h6>Items</h6>
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
        </thead>
        <tbody>
          @foreach($order->orderItems ?? [] as $item)
          <tr>
            <td>{{ $item->description }}</td>
            <td>{{ $item->qty }}</td>
            <td>{{ $item->unit_price }}</td>
            <td>{{ $item->line_total }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <h6>Payment Terms</h6>
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr><th>Term</th><th>Percentage</th></tr>
        </thead>
        <tbody>
          @foreach($order->paymentTerms ?? [] as $term)
          <tr>
            <td>{{ $term->term_no }}</td>
            <td>{{ $term->percentage }}%</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  @if($proforma)
  <div class="card mb-4">
    <div class="card-header">
      <strong>Proforma Information</strong>
    </div>
    <div class="card-body">
      <table class="table table-sm">
        <tr><th>Proforma No</th><td>{{ $proforma->proforma_no }}</td></tr>
        <tr><th>Term</th><td><b>{{ $proforma->term_no ?? 'Booking Fee' }}</b></td></tr>
        <tr><th>Status</th><td>{{ ucfirst($proforma->status) }}</td></tr>
        <tr><th>Amount</th><td>Rp{{ number_format($proforma->amount, 0, ',', '.') }}</td></tr>
      </table>

      <h6>Quotation Detail</h6>
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
        </thead>
        <tbody>
          @if($proforma->quotation->order && $proforma->quotation->order->orderItems->count() > 0)
            @foreach($proforma->quotation->order->orderItems ?? [] as $item)
            <tr>
              <td>{{ $item->description }}</td>
              <td>{{ $item->qty }}</td>
              <td>{{ $item->unit_price }}</td>
              <td>{{ $item->line_total }}</td>
            </tr>
            @endforeach
              @elseif($proforma->quotation->items)
                @foreach($proforma->quotation->items as $item)
                <tr>
                  <td>{{ $item->description }}</td>
                  <td>{{ $item->qty }}</td>
                  <td>{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                  <td>{{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="4" class="text-center">No items found</td>
                </tr>
              @endif
        </tbody>
      </table>

    @if($proforma->paymentConfirmation)
      <h6 class="mt-3">Payment Confirmation Details</h6>
      <table class="table table-bordered table-sm">
        <tr><th>Payer Name</th><td>{{ $proforma->paymentConfirmation->payer_name }}</td></tr>
        <tr><th>Payer Bank</th><td>{{ $proforma->paymentConfirmation->payer_bank }}</td></tr>
        <tr><th>Account Number</th><td>{{ $proforma->paymentConfirmation->payer_account_number }}</td></tr>
        <tr><th>Paid At</th><td>{{ $proforma->paymentConfirmation->paid_at?->format('d M Y') }}</td></tr>
        <tr><th>Amount</th><td>Rp{{ number_format($proforma->paymentConfirmation->amount, 0, ',', '.') }}</td></tr>
        <tr><th>Evidence</th><td>
          @if($proforma->paymentConfirmation->attachment)
            <a href="{{ route('attachments.download', $proforma->paymentConfirmation->attachment_id) }}" class="btn btn-sm btn-outline-secondary">Download</a>
          @endif
        </td></tr>
      </table>
      @endif
    </div>
  </div>
  @endif

  @if(isset($proforma) && $proforma->invoice && $proforma->invoice->attachment_id)
    <h6 class="mt-3">Invoice</h6>
    <table class="table table-bordered table-sm">
      <tr><th>Invoice No</th><td>{{ $proforma->invoice->invoice_no }}</td></tr>
      <tr><th>Issued At</th><td>{{ \Carbon\Carbon::parse($proforma->invoice->issued_at)->format('d M Y') }}</td></tr>
      <tr><th>Amount</th><td>Rp{{ number_format($proforma->invoice->amount, 0, ',', '.') }}</td></tr>
      <tr><th>Download</th>
        <td>
          <a href="{{ route('attachments.download', $proforma->invoice->attachment_id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download"></i> Download Invoice
          </a>
        </td>
      </tr>
    </table>
  @endif


  @if($meetingExpense)
    <div class="card mb-4">
      <div class="card-header">
        <strong>Meeting Expense Details</strong>
      </div>
      <div class="card-body">
        <table class="table table-sm">
          <tr><th>Lead</th><td>{{ $meetingExpense->meeting->lead->name ?? '-' }}</td></tr>
          <tr><th>Meeting Date</th><td>{{ \Carbon\Carbon::parse($meetingExpense->meeting->scheduled_start_at)->format('d M Y H:i') }}</td></tr>
          <tr><th>Total Amount</th><td>Rp{{ number_format($meetingExpense->amount, 0, ',', '.') }}</td></tr>
          <tr><th>Status</th><td><span class="badge badge-{{ $meetingExpense->status === 'approved' ? 'success' : ($meetingExpense->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($meetingExpense->status) }}</span></td></tr>
        </table>

        <h6>Expense Breakdown</h6>
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr>
              <th>Type</th>
              <th>Notes</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            @foreach($meetingExpense->details as $detail)
            <tr>
              <td>{{ $detail->expenseType->name ?? '-' }}</td>
              <td>{{ $detail->notes ?? '-' }}</td>
              <td>Rp{{ number_format($detail->amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  @if($financeRequest->status === 'pending')
  <form method="POST" action="{{ route('finance-requests.approve', $financeRequest->id) }}" require-confirmation="true" class="mb-3">
    @csrf
    <div class="mb-2">
      <textarea name="notes" class="form-control" placeholder="Notes" required></textarea>
    </div>
    <div class="text-end">
      <button class="btn btn-primary">Approve</button>
    </div>
  </form>
  <form method="POST" action="{{ route('finance-requests.reject', $financeRequest->id) }}" require-confirmation="true">
    @csrf
    <div class="mb-2">
      <textarea name="notes" class="form-control" placeholder="Notes" required></textarea>
    </div>
    <div class="text-end">
      <button class="btn btn-danger">Reject</button>
    </div>
  </form>
  <div class="text-end mt-2">
    <a href="{{ route('finance-requests.index') }}" class="btn btn-secondary">Back</a>
  </div>
  @endif

  @if($financeRequest->status !== 'pending')
  <div class="text-end">
    <a href="{{ route('finance-requests.index') }}" class="btn btn-secondary me-2">Back</a>
  </div>
  @endif
</section>
@endsection
