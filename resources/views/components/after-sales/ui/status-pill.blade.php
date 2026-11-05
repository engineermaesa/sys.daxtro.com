@props(['tone' => 'waiting'])

@php
    $classes = match($tone) {
        'done' => 'bg-[#E3F1EA] text-[#14573F]',
        'active' => 'bg-[#E3F1EA] text-[#14573F]',
        'next' => 'bg-[#FBF3D9] text-[#8A6B12]',
        default => 'bg-[#EEF1EF] text-[#5F6B65]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold {$classes}"]) }}>{{ $slot }}</span>
