@extends('layouts.app')

@section('content')
@php
    $isCreate = empty($form_data->id);
    $defaultName = old('name', $form_data->name);
    $defaultTitle = old('title');

    if (! $isCreate && empty($defaultTitle)) {
        if (str_starts_with((string) $defaultName, 'Mr ')) {
            $defaultTitle = 'Mr';
            $defaultName = substr($defaultName, 3);
        } elseif (str_starts_with((string) $defaultName, 'Mrs ')) {
            $defaultTitle = 'Mrs';
            $defaultName = substr($defaultName, 4);
        }
    }

    $defaultTitle = $defaultTitle ?: 'Mr';
    $selectedStatus = old('is_active', $form_data->exists ? (int) $form_data->is_active : 1);
@endphp

<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center gap-3">
            <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                    fill="#115640" />
            </svg>
            <h1 class="text-[#115640] font-semibold text-2xl">Agents</h1>
        </div>

        <div class="flex items-center mt-2 gap-3">
            <a href="{{ route('masters.agents.index') }}" class="text-[#757575] hover:no-underline">All Agents</a>
            <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
            <span class="text-[#083224] underline">{{ $isCreate ? 'Create Agents' : 'View Agents' }}</span>
        </div>

        <form method="POST"
            action="{{ $saveUrl ?? url('/api/masters/agents/save' . ($form_data->id ? '/' . $form_data->id : '')) }}"
            id="form"
            back-url="{{ $backUrl ?? route('masters.agents.index') }}"
            require-confirmation="true"
            class="mt-3">
            @csrf

            <div id="agent-entries">
                <div class="agent-entry">
                    <div class="bg-white rounded-lg">
                        <h1 class="text-black uppercase border-b border-b-[#D9D9D9] p-3 font-semibold">Primary Contact</h1>

                        <div class="p-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 justify-between">
                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Title <i class="required">*</i></label>
                                <select name="title"
                                    class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                    required>
                                    <option value="Mr" {{ $defaultTitle === 'Mr' ? 'selected' : '' }}>Mr</option>
                                    <option value="Mrs" {{ $defaultTitle === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Name <i class="required">*</i></label>
                                <input type="text" name="name" placeholder="Nama Lengkap"
                                    class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                    value="{{ $defaultName }}" required>
                            </div>

                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Position <i class="required">*</i></label>
                                <select name="jabatan_id"
                                    class="select2 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                    required>
                                    <option value="">Pilih</option>
                                    @foreach($jabatans as $jabatan)
                                        <option value="{{ $jabatan->id }}" {{ old('jabatan_id', $form_data->jabatan_id) == $jabatan->id ? 'selected' : '' }}>
                                            {{ $jabatan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Phone <i class="required">*</i></label>
                                <input type="text" name="phone" placeholder="0812xxxxxxx"
                                    class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                    value="{{ old('phone', $form_data->phone) }}" required>
                            </div>

                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Email</label>
                                <input type="email" name="email" placeholder="email@domain.com"
                                    class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                    value="{{ old('email', $form_data->email) }}">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mt-3">
                        <div class="bg-white rounded-lg">
                            <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Company Details</h1>

                            <div class="p-3">
                                <div class="w-full grid grid-cols-1">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Company Name <i class="required">*</i></label>
                                    <input type="text" name="company_name" placeholder="Nama Perusahaan"
                                        class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                        value="{{ old('company_name', $form_data->company_name) }}" required>
                                </div>

                                <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-2 mt-3">
                                    <div class="w-full">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Customer City <i class="required">*</i></label>
                                        <select name="region_id"
                                            class="select2 region-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!"
                                            required>
                                            <option value="">Pilih</option>
                                            @foreach($regions as $region)
                                                <option value="{{ $region->id }}"
                                                    data-branch="{{ $region->branch_id }}"
                                                    {{ old('region_id', $form_data->region_id) == $region->id ? 'selected' : '' }}>
                                                    {{ $region->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="hidden" name="branch_id" class="branch-id-field" value="{{ old('branch_id', $form_data->branch_id) }}">
                                    </div>

                                    <div class="w-full">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Customer Province <i class="required">*</i></label>
                                        <select name="province"
                                            class="select2 province-select bg-[#D9D9D9]! rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!"
                                            required>
                                            <option value="">Pilih</option>
                                            @foreach ($provinces as $province)
                                                <option value="{{ $province }}" {{ old('province', $form_data->province) == $province ? 'selected' : '' }}>
                                                    {{ $province }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="w-full grid grid-cols-1 mt-3">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Company Address <i class="required">*</i></label>
                                    <textarea name="company_address" placeholder="Alamat Perusahaan"
                                        class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                        rows="4"
                                        required>{{ old('company_address', $form_data->company_address) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg">
                            <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Agent Classification</h1>

                            <div class="p-3">
                                <div class="w-full grid grid-cols-1">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Source <i class="required">*</i></label>
                                    <select name="source_id"
                                        class="select2 source-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!"
                                        required>
                                        <option value="">Pilih</option>
                                        @foreach ($sources as $source)
                                            <option value="{{ $source->id }}" {{ old('source_id', $form_data->source_id) == $source->id ? 'selected' : '' }}>
                                                {{ $source->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="w-full grid grid-cols-1 mt-3">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Customer Type <i class="required">*</i></label>
                                    @php $agentCustomerType = $customerTypes->firstWhere('id', 16); @endphp
                                    <select name="customer_type_id" class="form-select select2" required>
                                        @if($agentCustomerType)
                                            <option value="{{ $agentCustomerType->id }}" selected>
                                                {{ $agentCustomerType->name }}
                                            </option>
                                        @else
                                            <option value="">Customer type Agent/Makelar belum tersedia</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="w-full grid grid-cols-1 mt-3">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Status <i class="required">*</i></label>
                                    <select name="is_active"
                                        class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                        required>
                                        <option value="1" {{ (string) $selectedStatus === '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ (string) $selectedStatus === '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>

                                @if(in_array(auth()->user()?->role?->code, ['sales_director', 'super_admin'], true))
                                    <div class="w-full grid grid-cols-1 mt-3">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Assign Branch</label>
                                        <select name="assignment_branch" class="form-select select2">
                                            <option value="">-- Assign Branch (JKT/MKS/SBY) --</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ old('assignment_branch', $form_data->branch_id) == $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->code }} - {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end py-3">
                @include('partials.template.save-btn-form', ['backUrl' => $backUrl ?? route('masters.agents.index')])
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<script>
    $(function () {
        const regionProvinces = @json($regions->pluck('province.name', 'id'));

        function styleDisabledProvince($target) {
            $target.each(function () {
                const $select2Container = $(this).next('.select2-container');

                $select2Container.find('.select2-selection').css({
                    'background-color': '#e9ecef',
                    'color': '#6c757d',
                    'pointer-events': 'none',
                    'border-color': '#ced4da',
                    'cursor': 'not-allowed'
                });
            });
        }

        function setProvince($regionSelect) {
            const regionId = $regionSelect.val();
            const $entry = $regionSelect.closest('.agent-entry, form');
            const $provinceSelect = $entry.find('.province-select');
            const $branchField = $entry.find('.branch-id-field');
            const selectedBranch = $regionSelect.find('option:selected').data('branch') || '';

            $branchField.val(selectedBranch);
            $provinceSelect.val(regionProvinces[regionId] || '').trigger('change.select2');
        }

        styleDisabledProvince($('.province-select'));

        $('.region-select').each(function () {
            setProvince($(this));
        });

        $(document).on('select2:opening', '.province-select', function (e) {
            e.preventDefault();
        });

        $(document).on('change', '.region-select', function () {
            setProvince($(this));
        });
    });
</script>
@endsection
