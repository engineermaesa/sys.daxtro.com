@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Meeting Schedule</strong>

          {{-- CONDITIONAL BY DATE --}}
          <div>
            @if($canReschedule ?? false)
              <a href="{{ route('leads.my.cold.meeting.reschedule', $data->id) }}"
                 class="btn btn-warning btn-sm">Update Meeting</a>
            @endif
            
            @if($data && now()->gt($data->scheduled_end_at) && $data->result === null &&
                ($data->is_online || optional($data->expense)->status === 'approved'))
              <a href="{{ route('leads.my.cold.meeting.result', $data->id) }}"
                class="btn btn-success btn-sm">Set Meeting Result</a>
            @endif

            @if($data && !in_array(optional($data->expense)->status, ['submitted', 'canceled']) && is_null($data->result))
              <button type="button"
                      class="btn btn-danger btn-sm"
                      id="btnCancelMeeting"
                      data-url="{{ route('leads.my.cold.meeting.cancel', $data->id) }}"
                      data-online="{{ $data->is_online ? 1 : 0 }}"
                      data-status="{{ optional($data->expense)->status }}">
                Cancel Meeting
              </button>
            @endif
          </div>
        </div>

        <div class="card-body pt-3">
          {{-- overall meeting lifecycle + expense workflow --}}
          @if(!empty($data))
            <div class="mb-3">
              @php
                $rescheduleCount = $data?->reschedules->count();
                $isOffline = !$data->is_online;
                $expenseStatus = $data->expense?->status ?? null;
                $now = now();
              @endphp

              {{-- Status Badge --}}
              <div>
                @if($isOffline && $expenseStatus === 'submitted')
                  <span class="badge badge-warning">Awaiting Finance Approval</span>
                @elseif($isOffline && $expenseStatus === 'rejected')
                  <span class="badge badge-danger">Rejected by Finance</span>
                  @if($data->expense?->financeRequest?->notes)
                    <div class="alert alert-danger mt-2 mb-0 py-2 px-3 small">
                      <i class="bi bi-info-circle-fill mr-1"></i>
                      <strong>FINANCE NOTES:</strong> {{ $data->expense->financeRequest->notes }}
                    </div>
                  @endif
                @elseif($isOffline && $expenseStatus === 'approved')
                  <span class="badge badge-success">Approved by Finance</span>
                  @if($data->expense?->financeRequest?->notes)
                    <div class="alert alert-success mt-2 mb-0 py-2 px-3 small">
                      <i class="bi bi-info-circle-fill mr-1"></i>
                      <strong>FINANCE NOTES:</strong> {{ $data->expense->financeRequest->notes }}
                    </div>
                  @endif
                @elseif($now->lt($data->scheduled_start_at))
                  <span class="badge badge-info">Scheduled</span>
                @elseif($now->gt($data->scheduled_end_at) && $data->result === null)
                  <span class="badge badge-secondary">Meeting Expired</span>
                @elseif($data->result !== null)
                  <span class="badge badge-success">Completed ({{ ucfirst($data->result) }})</span>
                @else
                  <span class="badge badge-primary">Meeting In Progress</span>
                @endif

                @if($rescheduleCount > 0)
                  <span class="badge badge-light text-dark ml-2">
                    Rescheduled {{ $rescheduleCount }} time{{ $rescheduleCount > 1 ? 's' : '' }}
                  </span>
                @endif
              </div>

            </div>
          @endif

          {{-- IF SCHEDULED --}}
          @if($isViewOnly ?? false)
            <div class="alert alert-info">
              <strong>Note:</strong> This meeting has already been scheduled. Fields are read-only.
            </div>
          @endif

          {{-- COUNT AND URL MEETING --}}
          @if(!empty($data))
            <div class="mb-3">
              {{-- COUNT RESCHEDULED --}}
              @if($data->reschedules && $data->reschedules->count() > 0)
                <span class="badge bg-warning me-2">
                  Rescheduled {{ $data->reschedules->count() }} time{{ $data->reschedules->count() > 1 ? 's' : '' }}
                </span>
              @endif

              {{-- URL MEETING ONLINE --}}
              @if($data->is_online && !empty($data->online_url))
                <div class="alert alert-secondary mt-2">
                  <strong>Meeting Link:</strong>
                  <a href="{{ $data->online_url }}" target="_blank">{{ $data->online_url }}</a>
                </div>
              @endif
            </div>
          @endif

          {{-- MAIN FORM --}}
          <form id="form"
                method="POST"
                action="{{ route('leads.my.cold.meeting.save', ['id' => $data->id ?? '']) }}"
                back-url="{{ route('leads.my') }}"
                enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="lead_id" value="{{ old('lead_id', $data->lead_id ?? $lead_id ?? '') }}">

            {{-- Meeting Type --}}
            {{-- <div class="mb-3">
              <label for="meeting_type_id" class="form-label">Meeting Type <i class="required">*</i></label>
              <select name="meeting_type_id" id="meeting_type_id"
                      class="form-select select2"
                      @if($isViewOnly ?? false) disabled @endif required>
                <option value="">-- Select Type --</option>
                @foreach($meetingTypes as $mt)
                  <option value="{{ $mt->id }}"
                          data-name="{{ $mt->name }}"
                          {{ old('meeting_type_id', $data->meeting_type_id ?? '') == $mt->id ? 'selected' : '' }}>
                    {{ $mt->name }}
                  </option>
                @endforeach
              </select>
            </div> --}}

            {{-- Lead Details Table
            <div class="mb-3">
              <label class="form-label">Lead Details <i class="required">*</i></label>
              <table class="table table-bordered" id="leads-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Province</th>
                    <th>City</th>
                    <th>Product</th>
                    <th style="width:190px;">Price</th>
                    <th>Description</th>
                    @if(!($isViewOnly ?? false))
                      <th style="width:40px;"></th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @php
                    $leadDetails = old('lead_name')
                      ? collect(old('lead_name'))->map(fn($name, $i) => [
                          'name' => $name,
                          'type' => old('lead_type')[$i] ?? '',
                          'province' => old('lead_province')[$i] ?? '',
                          'city' => old('lead_city')[$i] ?? '',
                          'product_id' => old('lead_product_id')[$i] ?? '',
                          'price' => old('lead_price')[$i] ?? '',
                          'description' => old('lead_description')[$i] ?? '',
                        ])
                      : (isset($data) && $data->leadDetails && $data->leadDetails->count() 
                          ? $data->leadDetails->map(fn($d) => [
                              'name' => $d->name,
                              'type' => $d->type,
                              'province' => $d->province,
                              'city' => $d->city,
                              'product_id' => $d->product_id,
                              'price' => $d->price,
                              'description' => $d->description,
                            ])
                          : collect([['name' => '', 'type' => 'office', 'province' => '', 'city' => '', 'product_id' => '', 'price' => '', 'description' => '']])
                        );
                  @endphp

                  @foreach($leadDetails as $row)
                  <tr>
                    <td>
                      <input type="text" name="lead_name[]" class="form-control" 
                             value="{{ $row['name'] }}" 
                             @if($isViewOnly ?? false) readonly @endif required>
                    </td>
                    <td>
                      <select name="lead_type[]" class="form-select" @if($isViewOnly ?? false) disabled @endif required>
                        <option value="office" {{ $row['type'] == 'office' ? 'selected' : '' }}>Office</option>
                        <option value="canvas" {{ $row['type'] == 'canvas' ? 'selected' : '' }}>Canvas</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" name="lead_province[]" class="form-control lead-province" 
                             value="{{ $row['province'] }}" 
                             @if($isViewOnly ?? false) readonly @endif required>
                    </td>
                    <td>
                      <select name="lead_city[]" class="form-select select2 lead-city" 
                              @if($isViewOnly ?? false) disabled @endif required>
                        <option value="">Select City</option>
                        @foreach($cities as $cityOption)
                          <option value="{{ $cityOption }}" 
                                  data-province="{{ $cityOption }}"
                                  {{ $row['city'] == $cityOption ? 'selected' : '' }}>
                            {{ $cityOption }}
                          </option>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <select name="lead_product_id[]" class="form-select lead-product select2" 
                              @if($isViewOnly ?? false) disabled @endif required>
                        <option value="">Select Product</option>
                        @foreach($products as $p)
                          <option value="{{ $p->id }}"
                                  {{ $row['product_id'] == $p->id ? 'selected' : '' }}
                                  data-price="{{ $p->price }}"
                                  data-gov="{{ $p->government_price }}"
                                  data-corp="{{ $p->corporate_price }}"
                                  data-pers="{{ $p->personal_price }}"
                                  data-fob="{{ $p->fob_price }}">
                            {{ $p->name }} ({{ $p->sku }})
                          </option>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <input type="text" name="lead_price[]" 
                             class="form-control lead-price number-input" 
                             value="{{ $row['price'] ? number_format($row['price'], 0, ',', '.') : '' }}" 
                             @if($isViewOnly ?? false) readonly @endif required>
                      @if(isset($row['product_id']) && $row['product_id'])
                        @php
                          $product = $products->firstWhere('id', $row['product_id']);
                        @endphp
                        @if($product)
                        <div class="form-text segment-price-info small text-muted">
                          <ul class="list-inline mb-0">
                            <li class="list-inline-item me-3">
                              <span class="fw-semibold">Gov:</span>
                              Rp{{ number_format($product->government_price ?? 0, 0, ',', '.') }}
                            </li>
                            <li class="list-inline-item me-3">
                              <span class="fw-semibold">Corp:</span>
                              Rp{{ number_format($product->corporate_price ?? 0, 0, ',', '.') }}
                            </li>
                            <li class="list-inline-item me-3">
                              <span class="fw-semibold">Personal:</span>
                              Rp{{ number_format($product->personal_price ?? 0, 0, ',', '.') }}
                            </li>
                            <li class="list-inline-item me-3">
                              <span class="fw-semibold">FOB:</span>
                              Rp{{ number_format($product->fob_price ?? 0, 0, ',', '.') }}
                            </li>
                          </ul>
                        </div>
                        @endif
                      @else
                        <div class="form-text segment-price-info small text-muted"></div>
                      @endif
                    </td>
                    <td>
                      <input type="text" name="lead_description[]" class="form-control" 
                             value="{{ $row['description'] }}" 
                             @if($isViewOnly ?? false) readonly @endif>
                    </td>
                    @if(!($isViewOnly ?? false))
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-lead">&times;</button>
                      </td>
                    @endif
                  </tr>
                  @endforeach
                </tbody>
              </table>
              @if(!($isViewOnly ?? false))
                <button type="button" id="add-lead" class="btn btn-sm btn-outline-primary">Add Lead</button>
              @endif
            </div> --}}

            {{-- Online URL --}}
            {{-- <div id="online-url-section" class="mb-3" style="display: none;">
              <label for="meeting_url" class="form-label">Meeting URL <i class="required">*</i></label>
              <input type="url"
                    name="meeting_url"
                    id="meeting_url"
                    class="form-control"
                    value="{{ old('meeting_url', $data->online_url ?? '') }}"
                    @if($isViewOnly ?? false) readonly @endif>
            </div> --}}

            {{-- Offline Section --}}
            {{-- <div id="offline-section" style="display: none;">
              <div class="mb-3">
                <label for="province" class="form-label">Province <i class="required">*</i></label>
                <input type="text" 
                       name="province" 
                       id="province" 
                       class="form-control" 
                       value="{{ old('province', $data->province ?? '') }}"
                       readonly
                       style="background-color: #e9ecef; cursor: not-allowed;"
                       @if($isViewOnly ?? false) readonly @endif>
              </div>
              
              <div class="mb-3">
                <label for="city" class="form-label">City <i class="required">*</i></label>
                <select name="city" id="city" class="form-select select2" @if($isViewOnly ?? false) disabled @endif>
                  <option value="">-- Select City --</option>
                  @foreach($cities as $cityOption)
                    <option value="{{ $cityOption }}" {{ old('city', $data->city ?? '') == $cityOption ? 'selected' : '' }}>{{ $cityOption }}</option>
                  @endforeach
                </select>
              </div>
              
              <div class="mb-3">
                <label for="address" class="form-label">Address <i class="required">*</i></label>
                <textarea name="address" id="address"
                          class="form-control"
                          rows="2"
                          @if($isViewOnly ?? false) readonly @endif>{{ old('address', $data->address ?? '') }}</textarea>
              </div>
\
              <div class="mb-3">
                <label class="form-label">Expenses <i class="required">*</i></label>
                <table class="table table-bordered" id="expense-table">
                  <thead>
                    <tr>
                      <th>Type</th>
                      <th>Notes</th>
                      <th style="width: 150px;">Amount</th>
                      <th style="width: 40px;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    @php
                      $expenseDetails = old('expense_type_id')
                        ? collect(old('expense_type_id'))->map(fn($id, $i) => [
                            'type_id' => $id,
                            'notes'  => old('expense_notes')[$i] ?? '',
                            'amount'  => old('expense_amount')[$i] ?? '',
                          ])
                        : (isset($data) && $data->expense
                            ? $data->expense->details->map(fn($e) => [
                                'type_id' => $e->expense_type_id,
                                'notes'  => $e->notes,
                                'amount'  => $e->amount,
                              ])
                            : collect([[ 'type_id' => null, 'notes' => null, 'amount' => null ]])
                          );
                    @endphp

                    @foreach($expenseDetails as $row)
                    <tr>
                      <td>
                        <select name="expense_type_id[]" class="form-select" @if($isViewOnly ?? false) disabled @endif>
                          @foreach($expenseTypes as $et)
                            <option value="{{ $et->id }}" {{ $et->id == $row['type_id'] ? 'selected' : '' }}>{{ $et->name }}</option>
                          @endforeach
                        </select>
                      </td>
                      <td>
                        <input type="text"
                              name="expense_notes[]"
                              class="form-control"
                              value="{{ $row['notes'] }}"
                              @if($isViewOnly ?? false) readonly @endif>
                      </td>
                      <td>
                        <input type="number" step="0.01"
                              name="expense_amount[]"
                              class="form-control"
                              value="{{ $row['amount'] }}"
                              @if($isViewOnly ?? false) readonly @endif>
                      </td>
                      <td class="text-center">
                        @if(!($isViewOnly ?? false))
                          <button type="button" class="btn btn-sm btn-danger remove-expense">&times;</button>
                        @endif
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
                @if(!($isViewOnly ?? false))
                  <button type="button" id="add-expense" class="btn btn-sm btn-outline-primary">Add Expense</button>
                @endif
              </div>
            </div> --}}

            {{-- Reschedule reason --}}
            @if(isset($isReschedule) && $isReschedule)
              <div class="mb-3">
                <label for="reason" class="form-label">Reschedule Reason <i class="required">*</i></label>
                <textarea name="reason" id="reason" class="form-control" rows="2" required></textarea>
              </div>
            @endif

            {{-- Schedule --}}
            <div class="mb-3">
              <label for="scheduled_start_at" class="form-label">Start Time <i class="required">*</i></label>
              <input type="datetime-local"
                    onfocus="this.showPicker()"
                    name="scheduled_start_at"
                    id="scheduled_start_at"
                    class="form-control"
                    min="{{ now()->format('Y-m-d\TH:i') }}"
                    value="{{ old('scheduled_start_at', isset($data->scheduled_start_at) ? \Carbon\Carbon::parse($data->scheduled_start_at)->format('Y-m-d\TH:i') : '') }}"
                    @if($isViewOnly ?? false) readonly @endif required>
            </div>

            <div class="mb-3">
              <label for="scheduled_end_at" class="form-label">End Time <i class="required">*</i></label>
              <input type="datetime-local"
                    onfocus="this.showPicker()"
                    name="scheduled_end_at"
                    id="scheduled_end_at"
                    class="form-control"
                    min="{{ now()->format('Y-m-d\TH:i') }}"
                    value="{{ old('scheduled_end_at', isset($data?->scheduled_end_at) ? \Carbon\Carbon::parse($data?->scheduled_end_at)->format('Y-m-d\TH:i') : '') }}"
                    @if($isViewOnly ?? false) readonly @endif required>
            </div>

            {{-- Submit / Back --}}
            <div class="d-flex justify-content-between">
              @if(!($isViewOnly ?? false))
                @include('partials.common.save-btn-form', ['backUrl' => route('leads.my')])
              @else
                <a href="{{ route('leads.my') }}" class="btn btn-secondary">Back</a>
              @endif
            </div>
          </form>
          

          @if( ! empty($data->reschedules) && $data->reschedules->count())
            <hr>
            <h6 class="text-muted">Reschedule History</h6>
            <table class="table table-sm table-bordered mt-2">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Old Time</th>                  
                  <th>Old Location</th>                  
                  <th>Old Online URL</th>
                  <th>Reason</th>
                  <th>By</th>
                  <th>At</th>
                </tr>
              </thead>
              <tbody>
                @foreach($data->reschedules as $i => $r)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>
                    {{ \Carbon\Carbon::parse($r->old_scheduled_start_at)->format('d M Y H:i') }}<br>
                    - {{ \Carbon\Carbon::parse($r->old_scheduled_end_at)->format('d M Y H:i') }}
                  </td>
                  <td>{{ !empty($r->old_location) ? $r->old_location : '-' }}</td>
                  <td>
                    @if(!empty($r->old_online_url))
                      <a href="{{ $r->old_online_url }}" target="_blank">Open Link</a>
                    @else
                      -
                    @endif
                  </td>
                  <td>{{ $r->reason ?? '-' }}</td>
                  <td>{{ $r->rescheduler->name ?? 'N/A' }}</td>
                  <td>{{ \Carbon\Carbon::parse($r->rescheduled_at)->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>

<section class="min-h-screen">
  <div class="pt-4">
    <div class="flex items-center gap-3">
      <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
          fill="#115640" />
      </svg>
      <h1 class="text-[#115640] font-semibold text-2xl">Leads</h1>
    </div>
    <div class="flex items-center mt-2 gap-3">
      <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">My Leads</a>
      <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
      <a href="/" class="text-[#083224] underline">
          Set A Meeting
      </a>
    </div>

    <form id="form"
        method="POST"
        action="{{ route('leads.my.cold.meeting.save', ['id' => $data->id ?? '']) }}"
        back-url="{{ route('leads.my') }}"
        enctype="multipart/form-data">
      @csrf

      <input type="hidden" name="lead_id" value="{{ old('lead_id', $data->lead_id ?? $lead_id ?? '') }}">

      {{-- MEETING PLAN SECTION --}}
      <div class="bg-white border border-[#D9D9D9] rounded mt-4">
        <h1 class="font-semibold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9] uppercase">Meeting Plan</h1>
        {{-- MEETING TYPE AND DATE TIME --}}
        <div class="px-3 py-2 grid grid-cols-2 gap-5">
          {{-- MEETING TYPE SELECT FIELD --}}
          <div class="text-[#1E1E1E]">
            <label for="meeting_type_id" class="text-[#1E1E1E]! mb-1!">Meeting Type <i class="required">*</i></label>
            <select name="meeting_type_id" id="meeting_type_id"
              class="px-3 py-2 rounded-lg border border-[#D9D9D9] w-full"
              @if($isViewOnly ?? false) disabled @endif required>
              <option value="">Select</option>
              @foreach($meetingTypes as $mt)
                <option value="{{ $mt->id }}"
                        data-name="{{ $mt->name }}"
                        {{ old('meeting_type_id', $data->meeting_type_id ?? '') == $mt->id ? 'selected' : '' }}>
                  {{ $mt->name }}
                </option>
              @endforeach
            </select>
          </div>
          {{-- START & END TIME --}}
          <div class="text-[#1E1E1E]">
              <label for="scheduled_start_at" class="text-[#1E1E1E]! mb-1!">Start & End Time <i class="required">*</i></label>
              <button
                  type="button"
                  id="selectDateBtn"
                  class="px-4 py-2 rounded-lg w-full text-left border border-[#D9D9D9] cursor-pointer"
              >
                  Select Date
              </button>

              <div id="dateDropdown" class="date-dropdown hidden mt-2 rounded-lg p-3 bg-white grid grid-cols-2 gap-4 border border-[#D9D9D9]">
                  <div class="fp-wrap relative">
                      <p class="text-sm mb-1">Start Time</p>
                      <input id="fpStart" type="text" class="rounded p-2 w-full border border-[#D9D9D9] cursor-pointer"
                      placeholder="Select start time">
                  </div>
                  <div class="fp-wrap relative">
                      <p class="text-sm mb-1">End Time</p>
                      <input id="fpEnd" type="text" class="rounded p-2 w-full border border-[#D9D9D9] cursor-pointer"
                      placeholder="Select end time">
                  </div>

              </div>
              <input 
                type="hidden" 
                name="scheduled_start_at" 
                id="scheduled_start_at"
                value="{{ old('scheduled_start_at', isset($data->scheduled_start_at)
                ? \Carbon\Carbon::parse($data->scheduled_start_at)->format('Y-m-d H:i')
                : '') }}">
              <input 
                type="hidden" 
                name="scheduled_end_at" 
                id="scheduled_end_at"
                value="{{ old('scheduled_end_at', isset($data->scheduled_end_at)
                ? \Carbon\Carbon::parse($data->scheduled_end_at)->format('Y-m-d H:i')
                : '') }}">
          </div>
        </div>
        {{-- Online URL --}}
        <div id="online-url-section" class="px-3 py-2 text-[#1E1E1E]!" style="display: none;">
          <label for="meeting_url" class="text-[#1E1E1E]! mb-1!">Meeting URL <i class="required">*</i></label>
          <input type="url"
                name="meeting_url"
                id="meeting_url"
                class="px-3 py-2 rounded-lg border border-[#D9D9D9] w-full"
                value="{{ old('meeting_url', $data->online_url ?? '') }}"
                @if($isViewOnly ?? false) readonly @endif>
        </div>

        {{-- Offline Section --}}
        <div id="offline-section" class="px-3 py-2" style="display: none;">
          <div class="grid grid-cols-2 gap-5">
            <div class="text-[#1E1E1E]!">
              <label for="province" class="text-[#1E1E1E]! mb-1! block">Province <i class="required">*</i></label>
              <input type="text" 
                    name="province" 
                    id="province"
                    value="{{ old('province', $data->province ?? '') }}"
                    readonly
                    class="px-3 py-2 rounded-lg! cursor-not-allowed! bg-[#e9ecef]! w-full! border border-[#D9D9D9]!"
                    @if($isViewOnly ?? false) readonly @endif>
            </div>
            
            <div class="text-[#1E1E1E]!">
              <label for="city" class="text-[#1E1E1E]! mb-1! block">City <i class="required">*</i></label>
              <select name="city" id="city" class="select2" @if($isViewOnly ?? false) disabled @endif>
                <option value="">Select City</option>
                @foreach($cities as $cityOption)
                  <option value="{{ $cityOption }}" {{ old('city', $data->city ?? '') == $cityOption ? 'selected' : '' }}>{{ $cityOption }}</option>
                @endforeach
              </select>
            </div>
          </div>
          
          <div class="text[#1E1E1E]! mt-3">
            <label for="address" class="block! mb-1!">Address <i class="required">*</i></label>
            <textarea name="address" id="address"
              class="px-3! py-2! rounded-lg! w-full! border border-[#D9D9D9]! focus:outline-[#115640]"
              rows="5"
              @if($isViewOnly ?? false) readonly @endif>{{ old('address', $data->address ?? '') }}</textarea>
          </div>
        </div>
      </div>

      {{-- EXPENSES SECTION --}}
      <div id="expense-section" class="expense-table bg-white border border-[#D9D9D9] rounded mt-4">
        <h1 class="font-semibold text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9] uppercase">Expenses</h1>
        <div class="px-3 py-2">
          <div class="border border-[#D9D9D9] rounded-lg ">
            <table class=" w-full" id="expense-table">
              <thead class="text-[#1E1E1E]!">
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="p-3">Type</th>
                  <th class="px-3">Notes</th>
                  <th class="px-3">Amount</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $expenseDetails = old('expense_type_id')
                    ? collect(old('expense_type_id'))->map(fn($id, $i) => [
                        'type_id' => $id,
                        'notes'  => old('expense_notes')[$i] ?? '',
                        'amount'  => old('expense_amount')[$i] ?? '',
                      ])
                    : (isset($data) && $data->expense
                        ? $data->expense->details->map(fn($e) => [
                            'type_id' => $e->expense_type_id,
                            'notes'  => $e->notes,
                            'amount'  => $e->amount,
                          ])
                        : collect([[ 'type_id' => null, 'notes' => null, 'amount' => null ]])
                      );
                @endphp
  
                @foreach($expenseDetails as $row)
                <tr class="text-[#1E1E1E]">
                  <td class="px-3 py-2">
                    <select name="expense_type_id[]" class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg focus:outline-none" @if($isViewOnly ?? false) disabled @endif>
                      @foreach($expenseTypes as $et)
                        <option value="{{ $et->id }}" {{ $et->id == $row['type_id'] ? 'selected' : '' }}>{{ $et->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td class="px-3 py-2">
                    <input type="text"
                          name="expense_notes[]"
                          class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg focus:outline-none"
                          value="{{ $row['notes'] }}"
                          placeholder="Type Note Here..."
                          @if($isViewOnly ?? false) readonly @endif>
                  </td>
                  <td class="px-3 py-2">
                    <input type="number" step="0.01"
                          name="expense_amount[]"
                          class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg focus:outline-none"
                          value="{{ $row['amount'] }}"
                          placeholder="Input Amount Here..."
                          @if($isViewOnly ?? false) readonly @endif>
                  </td>
                  <td class="text-center">
                    @if(!($isViewOnly ?? false))
                      <button type="button" class="remove-expense flex items-center justify-center w-full text-[#900B09] cursor-pointer font-semibold">
                        @include('components.icon.trash', ['class' => 'text-[#900B09]'])
                        Delete
                      </button>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @if(!($isViewOnly ?? false))
            <button type="button" id="add-expense" class="flex items-center w-full text-[#083224] cursor-pointer font-semibold mt-3 gap-3">
              @include('components.icon.circle-plus', ['class' => 'text-[#083224]'])
              More Expense
            </button>
          @endif
        </div>
      </div>
      <div class="flex justify-end py-3">
        @php
          $isViewOnly = $isViewOnly ?? false;
        @endphp

        @if(!$isViewOnly)
          @include('partials.template.save-btn-form', ['backUrl' => 'back'])
        @else
        <div class="w-full flex justify-between items-center">
          <a href="{{ route('leads.my') }}"
            class="inline-block text-center w-[150px] px-3 py-2 border border-[#083224] text-[#083224] font-semibold rounded-lg cursor-pointer transition-all duration-300 bg-white hover:bg-[#F1F1F1]">
              Back
          </a>
          <div class="flex items-center gap-3">
            @if($data && !in_array(optional($data->expense)->status, ['submitted', 'canceled']) && is_null($data->result))
                <button type="button"
                        class="cursor-pointer text-[#900B09] bg-[#FDD3D0] font-semibold rounded-lg inline-block text-center w-[150px] px-3 py-2 border border-[#900B09]"
                        id="btnCancelMeeting"
                        data-url="{{ route('leads.my.cold.meeting.cancel', $data->id) }}"
                        data-online="{{ $data->is_online ? 1 : 0 }}"
                        data-status="{{ optional($data->expense)->status }}">
                    Cancel Meeting
                </button>
            @endif

            @if($canReschedule ?? false)
              <a href="{{ route('leads.my.cold.meeting.reschedule', $data->id) }}"
                class="text-[#522504] bg-[#FFF1C2] font-semibold rounded-lg inline-block text-center w-[150px] px-3 py-2 border border-[#522504]">Update Meeting</a>
            @endif

            @if($data && now()->gt($data->scheduled_end_at) && $data->result === null &&
              ($data->is_online || optional($data->expense)->status === 'approved'))
              <a href="{{ route('leads.my.cold.meeting.result', $data->id) }}"
                class="cursor-pointer text-[#02542D] bg-[#CFF7D3] font-semibold rounded-lg inline-block text-center w-[150px] px-3 py-2 border border-[#02542D]">Set Meeting Result</a>
            @endif
          </div>
        </div>
        @endif
      </div>
    </form>
  </div>
</section>
@endsection

@section('styles')
<style>
    .expo-auto-filled {
        background-color: #f8f9fa;
        border-color: #6c757d;
        color: #6c757d;
    }
    .expo-info {
        background-color: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    .date-dropdown {
        opacity: 0;
        transform: translateY(-6px) scale(.98);
        transition: all .25s cubic-bezier(.4,0,.2,1);
        pointer-events: none;
    }

    .date-dropdown.show {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .hidden {
        display: none;
    }
</style>
@endsection

@section('scripts')
<script >
  $(function () {
    $('.select2').select2({ width: '100%' });

    const onlineNames = ['Zoom / Google Meet', 'Video Call'];
    const zoomName = 'Zoom / Google Meet';
    const expoName = 'EXPO';

    let startVal = $('input[name="scheduled_start_at"][type="hidden"]').val()?.replace('T', ' ');
    let endVal = $('input[name="scheduled_end_at"][type="hidden"]').val()?.replace('T', ' ');

    $('#selectDateBtn').on('click', function () {
        const dropdown = $('#dateDropdown');

        if (dropdown.hasClass('show')) {
            dropdown.removeClass('show');

            setTimeout(() => {
                dropdown.addClass('hidden');
            }, 200);

        } else {
            dropdown.removeClass('hidden');

            setTimeout(() => {
                dropdown.addClass('show');
            }, 10);
        }
    });

    const fpStart = flatpickr("#fpStart", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        defaultDate: startVal || null,
        position: "below",
        disableMobile: true,
        onChange(selectedDates, dateStr) {
            startVal = dateStr;
            $('input[name="scheduled_start_at"]').val(dateStr);
            updateLabel();
        }
    });

    const fpEnd = flatpickr("#fpEnd", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        defaultDate: endVal || null,
        position: "below",
        disableMobile: true,
        onChange(selectedDates, dateStr) {
          endVal = dateStr;
          $('input[name="scheduled_end_at"][type="hidden"]').val(dateStr);

          $('input[type="datetime-local"][name="scheduled_end_at"]').val(dateStr.replace(' ', 'T'));
          updateLabel();
        }
    });

    function updateLabel() {
        if (startVal && endVal) {
            $('#selectDateBtn').text(`Start Date: ${startVal} & End Date: ${endVal}`);
        }
    }

    // INITIAL LOAD
    updateLabel();

    // Helper to get province from city mapping
    const cityProvinceMap = @json(
        collect($cities)->mapWithKeys(function($city) use ($regions) {
            // Try to find matching region
            $region = $regions->first(function($r) use ($city) {
                return $r->name === $city;
            });
            return [$city => $region ? $region->province->name : ''];
        })
    );

    // Helper functions for number formatting
    function parseNumber(val) {
        if (!val) return 0;
        return parseFloat(val.toString().replace(/\./g, '').replace(',', '.')) || 0;
    }

    function formatNumber(val) {
        return new Intl.NumberFormat('id-ID').format(val);
    }

    // Render the four price tiers as a neat inline list
    function renderPriceTiers(gov, corp, pers, fob) {
        return `
            <ul class="list-inline mb-0 small text-muted">
              <li class="list-inline-item me-3">
                <span class="fw-semibold">Gov:</span> Rp${formatNumber(gov)}
              </li>
              <li class="list-inline-item me-3">
                <span class="fw-semibold">Corp:</span> Rp${formatNumber(corp)}
              </li>
              <li class="list-inline-item me-3">
                <span class="fw-semibold">Personal:</span> Rp${formatNumber(pers)}
              </li>
              <li class="list-inline-item me-3">
                <span class="fw-semibold">FOB:</span> Rp${formatNumber(fob)}
              </li>
            </ul>
        `;
    }

    // Update province when city changes in lead details table
    function updateLeadProvince($citySelect) {
        const city = $citySelect.val();
        const $row = $citySelect.closest('tr');
        const $provinceInput = $row.find('.lead-province');
        
        if (city && cityProvinceMap[city]) {
            $provinceInput.val(cityProvinceMap[city]);
        } else {
            $provinceInput.val('');
        }
    }

    // Update province when offline meeting city changes
    function updateMeetingProvince() {
        const city = $('#city').val();
        const $provinceInput = $('#province');
        
        if (city && cityProvinceMap[city]) {
            $provinceInput.val(cityProvinceMap[city]);
        } else {
            $provinceInput.val('');
        }
    }

    // Initialize province for existing lead cities
    $('#leads-table tbody tr').each(function() {
        updateLeadProvince($(this).find('.lead-city'));
    });

    // Initialize province for meeting city if exists
    if ($('#city').val()) {
        updateMeetingProvince();
    }

    // Lead city change handler
    $(document).on('change', '.lead-city', function() {
        updateLeadProvince($(this));
    });

    // Meeting city change handler
    $('#city').on('change', updateMeetingProvince);

    // Lead product change handler
    $(document).on('change', '.lead-product', function() {
        let row = $(this).closest('tr');
        let opt = $(this).find('option:selected');
        let price = opt.data('price') || 0;
        
        row.find('.lead-price').val(formatNumber(price));
        
        // Update price tiers info
        row.find('.segment-price-info').html(
            renderPriceTiers(
                opt.data('gov')  || 0,
                opt.data('corp') || 0,
                opt.data('pers') || 0,
                opt.data('fob')  || 0
            )
        );
    });

    // Format number inputs
    $(document).on('keyup', '.number-input', function() {
        $(this).val(formatNumber(parseNumber($(this).val())));
    });

    // Add lead row
    $('#add-lead').on('click', function() {
        $('select.select2').select2('destroy');
        let newRow = $('#leads-table tbody tr:first').clone();
        
        newRow.find('input').val('');
        newRow.find('select').val('').prop('selectedIndex', 0);
        newRow.find('select[name="lead_type[]"]').val('office');
        newRow.find('.segment-price-info').html('');
        
        $('#leads-table tbody').append(newRow);
        $('.select2').select2({ width: '100%' });
    });

    // Remove lead row
    $(document).on('click', '.remove-lead', function() {
        if ($('#leads-table tbody tr').length > 1) {
            $(this).closest('tr').remove();
        }
    });

    function toggleMeetingTypeSections() {
        const selectedName = $('#meeting_type_id option:selected').data('name');

        console.log('selected:', selectedName);
        console.log('zoomName:', zoomName);

        const isOnline = onlineNames.includes(selectedName);
        const requiresUrl = selectedName === zoomName;
        const isExpo = selectedName === expoName;

        // Remove EXPO styling if switching away from EXPO
        if (!isExpo) {
            $('#city, #province, #address, #scheduled_start_at, #scheduled_end_at, #expense-table input, #expense-table select')
                .removeClass('expo-auto-filled');
            $('#expo-info-alert').remove();
        }

        // Handle EXPO type - auto-fill fields
        if (isExpo) {
            autoFillExpoFields();
        }

        if (isOnline) {
            $('#offline-section').hide();
            $('#expense-section').hide();
            $('#offline-section input, #offline-section textarea, #offline-section select').val('');
            $('#expense-section input, #expense-section textarea, #expense-section select').val('');
            $('#offline-section .select2').val(null).trigger('change');
        } else {
            $('#offline-section').show();
        }

        if (requiresUrl) {
            $('#online-url-section').show();
        } else {
            $('#online-url-section').hide();
            $('#meeting_url').val('');
        }
    }

    function autoFillExpoFields() {
        // Show EXPO info message
        if (!$('#expo-info-alert').length) {
            $('#meeting_type_id').closest('.mb-3').after(
                '<div id="expo-info-alert" class="expo-info">' +
                '<strong>EXPO Meeting Detected:</strong> Fields will be auto-filled for quick setup.' +
                '</div>'
            );
        }

        // Set city to Jakarta Pusat and update province automatically
        $('#city').val('Kota Administrasi Jakarta Pusat').trigger('change');
        updateMeetingProvince();
        
        // Set address
        $('#address').val('Jiexpo Kemayoran Jakarta Indonesia Jakarta, Pademangan Tim., Kec. Pademangan, Jkt Utara, Daerah Khusus Ibukota Jakarta 10620');
        
        // Set expenses to 0 for all rows
        $('#expense-table tbody tr').each(function() {
            $(this).find('input[name="expense_amount[]"]').val('0');
            $(this).find('input[name="expense_notes[]"]').val('EXPO Meeting');
        });
        
        // Set start time to now
        const now = new Date();
        const startTime = formatDateTimeLocal(now); // returns T-format
        // hidden inputs expect space-separated format
        $('input[name="scheduled_start_at"][type="hidden"]').val(startTime.replace('T', ' '));
        $('input[type="datetime-local"][name="scheduled_start_at"]').val(startTime);
        
        // Set end time to now + 1 minute
        const endTime = new Date(now.getTime() + 60000); // 1 minute in milliseconds
        const endTimeStr = formatDateTimeLocal(endTime);
        $('input[name="scheduled_end_at"][type="hidden"]').val(endTimeStr.replace('T', ' '));
        $('input[type="datetime-local"][name="scheduled_end_at"]').val(endTimeStr);
        
        // Hide online URL section
        $('#online-url-section').hide();
        $('#meeting_url').val('');
        
        // Add visual styling to auto-filled fields
        $('#city, #province, #address, #scheduled_start_at, #scheduled_end_at, #expense-table input, #expense-table select')
            .addClass('expo-auto-filled');
    }

    function formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    $('#meeting_type_id').on('change', toggleMeetingTypeSections);
    
    // Run on page load if EXPO is already selected
    toggleMeetingTypeSections();

    $('#add-expense').on('click', function () {
        const row = $('#expense-table tbody tr:first').clone();
        row.find('input').val('');
        row.find('select').val($('#expense-table tbody tr:first select').val());
        
        // If EXPO is selected, set default values for new expense row
        const selectedName = $('#meeting_type_id option:selected').data('name');
        if (selectedName === expoName) {
            row.find('input[name="expense_amount[]"]').val('0');
            row.find('input[name="expense_notes[]"]').val('EXPO Meeting');
            row.find('input, select').addClass('expo-auto-filled');
        }
        
        $('#expense-table tbody').append(row);
    });

    $(document).on('click', '.remove-expense', function () {
        if ($('#expense-table tbody tr').length > 1) {
            $(this).closest('tr').remove();
        }
    });

    // Cancel meeting
    $('#btnCancelMeeting').on('click', function () {
        const url = $(this).data('url');      
        const isOnline = $(this).data('online') === 1 || $(this).data('online') === '1';
        const status_rejected = $(this).data('status') === 'rejected';
        const text = isOnline || status_rejected ? 'Are you sure you want to cancel this meeting?' : 'Please return the expense to finance before cancelling. Have you returned it?';

        Swal.fire({
            title: 'Cancel Meeting',
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(url, {_token: '{{ csrf_token() }}'}, function (res) {
                    notif(res.message || 'Meeting canceled');
                    window.location.href = '{{ route('leads.my') }}';
                }).fail(function (xhr) {
                    let err = 'Failed to cancel meeting';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        err = xhr.responseJSON.message;
                    }
                    notif(err, 'error');
                });
            }
        });
    });

    // Prevent manual editing of EXPO fields (optional)
    $(document).on('input change', '.expo-auto-filled', function() {
        const selectedName = $('#meeting_type_id option:selected').data('name');
        if (selectedName === expoName) {
            // Re-apply EXPO values if user tries to change them
            setTimeout(() => {
                if ($(this).is('#city')) {
                    $(this).val('Kota Administrasi Jakarta Pusat').trigger('change');
                    updateMeetingProvince();
                } else if ($(this).is('#province')) {
                    updateMeetingProvince();
                } else if ($(this).is('#address')) {
                    $(this).val('Jiexpo Kemayoran Jakarta Indonesia Jakarta, Pademangan Tim., Kec. Pademangan, Jkt Utara, Daerah Khusus Ibukota Jakarta 10620');
                } else if ($(this).is('[name="expense_amount[]"]')) {
                    $(this).val('0');
                } else if ($(this).is('[name="expense_notes[]"]')) {
                    $(this).val('EXPO Meeting');
                } else if ($(this).is('#scheduled_start_at') || $(this).is('#scheduled_end_at')) {
                    const now = new Date();
                    if ($(this).is('#scheduled_start_at')) {
                        $(this).val(formatDateTimeLocal(now));
                    } else {
                        $(this).val(formatDateTimeLocal(new Date(now.getTime() + 60000)));
                    }
                }
            }, 100);
        }
    });
  });
</script>
@endsection