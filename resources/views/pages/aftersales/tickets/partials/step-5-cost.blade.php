{{-- STEP 5: COST --}}
<div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="5">
    <div class="flex items-center justify-between flex-wrap border-b border-[#D9D9D9] pb-3 mb-4" style="gap: 12px;">
        <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide">Cost</h6>
        <div class="flex items-center" style="gap: 8px;">
            <label for="cost-total-currency" class="text-xs font-semibold text-[#757575] uppercase">Total Currency</label>
            <select id="cost-total-currency" class="border border-[#D9D9D9] rounded-lg px-3 py-1.5 bg-white focus:outline-none!">
                <option value="idr" selected>IDR</option>
                <option value="usd">USD</option>
                <option value="cny">CNY</option>
            </select>
        </div>
    </div>

    {{-- RINCIAN SPAREPART (read-only, dari Step 4) --}}
    <div style="margin-bottom: 24px;">
        <p class="font-semibold" style="margin-bottom: 8px;">Sparepart</p>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                        <th class="p-2">Item</th>
                        <th class="p-2 w-24 text-right">Qty</th>
                        <th class="p-2 w-28">Unit</th>
                        <th class="p-2 w-40 text-right">Unit Price</th>
                        <th class="p-2 w-44 text-right">Total</th>
                    </tr>
                </thead>
                <tbody id="cost-sparepart-rows"></tbody>
                <tfoot>
                    <tr class="border-t border-[#D9D9D9] bg-[#F5FAF8]">
                        <td class="p-2 font-semibold text-[#115640]" colspan="4">Total Sparepart</td>
                        <td id="cost-sparepart-total" class="p-2 text-right font-semibold text-[#115640]">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                    <th class="p-2">Category</th>
                    <th class="p-2">Amount</th>
                    <th class="p-2 w-28">Unit of Material</th>
                    <th class="p-2">Remarks</th>
                </tr>
            </thead>
            <tbody id="cost-rows">
                @foreach (['labor' => 'Labor', 'accommodation' => 'Accommodation', 'transportation' => 'Transportation', 'other' => 'Other'] as $key => $label)
                    <tr class="border-t border-[#D9D9D9]" data-cost-category="{{ $key }}">
                        <td class="p-2 font-semibold">{{ $label }}</td>
                        <td class="p-2">
                            <input type="number" min="0" value="0" class="cost-amount w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        </td>
                        <td class="p-2">
                            <select class="cost-currency w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                                <option value="idr" selected>IDR</option>
                                <option value="usd">USD</option>
                                <option value="cny">CNY</option>
                            </select>
                        </td>
                        <td class="p-2">
                            <input type="text" placeholder="Remarks..." class="cost-remarks w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        </td>
                    </tr>
                @endforeach
                <tr class="border-t border-[#D9D9D9] bg-[#F5FAF8]">
                    <td class="p-2 font-semibold text-[#115640]">Total Category</td>
                    <td class="p-2 font-semibold text-[#115640]" colspan="3">
                        <span id="cost-total-category">Rp0</span>
                    </td>
                </tr>
                <tr class="border-t-2 border-[#115640] bg-[#E7F3EE]">
                    <td class="p-2 font-bold text-[#115640] uppercase">Final Cost</td>
                    <td class="p-2 font-bold text-[#115640]">
                        <span id="cost-total" style="font-size: 1.1em;">Rp0</span>
                        <span class="block text-[11px] font-normal text-[#4B5563]">Total category + total sparepart</span>
                    </td>
                    <td class="p-2 text-xs text-[#757575]" colspan="2">
                        <span id="cost-rate-info">Loading exchange rates...</span>
                        <button type="button" id="cost-rate-refresh" class="underline text-[#115640] cursor-pointer ml-1">Refresh</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    // IDR value of 1 unit of each currency. Replaced by live rates from /api/aftersales/tickets/exchange-rates.
    var COST_RATES = { idr: 1, usd: 16000, cny: 2250 };
    var COST_RATES_LOADED = false;

    function costRateInfoText(data) {
        const fmt = (n) => 'Rp' + Number(n).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        const asOf = data.as_of ? ' (' + data.as_of + ')' : (data.source === 'fallback' ? ' (default rate)' : '');
        return '1 USD = ' + fmt(data.idr_per_usd) + ' · 1 CNY = ' + fmt(data.idr_per_cny) + asOf;
    }

    function loadCostRates() {
        $('#cost-rate-info').text('Loading exchange rates...');
        return $.ajax({
            url: '/api/aftersales/tickets/exchange-rates',
            method: 'GET',
            headers: { Accept: 'application/json' },
            success: function (res) {
                const d = res.data || {};
                if (d.idr_per_usd > 0 && d.idr_per_cny > 0) {
                    COST_RATES = { idr: 1, usd: Number(d.idr_per_usd), cny: Number(d.idr_per_cny) };
                    COST_RATES_LOADED = true;
                    $('#cost-rate-info').text(costRateInfoText(d));
                    recalculateCostTotal();
                }
            },
            error: function () {
                $('#cost-rate-info').text('Failed to load exchange rates. Using default rates.');
            },
        });
    }

    function costRateFor(currency) {
        return COST_RATES[currency] || 1;
    }

    // rincian sparepart dari Step 4 (qty x harga satuan); mengembalikan total dalam IDR
    function renderCostSpareparts() {
        const unitLabel = { pieces: 'Pieces', set: 'Set', lot: 'Lot', kilogram: 'Kilogram' };
        const target = $('#cost-total-currency').val() || 'idr';
        // harga satuan & total per baris tetap IDR; hanya Total Sparepart yang ikut dropdown
        const fmt = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        let total = 0;
        let html = '';

        $('#sparepart-rows tr').each(function () {
            const itemId = $(this).find('.sparepart-item').val();
            const qty = Number($(this).find('.sparepart-qty').val() || 0);
            const unit = $(this).find('.sparepart-unit').val();
            if (!itemId || !qty) return;

            const name = $(this).find('.sparepart-item option:selected').text();
            const price = Number($(this).attr('data-unit-price') || 0);
            const line = qty * price;
            total += line;

            html += '<tr class="border-t border-[#D9D9D9]">'
                + '<td class="p-2">' + escapeHtml(name) + '</td>'
                + '<td class="p-2 text-right">' + qty + '</td>'
                + '<td class="p-2">' + escapeHtml(unitLabel[unit] || '-') + '</td>'
                + '<td class="p-2 text-right text-[#4B5563]">' + (price > 0 ? fmt(price) : '-') + '</td>'
                + '<td class="p-2 text-right font-semibold">' + (price > 0 ? fmt(line) : '-') + '</td>'
                + '</tr>';
        });

        if (!html) {
            html = '<tr class="border-t border-[#D9D9D9]"><td class="p-2 text-[#9CA3AF]" colspan="5">No sparepart added.</td></tr>';
        }

        $('#cost-sparepart-rows').html(html);
        $('#cost-sparepart-total').text(total > 0 ? formatCostTotal(total, target) : '-');
        return total;
    }

    function formatCostTotal(valueIdr, target) {
        const value = valueIdr / costRateFor(target);
        if (target === 'idr') {
            return 'Rp' + Math.round(value).toLocaleString('id-ID');
        }
        const symbol = target === 'usd' ? '$' : '\u00A5';
        return symbol + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalculateCostTotal() {
        if (typeof refreshSparepartPrices === 'function') refreshSparepartPrices();
        const sparepartIdr = renderCostSpareparts();
        let categoryIdr = 0;

        $('#cost-rows tr[data-cost-category]').each(function () {
            const amount = Number($(this).find('.cost-amount').val() || 0);
            const currency = $(this).find('.cost-currency').val() || 'idr';
            categoryIdr += amount * costRateFor(currency);
        });

        const target = $('#cost-total-currency').val() || 'idr';
        $('#cost-total-category').text(formatCostTotal(categoryIdr, target));
        $('#cost-total').text(formatCostTotal(categoryIdr + sparepartIdr, target));
    }

    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('input change', '.cost-amount, .cost-currency, #cost-total-currency', recalculateCostTotal);
        $(document).on('click', '#cost-rate-refresh', loadCostRates);
        loadCostRates();
    });
</script>
