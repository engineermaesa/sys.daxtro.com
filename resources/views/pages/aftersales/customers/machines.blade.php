@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-[#115640] font-semibold text-2xl">{{ $customer->name }} — Machine List</h1>
                <p class="text-[#757575] text-sm mt-1">
                    <a href="{{ route('aftersales.pages.customers.index') }}" class="hover:underline">Customer</a>
                    <span class="mx-1">&gt;</span>
                    <span class="font-semibold text-[#115640] underline">{{ $customer->name }} — Machine List</span>
                </p>
            </div>
            <a href="{{ route('aftersales.pages.customers.machines.create', $customer->id) }}"
                class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors cursor-pointer">
                <i class="bi bi-plus-lg"></i> New Machine
            </a>
        </div>

        {{-- TABLE --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-left uppercase text-xs font-bold">Machine Name</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Serial Number</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Model</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Warranty Period</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Warranty Start</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Warranty End</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Warranty Status</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">PM Contract</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">PM Frequency</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Action</th>
                    </tr>
                </thead>
                <tbody id="machine-table-body"></tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const customerId = {{ $customer->id }};
    const apiBaseUrl = '/api/aftersales/customers';
    const machineCreateBaseUrl = '{{ route('aftersales.pages.customers.machines.create', $customer->id) }}';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function machineBadge(active, activeLabel, inactiveLabel) {
        return active
            ? `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#E6F4EF] text-[#115640]">${activeLabel}</span>`
            : `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#FBEAEA] text-[#900B09]">${inactiveLabel}</span>`;
    }

    function pmContractBadge(status) {
        if (status === 'active') {
            return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#FFF3CD] text-[#8A6D00]">Active</span>`;
        }
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#F0F0F0] text-[#757575]">None</span>`;
    }

    function renderMachineRows(rows) {
        const $body = $('#machine-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="10" class="text-center p-4 text-[#757575]">No machines found</td></tr>');
            return;
        }

        const html = rows.map(row => `
            <tr class="border-t border-t-[#D9D9D9]">
                <td class="p-3 text-left text-black font-semibold">${escapeHtml(row.product_name) || '-'}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.serial_number) || '-'}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.product_type_name) || '-'}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.warranty_period) || '-'}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.warranty_start) || '-'}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.warranty_end) || '-'}</td>
                <td class="p-3 text-left">${machineBadge(row.warranty_active, 'Active', 'Expired')}</td>
                <td class="p-3 text-left">${pmContractBadge(row.pm_contract_status)}</td>
                <td class="p-3 text-left text-black">${row.pm_frequency ? escapeHtml(row.pm_frequency) : '<span class="text-[#B0B0B0]">&mdash;</span>'}</td>
                <td class="p-3 text-center">
                    <div class="dropdown">
                        <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"
                            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right mt-2">
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer" href="${machineCreateBaseUrl}?product=${row.id}">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a class="dropdown-item btn-delete-machine flex! items-center! gap-2! cursor-pointer text-red-600!" data-id="${row.id}">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');

        $body.html(html);
    }

    function loadMachines() {
        $('#machine-table-body').html('<tr><td colspan="10" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: `${apiBaseUrl}/${customerId}`,
            method: 'GET',
            success: function (response) {
                const products = response.data?.products || [];
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                renderMachineRows(products.map(p => ({
                    id: p.id,
                    product_name: p.product?.name,
                    serial_number: p.serial_number,
                    product_type_name: p.product_type?.name,
                    warranty_period: p.warranty_period,
                    warranty_start: p.warranty_start ? String(p.warranty_start).slice(0, 10) : null,
                    warranty_end: p.warranty_end ? String(p.warranty_end).slice(0, 10) : null,
                    warranty_active: !!p.warranty_end && new Date(p.warranty_end) >= today,
                    pm_contract_status: p.pm_contract_status,
                    pm_frequency: p.pm_frequency,
                })));
            },
            error: function (xhr) {
                console.error('Failed to load customer machines', xhr.responseJSON?.message || xhr.statusText);
                $('#machine-table-body').html('<tr><td colspan="10" class="text-center p-4 text-[#900B09]">Failed to load machines</td></tr>');
            }
        });
    }

    $(function () {
        loadMachines();

        $(document).on('click', '.btn-delete-machine', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Delete this machine?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#115640',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: `${apiBaseUrl}/products/${id}`,
                    method: 'DELETE',
                    success: function () {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Machine has been deleted.',
                            icon: 'success',
                            confirmButtonColor: '#115640'
                        });
                        loadMachines();
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: 'Failed',
                            text: xhr.responseJSON?.message || 'Failed to delete machine',
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
