@props([
    'id',
    'label',
    'value' => '0',
    'tone' => 'blue',
])

@php
    $tones = [
        'blue' => ['bg' => '#E1EBFA', 'text' => '#3F80EA'],
        'green' => ['bg' => '#CFF7D3', 'text' => '#14AE5C'],
        'yellow' => ['bg' => '#FFF1C2', 'text' => '#E5A000'],
        'red' => ['bg' => '#FDD3D0', 'text' => '#EC221F'],
    ];

    $toneColor = $tones[$tone] ?? $tones['blue'];
@endphp

<div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp">
    <div>
        <div class="flex items-center gap-2 px-3 rounded-lg" style="background-color: {{ $toneColor['bg'] }}">
            <p class="text-4xl" style="color: {{ $toneColor['text'] }}">&#8226;</p>
            <p class="font-semibold text-[#1E1E1E]">{{ $label }}</p>
        </div>
        <p class="mt-auto text-2xl font-bold pt-3 text-black">
            <span id="{{ $id }}">{{ $value }}</span>
        </p>
    </div>
    <div>
        <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path opacity="0.21" d="M44 0C52.8366 0 60 7.16344 60 16V44C60 52.8366 52.8366 60 44 60H16C7.16344 60 0 52.8366 0 44V16C0 7.16344 7.16344 0 16 0H44Z" fill="{{ $toneColor['text'] }}" />
            <path d="M19.1111 40.8889H42.4444C43.3036 40.8889 44 41.5853 44 42.4444C44 43.3036 43.3036 44 42.4444 44H17.5556C16.6964 44 16 43.3036 16 42.4444V17.5556C16 16.6964 16.6964 16 17.5556 16C18.4147 16 19.1111 16.6964 19.1111 17.5556V40.8889Z" fill="{{ $toneColor['text'] }}" />
            <path opacity="0.6" d="M24.9126 34.175C24.325 34.8017 23.3406 34.8335 22.7138 34.2459C22.0871 33.6583 22.0553 32.6739 22.6429 32.0472L28.4762 25.8249C29.0445 25.2188 29.9888 25.1662 30.6208 25.7056L35.2248 29.6343L41.2235 22.0361C41.7558 21.3618 42.734 21.2467 43.4083 21.779C44.0826 22.3114 44.1977 23.2895 43.6653 23.9638L36.6653 32.8305C36.1186 33.5231 35.1059 33.6227 34.4347 33.0499L29.7306 29.0358L24.9126 34.175Z" fill="{{ $toneColor['text'] }}" />
        </svg>
    </div>
</div>
