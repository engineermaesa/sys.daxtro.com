{{-- STEP 4: SPAREPART --}}
<div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="4">
    <div class="flex items-center justify-between border-b border-[#D9D9D9] pb-3 mb-4">
        <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide mb-0">Sparepart</h6>
        <button type="button" id="btn-add-sparepart"
            class="border border-[#115640] text-[#115640] rounded-lg px-3 py-1.5 text-sm hover:bg-[#F5FAF8] cursor-pointer">
            <i class="bi bi-plus-lg"></i> Add Sparepart
        </button>
    </div>

    {{-- ALERT STOK TIDAK CUKUP: dikontrol checkSparepartStock() / showSparepartStockAlert() di bawah --}}
    <div id="sparepart-stock-alert" class="hidden items-start gap-3 rounded-lg mb-4" style="padding: 12px 16px; background-color: #FFF3CD; border: 1px solid #F5DDB3; color: #8A6D00;">
        <i class="bi bi-exclamation-triangle text-lg" style="color: #8A6D00;"></i>
        <div>
            <p class="font-bold text-xs mb-1" style="color: #8A6D00;">Insufficient sparepart stock</p>
            <p id="sparepart-stock-alert-message" class="text-xs mb-0" style="color: #8A6D00;"></p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                    <th class="p-2" style="width: 110px;">No</th>
                    <th class="p-2">Item Name</th>
                    <th class="p-2">Part Code</th>
                    <th class="p-2" style="width: 140px;">Qty</th>
                    <th class="p-2 w-32">Unit<span class="text-red-600">*</span></th>
                    <th class="p-2 w-36 text-right">Unit Price</th>
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

    const SPAREPART_STOCK_MESSAGE = 'One or more requested items cannot be provided by Aftersales. Update the quantity or select another item before continuing.';

    function sparepartOptionsHtml() {
        return sparepartsCache.map(sp =>
            `<option value="${sp.id}" data-code="${escapeHtml(sp.part_number || '')}" data-price="${Number(sp.price || 0)}">${escapeHtml(sp.name)}</option>`
        ).join('');
    }

    function showSparepartStockAlert(message) {
        $('#sparepart-stock-alert-message').text(message || SPAREPART_STOCK_MESSAGE);
        $('#sparepart-stock-alert').removeClass('hidden').css('display', 'flex');
    }

    function hideSparepartStockAlert() {
        $('#sparepart-stock-alert').addClass('hidden').css('display', '');
    }

    // stok yang boleh diminta untuk satu sparepart; null = belum diketahui.
    // BE memotong stok saat Save Ticket, jadi saat edit stok tersedia = stok gudang + qty yang
    // sudah dipegang tiket ini (part type 'estimated').
    function sparepartAvailableStock(id) {
        const sp = sparepartsCache.find(x => String(x.id) === String(id));
        if (!sp) return null;
        const held = (typeof EDIT_TICKET !== 'undefined' && EDIT_TICKET?.parts || [])
            .filter(p => p.type === 'estimated' && String(p.ref_sparepart_id) === String(id))
            .reduce((sum, p) => sum + Number(p.qty || 0), 0);
        return Number(sp.stock || 0) + held;
    }

    // cek qty (dijumlahkan per sparepart) terhadap stok; true bila semua cukup
    function checkSparepartStock() {
        const requested = {};
        $('#sparepart-rows tr').each(function () {
            const id = $(this).find('.sparepart-item').val();
            if (id) requested[id] = (requested[id] || 0) + Number($(this).find('.sparepart-qty').val() || 0);
        });

        let ok = true;
        $('#sparepart-rows tr').each(function () {
            const id = $(this).find('.sparepart-item').val();
            const available = id ? sparepartAvailableStock(id) : null;
            const over = available !== null && requested[id] > available;

            $(this).find('.sparepart-qty').css('border-color', over ? '#EC221F' : '');
            $(this).find('.sparepart-stock-hint').toggleClass('hidden', !over).text(over ? `Stock available: ${available}` : '');
            if (over) ok = false;
        });

        if (ok) hideSparepartStockAlert(); else showSparepartStockAlert();
        return ok;
    }


    function checkSparepartUnits(focusInvalid = false) {
        let firstInvalid = null;
        $('#sparepart-rows tr').each(function () {
            const $row = $(this);
            const $unit = $row.find('.sparepart-unit');
            const invalid = !$unit.val();
            $unit.prop('required', true).attr('aria-invalid', invalid ? 'true' : 'false')
                .css('border-color', invalid ? '#EC221F' : '');
            $row.find('.sparepart-unit-error').toggleClass('hidden', !invalid);
            if (invalid && !firstInvalid) firstInvalid = $unit;
        });
        if (focusInvalid && firstInvalid) firstInvalid.trigger('focus');
        return !firstInvalid;
    }

    // harga satuan (IDR) sebagai teks, tidak bisa diedit
    // (nilai tetap IDR di data-unit-price; hanya tampilan yang ikut Total Currency)
    function formatRupiah(value) {
        const n = Number(value || 0);
        if (!(n > 0)) return '-';
        return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }

    function refreshSparepartPrices() {
        $('#sparepart-rows tr').each(function () {
            $(this).find('.sparepart-price').text(formatRupiah($(this).attr('data-unit-price')));
        });
    }

    function setSparepartRowPrice($row, price) {
        $row.attr('data-unit-price', Number(price || 0));
        $row.find('.sparepart-price').text(formatRupiah(price));
    }

    function masterSparepartPrice(id) {
        const sp = sparepartsCache.find(x => String(x.id) === String(id));
        return sp ? Number(sp.price || 0) : 0;
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
                <td class="px-2 py-4 sparepart-row-label whitespace-nowrap">Sparepart ${rowNumber}</td>
                <td class="px-2 py-4">
                    <select class="sparepart-item w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        <option value="">Select Item</option>
                        ${sparepartOptionsHtml()}
                    </select>
                </td>
                <td class="px-2 py-4">
                    <input type="text" readonly placeholder="PRT-XXX-000" class="sparepart-code w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 bg-[#F5F5F5] focus:outline-none!">
                </td>
                <td class="px-2 py-4" style="position: relative;">
                    <input type="number" min="1" step="1" value="1" class="sparepart-qty w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                    <p class="sparepart-stock-hint hidden mb-0 whitespace-nowrap" style="position: absolute; left: 8px; top: calc(100% - 14px); font-size: 11px; line-height: 14px; color: #8A6D00;"></p>
                </td>
                <td class="px-2 py-4">
                    <select aria-label="Unit" required class="sparepart-unit w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        <option value="">Select</option>
                        <option value="pieces">Pieces</option>
                        <option value="set">Set</option>
                        <option value="lot">Lot</option>
                        <option value="kilogram">Kilogram</option>
                    </select>
                    <p class="sparepart-unit-error hidden mb-0" style="font-size:11px;color:#EC221F;margin-top:4px;" role="alert">Please select a unit.</p>
                </td>
                <td class="px-2 py-4 text-right text-[#4B5563] sparepart-price">-</td>
                <td class="px-2 py-4 text-center">
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
            // pakai harga snapshot tiket bila ada, kalau tidak harga master saat ini
            setSparepartRowPrice($row, prefill.unit_price !== undefined && prefill.unit_price !== null
                ? prefill.unit_price
                : masterSparepartPrice(prefill.ref_sparepart_id));
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

                // harga master baru tersedia: isi harga baris yang belum punya harga
                $('#sparepart-rows tr').each(function () {
                    const id = $(this).find('.sparepart-item').val();
                    if (id && !Number($(this).attr('data-unit-price'))) {
                        setSparepartRowPrice($(this), masterSparepartPrice(id));
                    }
                });
                if (typeof recalculateCostTotal === 'function') recalculateCostTotal();
                checkSparepartStock();
            },
            error: function (xhr) {
                console.error('Failed to load spareparts', xhr.responseJSON?.message || xhr.statusText);
            }
        });
    }

    function prefillSparepartAndCostRows() {
        // mata uang tampilan Final Cost yang terakhir dipilih untuk tiket ini
        if (EDIT_TICKET?.cost_display_currency) {
            $('#cost-total-currency').val(EDIT_TICKET.cost_display_currency);
        }
        const parts = (EDIT_TICKET?.parts || []).filter(p => p.type === 'estimated' && Number(p.qty) > 0);

        if (parts.length > 0) {
            parts.forEach(part => addSparepartRow({
                ref_sparepart_id: part.ref_sparepart_id,
                qty: part.qty,
                unit: part.unit,
                part_number: part.sparepart?.part_number,
                unit_price: part.unit_price,
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
        checkSparepartStock();
    }

    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('change', '.sparepart-item', function () {
            const code = $(this).find('option:selected').data('code') || '';
            const $row = $(this).closest('tr');
            $row.find('.sparepart-code').val(code);
            setSparepartRowPrice($row, $(this).find('option:selected').data('price') || 0);
            if (typeof recalculateCostTotal === 'function') recalculateCostTotal();
            checkSparepartStock();
            checkSparepartUnits();
        });

        $(document).on('input change', '.sparepart-qty, .sparepart-unit', function () {
            if (typeof recalculateCostTotal === 'function') recalculateCostTotal();
            checkSparepartStock();
            checkSparepartUnits();
        });

        $('#btn-add-sparepart').on('click', function () {
            addSparepartRow();
        });

        $(document).on('click', '.btn-remove-sparepart', function () {
            $(this).closest('tr').remove();
            renumberSparepartRows();
            if (typeof recalculateCostTotal === 'function') recalculateCostTotal();
            checkSparepartStock();
        });
    });
</script>
