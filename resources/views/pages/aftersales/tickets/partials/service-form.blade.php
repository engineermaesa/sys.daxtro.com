{{-- SERVICE & MACHINE INSPECTION FORM (partial; di-include dari tickets/index.blade.php, diisi oleh documents-script dari GET /api/aftersales/tickets/{id}/service-form) --}}
@php
    $fieldClass = 'w-full border border-[#D9D9D9] rounded-md focus:outline-none focus:border-[#115640] bg-white';
    $fieldStyle = 'padding: 10px 12px;';
    $readonlyStyle = 'padding: 10px 12px; background: #F3F4F6;';
    $labelStyle = 'margin-bottom: 8px;';
@endphp

<div id="ticketServiceFormView" class="hidden">
    <div class="pt-4">
        <p class="text-[#757575] flex items-center gap-2">
            <button type="button" class="btn-sf-back cursor-pointer">Ticket Detail</button>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-[#115640] font-semibold underline">Service &amp; Machine Inspection Form</span>
        </p>

        <div class="flex items-start justify-between" style="margin-top: 16px; gap: 12px;">
            <div>
                <h1 class="text-[#115640] font-bold text-2xl">Service &amp; Machine Inspection Form</h1>
                <p id="sfSubtitle" class="text-[#757575]" style="margin-top: 4px;"></p>
            </div>
            <div class="flex items-center flex-wrap justify-end" style="gap: 10px;">
                @include('pages.aftersales.tickets.partials.language-switcher', ['doc' => 'service_form'])
                <span id="sfStatusBadge" class="rounded-full bg-[#FFF3CD] text-[#D97706] font-semibold text-xs shrink-0" style="padding: 4px 12px;">Draft</span>
            </div>
        </div>

        <div id="sfLangNotice" class="hidden rounded-md bg-[#FFF3CD] text-[#8A6D00] text-xs" style="margin-top: 12px; padding: 10px 14px;" data-no-i18n></div>

        <div id="sfLoading" class="text-[#757575]" style="margin-top: 20px;">Loading...</div>

        <div id="sfBody" class="hidden">
            {{-- FORM INFORMATION --}}
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
                <h2 class="font-bold tracking-wide">FORM INFORMATION</h2>
                <hr class="border-[#EDEDED]" style="margin: 12px 0 20px;">
                <div class="grid grid-cols-1 lg:grid-cols-4" style="gap: 20px;">
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Form Number</label>
                        <input type="text" id="sfFormNumber" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Service Date</label>
                        <input type="text" id="sfServiceDate" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Technician</label>
                        <input type="text" id="sfTechnician" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Supervisor</label>
                        <input type="text" id="sfSupervisor" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                </div>
            </div>

            {{-- 1. CUSTOMER INFORMATION & JOB CATEGORY --}}
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
                <h2 class="font-bold tracking-wide">1. CUSTOMER INFORMATION &amp; JOB CATEGORY</h2>
                <hr class="border-[#EDEDED]" style="margin: 12px 0 20px;">
                <div class="grid grid-cols-1 lg:grid-cols-3" style="gap: 20px;">
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Customer Name</label>
                        <input type="text" id="sfCustomer" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">PIC</label>
                        <input type="text" id="sfPic" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Phone</label>
                        <input type="text" id="sfPhone" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block font-medium" style="{{ $labelStyle }}">Address</label>
                        <input type="text" id="sfAddress" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Service Type <span class="text-[#EC221F]">*</span></label>
                        <select id="sfServiceType" class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                            <option value="">Select service type</option>
                            <option value="preventive">Preventive</option>
                            <option value="repair">Repair</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 2. MACHINE SPECIFICATION & IDENTITY --}}
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
                <h2 class="font-bold tracking-wide">2. DAXTRO ICE MACHINE SPECIFICATION &amp; IDENTITY</h2>
                <hr class="border-[#EDEDED]" style="margin: 12px 0 20px;">
                <div class="grid grid-cols-1 lg:grid-cols-4" style="gap: 20px;">
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Machine Type</label>
                        <input type="text" id="sfMachineType" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">Serial Number</label>
                        <input type="text" id="sfSerialNumber" readonly class="{{ $fieldClass }}" style="{{ $readonlyStyle }}">
                    </div>
                    @foreach ([
                        'capacity' => 'Capacity', 'refrigerant' => 'Refrigerant Type', 'condenser_type' => 'Condenser Type',
                        'voltage' => 'Voltage (V)', 'phase' => 'Phase', 'hz' => 'Frequency (Hz)',
                    ] as $key => $label)
                        <div>
                            <label class="block font-medium" style="{{ $labelStyle }}">{{ $label }} <span class="text-[#EC221F]">*</span></label>
                            <input type="text" data-unit="{{ $key }}" class="sf-unit {{ $fieldClass }}" style="{{ $fieldStyle }}">
                        </div>
                    @endforeach
                </div>
                <div class="rounded-md bg-[#EEF4FF] text-[#2F5BD3] text-xs" style="margin-top: 20px; padding: 10px 14px;">
                    Fill machine identity data according to the nameplate. It is remembered for this machine on the next ticket.
                </div>
            </div>

            {{-- 3. INSPECTION SHEET --}}
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; overflow: hidden;">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-3 px-4 sm:px-6 py-4 sm:py-[18px]">
                    <h2 class="font-bold tracking-wide">3. INSPECTION SHEET &amp; DETAILED TECHNICAL CHECK</h2>
                    <span id="sfItemCount" class="text-[#9CA3AF] text-xs sm:whitespace-nowrap"></span>
                </div>
                <div id="sfTabs" class="flex flex-wrap border-t border-[#EDEDED]" style="padding: 12px; gap: 8px;"></div>
                <div id="sfPanels"></div>
            </div>

            {{-- SIGNATURES --}}
            <div id="sfDiagnosis" style="margin-top:20px;"></div>
            <div id="sfSignatures" class="grid grid-cols-1 lg:grid-cols-3" style="margin-top: 20px; gap: 16px;"></div>

            {{-- COMPLETENESS --}}
            <div id="sfCompleteness" class="rounded-md text-xs" style="margin-top: 16px; padding: 10px 14px;"></div>

            <div class="flex items-center justify-between flex-wrap" style="margin: 20px 0 24px; gap: 12px;">
                <button type="button" class="btn-sf-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                    <i class="bi bi-chevron-left text-[10px]"></i> Back
                </button>
                <div class="flex items-center" style="gap: 10px;">
                    <button type="button" id="sfSaveDraft" class="bg-white border border-[#115640] text-[#115640] font-semibold rounded-lg cursor-pointer" style="padding: 8px 18px;">Save Draft</button>
                    <button type="button" id="sfSaveForm" class="bg-[#115640] text-white font-semibold rounded-lg cursor-pointer" style="padding: 8px 18px;">Save Service Form</button>
                </div>
            </div>
        </div>
    </div>
</div>
