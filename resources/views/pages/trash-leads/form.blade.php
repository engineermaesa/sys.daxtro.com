@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card mb-4">
    <div class="card-header"><strong>Lead Detail</strong></div>
    <div class="card-body">
      <table class="table table-sm">
        <tr>
          <th>Name</th>
          <td>{{ $lead->name }}</td>
        </tr>
        <tr>
          <th>Phone</th>
          <td>{{ $lead->phone }}</td>
        </tr>
        <tr>
          <th>Email</th>
          <td>{{ $lead->email }}</td>
        </tr>
        <tr>
          <th>Needs</th>
          <td>{{ $lead->needs }}</td>
        </tr>
        <tr>
          <th>Status</th>
          <td>{{ $lead->status->name ?? '-' }}</td>
        </tr>
        <tr>
          <th>Source</th>
          <td>{{ $lead->source->name ?? '-' }}</td>
        </tr>
        <tr>
          <th>Segment</th>
          <td>{{ $lead->segment->name ?? '-' }}</td>
        </tr>
        <tr>
          <th>Region</th>
          <td>{{ $lead->region->name ?? '-' }}</td>
        </tr>
        <tr>
          <th>First Sales</th>
          <td>{{ $lead->firstSales->name ?? '-' }}</td>
        </tr>
        <tr>
          <th>Trash Note</th>
          <td>{{ $claim->trash_note ?? '-' }}</td>
        </tr>
      </table>
    </div>
  </div>

  @if($meeting)
  <div class="card mb-4">
    <div class="card-header"><strong>Latest Meeting</strong></div>
    <div class="card-body">
      <table class="table table-sm">
        <tr>
          <th>Schedule</th>
          <td>
            {{ $meeting->scheduled_start_at ? date('d M Y H:i', strtotime($meeting->scheduled_start_at)) : '' }}
            -
            {{ $meeting->scheduled_end_at ? date('d M Y H:i', strtotime($meeting->scheduled_end_at)) : '' }}
          </td>
        </tr>
        <tr>
          <th>Type</th>
          <td>{{ $meeting->is_online ? 'Online' : 'Offline' }}</td>
        </tr>
        @if($meeting->is_online)
        <tr>
          <th>URL</th>
          <td>{{ $meeting->online_url }}</td>
        </tr>
        @else
        <tr>
          <th>Location</th>
          <td>{{ trim(($meeting->city ?? '') . ' ' . ($meeting->address ?? '')) }}</td>
        </tr>
        @endif
        <tr>
          <th>Result</th>
          <td>{{ $meeting->result ?? '-' }}</td>
        </tr>
        <tr>
          <th>Summary</th>
          <td>{{ $meeting->summary ?? '-' }}</td>
        </tr>
      </table>
    </div>
  </div>

  @php
  $statusColors = [
  'pending' => 'warning',
  'approved' => 'success',
  'rejected' => 'danger',
  ];
  @endphp

  @if($meeting->expense)
  <div class="card mb-4">
    <div class="card-header"><strong>Meeting Expense</strong></div>
    <div class="card-body">
      <table class="table table-sm">
        <tr>
          <th>Total Amount</th>
          <td>Rp{{ number_format($meeting->expense->amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
          <th>Status</th>
          <td><span class="badge bg-{{ $statusColors[$meeting->expense->status] ?? 'secondary' }}">{{
              ucwords(str_replace('-', ' ', $meeting->expense->status)) }}</span></td>
        </tr>
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
          @foreach($meeting->expense->details as $detail)
          <tr>
            <td>{{ $detail->expenseType->name ?? '-' }}</td>
            <td>{{ $detail->notes ?? '-' }}</td>
            <td>Rp{{ number_format($detail->amount, 0, ',', '.') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      @if($meeting->expense->financeRequest)
      <h6 class="mt-3">Finance Request</h6>
      <table class="table table-sm">
        <tr>
          <th>Status</th>
          <td><span class="badge bg-{{ $statusColors[$meeting->expense->financeRequest->status] ?? 'secondary' }}">{{
              ucwords(str_replace('-', ' ', $meeting->expense->financeRequest->status)) }}</span></td>
        </tr>
        <tr>
          <th>Notes</th>
          <td>{{ $meeting->expense->financeRequest->notes ?? '-' }}</td>
        </tr>
        <tr>
          <th>Decided At</th>
          <td>
            {{ $meeting->expense->financeRequest->decided_at ? date('d M Y H:i',
            strtotime($meeting->expense->financeRequest->decided_at)) : '' }}
          </td>
        </tr>
      </table>
      @endif
    </div>
  </div>
  @endif
  @endif

  @if($lead->quotation)
  <div class="card mb-4">
    <div class="card-header"><strong>Quotation</strong></div>
    <div class="card-body">
      <table class="table table-sm">
        <tr>
          <th>No</th>
          <td>{{ $lead->quotation->quotation_no }}</td>
        </tr>
        <tr>
          <th>Status</th>
          <td>{{ ucfirst($lead->quotation->status) }}</td>
        </tr>
        <tr>
          <th>Grand Total</th>
          <td>Rp{{ number_format($lead->quotation->grand_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
          <th>Expiry Date</th>
          <td>{{ $lead->quotation->expiry_date ? date('d M Y', strtotime($lead->quotation->expiry_date)) : '-' }}</td>
        </tr>
      </table>

      @if($lead->quotation->proformas->count())
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
          @php $isSales = auth()->user()->role?->code === 'sales'; $orderId = $lead->order->id ?? null; @endphp
          @foreach($lead->quotation->proformas as $pf)
          <tr>
            <td>{{ $pf->term_no }}</td>
            <td>{{ $pf->proforma_no ?? '-' }}</td>
            <td>{{ ucfirst($pf->status) }}</td>
            <td>{{ $pf->issued_at ? date('d M Y', strtotime($pf->issued_at)) : '-' }}</td>
            <td>Rp{{ number_format($pf->amount, 0, ',', '.') }}</td>
            <td>
              @if($pf->attachment_id)
              <a href="{{ route('attachments.download', $pf->attachment_id) }}"
                class="btn btn-sm btn-outline-secondary">Download</a>
              @if($isSales && $pf->status === 'confirmed' && !$pf->paymentConfirmation && $orderId)
              <a href="{{ route('orders.terms.payment.confirm.form', [$orderId, $pf->term_no]) }}"
                class="btn btn-sm btn-primary ms-1">Payment Confirmation</a>
              @endif
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    </div>
  </div>
  @endif

  <div class="text-end">
    <a href="{{ route('trash-leads.index') }}" class="btn btn-secondary">Back</a>
  </div>
</section>
@endsection