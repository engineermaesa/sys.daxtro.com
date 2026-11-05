{{-- Satu baris peringkat dengan bar. Dipakai di dalam <template>, JS meng-clone lalu mengisi lewat atribut data-*. --}}
@props(['rank' => '-', 'name' => '-', 'status' => '', 'value' => 0, 'percent' => 0])

<div class="grid grid-cols-[150px_minmax(0,1fr)_48px] items-center gap-3 py-2.5">
    <div class="flex items-center gap-3 min-w-0">
        <span data-rank class="size-6 shrink-0 grid place-items-center rounded-md bg-[#E6F1EC] text-[11px] font-semibold text-[#14573F]">{{ $rank }}</span>
        <div class="min-w-0">
            <p data-name class="text-xs font-semibold text-[#2B3441] truncate">{{ $name }}</p>
            <p data-status class="text-[10px] text-[#9AA39E]">{{ $status }}</p>
        </div>
    </div>
    <div class="h-2.5 rounded-full bg-[#E6EBE8] overflow-hidden">
        <div data-bar class="h-full rounded-full bg-[#14573F] transition-all duration-500" style="width:{{ $percent }}%"></div>
    </div>
    <span data-value class="text-right text-sm font-bold text-[#2B3441] tabular-nums">{{ $value }}</span>
</div>
