@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="mb-1">
            <h1 id="page-heading" class="text-[#115640] font-bold text-2xl">Sparepart</h1>
            <p class="text-[#757575] text-sm mt-1">
                <a href="{{ route('aftersales.pages.spareparts.index') }}" class="hover:underline">Sparepart</a>
                <span class="mx-1">&gt;</span>
                <span id="breadcrumb-current" class="font-semibold text-[#1E1E1E] underline">Add Sparepart</span>
            </p>
        </div>

        <form id="sparepart-form" class="mt-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- IDENTITY --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Identity</h6>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Sparepart Name<span class="text-red-600">*</span></label>
                        <input type="text" id="field-name" placeholder="e.g. Filter Drier 1/2 Inch" required
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Brand</label>
                        <input type="text" id="field-brand" placeholder="e.g. Emerson"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Supplier</label>
                        <input type="text" id="field-supplier" placeholder="Supplier name"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                </div>

                {{-- INVENTORY & PRICING --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Inventory & Pricing</h6>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Price (IDR)</label>
                        <input type="number" id="field-price" min="0" placeholder="0"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Stock</label>
                        <input type="number" id="field-stock" min="0" placeholder="0"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                        <p id="field-stock-hint" class="text-xs text-[#B0B0B0] mt-1" hidden>Stok hanya bisa diubah lewat menu Stock Movement, bukan dari sini.</p>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Minimum Stock</label>
                        <input type="number" id="field-min-stock" min="0" placeholder="0"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Rack Location</label>
                        <input type="text" id="field-rack-location" placeholder="e.g. A1-01"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                </div>
            </div>

            {{-- NAVIGATION --}}
            <div class="flex items-center justify-between mt-4">
                <a href="{{ route('aftersales.pages.spareparts.index') }}"
                    class="border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
                <button type="submit" id="btn-save-sparepart"
                    class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer">
                    <span id="btn-save-sparepart-label">Save Sparepart</span>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const sparepartId = new URLSearchParams(window.location.search).get('id');

    function formatApiErrors(xhr) {
        const errors = xhr.responseJSON?.errors;
        if (errors) {
            return Object.values(errors).flat().join('\n');
        }
        return xhr.responseJSON?.message || 'Terjadi kesalahan, silakan coba lagi.';
    }

    function enterEditMode() {
        $('#page-heading').text('Edit Sparepart');
        $('#breadcrumb-current').text('Edit Sparepart');
        $('#btn-save-sparepart-label').text('Update Sparepart');
        $('#field-stock').prop('disabled', true).addClass('bg-[#F5F5F5]');
        $('#field-stock-hint').prop('hidden', false);
    }

    function loadSparepartForEdit() {
        $.ajax({
            url: `/api/aftersales/spareparts/${sparepartId}`,
            method: 'GET',
            success: function (response) {
                const data = response.data || {};
                $('#field-name').val(data.name || '');
                $('#field-brand').val(data.brand || '');
                $('#field-supplier').val(data.supplier || '');
                $('#field-price').val(data.price || 0);
                $('#field-stock').val(data.stock || 0);
                $('#field-min-stock').val(data.min_stock || 0);
                $('#field-rack-location').val(data.rack_location || '');
            },
            error: function (xhr) {
                alert(formatApiErrors(xhr));
                window.location.href = '{{ route('aftersales.pages.spareparts.index') }}';
            }
        });
    }

    $(function () {
        if (sparepartId) {
            enterEditMode();
            loadSparepartForEdit();
        }

        $('#sparepart-form').on('submit', function (e) {
            e.preventDefault();

            const payload = {
                name: $('#field-name').val(),
                brand: $('#field-brand').val() || null,
                supplier: $('#field-supplier').val() || null,
                price: $('#field-price').val() || 0,
                min_stock: $('#field-min-stock').val() || 0,
                rack_location: $('#field-rack-location').val() || null,
            };

            if (!sparepartId) {
                payload.stock = $('#field-stock').val() || 0;
            }

            const savingLabel = sparepartId ? 'Updating...' : 'Saving...';
            const idleLabel = sparepartId ? 'Update Sparepart' : 'Save Sparepart';
            $('#btn-save-sparepart').prop('disabled', true);
            $('#btn-save-sparepart-label').text(savingLabel);

            $.ajax({
                url: sparepartId ? `/api/aftersales/spareparts/${sparepartId}` : '/api/aftersales/spareparts',
                method: sparepartId ? 'PUT' : 'POST',
                data: payload,
                success: function () {
                    window.location.href = '{{ route('aftersales.pages.spareparts.index') }}';
                },
                error: function (xhr) {
                    alert(formatApiErrors(xhr));
                    $('#btn-save-sparepart').prop('disabled', false);
                    $('#btn-save-sparepart-label').text(idleLabel);
                }
            });
        });
    });
</script>
@endsection
