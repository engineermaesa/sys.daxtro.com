{{-- Baris bar horizontal: label kiri, track+fill, angka kanan. Dipakai di <template>; JS mengisi lewat data-*. --}}
@props(['label' => '-', 'value' => 0, 'percent' => 0, 'color' => '#14573F'])

<div class="grid grid-cols-[88px_minmax(0,1fr)_24px] items-center gap-3 py-2">
    <span data-label class="text-xs text-[#2B3441] truncate">{{ $label }}</span>
    <div class="h-2.5 rounded-full bg-[#E6EBE8] overflow-hidden">
        <div data-bar class="h-full rounded-full transition-all duration-500" style="width:{{ $percent }}%;background:{{ $color }}"></div>
    </div>
    <span data-value class="text-right text-xs font-bold text-[#2B3441] tabular-nums">{{ $value }}</span>
</div>
