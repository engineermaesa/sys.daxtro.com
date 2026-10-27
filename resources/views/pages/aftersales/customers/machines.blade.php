@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <a href="{{ route('aftersales.pages.customers.index') }}" class="sm:hidden inline-flex items-center gap-2 text-[#115640] font-semibold mb-3">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div class="min-w-0">
                <h1 class="text-[#115640] font-semibold text-2xl">{{ $customer->name }} — Machine List</h1>
                <p class="hidden sm:block text-[#757575] text-sm mt-1">
                    <a href="{{ route('aftersales.pages.customers.index') }}" class="hover:underline">Customer</a>
                    <span class="mx-1">&gt;</span>
                    <span class="font-semibold text-[#115640] underline">{{ $customer->name }} — Machine List</span>
                </p>
                {{-- Filled from the customer detail API: Lead no longer Deal badge and Open in Google Maps. --}}
                <div id="machines-header-extras" class="flex items-center gap-2 mt-2"></div>
            </div>
            <a href="{{ route('aftersales.pages.customers.machines.create', $customer->id) }}"
                class="self-start sm:self-auto shrink-0 whitespace-nowrap bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors cursor-pointer">
                <i class="bi bi-plus-lg"></i> New Machine
            </a>
        </div>

        {{-- TABLE --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-center uppercase text-xs font-bold">Machine Name</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Brand</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Serial Number</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Model</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Warranty Period</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Warranty Start</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Warranty End</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Warranty Status</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">PM Contract</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">PM Frequency</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Action</th>
                    </tr>
                </thead>
                <tbody id="machine-table-body"></tbody>
            </table>
        </div>
    </div>
</section>

{{-- Machine details --}}
<div class="modal fade" id="machineDetailModal" tabindex="-1" role="dialog" aria-labelledby="machine-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content rounded-2xl! border-0!">
            <div class="modal-header">
                <h5 id="machine-detail-title" class="text-[#115640] font-semibold">Machine Detail</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="border border-[#D9D9D9] rounded-lg bg-white p-2 cursor-pointer"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body max-h-[70vh] overflow-y-auto">
                <dl id="machine-detail-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></dl>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" class="border border-[#D9D9D9] rounded-lg px-4 py-2 bg-white cursor-pointer">Close</button>
                <a id="machine-detail-edit" class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633]">Edit Machine</a>
            </div>
        </div>
    </div>
</div>

{{-- Move Machine modal --}}
<div class="modal fade" id="moveMachineModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:520px">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div style="padding:22px 26px 0">
                <div class="flex items-start justify-between">
                    <div>
                        <h5 class="text-[#115640] mb-1" style="font-size:20px;font-weight:500">Move Machine</h5>
                        <p class="text-[#9A9A9A] mb-0" style="font-size:11px" id="move-machine-subtitle"></p>
                    </div>
                    <button type="button" data-dismiss="modal" aria-label="Close"
                        style="width:32px;height:32px;font-size:12px"
                        class="border border-[#D9D9D9] rounded-lg bg-white flex items-center justify-center cursor-pointer"><i class="bi bi-x-lg"></i></button>
                </div>

                <div id="move-machine-error" role="alert" aria-live="assertive" aria-atomic="true" tabindex="-1" hidden
                    style="margin-top:16px;padding:12px 14px;border:1px solid #F0B8B3;border-radius:8px;background:#FBEAEA;color:#900B09;font-size:13px;line-height:1.5">
                    <strong class="block mb-1"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Unable to move machine</strong>
                    <span id="move-machine-error-message"></span>
                </div>

                <div style="margin-top:18px">
                    <label class="block font-semibold text-[#2D3748] mb-1" style="font-size:12px">Move to<span class="text-red-600">*</span></label>
                    <select id="move-machine-target" style="width:100%">
                        <option value=""></option>
                    </select>
                </div>

                <div style="margin-top:14px">
                    <label class="block font-semibold text-[#2D3748] mb-1" style="font-size:12px">Note</label>
                    <textarea id="move-machine-note" rows="4" placeholder="Add a note about this machine transfer"
                        style="width:100%;font-size:12px;padding:10px 12px;border:1px solid #D9D9D9;border-radius:10px"
                        class="focus:outline-none!"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2" style="padding:16px 26px 22px">
                <button type="button" data-dismiss="modal" style="font-size:12px;padding:8px 18px"
                    class="bg-white border border-[#D9D9D9] rounded-lg cursor-pointer font-semibold hover:bg-[#E8E8E8]">Cancel</button>
                <button type="button" id="move-machine-submit" style="font-size:12px;padding:8px 18px"
                    class="bg-[#115640] text-white rounded-lg cursor-pointer font-semibold hover:bg-[#0a3b2b]">Move Machine</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="application/json" id="machines-page-config">
{!! json_encode([
    'id' => $customer->id,
    'name' => $customer->name,
    'machineCreateUrl' => route('aftersales.pages.customers.machines.create', $customer->id),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}
</script>
<script>
    const pageConfig = JSON.parse(document.getElementById('machines-page-config').textContent);
    const customerId = pageConfig.id;
    const apiBaseUrl = '/api/aftersales/customers';
    const machineCreateBaseUrl = pageConfig.machineCreateUrl;
    const machinesById = new Map();

    /*
     * TODO(API) — backend contract still pending (PRD v2.2 IM-74..83):
     *  GET /api/aftersales/customers/{customer} -> loadMachines(): products[] must also return is_daxtro (bool) and compressor (string|null).
     *  Until then is_daxtro defaults to true (every existing machine is a Daxtro machine).
     */

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
        if (status === 'premium') {
            return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#E6F4EF] text-[#115640]">Premium</span>`;
        }
        if (status === 'basic') {
            return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#FFF3CD] text-[#8A6D00]">Basic</span>`;
        }
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#F0F0F0] text-[#757575]">None</span>`;
    }


    function renderMachineDetails(machine) {
        const daxtro = machine.is_daxtro;
        const fields = [
            ['Machine Name', machine.product_name],
            ['Daxtro Machine', daxtro ? 'Yes' : 'No'],
            ['Model', machine.product_type_name],
            ...(daxtro ? [['Serial Number', machine.serial_number]] : [['Brand', machine.brand]]),
            ['Capacity (ton)', machine.capacity],
            ['Industry', machine.industry?.name],
            ['Compressor', machine.compressor],
            ['Refrigerant Type', machine.refrigerant],
            ...(daxtro ? [
                ['Warranty Period', machine.warranty_period],
                ['Warranty Start', machine.warranty_start],
                ['Warranty End', machine.warranty_end],
                ['Warranty Status', machine.warranty_end ? (machine.warranty_active ? 'Active' : 'Expired') : '-'],
                ['PM Contract', { none: 'None', basic: 'Basic', premium: 'Premium' }[machine.pm_contract_status] || machine.pm_contract_status],
                ['PM Frequency', machine.pm_frequency],
            ] : []),
            ['Note', machine.note],
        ];

        $('#machine-detail-fields').html(fields.map(([label, value]) => `
            <div class="${label === 'Note' ? 'sm:col-span-2' : ''} min-w-0">
                <dt class="text-xs font-semibold text-[#757575] mb-1">${label}</dt>
                <dd class="text-sm text-[#1E1E1E] whitespace-pre-wrap break-words">${escapeHtml(value ?? '') || '-'}</dd>
            </div>
        `).join(''));
        $('#machine-detail-edit').attr('href', `${machineCreateBaseUrl}?product=${machine.id}`);
    }

    function renderMachineRows(rows) {
        const $body = $('#machine-table-body');
        machinesById.clear();
        (rows || []).forEach(row => machinesById.set(String(row.id), row));

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="11" class="text-center p-4 text-[#757575]">No machines found</td></tr>');
            return;
        }

        const dash = '<span class="text-[#B0B0B0]">&mdash;</span>';
        const html = rows.map(row => `
            <tr class="border-t border-t-[#D9D9D9]">
                <td class="p-3 text-left text-black font-semibold">${escapeHtml(row.product_name) || '-'}${row.is_daxtro ? '' : `<div class="text-xs font-normal text-[#757575]">Non-Daxtro${row.brand ? ' &middot; ' + escapeHtml(row.brand) : ''}</div>`}</td>
                <td class="p-3 text-center text-black">${escapeHtml(row.brand) || '-'}</td>
                <td class="p-3 text-center text-black">${row.is_daxtro ? (escapeHtml(row.serial_number) || '-') : dash}</td>
                <td class="p-3 text-center text-black">${escapeHtml(row.product_type_name) || '-'}</td>
                <td class="p-3 text-center text-black">${row.is_daxtro ? (escapeHtml(row.warranty_period) || '-') : dash}</td>
                <td class="p-3 text-center text-black">${row.is_daxtro ? (escapeHtml(row.warranty_start) || '-') : dash}</td>
                <td class="p-3 text-center text-black">${row.is_daxtro ? (escapeHtml(row.warranty_end) || '-') : dash}</td>
                <td class="p-3 text-center">${row.is_daxtro ? machineBadge(row.warranty_active, 'Active', 'Expired') : dash}</td>
                <td class="p-3 text-center">${row.is_daxtro ? pmContractBadge(row.pm_contract_status) : dash}</td>
                <td class="p-3 text-center text-black">${row.is_daxtro && row.pm_frequency ? escapeHtml(row.pm_frequency) : dash}</td>
                <td class="p-3 text-center">
                    <div class="dropdown">
                        <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"
                            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right mt-2">
                            <button type="button" class="dropdown-item btn-detail-machine flex! items-center! gap-2! cursor-pointer" data-id="${row.id}">
                                <i class="bi bi-eye"></i> Detail
                            </button>
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer" href="${machineCreateBaseUrl}?product=${row.id}">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a class="dropdown-item btn-move-machine flex! items-center! gap-2! cursor-pointer"
                                data-id="${row.id}" data-name="${escapeHtml(row.product_name)}" data-type="${escapeHtml(row.product_type_name)}">
                                <i class="bi bi-arrow-left-right"></i> Move to Another Customer
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
        $('#machine-table-body').html('<tr><td colspan="11" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: `${apiBaseUrl}/${customerId}`,
            method: 'GET',
            success: function (response) {
                const customer = response.data || {};
                const products = customer.products || [];
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                renderHeaderExtras(customer);

                renderMachineRows(products.map(p => ({
                    ...p,
                    id: p.id,
                    // Machine Name is typed by the user; Product name is only a fallback for older/Deal machines.
                    product_name: p.machine_name || p.product?.name,
                    is_daxtro: p.is_daxtro !== false && p.is_daxtro !== 0, // TODO(API): returned by the customer detail endpoint
                    compressor: p.compressor ?? null,                       // TODO(API): returned by the customer detail endpoint
                    brand: p.brand,
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
                $('#machine-table-body').html('<tr><td colspan="11" class="text-center p-4 text-[#900B09]">Failed to load machines</td></tr>');
            }
        });
    }

    function setMachineMoveError(message = '') {
        const alert = document.getElementById('move-machine-error');
        document.getElementById('move-machine-error-message').textContent = message;
        alert.hidden = !message;
        if (message) {
            alert.focus({ preventScroll: true });
            alert.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    function renderHeaderExtras(customer) {
        let html = '';
        if (customer.lead_no_longer_deal) {
            html += '<span style="background:#FBE0DF;color:#C0281F;font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px">Lead no longer Deal</span>';
        }
        if (customer.address_maps_url) {
            html += `<a href="${escapeHtml(customer.address_maps_url)}" target="_blank" rel="noopener noreferrer"
                class="bg-white border border-[#D9D9D9] rounded-lg px-3 py-1 text-xs hover:bg-[#E8E8E8] transition-colors">
                <i class="bi bi-geo-alt"></i> Open in Google Maps</a>`;
        }
        $('#machines-header-extras').html(html);
    }

    $(function () {
        loadMachines();

        $(document).on('click', '.btn-detail-machine', function () {
            const machine = machinesById.get(String($(this).data('id')));
            if (!machine) return;
            renderMachineDetails(machine);
            $('#machineDetailModal').modal('show');
        });

        // ---- Move Machine (M04) ----
        let moveMachineId = null;

        function initMoveTarget() {
            const $target = $('#move-machine-target');
            if ($target.data('select2')) return;
            if (!window.jQuery || !jQuery.fn?.select2) return;

            $target.select2({
                width: '100%',
                dropdownParent: $('#moveMachineModal'),
                placeholder: 'Select destination customer',
                allowClear: true,
                ajax: {
                    url: apiBaseUrl,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ search: params.term || '', per_page: 20, page: params.page || 1 }),
                    processResults: function (response, params) {
                        params.page = params.page || 1;
                        const results = (response.data || [])
                            .filter(c => String(c.id) !== String(customerId))
                            .map(c => ({ id: c.id, text: c.name }));
                        return { results, pagination: { more: response.current_page < response.last_page } };
                    }
                }
            });
        }

        $(document).on('click', '.btn-move-machine', function () {
            moveMachineId = $(this).data('id');
            const name = $(this).data('name') || '';
            const type = $(this).data('type') || '';
            $('#move-machine-subtitle').text([name, type].filter(Boolean).join(' | '));
            $('#move-machine-note').val(`Previously owned by ${pageConfig.name}`);
            setMachineMoveError();
            initMoveTarget();
            $('#move-machine-target').val(null).trigger('change');
            $('#moveMachineModal').modal('show');
        });

        $('#move-machine-submit').on('click', function () {
            if (!$('#move-machine-target').val()) {
                Swal.fire({
                    title: 'Incomplete data',
                    text: 'Please select the destination customer.',
                    icon: 'warning',
                    confirmButtonColor: '#115640'
                });
                return;
            }

            const destination = $('#move-machine-target').find('option:selected').text();
            const $submit = $('#move-machine-submit').prop('disabled', true);
            setMachineMoveError();

            // M04: the row is only removed after the server confirms (reload below), never optimistically.
            $.ajax({
                url: `${apiBaseUrl}/products/${moveMachineId}/move`,
                method: 'POST',
                dataType: 'json',
                headers: { Accept: 'application/json' },
                data: {
                    customer_id: $('#move-machine-target').val(),
                    note: $('#move-machine-note').val() || null,
                },
                success: function () {
                    $('#moveMachineModal').modal('hide');
                    Swal.fire({
                        title: 'Machine moved',
                        text: `The machine and all its details were moved to ${destination}.`,
                        icon: 'success',
                        confirmButtonColor: '#115640'
                    });
                    loadMachines();
                },
                error: function (xhr) {
                    // 422 (open tickets / same destination): keep the modal open and show the message.
                    const fieldErrors = Object.values(xhr.responseJSON?.errors || {}).flat();
                    const message = fieldErrors.length ? fieldErrors.join(' ') : (xhr.responseJSON?.message || 'Failed to move machine');
                    setMachineMoveError(message);
                },
                complete: function () {
                    $submit.prop('disabled', false);
                }
            });
        });

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
