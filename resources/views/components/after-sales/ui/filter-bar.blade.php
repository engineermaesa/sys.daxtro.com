@props(['only' => null, 'id' => 'spv-filters'])

@php
    $filters = [
        'period' => ['Period', ['this_month' => 'This month', 'last_month' => 'Last month', 'this_year' => 'This year']],
        'region' => ['Region', ['' => 'All regions']],
        'category' => ['Machine Category', ['' => 'All categories']],
    ];
    if ($only) {
        $filters = array_intersect_key($filters, array_flip($only));
    }
@endphp

<div class="flex flex-wrap justify-end gap-3" id="{{ $id }}">
    @foreach($filters as $key => [$label, $options])
        <label class="flex items-center gap-2 text-xs text-[#8A948F]">
            {{ $label }}
            <select data-filter="{{ $key }}"
                class="min-w-[130px] bg-white border border-[#D9D9D9] rounded-lg px-3 py-1.5 text-sm text-[#1E1E1E] focus:outline-none focus:border-[#14573F]">
                @foreach($options as $value => $text)
                    <option value="{{ $value }}">{{ $text }}</option>
                @endforeach
            </select>
        </label>
    @endforeach
</div>
