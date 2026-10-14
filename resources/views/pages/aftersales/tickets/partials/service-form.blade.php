{{-- SERVICE & MACHINE INSPECTION FORM (partial; di-include dari tickets/index.blade.php, frontend only) --}}
@php
    // TODO: DUMMY DATA — item inspeksi akan datang dari backend.
    $std = 'According to manufacturer specification';
    $stable = 'Stable and within operating range';

    $inspectionTabs = [
        'A' => ['Electrical & Automation', [
            ['Machine body condition', $std, 'Normal condition', 'OK', 1],
            ['Condenser cleanliness', $stable, 'Reading recorded', 'OK', 2],
            ['Evaporator cleanliness', $std, 'Normal condition', 'OK', 0],
            ['Compressor vibration', $stable, 'Not applicable', 'N/A', 1],
            ['Compressor current', $std, 'Not entered', 'Empty', 2],
            ['Compressor pressure', $stable, 'Reading recorded', 'Rep', 0],
            ['Refrigerant level', $std, 'Normal condition', 'OK', 1],
        ]],
        'B' => ['Refrigeration', [
            ['Refrigerant pressure (high side)', $stable, 'Reading recorded', 'OK', 1],
            ['Refrigerant pressure (low side)', $stable, 'Reading recorded', 'OK', 0],
            ['Expansion valve condition', $std, 'Normal condition', 'OK', 1],
            ['Filter drier condition', $std, 'Not entered', 'Empty', 0],
            ['Pipe insulation', $std, 'Normal condition', 'OK', 2],
            ['Leak detection', $std, 'Minor leak found', 'Rep', 1],
            ['Evaporator temperature', $stable, 'Reading recorded', 'OK', 0],
        ]],
        'C' => ['Water System', [
            ['Water inlet pressure', $stable, 'Reading recorded', 'OK', 1],
            ['Water filter condition', $std, 'Normal condition', 'OK', 0],
            ['Water pump operation', $stable, 'Reading recorded', 'OK', 1],
            ['Water distributor', $std, 'Not applicable', 'N/A', 0],
            ['Drain line', $std, 'Normal condition', 'OK', 0],
            ['Water quality', $std, 'Not entered', 'Empty', 0],
            ['Float valve', $std, 'Normal condition', 'OK', 1],
        ]],
        'D' => ['Mechanical', [
            ['Gear motor condition', $std, 'Normal condition', 'OK', 1],
            ['Auger / evaporator drum', $std, 'Normal condition', 'OK', 0],
            ['Bearing condition', $stable, 'Abnormal noise', 'Rep', 2],
            ['Belt / coupling', $std, 'Normal condition', 'OK', 0],
            ['Frame & mounting', $std, 'Normal condition', 'OK', 1],
            ['Lubrication', $std, 'Not entered', 'Empty', 0],
            ['Fan motor', $stable, 'Reading recorded', 'OK', 0],
        ]],
        'E' => ['Final Test', [
            ['Ice production test', $stable, 'Reading recorded', 'OK', 2],
            ['Ice quality check', $std, 'Normal condition', 'OK', 1],
            ['Safety device test', $std, 'Normal condition', 'OK', 0],
            ['Noise level check', $stable, 'Not applicable', 'N/A', 0],
            ['Cleaning & housekeeping', $std, 'Normal condition', 'OK', 1],
            ['Customer handover', $std, 'Not entered', 'Empty', 0],
        ]],
    ];

    $totalItems = collect($inspectionTabs)->sum(fn ($tab) => count($tab[1]));

    $statusBadge = [
        'OK' => 'background:#E7F3EE;color:#115640;',
        'N/A' => 'background:#F0F0F0;color:#757575;',
        'Empty' => 'background:#F0F0F0;color:#4B5563;',
        'Rep' => 'background:#FBEAEA;color:#EC221F;',
    ];

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
            <span class="rounded-full bg-[#FFF3CD] text-[#D97706] font-semibold text-xs shrink-0" style="padding: 4px 12px;">Draft</span>
        </div>

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
                    <label class="block font-medium" style="{{ $labelStyle }}">Date</label>
                    <input type="date" class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                </div>
                <div>
                    <label class="block font-medium" style="{{ $labelStyle }}">Working Hours — Start</label>
                    <input type="time" class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                </div>
                <div>
                    <label class="block font-medium" style="{{ $labelStyle }}">Working Hours — Finish</label>
                    <input type="time" class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
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
                    <input type="text" id="sfPic" class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                </div>
                <div>
                    <label class="block font-medium" style="{{ $labelStyle }}">Phone</label>
                    <input type="text" id="sfPhone" class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                </div>
                <div>
                    <label class="block font-medium" style="{{ $labelStyle }}">Address</label>
                    <input type="text" id="sfAddress" class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                </div>
                <div>
                    <label class="block font-medium" style="{{ $labelStyle }}">Service Type</label>
                    <select class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                        @foreach (['Corrective Maintenance', 'Preventive Maintenance', 'Installation', 'Inspection'] as $opt)
                            <option>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium" style="{{ $labelStyle }}">Warranty Status</label>
                    <select class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                        @foreach (['Active', 'Expired', 'No Warranty'] as $opt)
                            <option>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. MACHINE SPECIFICATION & IDENTITY --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <h2 class="font-bold tracking-wide">2. DAXTRO ICE MACHINE SPECIFICATION &amp; IDENTITY</h2>
            <hr class="border-[#EDEDED]" style="margin: 12px 0 20px;">
            <div class="grid grid-cols-1 lg:grid-cols-4" style="gap: 20px;">
                @foreach (['Machine Type', 'Unit Model', 'Serial Number', 'Capacity', 'Refrigerant Type', 'Compressor Model', 'Electrical Supply', 'Installation Year'] as $label)
                    <div>
                        <label class="block font-medium" style="{{ $labelStyle }}">{{ $label }}</label>
                        <input type="text" @if ($label === 'Machine Type') id="sfMachineType" @endif class="{{ $fieldClass }}" style="{{ $fieldStyle }}">
                    </div>
                @endforeach
            </div>
            <div class="rounded-md bg-[#EEF4FF] text-[#2F5BD3] text-xs" style="margin-top: 20px; padding: 10px 14px;">
                Fill machine identity data according to the nameplate before starting the detailed inspection.
            </div>
        </div>

        {{-- 3. INSPECTION SHEET --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; overflow: hidden;">
            <div class="flex items-center justify-between" style="padding: 18px 24px;">
                <h2 class="font-bold tracking-wide">3. INSPECTION SHEET &amp; DETAILED TECHNICAL CHECK</h2>
                <span class="text-[#9CA3AF] text-xs">{{ $totalItems }} inspection items</span>
            </div>

            <div class="flex flex-wrap border-t border-[#EDEDED]" style="padding: 12px; gap: 8px;">
                @foreach ($inspectionTabs as $key => $tab)
                    <button type="button" data-tab="{{ $key }}"
                        class="sf-tab rounded-md border cursor-pointer font-semibold {{ $loop->first ? 'bg-[#115640] text-white border-[#115640]' : 'bg-white text-[#4B5563] border-[#D9D9D9]' }}"
                        style="padding: 8px 14px;">{{ $key }}. {{ $tab[0] }}</button>
                @endforeach
            </div>

            @foreach ($inspectionTabs as $key => $tab)
                <div class="sf-panel overflow-x-auto {{ $loop->first ? '' : 'hidden' }}" data-tab="{{ $key }}">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="text-left border-y border-[#EDEDED] text-[#1E1E1E]" style="background: #FAFAFA;">
                                <th style="padding: 12px 16px;">No</th>
                                <th style="padding: 12px 16px;">Inspection Item &amp; Component</th>
                                <th style="padding: 12px 16px;">Parameter / Reference Standard</th>
                                <th style="padding: 12px 16px;">Measurement Result / Condition</th>
                                <th style="padding: 12px 16px;">Status</th>
                                <th style="padding: 12px 16px;">Photo Evidence</th>
                                <th style="padding: 12px 16px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tab[1] as [$item, $param, $result, $status, $photos])
                                <tr class="border-b border-[#EDEDED] last:border-b-0">
                                    <td class="text-[#9CA3AF]" style="padding: 14px 16px;">{{ $loop->iteration }}</td>
                                    <td class="font-semibold" style="padding: 14px 16px;">{{ $item }}</td>
                                    <td class="text-[#757575]" style="padding: 14px 16px;">{{ $param }}</td>
                                    <td class="text-[#757575]" style="padding: 14px 16px;">{{ $result }}</td>
                                    <td style="padding: 14px 16px;">
                                        <span class="rounded-full font-semibold text-xs" style="padding: 3px 12px; {{ $statusBadge[$status] }}">{{ $status }}</span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <div class="flex items-center" style="gap: 8px;">
                                            <span class="rounded-md font-semibold text-center" style="width: 32px; padding: 6px 0; {{ $photos > 0 ? 'background:#E7F3EE;color:#115640;' : 'background:#F0F0F0;color:#9CA3AF;' }}">{{ $photos }}</span>
                                            <button type="button" class="sf-photo bg-white border border-[#D9D9D9] rounded-md text-xs cursor-pointer" style="padding: 6px 10px;">
                                                {{ $photos > 0 ? 'Add photo' : 'Take photo' }}
                                            </button>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <button type="button" class="sf-open text-[#115640] font-semibold cursor-pointer">Open &gt;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        {{-- SIGNATURES (TODO: tanda tangan digital menunggu backend) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3" style="margin-top: 20px; gap: 16px;">
            @foreach ([['Serviced by', 'sfSignTechnician'], ['Checked by', 'sfSignSupervisor'], ['Approved by', 'sfSignCustomer']] as [$label, $id])
                <div class="bg-white rounded-lg border border-[#D9D9D9]" style="overflow: hidden;">
                    <p class="uppercase font-semibold border-b border-[#EDEDED]" style="font-size: 11px; padding: 12px 14px; background: #FAFAFA;">{{ $label }}</p>
                    <div class="flex items-center justify-center text-[#B0B0B0]" style="height: 110px;">Signature area</div>
                    <p id="{{ $id }}" class="border-t border-[#EDEDED] text-xs" style="padding: 10px 14px;"></p>
                </div>
            @endforeach
        </div>

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
