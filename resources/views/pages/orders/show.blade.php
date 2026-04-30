@extends('layouts.app')

@section('content')
  <section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
      <div class="flex items-center gap-3">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 20C5.45 20 4.97917 19.8042 4.5875 19.4125C4.19583 19.0208 4 18.55 4 18C4 17.45 4.19583 16.9792 4.5875 16.5875C4.97917 16.1958 5.45 16 6 16C6.55 16 7.02083 16.1958 7.4125 16.5875C7.80417 16.9792 8 17.45 8 18C8 18.55 7.8042 19.0208 7.4125 19.4125C7.0208 19.8042 6.55 20 6 20ZM16 20C15.45 20 14.9792 19.8042 14.5875 19.4125C14.1958 19.0208 14 18.55 14 18C14 17.45 14.1958 16.9792 14.5875 16.5875C14.9792 16.1958 15.45 16 16 16C16.55 16 17.0208 16.1958 17.4125 16.5875C17.8042 16.9792 18 17.45 18 18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20ZM4.2 2H18.95C19.3333 2 19.625 2.17083 19.825 2.5125C20.025 2.85417 20.0333 3.2 19.85 3.55L16.3 9.95C16.1167 10.2833 15.8708 10.5417 15.5625 10.725C15.2542 10.9083 14.9167 11 14.55 11H7.1L6 13H17C17.2833 13 17.5208 13.0958 17.7125 13.2875C17.9042 13.4792 18 13.7167 18 14C18 14.2833 17.9042 14.5208 17.7125 14.7125C17.5208 14.9042 17.2833 15 17 15H6C5.25 15 4.68333 14.6708 4.3 14.0125C3.91667 13.3542 3.9 12.7 4.25 12.05L5.6 9.6L2 2H1C0.716667 2 0.479167 1.90417 0.2875 1.7125C0.0958333 1.52083 0 1.28333 0 1C0 0.716667 0.0958333 0.479167 0.2875 0.2875C0.479167 0.0958333 0.716667 0 1 0H2.625C2.80833 0 2.98333 0.05 3.15 0.15C3.31667 0.25 3.44167 0.391667 3.525 0.575L4.2 2Z" fill="#115640"/>
              </svg>
          <h1 class="text-[#115640] font-semibold text-2xl">Orders</h1>
      </div>
      <div class="flex items-center mt-2 gap-3">
          <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">Orders</a>
          <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
          <a href="{{ route('orders.show', $id) }}" class="text-[#083224] underline">
              View Orders
          </a>
      </div>
    </div>

    {{-- ORDER INFO --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Order Info</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg">
          <table class="w-full text-[#1E1E1E]">
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Order No</th>
              <td class="p-3">{{ $order->order_no }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Customer</th>
              <td class="p-3">{{ $order->lead->name ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Quotation No</th>
              <td class="p-3">{{ $quotation->quotation_no ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Total Billing</th>
              <td class="p-3">Rp{{ number_format($order->total_billing, 0, ',', '.') }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Status</th>
              <td class="p-3">{{ ucfirst($order->order_status) }}</td>
            </tr>
            <tr class="border-t border-t-[#D9D9D9]">
              <th class="p-3">Created At</th>
              <td class="p-3">{{ $order->created_at->format('d M Y H:i') }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    {{-- ORDER ITEMS --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
        <h1 class="font-semibold uppercase p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Order Items</h1>
        <div class="p-3">
          <div class="border border-[#D9D9D9] rounded-lg">
            <table class="w-full">
              <thead>
                  <tr class="border-b border-b-[#D9D9D9]">
                      <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">#</th>
                      <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Description</th>
                      <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Qty</th>
                      <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Unit Price</th>
                      <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Disc (%)</th>
                      <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Total</th>
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
                  <tr class="border-b border-b-[#D9D9D9]">
                      <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $i + 1 }} - {{$quotation->id}}</td>
                      <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $item->description }}</td>
                      <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $item->qty }}</td>
                      <td class="lg:p-3 p-2 text-[#1E1E1E]">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                      <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $item->discount_pct }}</td>
                      <td class="lg:p-3 p-2 text-[#1E1E1E]">Rp {{ number_format($itemTotal, 0, ',', '.') }}</td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                  <tr class="border-b border-b-[#D9D9D9]">
                      <th class="text-[#1E1E1E] font-semibold lg:p-3 p-2">
                          Sub Total
                      </th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class="text-[#1E1E1E] font-semibold lg:p-3 p-2">
                          Rp{{ number_format($quotation->subtotal, 0, ',', '.') }}
                      </th>
                  </tr>
                  <tr class="border-b border-b-[#D9D9D9]">
                      <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Tax ({{ $quotation->tax_pct }}%)</th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Rp{{ number_format($quotation->tax_total, 0, ',', '.') }}</th>
                  </tr>
                  @if (!empty($quotation->discount))
                      <tr class="border-b border-b-[#D9D9D9]">
                          <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Discount</th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th class="lg:p-3 p-2 text-danger text-[#1E1E1E] font-semibold">- Rp{{ number_format($quotation->discount, 0, ',', '.') }}
                          </th>
                      </tr>
                  @endif
                  <tr>
                      <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Grand Total</th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Rp{{ number_format($quotation->grand_total, 0, ',', '.') }}
                      </th>
                  </tr>
              </tfoot>
            </table>
          </div>
        </div>
    </div>

    {{-- TRANSACTIONS --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
      <h1 class="font-semibold uppercase p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Transactions</h1>
      <div class="p-3">

        {{-- TERM SUMMARY --}}
        <div class="border border-[#D9D9D9] rounded-lg">
          <h1 class="uppercase text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Term Summary</h1>
          <div class="p-2 lg:p-3">
            <div class="border border-[#D9D9D9] rounded-lg">
              <table class="w-full">
                <thead>
                    <tr class="border-b border-b-[#D9D9D9]">
                      <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Term</th>
                      <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Percentage</th>
                      <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Amount</th>
                      <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Paid At</th>
                      <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Status</th>
                    </tr>
                </thead>
                <tbody>
                  @foreach($terms as $idx => $term)
                    @php $i = $idx + 1; @endphp
                    <tr class="border-t border-t-[#D9D9D9]">
                      <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Term {{ $i }}</td>
                      <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ $term['percentage'] ?? '-' }}%</td>
                      <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ $term['payment'] ? 'Rp' . number_format($term['payment']->amount, 0, ',', '.') : '-' }}</td>
                      <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ $term['payment']?->paid_at ? \Carbon\Carbon::parse($term['payment']->paid_at)->format('d M Y') : '-' }}</td>
                      <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ ucfirst($term['invoice']?->status ?? '-') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- TERM DETAIL --}}
        @foreach($terms as $idx => $term)
          @php
            $i = $idx + 1;
            $termStatus = strtolower($term['invoice']?->status ?? 'unpaid');
            $isPaid = $termStatus === 'paid';
          @endphp
          <div class="border border-[#D9D9D9] rounded-lg mt-4">
            {{-- CONDITIONAL PAID AND UNPAID --}}
            <div class="term-detail-toggle flex items-center justify-between cursor-pointer p-3 rounded-tr-lg rounded-tl-lg {{ $isPaid ? 'bg-[#115640] text-white' : 'bg-[#FFF1C2] text-[#1E1E1E]' }}" data-target="#termDetail{{ $i }}">
              <h1 class="uppercase font-semibold">Term {{ $i }} Detail {{ ucfirst($termStatus) }}</h1>
              <i class="fas fa-chevron-right transform transition-transform duration-300 {{ $isPaid ? 'rotate-90' : '' }}"></i>
            </div>
            <div id="termDetail{{ $i }}" class="term-detail-panel p-2 lg:p-3" style="display: {{ $isPaid ? 'block' : 'none' }};">
              <div class="border border-[#D9D9D9] rounded-lg">
                <div class="grid grid-cols-3 divide-x divide-[#D9D9D9] border-b border-b-[#D9D9D9]">
                  {{-- PROFORMA --}}
                  <div>
                    <h1 class="uppercase font-semibold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Proforma</h1>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">No:</span> {{ $term['proforma']?->proforma_no ?? '-' }}</p>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">Status:</span> {{ ucfirst($term['proforma']?->status ?? '-') }}</p>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">Issued:</span> {{ $term['proforma']?->issued_at ? \Carbon\Carbon::parse($term['proforma']->issued_at)->format('d M Y') : '-' }}</p>
                    <div class="p-3 w-1/3">
                      @if($term['proforma']?->attachment_id)
                        <a href="{{ route('attachments.download', $term['proforma']->attachment_id) }}" class="bg-[#115640] rounded-lg flex justify-center items-center p-3 gap-3 text-white">
                          <x-icon.download/>
                          Download Proforma
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </div>
                  </div>

                  {{-- PAYMENT --}}
                  <div>
                    <h1 class="uppercase font-semibold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Payment</h1>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">Payer:</span> {{ $term['payment']?->payer_name ?? '-' }}</p>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">Amount:</span> {{ $term['payment'] ? 'Rp' . number_format($term['payment']->amount, 0, ',', '.') : '-' }} </p>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">Paid at:</span> {{ $term['payment']?->paid_at ? \Carbon\Carbon::parse($term['payment']->paid_at)->format('d M Y') : '-' }}</p>
                    <div class="p-3 w-1/3">
                      @if($term['payment']?->attachment_id)
                        <a href="{{ route('attachments.download', $term['payment']->attachment_id) }}" class="bg-[#115640] rounded-lg flex justify-center items-center p-3 gap-3 text-white">
                          <x-icon.download/>
                          Download Proof
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </div>
                  </div>

                  {{-- INVOICE --}}
                  <div>
                    <h1 class="uppercase font-semibold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Invoice</h1>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">No:</span> {{ $term['invoice']?->invoice_no ?? '-' }}</p>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">Status:</span> {{ ucfirst($term['invoice']?->status ?? '-') }} </p>
                    <p class="p-3 border-b border-b-[#D9D9D9]"><span class="font-medium text-slate-600">Issued At:</span> {{ $term['invoice']?->issued_at ? \Carbon\Carbon::parse($term['invoice']->issued_at)->format('d M Y') : '-' }}</p>
                    <div class="p-3 w-1/3">
                      @if($term['invoice']?->attachment_id)
                        <a href="{{ route('attachments.download', $term['invoice']->attachment_id) }}" class="bg-[#115640] rounded-lg flex justify-center items-center p-3 gap-3 text-white">
                          <x-icon.download/>
                          Download Invoice
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </div>
                  </div>
                </div>

                {{-- TERM NOTE --}}
                <div class="grid grid-cols-1 p-3">
                  <p><span class="font-medium text-slate-600">Note:</span> {{ $term['payment_note'] ?? '-'}} </p>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- PROGRESS LOGS --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
      <div class="flex items-center justify-between p-3 border-b border-b-[#D9D9D9]">
        <h1 class="font-semibold uppercase text-[#1E1E1E]" >Progress Logs</h1>
        @if(auth()->user()->role?->code === 'purchasing')
          <a href="{{ route('orders.progress.form', $order->id) }}">
            <div class="p-3 text-white rounded-lg bg-[#115640]">
              Update Progress
            </div>
          </a>
        @endif
      </div>

      <div class="p-3">
          <div class="border border-[#D9D9D9] rounded-lg">
            <table class="w-full">
              <thead>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Date</th>
                  <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Step</th>
                  <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Note</th>
                  <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">User</th>
                  <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Attachment</th>
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
                  <tr class="border-t border-t-[#D9D9D9]">
                    <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $log->logged_at ? \Carbon\Carbon::parse($log->logged_at)->format('d M Y') : '-' }}</td>
                    <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $log->progress_step }} - {{ $stepLabels[$log->progress_step] ?? '-' }}</td>
                    <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $log->note }}</td>
                    <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $log->user->name ?? '-' }}</td>
                    <td class="lg:p-3 p-2 text-[#1E1E1E]">
                      @if($log->attachment_id)
                        <a href="{{ route('attachments.download', $log->attachment_id) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                      @else
                        -
                      @endif
                    </td>
                  </tr>
                  @empty
                  <tr class="border-t border-t-[#D9D9D9]">
                    <td colspan="5" class="lg:p-3 p-2 text-[#1E1E1E] text-center">No logs</td>
                  </tr>
                @endforelse
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
    $('.term-detail-toggle').on('click', function () {
      const $trigger = $(this);
      const target = $trigger.data('target');
      const $panel = $(target);

      if (!$panel.length) {
        return;
      }

      $panel.stop(true, true).slideToggle(200);
      $trigger.find('.fa-chevron-right').first().toggleClass('rotate-90');
    });
  });
</script>
@endsection
