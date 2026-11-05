@props(['label', 'value' => '-', 'note' => ''])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <p class="text-[11px] text-[#9AA39E]">{{ $label }}</p>
    <p class="text-lg font-bold text-[#14573F] leading-tight" data-side-value>{{ $value }}</p>
    <div class="my-2 h-px w-10 bg-[#E3E8E5]"></div>
    <p class="text-[11px] text-[#5F6B65]" data-side-note>{{ $note }}</p>
</div>
