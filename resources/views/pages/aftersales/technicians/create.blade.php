@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="mb-1">
            <h1 class="text-[#115640] font-bold text-2xl">Technician</h1>
            <p class="text-[#757575] text-sm mt-1">
                <a href="{{ route('aftersales.pages.technicians.index') }}" class="hover:underline">Technician</a>
                <span class="mx-1">&gt;</span>
                <span class="font-semibold text-[#1E1E1E] underline">Add Technician</span>
            </p>
        </div>

        <form id="technician-form" class="mt-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- IDENTITY --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Identity</h6>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Technician Name<span class="text-red-600">*</span></label>
                        <input type="text" id="field-name" placeholder="Full name" required
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Email<span class="text-red-600">*</span></label>
                        <input type="email" id="field-email" placeholder="name@company.com" required
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Password<span class="text-red-600">*</span></label>
                        <input type="password" id="field-password" placeholder="Minimum 6 characters" required minlength="6"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Level</label>
                        <select id="field-grade" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="Junior">Junior</option>
                            <option value="Mid">Mid</option>
                            <option value="Senior">Senior</option>
                        </select>
                    </div>
                </div>

                {{-- ADDITIONAL --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Additional</h6>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Area Coverage</label>
                        <select id="field-region" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select area...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Certification</label>
                        <input type="text" id="field-certification" placeholder="e.g. BNSP Refrigeration"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                </div>
            </div>

            {{-- SKILLS --}}
            <div class="bg-white rounded-lg border border-[#D9D9D9] p-5 mt-4">
                <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Skills</h6>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach ([
                        'refrigeration' => 'Refrigeration',
                        'electrical' => 'Electrical',
                        'plc_control' => 'PLC Control',
                        'water_treatment' => 'Water Treatment',
                        'cold_storage' => 'Cold Storage',
                    ] as $key => $label)
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $label }}</label>
                            <select id="field-skill-{{ $key }}" class="field-skill w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                                <option value="none" selected>None</option>
                                <option value="basic">Basic</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="expert">Expert</option>
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- NAVIGATION --}}
            <div class="flex items-center justify-between mt-4">
                <a href="{{ route('aftersales.pages.technicians.index') }}"
                    class="border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
                <button type="submit" id="btn-save-technician"
                    class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer">
                    <span id="btn-save-technician-label">Save Technician</span>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function loadRegionOptions() {
        $.ajax({
            url: '/api/regions/list',
            method: 'GET',
            success: function (response) {
                const regions = response.data || [];
                const options = regions.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
                $('#field-region').html('<option value="">Select area...</option>' + options);
            },
            error: function (xhr) {
                console.error('Failed to load regions', xhr.responseJSON?.message || xhr.statusText);
            }
        });
    }

    function collectSkills() {
        const skillMap = {
            'field-skill-refrigeration': 'Refrigeration',
            'field-skill-electrical': 'Electrical',
            'field-skill-plc_control': 'PLC Control',
            'field-skill-water_treatment': 'Water Treatment',
            'field-skill-cold_storage': 'Cold Storage',
        };

        return Object.entries(skillMap)
            .map(([id, skillName]) => ({ skill_name: skillName, level: $('#' + id).val() }))
            .filter(skill => skill.level !== 'none');
    }

    function formatApiErrors(xhr) {
        const errors = xhr.responseJSON?.errors;
        if (errors) {
            return Object.values(errors).flat().join('\n');
        }
        return xhr.responseJSON?.message || 'Terjadi kesalahan, silakan coba lagi.';
    }

    $(function () {
        loadRegionOptions();

        $('#technician-form').on('submit', function (e) {
            e.preventDefault();

            const payload = {
                name: $('#field-name').val(),
                email: $('#field-email').val(),
                password: $('#field-password').val(),
                grade: $('#field-grade').val(),
                region_id: $('#field-region').val() || null,
                certification: $('#field-certification').val() || null,
                skills: collectSkills(),
            };

            $('#btn-save-technician').prop('disabled', true);
            $('#btn-save-technician-label').text('Saving...');

            $.ajax({
                url: '/api/aftersales/technicians',
                method: 'POST',
                data: payload,
                success: function () {
                    window.location.href = '{{ route('aftersales.pages.technicians.index') }}';
                },
                error: function (xhr) {
                    alert(formatApiErrors(xhr));
                    $('#btn-save-technician').prop('disabled', false);
                    $('#btn-save-technician-label').text('Save Technician');
                }
            });
        });
    });
</script>
@endsection
