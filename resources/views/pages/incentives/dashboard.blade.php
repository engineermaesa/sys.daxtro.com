@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Incentive Balance</strong>
    </div>
    <div class="card-body pt-4">
      <div class="mb-3">
        <strong>Current Balance:</strong> {{ number_format($balance->total_balance, 0, ',', '.') }}
      </div>

      <div class="table-responsive">
        <table id="incentiveLogsTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Status</th>
            <th>Amount</th>
          </tr>
          </thead>
          <tbody>
          @foreach($logs as $log)
            <tr>
              <td>{{ $log->created_at }}</td>
              <td>{{ $log->description }}</td>
              <td>
                @if ($log->status === 'received')
                  <span class="badge bg-success">Received</span>
                @elseif ($log->status === 'pending')
                  <span class="badge bg-danger">Pending</span>
                @elseif ($log->status === 'expired')
                  <span class="badge bg-secondary">Expired</span>
                @endif
              </td>
              <td>{{ number_format($log->amount, 0, ',', '.') }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script>
$(function () {
  $('#incentiveLogsTable').DataTable();
});
</script>
@endsection