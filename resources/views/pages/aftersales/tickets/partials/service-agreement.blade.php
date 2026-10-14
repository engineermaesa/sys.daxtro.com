{{-- SERVICE / REPAIR WORK AGREEMENT SHEET (partial; di-include dari tickets/index.blade.php, frontend only) --}}
@php
    $agreementSections = [
        'Scope of Work' => [
            'Daxtro technicians only carry out work in accordance with the agreed Work Order, Work Instruction, or Purchase Order.',
            'Work outside the agreed scope will only be carried out after written approval from the Customer.',
            'If additional damage is found, the Technician may pause work and submit an additional quotation.',
        ],
        'Site Access' => [
            'The Customer must provide safe working access to the Technician.',
            'The work area must be free from hazards that could endanger the Technician.',
            'Required access permits must be prepared before the scheduled visit.',
        ],
        'Work Safety' => [
            'All parties must comply with site safety procedures.',
            'Technicians will use the required personal protective equipment.',
            'Unsafe work may be suspended until the hazard is resolved.',
        ],
        'Machine Shutdown' => [
            'The Customer authorizes machine shutdown when required for inspection.',
            'Production impact caused by an approved shutdown is acknowledged.',
            'Restart will only occur after the Technician confirms safe conditions.',
        ],
        'Sparepart Approval' => [
            'Replacement parts require Customer or Supervisor approval.',
            'Part availability may affect the completion schedule.',
            'Removed parts will be documented and returned when requested.',
        ],
        'Additional Findings' => [
            'New technical findings will be reported before further work.',
            'Additional costs require approval before execution.',
            'Critical safety findings may require immediate isolation.',
        ],
        'Working Area' => [
            'The service area must remain clear and accessible.',
            'Customer personnel must keep a safe distance during work.',
            'Tools and components will be kept within the designated area.',
        ],
        'Utilities' => [
            'Electricity, water, and drainage required for testing are provided by the Customer.',
            'Utility quality must meet the machine specification.',
            'Utility interruptions may extend the service duration.',
        ],
        'Data Recording' => [
            'Technical readings may be recorded for service documentation.',
            'Machine photos may be attached to the service report.',
            'Recorded data is used only for service and quality purposes.',
        ],
        'Testing & Commissioning' => [
            'Operational testing is performed after repair.',
            'Customer representatives may witness the final test.',
            'Test results will be recorded in the Service Form.',
        ],
        'Warranty Terms' => [
            'Warranty follows the approved repair scope.',
            'Consumables and external causes are excluded unless stated otherwise.',
            'Warranty claims require the related service document.',
        ],
        'Service Limitations' => [
            'External site conditions are excluded from repair responsibility.',
            'Hidden damage may only be identified after disassembly.',
            'Temporary repairs will be clearly documented.',
        ],
        'Completion Review' => [
            'Results are reviewed by the Technician and Supervisor.',
            'Outstanding recommendations are listed before handover.',
            'The Customer may request clarification before signing.',
        ],
        'Customer Confirmation' => [
            'The Customer confirms the machine condition after service.',
            'Any remaining issue must be recorded before approval.',
            'The Customer receives a copy of the completed document.',
        ],
        'Document Approval' => [
            'Signatures confirm acceptance of the recorded work.',
            'Digital or handwritten signatures are considered valid.',
            'The approved document becomes part of the ticket history.',
        ],
    ];
@endphp

<div id="ticketAgreementView" class="hidden">
    <div class="pt-4">
        <p class="text-[#757575] flex items-center gap-2">
            <button type="button" class="btn-sa-back cursor-pointer">Ticket Detail</button>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-[#115640] font-semibold underline">Service / Repair Work Agreement Sheet</span>
        </p>

        <div class="flex items-start justify-between" style="margin-top: 16px; gap: 12px;">
            <div>
                <h1 class="text-[#115640] font-bold text-2xl">Service / Repair Work Agreement Sheet</h1>
                <p id="saSubtitle" class="text-[#757575]" style="margin-top: 4px;"></p>
            </div>
            <span class="rounded-full bg-[#FFF3CD] text-[#D97706] font-semibold text-xs shrink-0" style="padding: 4px 12px;">Draft</span>
        </div>

        {{-- CHECKLIST --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; overflow: hidden;">
            <label class="flex items-center cursor-pointer border-b border-[#EDEDED]" style="padding: 14px 16px; gap: 12px;">
                <input type="checkbox" id="saCheckAll" style="width: 18px; height: 18px;">
                <span class="font-semibold text-[#115640]">Check all (A–O)</span>
            </label>

            @foreach ($agreementSections as $title => $clauses)
                @php $letter = chr(ord('A') + $loop->index); @endphp
                <div class="sa-section border-b border-[#EDEDED] last:border-b-0">
                    <div class="flex items-center justify-between" style="padding: 14px 16px; background: #FAFAFA;">
                        <label class="flex items-center cursor-pointer" style="gap: 12px;">
                            <input type="checkbox" class="sa-section-check" style="width: 18px; height: 18px;">
                            <span class="font-bold">{{ $letter }}. {{ $title }}</span>
                        </label>
                        <span class="sa-count text-[#9CA3AF] text-xs">0 / {{ count($clauses) }} clauses</span>
                    </div>

                    <div style="padding: 8px 16px 12px 36px;">
                        @foreach ($clauses as $clause)
                            <label class="flex items-start cursor-pointer" style="gap: 12px; padding: 8px 0;">
                                <input type="checkbox" class="sa-clause" style="width: 16px; height: 16px; margin-top: 2px;">
                                <span class="text-[#4B5563]">{{ $loop->iteration }}. {{ $clause }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- SIGNATURES (TODO: tanda tangan digital menunggu backend) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3" style="margin-top: 20px; gap: 16px;">
            @foreach ([
                ['Serviced by / Technician', 'saSignTechnician'],
                ['Checked by / Supervisor', 'saSignSupervisor'],
                ['Approved by / Customer', 'saSignCustomer'],
            ] as [$label, $id])
                <div class="bg-white rounded-lg border border-[#D9D9D9]" style="overflow: hidden;">
                    <p class="uppercase font-semibold border-b border-[#EDEDED]" style="font-size: 11px; padding: 12px 14px; background: #FAFAFA;">{{ $label }}</p>
                    <div class="flex items-center justify-center text-[#B0B0B0]" style="height: 110px;">Signature area</div>
                    <p id="{{ $id }}" class="border-t border-[#EDEDED] text-xs" style="padding: 10px 14px;"></p>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between" style="margin: 20px 0 24px;">
            <button type="button" class="btn-sa-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                <i class="bi bi-chevron-left text-[10px]"></i> Back
            </button>
            <button type="button" id="saSave" class="bg-[#115640] text-white font-semibold rounded-lg cursor-pointer" style="padding: 8px 24px;">Save</button>
        </div>
    </div>
</div>
