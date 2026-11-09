@props(['name', 'options', 'active' => null])

@php $active = $active ?? array_key_first($options); @endphp

<div class="inline-flex items-center bg-[#F0F3F1] rounded-lg p-0.5" role="group" data-toggle="{{ $name }}">
    @foreach($options as $value => $text)
        <button type="button" data-value="{{ $value }}" aria-pressed="{{ $value === $active ? 'true' : 'false' }}"
            class="spv-toggle-btn px-3 py-1 rounded-md text-xs font-medium text-[#8A948F] transition-colors
                   aria-pressed:bg-white aria-pressed:text-[#14573F] aria-pressed:shadow-sm">
            {{ $text }}
        </button>
    @endforeach
</div>
