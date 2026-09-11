{{-- STEP 4: SPAREPART --}}
<div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="4">
    <div class="flex items-center justify-between border-b border-[#D9D9D9] pb-3 mb-4">
        <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide mb-0">Sparepart</h6>
        <button type="button" id="btn-add-sparepart"
            class="border border-[#115640] text-[#115640] rounded-lg px-3 py-1.5 text-sm hover:bg-[#F5FAF8] cursor-pointer">
            <i class="bi bi-plus-lg"></i> Add Sparepart
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                    <th class="p-2 w-10">No</th>
                    <th class="p-2">Item Name</th>
                    <th class="p-2">Part Code</th>
                    <th class="p-2 w-24">Qty</th>
                    <th class="p-2 w-32">Unit</th>
                    <th class="p-2 w-10"></th>
                </tr>
            </thead>
            <tbody id="sparepart-rows"></tbody>
        </table>
    </div>
</div>

<script>
    let sparepartRowCount = 0;
    let sparepartsCache = [];

    function sparepartOptionsHtml() {
        return sparepartsCache.map(sp =>
            `<option value="${sp.id}" data-code="${escapeHtml(sp.part_number || '')}">${escapeHtml(sp.name)}</option>`
        ).join('');
    }

    function renumberSparepartRows() {
        $('#sparepart-rows tr').each(function (index) {
            $(this).find('.sparepart-row-label').text(`Sparepart ${index + 1}`);
        });
    }

    function addSparepartRow(prefill) {
        sparepartRowCount += 1;
        const rowNumber = sparepartRowCount;

        const html = `
            <tr class="border-t border-[#D9D9D9]" data-sparepart-row="${rowNumber}">
                <td class="p-2 sparepart-row-label">Sparepart ${rowNumber}</td>
                <td class="p-2">
                    <select class="sparepart-item w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        <option value="">Select Item</option>
                        ${sparepartOptionsHtml()}
                    </select>
                </td>
                <td class="p-2">
                    <input type="text" readonly placeholder="PRT-XXX-000" class="sparepart-code w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 bg-[#F5F5F5] focus:outline-none!">
                </td>
                <td class="p-2">
                    <input type="number" min="0" value="0" class="sparepart-qty w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                </td>
                <td class="p-2">
                    <select class="sparepart-unit w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        <option value="">Select</option>
                        <option value="pieces">Pieces</option>
                        <option value="set">Set</option>
                        <option value="lot">Lot</option>
                        <option value="kilogram">Kilogram</option>
                    </select>
                </td>
                <td class="p-2 text-center">
                    <button type="button" class="btn-remove-sparepart text-[#900B09] cursor-pointer" title="Remove row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        const $row = $(html);
        $('#sparepart-rows').append($row);

        if (prefill) {
            $row.find('.sparepart-item').val(String(prefill.ref_sparepart_id));
            $row.find('.sparepart-code').val(prefill.part_number || '');
            $row.find('.sparepart-qty').val(prefill.qty);
            $row.find('.sparepart-unit').val(prefill.unit);
        }

        renumberSparepartRows();
    }

    function loadSparepartOptions() {
        return $.ajax({
            url: '/api/aftersales/spareparts',
            method: 'GET',
            data: { per_page: 100 },
            success: function (response) {
                sparepartsCache = response.data || [];

                $('.sparepart-item').each(function () {
                    const current = $(this).val();
                    $(this).html('<option value="">Select Item</option>' + sparepartOptionsHtml());
                    if (current) {
                        $(this).val(current);
                    }
                });
            },
            error: function (xhr) {
                console.error('Failed to load spareparts', xhr.responseJSON?.message || xhr.statusText);
            }
        });
    }

    function prefillSparepartAndCostRows() {
        const parts = (EDIT_TICKET?.parts || []).filter(p => p.type === 'estimated');

        if (parts.length > 0) {
            parts.forEach(part => addSparepartRow({
                ref_sparepart_id: part.ref_sparepart_id,
                qty: part.qty,
                unit: part.unit,
                part_number: part.sparepart?.part_number,
            }));
        } else {
            addSparepartRow();
        }

        (EDIT_TICKET?.costs || []).forEach(cost => {
            const $row = $(`#cost-rows tr[data-cost-category="${cost.category}"]`);
            $row.find('.cost-amount').val(cost.amount);
            $row.find('.cost-currency').val(cost.currency);
            $row.find('.cost-remarks').val(cost.remarks || '');
        });

        recalculateCostTotal();
    }

    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('change', '.sparepart-item', function () {
            const code = $(this).find('option:selected').data('code') || '';
            $(this).closest('tr').find('.sparepart-code').val(code);
        });

        $('#btn-add-sparepart').on('click', function () {
            addSparepartRow();
        });

        $(document).on('click', '.btn-remove-sparepart', function () {
            $(this).closest('tr').remove();
            renumberSparepartRows();
        });
    });
</script>
