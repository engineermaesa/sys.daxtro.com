@php
    $filters = [
        'period' => ['Period', ['this_month' => 'This month', 'last_month' => 'Last month', 'this_year' => 'This year']],
        'region' => ['Region', ['all' => 'All regions', 'java' => 'Java', 'sumatera' => 'Sumatera', 'kalimantan' => 'Kalimantan']],
        'category' => ['Machine Category', ['all' => 'All categories', 'ice' => 'Ice machine', 'cold' => 'Cold room']],
    ];
@endphp

<div class="flex flex-wrap justify-end gap-3" id="spv-filters">
    @foreach($filters as $id => [$label, $options])
        <label class="flex items-center gap-2 text-xs text-[#8A948F]">
            {{ $label }}
            <select data-filter="{{ $id }}"
                class="min-w-[130px] bg-white border border-[#D9D9D9] rounded-lg px-3 py-1.5 text-sm text-[#1E1E1E] focus:outline-none focus:border-[#14573F]">
                @foreach($options as $value => $text)
                    <option value="{{ $value }}">{{ $text }}</option>
                @endforeach
            </select>
        </label>
    @endforeach
</div>
