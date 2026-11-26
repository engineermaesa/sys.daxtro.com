@php
    $gross = $discountItem ? $discountItem->unit_price * $discountItem->qty : 0;
    $discountAmount = $discountItem?->line_total !== null
        ? $gross - $discountItem->line_total
        : $gross * ($discountItem?->discount_pct ?? 0) / 100;
@endphp
<td class="p-2 lg:p-3 min-w-[145px]">
    <div class="flex items-center">
        <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-l-lg">Rp</span>
        <input type="text" inputmode="decimal" aria-label="Discount in rupiah"
            class="p-2 border border-[#D9D9D9] w-full rounded-r-lg item-discount-amount"
            value="{{ $discountItem ? rtrim(rtrim(number_format(max(0, $discountAmount), 2, ',', '.'), '0'), ',') : '' }}"
            {{ $disabled }}>
    </div>
    <input type="hidden" name="discount_amount[]" class="item-discount-value" {{ $disabled }}>
    <div class="item-discount-error text-sm text-[#900B09]" aria-live="polite"></div>
</td>
<td class="p-2 lg:p-3 w-[100px]">
    <input type="text" aria-label="Discount percentage" class="form-control item-disc bg-[#F5F5F5]"
        value="{{ number_format($discountItem?->discount_pct ?? 0, 2, '.', '') }}" readonly>
</td>
