@props(['title', 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-[#E3E8E5] p-5 flex flex-col']) }}>
    <div class="flex items-start justify-between gap-3 mb-4">
        <div class="min-w-0">
            <h2 class="text-[#1E1E1E] font-semibold text-base">{{ $title }}</h2>
            @if($subtitle)
                <p class="text-[#8A948F] text-xs mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>
    <div class="flex-1">{{ $slot }}</div>
</section>
