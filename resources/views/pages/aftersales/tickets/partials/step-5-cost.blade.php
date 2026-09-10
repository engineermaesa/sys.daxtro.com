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
                            </select>
                        </td>
                        <td class="p-2">
                            <input type="text" placeholder="Remarks..." class="cost-remarks w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        </td>
                    </tr>
                @endforeach
                <tr class="border-t border-[#D9D9D9] bg-[#F5FAF8]">
                    <td class="p-2 font-semibold text-[#115640]">Total</td>
                    <td class="p-2 font-semibold text-[#115640]" colspan="3">
                        <span id="cost-total-idr">Rp0</span>
                        <span class="mx-2 text-[#D9D9D9]">|</span>
                        <span id="cost-total-usd">$0.00</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function recalculateCostTotal() {
        const totals = { idr: 0, usd: 0 };

        $('#cost-rows tr[data-cost-category]').each(function () {
            const amount = Number($(this).find('.cost-amount').val() || 0);
            const currency = $(this).find('.cost-currency').val() || 'idr';
            totals[currency] = (totals[currency] || 0) + amount;
        });

        $('#cost-total-idr').text('Rp' + totals.idr.toLocaleString('id-ID'));
        $('#cost-total-usd').text('$' + totals.usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('input change', '.cost-amount, .cost-currency', recalculateCostTotal);
    });
</script>
