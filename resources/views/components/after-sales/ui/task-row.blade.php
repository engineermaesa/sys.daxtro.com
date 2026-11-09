{{-- Satu baris tugas. Dipakai di <template>; JS meng-clone lalu mengisi lewat data-*. --}}
@props(['time' => '-', 'code' => '-', 'meta' => '', 'tone' => 'waiting', 'status' => ''])

<div class="grid grid-cols-[48px_28px_minmax(0,1fr)_auto] items-center gap-3 py-4">
    <span data-time class="text-[11px] text-[#5F6B65] tabular-nums">{{ $time }}</span>
    <span data-dot class="size-4 rounded-full border-2 grid place-items-center">
        <span class="size-1.5 rounded-full bg-current"></span>
    </span>
    <div class="min-w-0">
        <p data-code class="text-xs font-bold text-[#14573F] truncate">{{ $code }}</p>
        <p data-meta class="text-[11px] text-[#5F6B65] truncate">{{ $meta }}</p>
    </div>
    <x-after-sales.ui.status-pill data-pill :tone="$tone">{{ $status }}</x-after-sales.ui.status-pill>
</div>
