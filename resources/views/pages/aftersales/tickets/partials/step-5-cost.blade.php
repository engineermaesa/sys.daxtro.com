{{-- STEP 5: COST --}}
<div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="5">
    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Cost</h6>

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
                    <td class="p-2 font-semibold text-[#115640]">Total</td>
                    <td class="p-2 font-semibold text-[#115640]">
                        <span id="cost-total">Rp0</span>
                    </td>
                    <td class="p-2">
                        <select id="cost-total-currency" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 bg-white focus:outline-none!">
                            <option value="idr" selected>IDR</option>
                            <option value="usd">USD</option>
                            <option value="cny">CNY</option>
                        </select>
                    </td>
                    <td class="p-2 text-xs text-[#757575]">
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

    function recalculateCostTotal() {
        let totalIdr = 0;

        $('#cost-rows tr[data-cost-category]').each(function () {
            const amount = Number($(this).find('.cost-amount').val() || 0);
            const currency = $(this).find('.cost-currency').val() || 'idr';
            totalIdr += amount * costRateFor(currency);
        });

        const target = $('#cost-total-currency').val() || 'idr';
        const value = totalIdr / costRateFor(target);

        if (target === 'idr') {
            $('#cost-total').text('Rp' + Math.round(value).toLocaleString('id-ID'));
        } else {
            const symbol = target === 'usd' ? '$' : '¥';
            $('#cost-total').text(symbol + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('input change', '.cost-amount, .cost-currency, #cost-total-currency', recalculateCostTotal);
        $(document).on('click', '#cost-rate-refresh', loadCostRates);
        loadCostRates();
    });
</script>
