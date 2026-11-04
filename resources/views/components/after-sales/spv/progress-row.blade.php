@props(['key', 'label'])

<div class="rounded-lg border border-[#E3E8E5] px-3 py-2.5" data-progress="{{ $key }}">
    <div class="flex items-center justify-between text-xs">
        <span class="text-[#5F6B65]">{{ $label }}</span>
        <span class="font-semibold text-[#2B3441] tabular-nums" data-progress-value>-</span>
    </div>
    <div class="mt-2 h-1.5 rounded-full bg-[#E6EBE8] overflow-hidden">
        <div class="h-full rounded-full bg-[#14573F] transition-all duration-500" style="width:0%" data-progress-bar></div>
    </div>
</div>
