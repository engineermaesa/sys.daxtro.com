@extends('layouts.app')

@section('content')
  <section class="min-h-screen">
    {{-- HEADER PAGES --}}
    <div class="pt-4">
        {{-- ICON PAGES --}}
        <div class="flex items-center gap-3">        
          <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z" fill="#115640"/>
          </svg>
          <h1 class="text-[#115640] font-semibold text-2xl">Leads</h1>
        </div>

        {{-- BREADCUMBS HREF --}}
        <div class="flex items-center mt-2 gap-3">
            <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">My Leads</a>
            <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
            <a href="/" class="text-[#083224] underline">
              Detail Leads
            </a>
        </div>

        {{-- LEAD DETAIL --}}
        <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
          <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Lead Detail</h1>
          <div class="p-3">
            <div class="border border-[#D9D9D9] rounded-lg">
              <table class="w-full text-[#1E1E1E]">
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Name</th>
                  <td class="p-3">{{ $lead->name }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Phone</th>
                  <td class="p-3">{{ $lead->phone }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Email</th>
                  <td class="p-3">{{ $lead->email }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Needs</th>
                  <td class="p-3">{{ $lead->needs }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Status</th>
                  <td class="p-3">{{ $lead->status->name ?? '-' }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Source</th>
                  <td class="p-3">{{ $lead->source->name ?? '-' }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Segment</th>
                  <td class="p-3">{{ $lead->segment->name ?? '-' }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Region</th>
                  <td class="p-3">{{ $lead->region->name ?? '-' }}</td>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">First Sales</th>
                  <td class="p-3">{{ $lead->firstSales->name ?? '-' }}</td>
                </tr>
                <tr>
                  <th class="p-3">Trash Note</th>
                  <td class="p-3">{{ $claim->trash_note ?? '-' }}</td>
                </tr>
              </table>
            </div>
          </div>
        </div>

        {{-- MEETING SECTION --}}
        @if($meeting)
          <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
            <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Latest Meeting</h1>
            <div class="p-3">
              <div class="border border-[#D9D9D9] rounded-lg">
                <table class="w-full text-[#1E1E1E]">
                  <tr>
                    <th class="p-3">Schedule</th>
                    <td class="p-3">
                      {{ $meeting->scheduled_start_at ? date('d M Y H:i', strtotime($meeting->scheduled_start_at)) : '' }}
                      -
                      {{ $meeting->scheduled_end_at ? date('d M Y H:i', strtotime($meeting->scheduled_end_at)) : '' }}
                    </td>
                  </tr>
                  <tr class="border-t border-t-[#D9D9D9]">
                    <th class="p-3">Type</th>
                    <td class="p-3">{{ $meeting->is_online ? 'Online' : 'Offline' }}</td>
                  </tr>
                  @if($meeting->is_online)
                    <tr class="border-t border-t-[#D9D9D9]">
                      <th class="p-3">URL</th>
                      <td class="p-3">{{ $meeting->online_url }}</td>
                    </tr>
                  @else
                    <tr class="border-t border-t-[#D9D9D9]">
                      <th class="p-3">Location</th>
                      <td class="p-3">{{ trim(($meeting->city ?? '') . ' ' . ($meeting->address ?? '')) }}</td>
                    </tr>
                  @endif
                  <tr class="border-t border-t-[#D9D9D9]">
                    <th class="p-3">Result</th>
                    <td class="p-3">{{ $meeting->result ?? '-' }}</td>
                  </tr>
                  <tr class="border-t border-t-[#D9D9D9]">
                    <th class="p-3">Summary</th>
                    <td class="p-3">{{ $meeting->summary ?? '-' }}</td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
          @php
            $statusColors = [
            'pending' => 'status-warm',
            'approved' => 'status-deal',
            'rejected' => 'status-hot',
            ];
          @endphp
  
          @if ($meeting->expense)
            <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
              <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Meeting Expense</h1>
              <div class="p-3">
                <div class="border border-[#D9D9D9] rounded-lg text-[#1E1E1E]">
                  <table class="w-full">
                    <tr>
                      <th class="p-3">Total Amount</th>
                      <td class="p-3">
                        Rp{{ number_format($meeting->expense->amount, 0, ',', '.') }}
                        duit
                      </td>
                    </tr>
                    <tr class="border-t border-t-[#D9D9D9]">
                      <th class="p-3">Status</th>
                      <td class="p-3">
                        <span class="status-{{ $statusColors[$meeting->expense->status] ?? 'secondary' }}">
                            {{ucwords(str_replace('-', ' ', $meeting->expense->status)) }}
                        </span>
                      </td>
                    </tr>
                  </table>
                  
                  <h1 class="p-3 font-semibold border-y border-y-[#D9D9D9]">Expense Breakdown</h1>
                  <table class="w-full">
                    <thead>
                      <tr>
                        <th class="p-3">Type</th>
                        <th class="p-3">Notes</th>
                        <th class="p-3">Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($meeting->expense->details as $detail)
                        <tr class="border-t border-t-[#D9D9D9]">
                          <td class="p-3">{{ $detail->expenseType->name ?? '-' }}</td>
                          <td class="p-3">{{ $detail->notes ?? '-' }}</td>
                          <td class="p-3">Rp{{ number_format($detail->amount, 0, ',', '.') }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
  
                  @if($meeting->expense->financeRequest)
                    <h1 class="p-3 font-semibold border-y border-y-[#D9D9D9]">Expense Breakdown</h1>
                    <table class="w-full">
                      <tr class="border-t border-t-[#D9D9D9]">
                        <th class="p-3">Status</th>
                        <td class="p-3">
                          <span class="status-{{ $statusColors[$meeting->expense->status] ?? 'secondary' }}">
                            {{ucwords(str_replace('-', ' ', $meeting->expense->status)) }}
                          </span>
                        </td>
                      </tr>
                      <tr class="border-t border-t-[#D9D9D9]">
                        <th class="p-3">Notes</th>
                        <td class="p-3">{{ $meeting->expense->financeRequest->notes ?? '-' }}</td>
                      </tr>
                      <tr class="border-t border-t-[#D9D9D9]">
                        <th class="p-3">Decided At</th>
                        <td class="p-3">
                          {{ $meeting->expense->financeRequest->decided_at ? date('d M Y H:i', strtotime($meeting->expense->financeRequest->decided_at)) : '' }}
                        </td>
                      </tr>
                    </table>
                  @endif
                </div>
              </div>
            </div>
          @endif
        @endif

        {{-- LEAD QUOTATION --}}
        @if ($lead->quotation)
          <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
            <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Quotation</h1>
            <div class="p-3">
              <div class="border border-[#D9D9D9] rounded-lg">
                <table class="w-full text-[#1E1E1E]">
                  <tr>
                    <th class="p-3">No</th>
                    <td class="p-3">
                      {{ $lead->quotation->quotation_no }}
                    </td>
                  </tr>
                  <tr class="border-t border-t-[#D9D9D9]">
                    <th class="p-3">Status</th>
                    <td class="p-3">{{ ucfirst($lead->quotation->status) }}</td>
                  </tr>
                  <tr class="border-t border-t-[#D9D9D9]">
                    <th class="p-3">Grand Total</th>
                    <td class="p-3">Rp{{ number_format($lead->quotation->grand_total, 0, ',', '.') }}</td>
                  </tr>
                  <tr class="border-t border-t-[#D9D9D9]">
                    <th class="p-3">Expiry Date</th>
                    <td class="p-3">{{ $lead->quotation->expiry_date ? date('d M Y', strtotime($lead->quotation->expiry_date)) : '-' }}</td>
                  </tr>
                </table>
              </div>

              {{-- PROFORMAS SECTION --}}
              @if($lead->quotation->proformas->count())
              <div class="border border-[#D9D9D9] rounded-lg mt-4">
                <table class="w-full text-[#1E1E1E]">
                  <thead>
                    <tr>
                      <th class="p-3">Term</th>
                      <th class="p-3">No</th>
                      <th class="p-3">Status</th>
                      <th class="p-3">Issued</th>
                      <th class="p-3">Amount</th>
                      <th class="p-3">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php
                      $isSales = auth()->user()->role?->code === 'sales'; 
                      $orderId = $lead->order->id ?? null;
                    @endphp

                    @foreach($lead->quotation->proformas as $pf)
                      <tr class="border-t border-t-[#D9D9D9]">
                        <td class="p-3">{{ $pf->term_no }}</td>
                        <td class="p-3">{{ $pf->proforma_no ?? '-' }}</td>
                        <td class="p-3">{{ ucfirst($pf->status) }}</td>
                        <td class="p-3">{{ $pf->issued_at ? date('d M Y', strtotime($pf->issued_at)) : '-' }}</td>
                        <td class="p-3">Rp{{ number_format($pf->amount, 0, ',', '.') }}</td>
                        <td class="p-3">
                          @if($pf->attachment_id)
                          <a href="{{ route('attachments.download', $pf->attachment_id) }}"
                            class="cursor-pointer px-5 py-2 bg-white border border-[#115640] rounded-lg text-[#083224] font-semibold inline-flex items-center gap-1">
                              <x-icon.download/>
                              Download Quotation
                          </a>
                          @if($isSales && $pf->status === 'confirmed' && !$pf->paymentConfirmation && $orderId)
                          <a href="{{ route('orders.terms.payment.confirm.form', [$orderId, $pf->term_no]) }}"
                            class="inline-flex items-center gap-2 text-white bg-[#115640] px-3 py-2 duration-300 border border-[#05261B] hover:bg-[#0D4433] hover:border hover:border-[#05261B] rounded-lg">
                            <x-icon.dollar/>
                            Payment Confirmation
                          </a>
                          @endif
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @endif
            </div>  
          </div>  
        @endif
    </div>
  </section>
@endsection