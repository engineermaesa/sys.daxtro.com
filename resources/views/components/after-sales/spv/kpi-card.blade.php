@props(['key', 'label', 'info' => null])

<article class="bg-white rounded-xl border border-[#E3E8E5] p-4" data-kpi="{{ $key }}">
    <p class="flex items-center gap-1.5 text-xs font-medium text-[#5F6B65]">
        {{ $label }}
        @if($info)
            <i class="bi bi-info-circle text-[#8A948F] cursor-help" title="{{ $info }}"></i>
        @endif
    </p>
    <p class="mt-2 text-2xl font-bold text-[#2B3441] tabular-nums" data-kpi-value>-</p>
    <p class="mt-1 text-[11px] text-[#8A948F]">
        <span data-kpi-delta class="font-semibold"></span> vs previous period
    </p>
</article>
