@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4 max-w-4xl">
        <h1 class="text-[#1E1E1E] font-semibold text-2xl">Technical Specification Form</h1>
        <p class="text-[#757575] mt-2">
            Fill in the details of your industrial cooling system specifications to begin calculating capacity and
            estimating logistics for your project.
        </p>

        {{-- IMPORTANT NOTICE --}}
        <div class="bg-[#FCF3D9] border border-[#EAD98B] rounded-lg p-4 mt-4">
            <div class="flex items-center gap-2 text-[#8A6D1D] font-semibold">
                <i class="bi bi-info-circle-fill"></i>
                <span>Important Notice</span>
            </div>
            <p class="text-[#8A6D1D] mt-1">
                Please make sure the data you enter is accurate. Inconsistencies in electrical data can result in
                equipment malfunction or pose a risk of infrastructure damage.
            </p>
        </div>

        <form method="POST" action="{{ route('leads.my.deal.customer.store', $claim->id ?? '') }}" 
            id="form" 
            back-url="{{ route('leads.my.deal.manage', $lead->id ?? '') }}"
            require-confirmation="true" 
            class="mt-3">
            @csrf

            {{-- CUSTOMER DATA --}}
            <div class="flex items-center gap-3 mt-6">
                <span class="bg-[#DDEDE6] text-[#115640] rounded-lg w-9 h-9 flex items-center justify-center">
                    <i class="bi bi-person-fill"></i>
                </span>
                <h2 class="text-[#1E1E1E] font-semibold text-lg">Customer Data</h2>
            </div>

            <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="uppercase text-xs font-semibold text-[#757575]">Full Name</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $lead->name ?? '') }}"
                            placeholder="Enter your full name"
                            class="w-full mt-1 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" disabled>
                    </div>
                    <div>
                        <label class="uppercase text-xs font-semibold text-[#757575]">Phone Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $lead->phone ?? '') }}"
                            placeholder="+62 812-xxxx-xxxx"
                            class="w-full mt-1 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" disabled>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="uppercase text-xs font-semibold text-[#757575]">Full Address</label>
                    <textarea name="full_address" rows="3" placeholder="Enter your shipping and installation address details"
                        class="w-full mt-1 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none! resize-none" disabled>{{ old('full_address', $lead->company_address ?? '') }}</textarea>
                </div>

                <div class="mt-4">
                    <label class="uppercase text-xs font-semibold text-[#757575]">Share Link Lock Location</label>
                    <div class="flex items-stretch gap-2 mt-1">
                        <input type="text" name="location_link" id="location_link" value="{{ old('location_link', $claim->lead->customer->location_link ?? '') }}"
                            placeholder="https://maps.google.com/..."
                            class="flex-1 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!">
                        <button type="button" id="btn-select-location"
                            class="bg-[#115640] text-white! px-4 rounded-lg flex items-center gap-2 whitespace-nowrap hover:bg-[#0d4633] transition-colors">
                            <i class="bi bi-geo-alt-fill"></i>
                            Select
                        </button>
                    </div>
                </div>
            </div>

            @include('components.location-map-picker')

            {{-- ELECTRICAL & LOCATION INFORMATION --}}
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="bg-[#DDEDE6] text-[#115640] rounded-lg w-9 h-9 flex items-center justify-center">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </span>
                        <h2 class="text-[#1E1E1E] font-semibold text-lg">Electrical Information</h2>
                    </div>

                    <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-3">
                        <label class="uppercase text-xs font-semibold text-[#757575]">Electrical Power (Watts)</label>
                        <div class="relative mt-1">
                            <input type="number" name="electricity" value="{{ old('electricity', $claim->lead->customer->electricity ?? '') }}"
                                placeholder="Example: 3500"
                                class="w-full px-3! py-2! pr-9! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#757575]">W</span>
                        </div>

                        @php
                            $matchedType = $productTypes->first(fn ($type) => strcasecmp($type->name, trim((string) $lead->needs)) === 0);
                        @endphp
                        <label class="uppercase text-xs font-semibold text-[#757575] mt-4 block">Types of Machines</label>
                        <select name="machine_type" disabled
                            class="w-full mt-1 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none! bg-gray-50!">
                            <option value="">Select the type of ice machine</option>
                            @if(! $matchedType && filled($lead->needs))
                                <option value="{{ $lead->needs }}" selected>{{ $lead->needs }}</option>
                            @endif
                            @foreach($productTypes as $productType)
                                <option value="{{ $productType->id }}" {{ $matchedType && $matchedType->id === $productType->id ? 'selected' : '' }}>
                                    {{ $productType->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="machine_type" value="{{ $matchedType->id ?? $lead->needs }}">
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-3">
                        <span class="bg-[#DDEDE6] text-[#115640] rounded-lg w-9 h-9 flex items-center justify-center">
                            <i class="bi bi-rulers"></i>
                        </span>
                        <h2 class="text-[#1E1E1E] font-semibold text-lg">Location Information</h2>
                    </div>

                    <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-3">
                        <label class="uppercase text-xs font-semibold text-[#757575]">Building Area (m2)</label>
                        <div class="relative mt-1">
                            <input type="number" step="0.01" name="building_area" value="{{ old('building_area', $claim->lead->customer->building_area ?? '') }}"
                                placeholder="Example: 3500"
                                class="w-full px-3! py-2! pr-9! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#757575]">m<sup>2</sup></span>
                        </div>

                        <label class="uppercase text-xs font-semibold text-[#757575] mt-4 block">Road Width (Meters)</label>
                        <div class="relative mt-1">
                            <input type="number" step="0.01" name="access_road_width" value="{{ old('access_road_width', $claim->lead->customer->access_road_width ?? '') }}"
                                placeholder="Example: 6"
                                class="w-full px-3! py-2! pr-9! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#757575]">M</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="flex items-center justify-end gap-3 mt-6 mb-8">
                <a href="{{ url()->previous() }}"
                    class="bg-white text-[#1E1E1E] px-5 py-2 rounded-lg border border-[#D9D9D9] hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                    class="bg-[#115640] text-white! px-5 py-2 rounded-lg flex items-center gap-2 hover:bg-[#0d4633] transition-colors">
                    Simpan &amp; Lanjutkan
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal        = document.getElementById('map-picker-modal');
        const locationInput = document.getElementById('location_link');
        const btnSelect     = document.getElementById('btn-select-location');
        const btnClose      = document.getElementById('btn-close-map-modal');
        const btnCancel      = document.getElementById('btn-cancel-map');
        const btnConfirm     = document.getElementById('btn-confirm-map');

        let map = null;
        let marker = null;
        let selectedLatLng = null;

        function openModal() {
            modal.classList.remove('hidden');

            if (! map) {
                map = L.map('map-picker').setView([-6.2088, 106.8456], 12); // default: Jakarta

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                map.on('click', function (e) {
                    selectedLatLng = e.latlng;

                    if (marker) {
                        marker.setLatLng(selectedLatLng);
                    } else {
                        marker = L.marker(selectedLatLng).addTo(map);
                    }
                });
            }

            setTimeout(function () { map.invalidateSize(); }, 100);
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        btnSelect.addEventListener('click', openModal);
        btnClose.addEventListener('click', closeModal);
        btnCancel.addEventListener('click', closeModal);

        btnConfirm.addEventListener('click', function () {
            if (! selectedLatLng) {
                closeModal();
                return;
            }

            locationInput.value = 'https://maps.google.com/?q=' + selectedLatLng.lat + ',' + selectedLatLng.lng;
            closeModal();
        });
    });
</script>
@endsection
