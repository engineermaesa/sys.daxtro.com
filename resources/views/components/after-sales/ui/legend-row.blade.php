@props(['color', 'label', 'percent' => '-', 'count' => '-'])

<div class="flex items-center gap-3 py-2" data-legend>
    <span class="size-2.5 rounded-sm shrink-0" style="background:{{ $color }}"></span>
    <div class="min-w-0 flex-1">
        <p class="text-xs font-medium text-[#2B3441]">{{ $label }}</p>
        <p class="text-[10px] text-[#9AA39E]" data-legend-percent>{{ $percent }}</p>
    </div>
    <span class="text-lg font-bold text-[#2B3441] tabular-nums" data-legend-count>{{ $count }}</span>
</div>
