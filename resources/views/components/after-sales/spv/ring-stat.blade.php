@props(['key', 'label'])

<div class="rounded-xl border border-[#E3E8E5] py-3 flex flex-col items-center" data-ring="{{ $key }}">
    <div data-ring-chart class="w-[110px] h-[110px]"></div>
    <p class="text-xs font-semibold text-[#2B3441] -mt-1">{{ $label }}</p>
</div>

