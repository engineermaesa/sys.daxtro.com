@props(['label' => '-', 'value' => '-', 'note' => ''])

<div {{ $attributes->merge(['class' => 'px-4 py-3 border-l border-[#E3E8E5] first:border-l-0']) }}>
    <p class="text-[11px] text-[#9AA39E]" data-day-label>{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold text-[#14573F] tabular-nums" data-day-value>{{ $value }}</p>
    <p class="mt-1 text-[11px] text-[#5F6B65]" data-day-note>{{ $note }}</p>
</div>
