@props([
    'icon',
    'iconBg' => 'bg-yellow-200',
    'label',
    'value' => '-',
    'unit' => 'units',
    'percentage' => null, // 'text-primary',
    'trend' => null, // 'up' | 'down' | 'flat'
])

@php
    $trendColor = match($trend) {
        'up' => 'bg-green-50 text-green-700',
        'down' => 'bg-red-50 text-red-700',
        default => 'bg-gray-100 text-gray-600',
    };

    $trendIcon = match($trend) {
        'up' => 'bi-arrow-up-short',
        'down' => 'bi-arrow-down-short',
        default => 'bi-dash',
    };
@endphp

<article {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-[#D9D9D9] p-4']) }}>
    <div class="flex justify-between items-start">
        <div class="{{ $iconBg }} p-2 rounded-lg">
            <i class="bi {{ $icon }} text-lg"></i>
        </div>

        @if($percentage !== null)
            <span class="flex items-center gap-1 text-xs font-medium {{ $trendColor }} px-2 py-1 rounded-full">
                <i class="bi {{ $trendIcon }}"></i>
                {{ $percentage }}%
            </span>
        @endif
    </div>

    <p class="text-xs text-[#757575] font-medium mt-3 uppercase" data-stat="label">
        {{ $label }}
    </p>
    <p class="mt-1">
        <span class="text-2xl font-bold text-[#1E1E1E]" data-stat="value">
            {{ $value }}
        </span>
        <span class="text-sm text-[#757575]">
            {{ $unit }}
        </span>
    </p>
</article>
