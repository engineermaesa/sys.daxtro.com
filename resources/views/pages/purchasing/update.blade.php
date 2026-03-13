@extends('layouts.app')

@section('content')
    <section class="min-h-screen text-[#1E1E1E]">
        <div class="flex items-center gap-2 text-[#115640] pt-4">
            <x-icon.production/>
            <h1 class="font-semibold lg:text-2xl text-lg">Purchasing Log</h1>
        </div>
        <div class="flex items-center mt-2 gap-3">
            <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">Purchasing Log</a>
            <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
            <a href="{{ route('purchasing.update', $purchasing->id) }}" class="text-[#083224] underline">
                Update Purchasing
            </a>
        </div>
        
        @php
            $stageOptions = [
                'Invoice Received' => 'Invoice Received',
                'Vendor Processing' => 'Vendor Processing',
                'Ready for Handover' => 'Ready for Handover',
                'Completed' => 'Completed',
                'Pending' => 'Pending',
                'Cancel' => 'Cancel',
            ];

            $statusOptions = [
                1 => '1. Waiting',
                2 => '2. Accepted',
                3 => '3. On Progress Production',
                4 => '4. 50% Production',
                5 => '5. 70% Production',
                6 => '6. 100% Production',
                7 => '7. Running Test',
                8 => '8. Machine Completed',
                9 => '9. Document Registration',
                10 => '10. Waiting to Deliver',
                11 => '11. On Delivery to Indonesia',
                12 => '12. Arrived in Indonesia',
                13 => '13. Delivery to Customer',
                14 => '14. On Progress Install',
                15 => '15. Running Test Final',
                16 => '16. BAST',
                17 => '17. Completed',
                18 => '18. Pending',
                19 => '19. Cancel',
            ];

            $statusStageMap = [
                1 => 'Invoice Received',
                2 => 'Invoice Received',
                3 => 'Invoice Received',
                4 => 'Vendor Processing',
                5 => 'Vendor Processing',
                6 => 'Vendor Processing',
                7 => 'Vendor Processing',
                8 => 'Vendor Processing',
                9 => 'Vendor Processing',
                10 => 'Vendor Processing',
                11 => 'Vendor Processing',
                12 => 'Vendor Processing',
                13 => 'Vendor Processing',
                14 => 'Vendor Processing',
                15 => 'Vendor Processing',
                16 => 'Ready for Handover',
                17 => 'Completed',
                18 => 'Pending',
                19 => 'Cancel',
            ];

            $selectedStage = old('stage', $purchasing->stage ?? '');

            $statusCodeMap = [
                'Waiting' => 1,
                'Accepted' => 2,
                'On Progress Production' => 3,
                '50% Production' => 4,
                '70% Production' => 5,
                '100% Production' => 6,
                'Running Test' => 7,
                'Machine Completed' => 8,
                'Document Registration' => 9,
                'Waiting to Deliver' => 10,
                'On Delivery to Indonesia' => 11,
                'Arrived in Indonesia' => 12,
                'Delivery to Customer' => 13,
                'On Progress Install' => 14,
                'Running Test Final' => 15,
                'BAST' => 16,
                'Completed' => 17,
                'Pending' => 18,
                'Cancel' => 19,
            ];

            $selectedStatusCode = old(
                'status_code',
                $statusCodeMap[$purchasing->status ?? ''] ?? ''
            );
        @endphp

        <form id="form" method="POST" 
            action="{{ route('purchasing.save', $purchasing->id) }}"
            enctype="multipart/form-data">
            @csrf

            <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
                <h1 class="font-semibold text-[#1E1E1E] uppercase w-full p-2 lg:p-3 border-b-[#D9D9D9] border-b">
                    Update Purchasing
                </h1>

                {{-- STAGE N STATUS --}}
                <div class="p-3 grid grid-cols-1 gap-3">

                    {{-- STAGE --}}
                    <div>
                        <label for="stage_display" class="block mb-2 text-sm font-medium text-[#1E1E1E]">
                            Stage <span class="text-[#EC221F]">*</span>
                        </label>

                        <input
                            type="text"
                            id="stage_display"
                            value="{{ $selectedStage }}"
                            class="w-full rounded-lg border border-[#D9D9D9] px-3 py-2 bg-[#F5F5F5] text-[#1E1E1E] cursor-not-allowed focus:outline-none focus:ring-0"
                            disabled
                        >

                        <input
                                type="hidden"
                                name="stage"
                                id="stage_hidden"
                                value="{{ old('stage', $purchasing->stage ?? '') }}"
                            >

                        @error('stage')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- STATUS --}}
                    <div>
                        <label for="status" class="block mb-2 text-sm font-medium text-[#1E1E1E]">
                            Status <span class="text-[#EC221F]">*</span>
                        </label>
                        <select name="status_code" id="status"
                            class="w-full rounded-lg border border-[#D9D9D9] px-3 py-2 focus:outline-none focus:ring-0" required>
                            <option value="">Select Status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ (string) $selectedStatusCode === (string) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Attachment --}}
                <div id="fileWrapper" class="p-3">
                    <label for="attachment" class="block mb-2 text-sm font-medium text-[#1E1E1E]">
                        File <span id="fileRequiredMark" class="text-[#EC221F]">*</span>
                    </label>

                    <input type="file"
                        name="file"
                        id="attachment"
                        class="w-full rounded-lg border border-[#D9D9D9] px-3 py-2 focus:outline-none focus:ring-0">

                    @error('file')
                        <small class="text-red-500">{{ $message }}</small>
                    @enderror

                    @php
                        $downloadUrl = $purchasing ? route('purchasing.download', $purchasing->id) : null;
                    @endphp
                    
                    @if (!empty($purchasing->files))
                        <div class="mt-3">
                            <p>Old File</p>
                            <a href="{{ e($downloadUrl) }}" class="inline-flex items-center gap-2 border border-[#D9D9D9] rounded-lg p-3">
                                <x-icon.download/>
                                Download
                            </a>

                        </div>
                    @endif
                    
                </div>

                {{-- Notes --}}
                <div id="notesWrapper" class="p-3">
                    <label for="notesField" class="block mb-2 text-sm font-medium text-[#1E1E1E]">
                        Notes <span id="notesRequiredMark" class="text-[#EC221F] hidden">*</span>
                    </label>

                    <textarea
                        id="notesField"
                        name="notes"
                        rows="5"
                        class="w-full rounded-lg border border-[#D9D9D9] px-3 py-2 focus:outline-none focus:ring-0">{{ old('notes', $purchasing->notes ?? '') }}</textarea>

                    @error('notes')
                        <small class="text-red-500">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-3 flex justify-end">
                    <button
                        id="btnSubmit"
                        type="submit"
                        class="bg-[#083224] cursor-pointer text-white px-4 py-2 rounded-lg hover:opacity-90">
                        Save
                    </button>
                </div>
            </div>
        </form>
    </section>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        const statusStageMap = @json($statusStageMap);

        function updateFormByStatus() {
            const selectedStatus = parseInt($('#status').val(), 10);
            const stage = statusStageMap[selectedStatus] || '';

            // set stage ke input hidden + display
            $('#stage_hidden').val(stage);
            $('#stage_display').val(stage);

            if (selectedStatus >= 1 && selectedStatus <= 17) {
                $('#fileWrapper').removeClass('hidden');
                $('#notesWrapper').removeClass('hidden');

                $('#attachment').prop('required', true);
                $('#notesField').prop('required', false);

                $('#fileRequiredMark').removeClass('hidden');
                $('#notesRequiredMark').addClass('hidden');
            } else if (selectedStatus === 18 || selectedStatus === 19) {
                $('#fileWrapper').addClass('hidden');
                $('#notesWrapper').removeClass('hidden');

                $('#attachment').prop('required', false).val('');
                $('#notesField').prop('required', true);

                $('#fileRequiredMark').addClass('hidden');
                $('#notesRequiredMark').removeClass('hidden');
            } else {
                $('#stage_hidden').val('');
                $('#stage_display').val('');

                $('#fileWrapper').removeClass('hidden');
                $('#notesWrapper').removeClass('hidden');

                $('#attachment').prop('required', false);
                $('#notesField').prop('required', false);

                $('#fileRequiredMark').addClass('hidden');
                $('#notesRequiredMark').addClass('hidden');
            }
        }

        $('#status').on('change', function () {
            updateFormByStatus();
        });

        updateFormByStatus();
    });
</script>
@endsection