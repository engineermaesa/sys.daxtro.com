@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
@endsection

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <h1 class="text-[#115640] font-bold text-2xl">{{ $customer ? 'Edit Customer' : 'Add Customer' }}</h1>
        <p class="text-[#757575] text-sm mt-1">
            <a href="{{ route('aftersales.pages.customers.index') }}" class="hover:underline">Customer</a>
            <span class="mx-1">&gt;</span>
            <span class="font-semibold text-[#115640] underline">{{ $customer ? 'Edit Customer' : 'Add Customer' }}</span>
        </p>

        <form id="customer-form" class="mt-4">
            <input type="hidden" id="customer-id" value="{{ $customer->id ?? '' }}">

            <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Customer Information</h6>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Customer Name<span class="text-red-600">*</span></label>
                        <input type="text" id="customer-name" required value="{{ $customer->name ?? '' }}"
                            placeholder="e.g. Global Dynamics Inc."
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Address<span class="text-red-600">*</span></label>
                        <textarea id="customer-address" rows="2" required placeholder="e.g. Jl. Sudirman No. 123, Jakarta"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">{{ $customer->address ?? '' }}</textarea>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Google Maps Link<span class="text-red-600">*</span></label>
                        <div class="flex items-stretch gap-2">
                            <input type="url" id="customer-maps-link" required value="{{ $customer->address_maps_url ?? $customer->maps_link ?? '' }}"
                                placeholder="Paste Google Maps location link"
                                class="flex-1 border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <button type="button" id="btn-open-maps"
                                class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer whitespace-nowrap text-xs font-semibold">
                                Open Maps
                            </button>
                        </div>
                        <p class="text-[#9A9A9A] mt-1 mb-0" style="font-size:10px">Paste the customer link from Google Maps, or pick the location on the map.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Province<span class="text-red-600">*</span></label>
                        <select id="customer-province" required class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">City<span class="text-red-600">*</span></label>
                        <select id="customer-region" required class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" disabled>
                            <option value="">Select Province first</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">PIC Name<span class="text-red-600">*</span></label>
                        <input type="text" id="customer-pic-name" required value="{{ $customer->pic_name ?? '' }}"
                            placeholder="e.g. John Doe"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email<span class="text-red-600">*</span></label>
                        <input type="email" id="customer-email" required value="{{ $customer->email ?? '' }}"
                            placeholder="e.g. john.doe@company.com"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Phone<span class="text-red-600">*</span></label>
                        <input type="number" id="customer-phone" required value="{{ $customer->phone ?? '' }}"
                            placeholder="e.g. 081234567890"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <a href="{{ route('aftersales.pages.customers.index') }}"
                    class="bg-white border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 transition-colors inline-flex items-center gap-2">
                    <i class="bi bi-chevron-left"></i> Back
                </a>
                <button type="submit"
                    class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer">
                    Save Customer
                </button>
            </div>
        </form>
    </div>
</section>

{{-- Map picker (Leaflet) --}}
<div class="modal fade" id="mapPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:720px">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="flex items-start justify-between" style="padding:18px 22px 12px">
                <div>
                    <h5 class="text-[#115640] font-bold mb-1" style="font-size:18px">Pick Location</h5>
                    <p class="text-[#9A9A9A] mb-0" style="font-size:11px">Click the map or drag the pin to set the customer location.</p>
                </div>
                <button type="button" data-dismiss="modal" aria-label="Close"
                    style="width:32px;height:32px;font-size:12px"
                    class="border border-[#D9D9D9] rounded-lg bg-white flex items-center justify-center cursor-pointer"><i class="bi bi-x-lg"></i></button>
            </div>
            <div style="padding:0 22px">
                <div class="flex gap-2" style="margin-bottom:10px">
                    <input type="text" id="map-search" placeholder="Search place or address"
                        style="flex:1;font-size:12px;padding:8px 12px;border:1px solid #D9D9D9;border-radius:8px"
                        class="focus:outline-none!">
                    <button type="button" id="map-search-btn" style="font-size:12px;padding:8px 16px"
                        class="bg-white border border-[#D9D9D9] rounded-lg cursor-pointer hover:bg-[#E8E8E8]">Search</button>
                </div>
                <div id="map-canvas" style="height:360px;border-radius:10px;border:1px solid #E3E3E3"></div>
                <p class="text-[#757575] mb-0" style="font-size:11px;margin:8px 0 0" id="map-coords">No location selected.</p>
            </div>
            <div class="flex items-center justify-end gap-2" style="padding:14px 22px">
                <button type="button" data-dismiss="modal" style="font-size:12px;padding:8px 16px"
                    class="bg-white border border-[#D9D9D9] rounded-lg cursor-pointer hover:bg-[#E8E8E8]">Cancel</button>
                <button type="button" id="map-use-btn" style="font-size:12px;padding:8px 16px"
                    class="bg-[#115640] text-white rounded-lg cursor-pointer hover:bg-[#0a3b2b] font-semibold">Use this location</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="application/json" id="customer-page-config">
{!! json_encode([
    'id' => $customer->id ?? null,
    'indexUrl' => route('aftersales.pages.customers.index'),
    'record' => $customer ? [
        'ref_province_id' => $customer->ref_province_id,
        'ref_region_id' => $customer->ref_region_id,
    ] : null,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
    const pageConfig = JSON.parse(document.getElementById('customer-page-config').textContent);
    const customerId = pageConfig.id;
    const customerRecord = pageConfig.record;
    const customerIndexUrl = pageConfig.indexUrl;

    function initSelect2() {
        if (!window.jQuery || !jQuery.fn?.select2) {
            return;
        }

        $('#customer-province, #customer-region').select2({
            width: '100%',
            dropdownCssClass: 'select2-dropdown-modern'
        });
    }

    function loadProvinces(selectedId) {
        $.ajax({
            url: '/api/aftersales/customers/provinces',
            method: 'GET',
            success: function (response) {
                const options = (response.data || []).map(p =>
                    `<option value="${p.id}" ${selectedId && String(p.id) === String(selectedId) ? 'selected' : ''}>${p.name}</option>`
                ).join('');
                $('#customer-province').html('<option value="">Select Province</option>' + options).trigger('change.select2');

                if (selectedId) {
                    loadRegions(selectedId, customerRecord?.ref_region_id);
                }
            }
        });
    }

    function loadRegions(provinceId, selectedId) {
        if (!provinceId) {
            $('#customer-region').prop('disabled', true).html('<option value="">Select Province first</option>').trigger('change.select2');
            return;
        }

        $('#customer-region').prop('disabled', true).html('<option value="">Loading...</option>').trigger('change.select2');

        $.ajax({
            url: '/api/aftersales/customers/regions',
            method: 'GET',
            data: { province_id: provinceId },
            success: function (response) {
                const rows = response.data || [];
                if (rows.length === 0) {
                    $('#customer-region').html('<option value="">No city data available</option>').trigger('change.select2');
                    return;
                }
                const options = rows.map(r =>
                    `<option value="${r.id}" ${selectedId && String(r.id) === String(selectedId) ? 'selected' : ''}>${r.name}</option>`
                ).join('');
                $('#customer-region').prop('disabled', false).html('<option value="">Select City</option>' + options).trigger('change.select2');
            },
            error: function () {
                $('#customer-region').html('<option value="">Failed to load city data</option>').trigger('change.select2');
            }
        });
    }

    // Accepts google.com/maps, maps.google.com and the maps.app.goo.gl / goo.gl/maps short links.
    function isGoogleMapsLink(value) {
        try {
            const url = new URL(String(value || '').trim());
            const host = url.hostname.toLowerCase();
            if (host === 'maps.app.goo.gl') return true;
            if (host === 'goo.gl') return url.pathname.startsWith('/maps');
            if (host === 'maps.google.com') return true;
            return /^(www\.)?google\.[a-z.]+$/.test(host) && url.pathname.startsWith('/maps');
        } catch (e) {
            return false;
        }
    }

    // ---- Map picker (Leaflet) ----
    let pickerMap = null;
    let pickerMarker = null;
    let pickedLatLng = null;

    function parseLatLng(link) {
        const m = String(link || '').match(/(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
        return m ? { lat: parseFloat(m[1]), lng: parseFloat(m[2]) } : null;
    }

    function setPicked(latlng, moveView) {
        pickedLatLng = latlng;
        if (!pickerMarker) {
            pickerMarker = L.marker(latlng, { draggable: true }).addTo(pickerMap);
            pickerMarker.on('dragend', function () { setPicked(pickerMarker.getLatLng(), false); });
        } else {
            pickerMarker.setLatLng(latlng);
        }
        if (moveView) pickerMap.setView(latlng, Math.max(pickerMap.getZoom(), 15));
        $('#map-coords').text('Selected: ' + latlng.lat.toFixed(6) + ', ' + latlng.lng.toFixed(6));
    }

    function searchPlace() {
        const q = $('#map-search').val().trim();
        if (!q) return;
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(res => {
                if (!res.length) { $('#map-coords').text('Place not found.'); return; }
                setPicked({ lat: parseFloat(res[0].lat), lng: parseFloat(res[0].lon) }, true);
            })
            .catch(() => $('#map-coords').text('Search failed. Click the map instead.'));
    }

    $('#btn-open-maps').on('click', function () {
        $('#mapPickerModal').modal('show');
    });

    // (The sidebar collapses for every modal through the global handler in layouts/app.blade.php.)
    $('#mapPickerModal').on('shown.bs.modal', function () {
        if (!pickerMap) {
            pickerMap = L.map('map-canvas').setView([-2.5, 118], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(pickerMap);
            pickerMap.on('click', function (e) { setPicked(e.latlng, false); });
        }
        pickerMap.invalidateSize();

        const existing = parseLatLng($('#customer-maps-link').val());
        if (existing) setPicked(existing, true);
    });

    $('#map-search-btn').on('click', searchPlace);
    $('#map-search').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); searchPlace(); }
    });

    $('#map-use-btn').on('click', function () {
        if (!pickedLatLng) {
            $('#map-coords').text('Please select a location on the map first.');
            return;
        }
        $('#customer-maps-link').val('https://www.google.com/maps?q=' + pickedLatLng.lat.toFixed(6) + ',' + pickedLatLng.lng.toFixed(6));
        $('#mapPickerModal').modal('hide');
    });

    $(function () {
        initSelect2();
        loadProvinces(customerRecord?.ref_province_id);

        $('#customer-province').on('change', function () {
            loadRegions($(this).val(), null);
        });

        $('#customer-form').on('submit', function (e) {
            e.preventDefault();

            if (!isGoogleMapsLink($('#customer-maps-link').val())) {
                Swal.fire({
                    title: 'Invalid link',
                    text: 'Google Maps Link must be a valid Google Maps URL (google.com/maps, maps.google.com, or maps.app.goo.gl).',
                    icon: 'warning',
                    confirmButtonColor: '#115640'
                });
                return;
            }

            if (!$('#customer-province').val() || !$('#customer-region').val()) {
                Swal.fire({
                    title: 'Incomplete data',
                    text: 'Please select Province and City.',
                    icon: 'warning',
                    confirmButtonColor: '#115640'
                });
                return;
            }

            Swal.fire({
                title: 'Save this customer?',
                text: 'Please make sure the data you entered is correct.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#115640',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                const payload = {
                    name: $('#customer-name').val(),
                    address: $('#customer-address').val(),
                    address_maps_url: $('#customer-maps-link').val() || null,
                    ref_province_id: $('#customer-province').val() || null,
                    ref_region_id: $('#customer-region').val() || null,
                    pic_name: $('#customer-pic-name').val(),
                    email: $('#customer-email').val(),
                    phone: $('#customer-phone').val(),
                };

                const isEdit = !!customerId;
                const url = isEdit ? `/api/aftersales/customers/${customerId}` : '/api/aftersales/customers';
                const method = isEdit ? 'PUT' : 'POST';

                $.ajax({
                    url,
                    method,
                    data: payload,
                    success: function () {
                        Swal.fire({
                            title: 'Saved!',
                            text: 'Customer data has been saved successfully.',
                            icon: 'success',
                            confirmButtonColor: '#115640'
                        }).then(function () {
                            window.location.href = customerIndexUrl;
                        });
                    },
                    error: function (xhr) {
                        // 422: show every field error from `errors` (e.g. address_maps_url / maps_link).
                        const fieldErrors = Object.values(xhr.responseJSON?.errors || {}).flat();
                        Swal.fire({
                            title: 'Failed',
                            text: fieldErrors.length ? fieldErrors.join('\n') : (xhr.responseJSON?.message || 'Failed to save customer'),
                            icon: 'error',
                            confirmButtonColor: '#115640'
                        });
                    }
                });
            });
        });
    });
</script>
@endsection
