@extends('layouts.app')

@section('content')
    <section class="min-h-screen sm:text-xs lg:text-sm">
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
                    {{ old('name', $form_data->name) ? 'View Leads' : 'Create Leads' }}
                </a>
            </div>
            <form method="POST" action="{{ request()->routeIs('leads.my.form') ? route('leads.my.save', $form_data->id) : route('leads.save', $form_data->id) }}" id="form"
                    back-url="{{ request()->routeIs('leads.my.form') ? route('leads.my') : route('leads.available') }}" require-confirmation="true"
                    class="mt-3">
                @csrf
                @php $isCreate = empty($form_data->id); @endphp
                <div id="lead-entries">
                    <div class="lead-entry">
                        {{-- PRIMARY CONTACT --}}
                        <div class="bg-white rounded-lg">
                            <h1 class="text-black uppercase border-b border-b-[#D9D9D9] p-3 font-semibold">Primary Contact</h1>
                            <div class="p-3 grid grid-cols-5 gap-3 justify-between">
                                {{-- FOR MR/MRS --}}
                                <div>
                                    @php
                                    $defaultName = old('name', $form_data->name);
                                    $defaultTitle = old('title');
                                    if (! $isCreate && empty($defaultTitle)) {
                                        if (str_starts_with($defaultName, 'Mr ')) {
                                            $defaultTitle = 'Mr';
                                            $defaultName = substr($defaultName, 3);
                                        } elseif (str_starts_with($defaultName, 'Mrs ')) {
                                            $defaultTitle = 'Mrs';
                                            $defaultName = substr($defaultName, 4);
                                        }
                                    }
                                    @endphp
                                    <div class="grid grid-cols-1 gap-1">
                                        <label class="text-[#1E1E1E]! mb-1!">Title <i class="required">*</i></label>
                                        <select name="{{ $isCreate ? 'title[]' : 'title' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                            <option value="Mr" {{ $defaultTitle === 'Mr' ? 'selected' : '' }}>Mr</option>
                                            <option value="Mrs" {{ $defaultTitle === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- FOR NAME --}}
                                <div class="grid grid-cols-1 gap-1">
                                    <label class="text-[#1E1E1E]! mb-1!">Name <i class="required">*</i></label>
                                    <input type="text" name="{{ $isCreate ? 'name[]' : 'name' }}" placeholder="Nama Lengkap" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                        value="{{ $defaultName }}" required>
                                </div>

                                {{-- FOR POSITION --}}
                                <div class="grid grid-cols-1 gap-1">
                                    <label class="text-[#1E1E1E]! mb-1!">Position <i class="required">*</i></label>
                                    <select name="{{ $isCreate ? 'jabatan_id[]' : 'jabatan_id' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                        <option value="" disabled selected>Pilih</option>
                                        @foreach($jabatans as $jabatan)
                                            <option value="{{ $jabatan->id }}" {{ old('jabatan_id', $form_data->jabatan_id) == $jabatan->id ? 'selected' : '' }}>{{ $jabatan->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- FOR PHONE --}}
                                <div class="grid grid-cols-1 gap-1">
                                    <label class="text-[#1E1E1E]! mb-1!">Phone <i class="required">*</i></label>
                                    <input type="text" name="{{ $isCreate ? 'phone[]' : 'phone' }}" placeholder="0812xxxxxxx" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                        value="{{ old('phone', $form_data->phone) }}" required>
                                </div>

                                {{-- FOR EMAIL --}}
                                <div class="grid grid-cols-1 gap-1">
                                    <label class="text-[#1E1E1E]! mb-1!">Email</label>
                                    <input type="email" name="{{ $isCreate ? 'email[]' : 'email' }}" placeholder="email@domain.com" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                        value="{{ old('email', $form_data->email) }}">
                                </div>
                            </div>
                            {{-- TEMPLATE ADD PIC --}}
                            <div class="pic-extensions">
                                @foreach ($form_data->picExtensions ?? [] as $pic)
                                    <div class="pic-entry p-3 grid grid-cols-6 gap-2">
                                        {{-- FOR MR/MRS --}}
                                        <div class="grid grid-cols-1 gap-1">
                                            <label class="text-[#1E1E1E]! mb-1!">Title <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'title[]' : 'title' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                                <option value="Mr" {{ $defaultTitle === 'Mr' ? 'selected' : '' }}>Mr</option>
                                                <option value="Mrs" {{ $defaultTitle === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                            </select>
                                        </div>

                                        {{-- FOR NAME --}}
                                        <div class="grid grid-cols-1 gap-1">
                                            <label class="text-[#1E1E1E]! mb-1!">Name <i class="required">*</i></label>
                                            <input type="text" name="{{ $isCreate ? 'name[]' : 'name' }}" placeholder="Nama Lengkap" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                                value="{{ $defaultName }}" required>
                                        </div>

                                        {{-- FOR POSITION --}}
                                        <div class="grid grid-cols-1 gap-1">
                                            <label class="text-[#1E1E1E]! mb-1!">Position <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'jabatan_id[]' : 'jabatan_id' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach($jabatans as $jabatan)
                                                    <option value="{{ $jabatan->id }}" {{ old('jabatan_id', $form_data->jabatan_id) == $jabatan->id ? 'selected' : '' }}>{{ $jabatan->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- FOR PHONE --}}
                                        <div class="grid grid-cols-1 gap-1">
                                            <label class="text-[#1E1E1E]! mb-1!">Phone <i class="required">*</i></label>
                                            <input type="text" name="{{ $isCreate ? 'phone[]' : 'phone' }}" placeholder="0812xxxxxxx" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                                value="{{ old('phone', $form_data->phone) }}" required>
                                        </div>

                                        {{-- FOR EMAIL --}}
                                        <div class="grid grid-cols-1 gap-1">
                                            <label class="text-[#1E1E1E]! mb-1!">Email</label>
                                            <input type="email" name="{{ $isCreate ? 'email[]' : 'email' }}" placeholder="email@domain.com" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                                value="{{ old('email', $form_data->email) }}">
                                        </div>
                                        <div class="col-md-1 mb-3 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-danger remove-pic">&times;</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            {{-- TRIGGER ADD PIC --}}
                            <div class="px-3 pb-3">
                                <button type="button" class="add-pic cursor-pointer text-[#083224] font-semibold!">
                                    + More PIC
                                </button>
                            </div>
                        </div>

                        {{-- COMPANY DETAILS AND LEAD CLASSIFICATION --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mt-3">
                            {{-- COMPANY DETAILS --}}
                            <div class="bg-white rounded-lg">
                                <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Company Details</h1>
                                <div class="p-3">
                                    {{-- COMPANY NAME FIELD --}}
                                    <div class="w-full grid grid-cols-1">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Company Name<i class="required">*</i></label>
                                        <input type="text" name="{{ $isCreate ? 'company[]' : 'company' }}" placeholder="Nama Perusahaan" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                            value="{{ old('company', $form_data->company) }}" required>
                                    </div>

                                    {{-- CUSTOMER CITY AND PROVINCE FIELD SELECT --}}
                                    <div class="w-full grid grid-cols-2 gap-2 mt-3">
                                        {{-- CUSTOMER CITY FIELD SELECT --}}
                                        <div class="w-full">
                                            <label class="text-[#1E1E1E]! mb-2! block!">Customer City <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'region_id[]' : 'region_id' }}" class="select2 region-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!" required>
                                                <option value="" disabled {{ old('region_id', $form_data->region_id)===null ? 'selected' : '' }}>Pilih</option>
                                                <option value="ALL" {{ old('region_id', $form_data->region_id)==='ALL' ? 'selected' : '' }}>
                                                    All Regions (will show in all regions)
                                                </option>
                                                @foreach($regions as $region)
                                                    <option 
                                                    value="{{ $region->id }}" 
                                                    data-branch="{{ $region->branch_id }}"
                                                    {{ old('region_id', $form_data->region_id)==$region->id ? 'selected' : '' }}
                                                    >
                                                    {{ $region->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" 
                                                name="{{ $isCreate ? 'branch_id[]' : 'branch_id' }}" 
                                                class="branch-id-field" 
                                                value="{{ old('branch_id', $form_data->branch_id) }}">
                                        </div>

                                        {{-- CUSTOMER PROVINCE FIELD SELECT --}}
                                        <div class="w-full">
                                            <label class="text-[#1E1E1E]! mb-2! block!">Customer Province <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'province[]' : 'province' }}" class="select2 province-select bg-[#D9D9D9]! rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!">
                                            <option value="" selected>Pilih</option>
                                            @foreach ($provinces as $prov)
                                                <option value="{{ $prov }}"
                                                    {{ old('province', $form_data->province) == $prov ? 'selected' : '' }}>
                                                    {{ $prov }}</option>
                                            @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- COMPANY ADDRESS FIELD --}}
                                    <div class="w-full grid grid-cols-1 mt-3">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Company Address<i class="required">*</i></label>
                                        <textarea
                                            name="{{ $isCreate ? 'company_address[]' : 'company_address' }}"
                                            placeholder="Alamat Perusahaan"
                                            class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
                                            rows="3"
                                            required
                                        >{{ old('company_address', $form_data->company_address) }}</textarea>

                                    </div>
                                </div>
                            </div>

                            {{-- LEAD CLASSIFICATION --}}
                            <div class="bg-white rounded-lg">
                                <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Leads Classification</h1>
                                <div class="p-3">
                                    {{-- SOURCE SELECT FIELD --}}
                                    <div class="w-full grid grid-cols-1">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Source<i class="required">*</i></label>
                                        <select name="{{ $isCreate ? 'source_id[]' : 'source_id' }}" class="select2 source-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!" required>
                                            <option value="" disabled selected>Pilih</option>
                                            @php
                                                $filter = [
                                                    'Ads Google',
                                                    'Website',
                                                    'Meta',
                                                    'Linked In',
                                                    'Tik Tok',
                                                    'Friends Recommendation',
                                                    'Canvas', 
                                                    'Visit', 
                                                    'Expo RHVAC Jakarta 2025',
                                                    'Association',
                                                    'Business Association',
                                                    'Repeat Order',
                                                    'Sales Independen',
                                                    'Aftersales',
                                                    'Office Walk In',
                                                    'Media with QR/Referral',
                                                    'Agent / Reseller',
                                                    'Youtube',
                                                    'Google Search',
                                                    'Telemarketing',
                                                ];
                                                $isNew = empty($form_data->source_id);
                                            @endphp

                                            @foreach ($sources as $source)
                                                @if ($isNew ? in_array($source->name, $filter) : true)
                                                    <option value="{{ $source->id }}"
                                                        {{ old('source_id', $form_data->source_id) == $source->id ? 'selected' : '' }}>
                                                        {{ $source->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- CUSTOMER TYPE FIELD SELECT --}}
                                    <div class="w-full grid grid-cols-1 mt-3">
                                        {{-- CUSTOMER CITY FIELD SELECT --}}
                                        <div class="w-full">
                                            <label class="text-[#1E1E1E]! mb-2! block!">Customer Type <i class="required">*</i></label>
                                            <select name="{{ $isCreate ? 'customer_type[]' : 'customer_type' }}" class="form-select select2" required>
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach($customerTypes as $type)
                                                    <option value="{{ $type->name }}" {{ old('customer_type', $form_data->customer_type) == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- EXISTING CUSTOMER INDUSTRY SELECT FIELD --}}
                                    <div class="w-full grid grid-cols-1 mt-3">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Existing Customer Industry<i class="required">*</i></label>
                                        <select name="{{ $isCreate ? 'industry_id[]' : 'industry_id' }}" class="form-select select2 industry-select" required>
                                                <option value="" disabled selected>Pilih</option>
                                                @foreach($industries as $industry)
                                                    <option value="{{ $industry->id }}" {{ old('industry_id', $form_data->industry_id ?? ($form_data->other_industry ? 'other' : null)) == $industry->id ? 'selected' : '' }}>{{ $industry->name }}</option>
                                                @endforeach
                                                <option value="other" {{ old('industry_id', $form_data->industry_id ?? ($form_data->other_industry ? 'other' : null)) === 'other' ? 'selected' : '' }}>Lainnya</option>
                                            </select>
                                        <input type="text" name="{{ $isCreate ? 'other_industry[]' : 'other_industry' }}" class="form-control mt-2 industry-other d-none" placeholder="Isi industri" value="{{ old('other_industry', $form_data->other_industry) }}" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- REQUIREMENT & CONTEXT --}}
                        <div class="grid grid-cols-1 mt-3">
                            <div class="bg-white rounded-lg">
                                <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">
                                    Requirement & Context
                                </h1>
                                <div class="p-3">
                                    {{-- CONTACT / COMPETITOR / OPEN / INDUSTRY FIELD --}}
                                    <div class="grid grid-cols-4 gap-3">
                                        {{-- CONTACTING US FIELD --}}
                                        <div class="w-full grid grid-cols-1">
                                            <label class="form-label contact-reason-label text-[#1E1E1E]! mb-2! block!">Reason of Contacting Us</label>
                                            <textarea name="{{ $isCreate ? 'contact_reason[]' : 'contact_reason' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" rows="2" placeholder="Type Here...">{{ old('contact_reason', $form_data->contact_reason) }}</textarea>
                                        </div>

                                        {{-- COMPETITOR OFFER FIELD --}}
                                        <div class="w-full grid grid-cols-1">
                                            <label class="text-[#1E1E1E]! mb-2! block!">Competitor Offer</label>
                                            <textarea name="{{ $isCreate ? 'competitor_offer[]' : 'competitor_offer' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" rows="2"  placeholder="Type Here...">{{ old('competitor_offer', $form_data->competitor_offer) }}</textarea>
                                        </div>

                                        {{-- OPEN BUSINESS FIELD --}}
                                        <div class="w-full grid grid-cols-1">
                                            <label class="text-[#1E1E1E]! mb-2! block!">Reason to Open Business</label>
                                            <textarea name="{{ $isCreate ? 'business_reason[]' : 'business_reason' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" rows="2" placeholder="Type Here...">{{ old('business_reason', $form_data->business_reason) }}</textarea>
                                        </div>

                                        {{-- INDUSTRY REMARK FIELD --}}
                                        <div class="w-full grid grid-cols-1">
                                            <label class="text-[#1E1E1E]! mb-2! block!">Industry Remark</label>
                                            <textarea name="{{ $isCreate ? 'industry_remark[]' : 'industry_remark' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" placeholder="Additional comments about the industry" rows="2">{{ old('industry_remark', $form_data->industry_remark) }}</textarea>
                                        </div>
                                    </div>

                                    {{-- NEEDS SELECT FIELD --}}
                                    <div class="grid grid-cols-1 mt-3">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Needs (Ice Machine Type)<i class="required">*</i></label>
                                        <select name="{{ $isCreate ? 'needs[]' : 'needs' }}" class="select2 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                            <option value="" disabled selected>Pilih</option>
                                            @php
                                                $needsOptions = [
                                                    'Tube Ice',
                                                    'Cube Ice',
                                                    'Block Ice',
                                                    'Flake ice',
                                                    'Slurry Ice',
                                                    'Flake Ice',
                                                    'Cold Room',
                                                    'Other ( Keperluan Kustom )',
                                                ];
                                                $selectedNeed = old('needs', $form_data->needs);
                                            @endphp
                                            @foreach ($needsOptions as $need)
                                                <option value="{{ $need }}" {{ $selectedNeed == $need ? 'selected' : '' }}>{{ $need }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- CITY / PROVINCE / INDUSTRY / TONNAGE FIELD --}}
                                    <div class="w-full grid grid-cols-2 mt-3 gap-3">
                                        {{-- LEFT ITEMS FIELD --}}
                                        <div class="w-full grid grid-cols-1">
                                            {{-- CITY FACTORY TO BE FIELD --}}
                                            <div class="w-full">
                                                <label class="text-[#1E1E1E]! mb-2! block!">City Factory To Be</label>
                                                <select name="{{ $isCreate ? 'factory_city_id[]' : 'factory_city_id' }}" class="select2 factory-region-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!">
                                                    <option value="" disabled selected>Pilih</option>
                                                    <option value="ALL" {{ old('factory_city_id', $form_data->factory_city_id) === 'ALL' ? 'selected' : '' }}>
                                                        All Cities
                                                    </option>
                                                    @foreach($regions as $region)
                                                        <option value="{{ $region->id }}" 
                                                            data-branch="{{ $region->branch_id }}"
                                                            data-province="{{ $region->province->name ?? '' }}"
                                                            {{ old('factory_city_id', $form_data->factory_city_id) == $region->id ? 'selected' : '' }}>
                                                            {{ $region->name }}
                                                        </option>
                                                    @endforeach

                                                    @php
                                                        dump('Region object:', $region ?? null);
                                                        dump('Factory city data:', $form_data->factory_city_id ?? null);
                                                        dump('Factory province:', $form_data->factory_province ?? null);
                                                    @endphp
                                                    
                                                </select>
                                            </div>

                                            {{-- PROVINCE FACTORY TO BE FIELD --}}
                                            <div class="w-full mt-3">
                                                <label class="text-[#1E1E1E]! mb-2! block!">Province Factory To Be</label>
                                                <select name="{{ $isCreate ? 'factory_province[]' : 'factory_province' }}" class="form-select select2 factory-province-select">
                                                <option value="" selected>Pilih</option>
                                                    @foreach ($provinces as $prov)
                                                        <option value="{{ $prov }}" {{ old('factory_province', $form_data->factory_province) == $prov ? 'selected' : '' }}>
                                                            {{ $prov }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- INDUSTRY TO BE SELECT FIELD --}}
                                            <div class="w-full mt-3">
                                                <label class="text-[#1E1E1E]! mb-2! block!">Industry To Be</label>
                                                <select name="{{ $isCreate ? 'factory_industry_id[]' : 'factory_industry_id' }}" class="form-select select2 factory-industry-select">
                                                    <option value="" disabled selected>Pilih</option>
                                                    @foreach($industries as $industry)
                                                        <option value="{{ $industry->id }}" {{ old('factory_industry_id', $form_data->factory_industry_id) == $industry->id ? 'selected' : '' }}>
                                                            {{ $industry->name }}
                                                        </option>
                                                    @endforeach
                                                    <option value="other" {{ old('factory_industry_id', $form_data->factory_industry_id ?? ($form_data->factory_other_industry ? 'other' : null)) === 'other' ? 'selected' : '' }}>Lainnya</option>
                                                </select>
                                                <input type="text" name="{{ $isCreate ? 'factory_other_industry[]' : 'factory_other_industry' }}" 
                                                    class="form-control mt-2 factory-industry-other d-none" 
                                                    placeholder="Isi industri" 
                                                    value="{{ old('factory_other_industry', $form_data->factory_other_industry) }}" />
                                            </div>
                                        </div>

                                        {{-- RIGHT ITEMS FIELD --}}
                                        <div class="w-full grid grid-cols-1">
                                            {{-- TONASE FIELD NUMBER --}}
                                            <div class="w-full">
                                                <label class="text-[#1E1E1E]! mb-2! block!">Tonase</label>
                                                <input type="number" step="0.01" name="{{ $isCreate ? 'tonase[]' : 'tonase' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! appearance-none bg-white w-full!" value="{{ old('tonase', $form_data->tonase) }}" placeholder="0.00">
                                            </div>

                                            {{-- TONNAGE REMARK FIELD --}}
                                            <div class="w-full mt-3">
                                                <label class="text-[#1E1E1E]! mb-2! block!">Tonage Remark</label>
                                                <textarea name="{{ $isCreate ? 'tonage_remark[]' : 'tonage_remark' }}" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none! w-full!" rows="5" placeholder="Type Here...">{{ old('tonage_remark', $form_data->tonage_remark) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end py-3">
                    @include('partials.template.save-btn-form', ['backUrl' => 'back'])
                </div>
            </form>
        </div>
        
        {{-- MEETING VIEWS --}}
        @if (!empty($form_data->id))
            @foreach ($meetings as $meeting)
                <div id="meeting-view" class="my-4">
                    <div class="meeting-tabs">
                        {{-- PRIMARY CONTACT --}}
                        <div class="bg-white rounded-lg">
                            <h1 class="text-black uppercase border-b border-b-[#D9D9D9] p-3 font-semibold">Meeting</h1>
                            <table class="w-full">
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Schedule</th>
                                    <td class="text-[#1E1E1E]">
                                        {{ $meeting->scheduled_start_at ? date('d M Y H:i', strtotime($meeting->scheduled_start_at)) : '' }}
                                        -
                                        {{ $meeting->scheduled_end_at ? date('d M Y H:i', strtotime($meeting->scheduled_end_at)) : '' }}
                                    </td>
                                </tr>
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Type</th>
                                    <td class="text-[#1E1E1E]">
                                        {{ $meeting->is_online ? 'Online' : 'Offline' }}
                                    </td>
                                </tr>
                                @if ($meeting->is_online)
                                    <tr class="border-b border-b-[#D9D9D9]">
                                        <th class="p-3 text-[#1E1E1E] font-semibold">URL</th>
                                        <td class="text-[#1E1E1E]">{{ $meeting->online_url }}</td>
                                    </tr>
                                @else
                                    <tr class="border-b border-b-[#D9D9D9]">
                                        <th class="p-3 text-[#1E1E1E] font-semibold">Location</th>
                                        <td class="text-[#1E1E1E]">{{ trim(($meeting->city ?? '') . ' ' . ($meeting->address ?? '')) }}</td>
                                    </tr>
                                @endif
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Result</th>
                                    <td class="text-[#1E1E1E]">{{ $meeting->result ?? '-' }}</td>
                                </tr>
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Summary</th>
                                    <td class="text-[#1E1E1E]">{{ $meeting->summary ?? '-' }}</td>
                                </tr>
                                @if ($meeting->attachment)
                                    <tr class="border-b border-b-[#D9D9D9]">
                                        <th class="p-3 text-[#1E1E1E] font-semibold">Attachment</th>
                                        <td class="text-[#1E1E1E]">
                                            <a href="{{ route('attachments.download', $meeting->attachment_id) }}"
                                            class="cursor-pointer p-3 text-white rounded-lg bg-[#115640]">Download</a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </section>
@endsection

@section('scripts')
    <script>
    $(function () {
    /* -----------------------------------------------------------
    * helper: initialise select2 on the given jQuery collection
    * --------------------------------------------------------- */
    function initSelect2($elements) {
        $elements.each(function () {
        const $sel = $(this);

        // if this <select> already has Select2, leave it alone
        if ($sel.data('select2')) return;

        $sel.select2({ width: '100%' });
        
        });
    }

    function styleDisabledProvince($target) {
        $target.each(function () {
            const $select = $(this);
            const $select2Container = $select.next('.select2-container');

            $select2Container.find('.select2-selection').css({
                'background-color': '#e9ecef',
                'color': '#6c757d',
                'pointer-events': 'none',
                'border-color': '#ced4da',
                'cursor': 'not-allowed'
            });
        });
    }

    function updateContactReasonLabel($select) {
        const $entry = $select.closest('.lead-entry');
        const $contactReasonLabel = $entry.find('.contact-reason-label');
        const selectedText = $select.find('option:selected').text().trim();

        const labelMappings = {
            'Canvas': 'Reason of Contacting Them (via Canvas)',
        }

        const newLabel = labelMappings[selectedText] || 'Reason of Contacting Us';
        $contactReasonLabel.text(newLabel);
    }

    function toggleAgentFields($select) {
        const $entry = $select.closest('.lead-entry');
        const $agentFields = $entry.find('.agent-fields');
        const $agentTitle = $entry.find('select[name*="agent_title"]');
        const $agentName = $entry.find('input[name*="agent_name"]');
        const $canvasFields = $entry.find('.canvas-fields');
        const $spkCanvassing = $entry.find('input[name*="spk_canvassing"]');
        // const $contactReasonLabel = $entry.find('.contact-reason-label');
        
        const selectedText = $select.find('option:selected').text().trim();

        if (selectedText === 'Agent / Reseller') {
            $agentFields.removeClass('d-none');
            $agentTitle.prop('required', true);
            $agentName.prop('required', true);
        } else {
            $agentFields.addClass('d-none');
            $agentTitle.prop('required', false).val('');
            $agentName.prop('required', false).val('');
        }
        
        if (selectedText === 'Canvas') {
            $canvasFields.removeClass('d-none');
            $spkCanvassing.prop('required', true);
            // $contactReasonLabel.text('Reason of Contacting Them (via Canvas)');
        } else {
            $canvasFields.addClass('d-none');
            $spkCanvassing.prop('required', false).val('');
            // $contactReasonLabel.text('Reason of Contacting Us');
        }

        updateContactReasonLabel($select);
    }

    $('#lead-entries .source-select').each(function(){
        toggleAgentFields($(this));
    });

    // Add event handler for source changes
    $(document).on('change', '.source-select', function() {
        toggleAgentFields($(this));
    });

    /* -----------------------------------------------------------
    * helper: renumber the “Lead n” labels
    * --------------------------------------------------------- */
    function updateLeadLabels() {
        $('#lead-entries .lead-entry').each(function (i) {
        $(this).find('.lead-label').text('Lead ' + (i + 1));
        });
        updateLeadPicNames();
    }

    function updateLeadPicNames() {
        $('#lead-entries .lead-entry').each(function(i){
            $(this).attr('data-index', i);
            $(this).find('.pic-entry').each(function(){
                $(this).find('[data-field]').each(function(){
                    const field = $(this).data('field');
                    $(this).attr('name', `pic_extensions[${i}][${field}][]`);
                });
            });
        });
    }

    /* -----------------------------------------------------------
    * PAGE-LOAD: turn the first row into Select2 widgets
    * --------------------------------------------------------- */
    initSelect2($('#lead-entries').find('select.select2'));
    styleDisabledProvince($('#lead-entries').find('.province-select'));
    updateLeadLabels();
    updateLeadPicNames();

    const regionProvinces = @json($regions->pluck('province.name','id'));
    $('#lead-entries .province-select').on('select2:opening', e => e.preventDefault());
    $('#lead-entries .region-select').each(function(){
        setProvince($(this));
    });

    function setFactoryProvince($regionSelect) {
        const regionId = $regionSelect.val();
        const $entry = $regionSelect.closest('.lead-entry');
        const $provinceSelect = $entry.find('.factory-province-select');

        if (regionId === 'ALL') {
            $provinceSelect.val('').trigger('change.select2');
        } else {
            const province = $regionSelect.find('option:selected').data('province') || '';
            $provinceSelect.val(province).trigger('change.select2');
        }
    }

    $('#lead-entries .factory-region-select').each(function(){
        setFactoryProvince($(this));
    });

    $(document).on('change', '.factory-region-select', function() {
        setFactoryProvince($(this));
    });

    $(document).on('select2:opening', '.factory-province-select', function(e) {
        // Optional: prevent manual selection if you want it auto-filled only
        // e.preventDefault();
    });

    function toggleIndustryOther($select) {
        const $entry = $select.closest('.lead-entry');
        const $other = $entry.find('.industry-other');
        if ($select.val() === 'other') {
            $other.removeClass('d-none').prop('required', true);
        } else {
            $other.addClass('d-none').prop('required', false).val('');
        }
    }

    $('#lead-entries .industry-select').each(function(){
        toggleIndustryOther($(this));
    });

    /* -----------------------------------------------------------
    * PIC extension add/remove
    * --------------------------------------------------------- */
    const jabatanOptions = @json($jabatans->pluck('name', 'id'));

    function jabatanSelectHtml(selected = null) {
        const pilihSelected = !selected ? 'selected' : '';

        let opts = `<option value="" disabled ${pilihSelected}>Pilih</option>`;

        Object.entries(jabatanOptions).forEach(([id,name]) => {
            const isSelected = selected == id ? 'selected' : '';
            opts += `<option value="${id}" ${isSelected}>${name}</option>`;
        });

        return `
            <select class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! appearance-none bg-white"
            data-field="jabatan_id" required>
            ${opts}
            </select>`;
    }

    function picEntryHtml() {
        return `
        <div class="pic-entry p-3 grid grid-cols-5 justify-between gap-3">
            
            <div class="grid grid-cols-1 gap-1">
                <select class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg!" data-field="title">
                    <option value="Mr">Mr</option>
                    <option value="Mrs">Mrs</option>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-1">
                <input type="text" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg!" data-field="nama" placeholder="Nama Lengkap" required>
            </div>

            <div class="grid grid-cols-1 gap-1">
                ${jabatanSelectHtml()}
            </div>

            <div class="grid grid-cols-1 gap-1">
                <input type="text" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg!" data-field="phone" placeholder="0812xxxxxxx" required>
            </div>

            <div class="grid grid-cols-[1fr_auto] gap-3">
                <div class="grid grid-cols-1 gap-1">
                    <input type="email" 
                        class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg!" data-field="email" placeholder="email@domain.com" required>
                </div>

                <div class="flex items-end">
                    <button type="button" class="border border-[#9B201D] text-[#9B201D] px-3 py-2 duration-150 hover:bg-[#9B201D] hover:text-white rounded remove-pic cursor-pointer">×</button>
                </div>    
            </div>

        </div>`;
    }

    $(document).on('click', '.add-pic', function(){
        const $entry = $(this).closest('.lead-entry');
        const $html = $(picEntryHtml());
        $entry.find('.pic-extensions').append($html);
        initSelect2($html.find('select.select2'));
        updateLeadPicNames();
    });

    $(document).on('click', '.remove-pic', function(){
        $(this).closest('.pic-entry').remove();
        updateLeadPicNames();
    });

    /* -----------------------------------------------------------
    * ADD lead
    * --------------------------------------------------------- */
    $('#add-lead').on('click', function () {
        const $template = $('#lead-entries .lead-entry:first');
        const $clone    = $template.clone(false, false);   // Shallow-clone, no data/events

        /* ---- strip Select2 from the clone (important!) ---- */
        $clone.find('select.select2').each(function () {
        const $sel = $(this);

        // remove any duplicated Select2 container that came across in the clone
        $sel.siblings('.select2-container').remove();

        // remove Select2‐related markup / classes / data so it’s a brand-new <select>
        $sel.removeAttr('data-select2-id')
            .removeClass('select2-hidden-accessible')
            .removeData('select2')
            .show();

        // cloned <option> tags keep their Select2 IDs and selections
        $sel.find('option')
            .removeAttr('data-select2-id')
            .prop('selected', false)
            .removeAttr('selected');
        });

        toggleIndustryOther($clone.find('.industry-select'));
        toggleFactoryIndustryOther($clone.find('.factory-industry-select'));
        toggleAgentFields($clone.find('.source-select'));

        /* ---- clear field values ---- */
        $clone.find('input').val('');
        // ensure select boxes start on their placeholder
        $clone.find('select').each(function () {
            const $select = $(this);
            $select.val(''); // reset to empty

            // Jika ada option pertama yang kosong (""), pastikan tidak disabled
            const $firstOption = $select.find('option:first-child');
            if ($firstOption.val() === '' && $firstOption.prop('disabled')) {
                $firstOption.prop('disabled', false);
            }
        });
        /* ---- show remove button ---- */
        $clone.find('.remove-lead').removeClass('d-none');

        $clone.find('.pic-extensions').empty();

        /* ---- append clone & init its Select2s only ---- */
        $('#lead-entries').append($clone);
        initSelect2($clone.find('select.select2'));
        styleDisabledProvince($clone.find('.province-select'));

        $clone.find('.province-select').on('select2:opening', e => e.preventDefault());
        setProvince($clone.find('.region-select'));
        toggleIndustryOther($clone.find('.industry-select'));
        updateLeadLabels();
    });

    /* -----------------------------------------------------------
    * REMOVE lead
    * --------------------------------------------------------- */
    $(document).on('click', '.remove-lead', function () {
        $(this).closest('.lead-entry').remove();
        updateLeadLabels();
    });

    /* -----------------------------------------------------------
    * keep hidden branch_id in sync
    * --------------------------------------------------------- */
    function setProvince($regionSelect) {
        const regionId = $regionSelect.val();
        const $entry = $regionSelect.closest('.lead-entry');
        const $provinceSelect = $entry.find('.province-select');

        if (regionId === 'ALL') {
            $provinceSelect.val('').trigger('change.select2');
            $provinceSelect.prop('required', false);
        } else {
            const province = regionProvinces[regionId] || '';
            $provinceSelect.val(province).trigger('change.select2');
            $provinceSelect.prop('required', true);
        }
    }

    function toggleFactoryIndustryOther($select) {
        const $entry = $select.closest('.lead-entry');
        const $other = $entry.find('.factory-industry-other');
        if ($select.val() === 'other') {
            $other.removeClass('d-none').prop('required', true);
        } else {
            $other.addClass('d-none').prop('required', false).val('');
        }
    }

        // Initialize both industry selects
    $('#lead-entries .industry-select').each(function(){
        toggleIndustryOther($(this));
    });

    $('#lead-entries .factory-industry-select').each(function(){
        toggleFactoryIndustryOther($(this));
    });

    // Add event handlers for both industry selects
    $(document).on('change', '.industry-select', function() {
        toggleIndustryOther($(this));
    });

    $(document).on('change', '.factory-industry-select', function() {
        toggleFactoryIndustryOther($(this));
    });


    $(document).on('select2:opening', '.province-select', function (e) {
        e.preventDefault();
    });

    $(document).on('change', '.region-select', function () {
        const branch = $(this).find('option:selected').data('branch');
        $(this).closest('.lead-entry').find('.branch-id-field').val(branch);
        setProvince($(this));
    });

    $(document).on('change', '.industry-select', function () {
        toggleIndustryOther($(this));
    });

    /* -----------------------------------------------------------
    * Claim-lead button
    * --------------------------------------------------------- */
    $('#btnClaim').on('click', function () {
        const url = $(this).data('url');

        Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to claim this lead.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, claim it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#aaa'
        }).then(res => {
        if (!res.isConfirmed) return;

        $.post(url, { _token: '{{ csrf_token() }}' })
            .done(() => {
            notif('Lead claimed successfully');
            location.href = '{{ route('leads.my') }}';
            })
            .fail(xhr => {
            notif(xhr.responseJSON?.message || 'Failed to claim lead', 'error');
            });
        });
    });
    });
    </script>

@endsection