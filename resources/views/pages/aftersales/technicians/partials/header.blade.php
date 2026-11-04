{{-- HEADER: nama, grade/certification/status, filter periode (script: broadcast event technician:period) --}}
<div class="flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-[#115640] font-bold text-3xl">{{ $technician->user->name }}</h1>
        <p class="text-[#757575] text-sm mt-1">
            {{ $technician->user->grade ? ucfirst($technician->user->grade) . ' Technician' : 'Technician' }}
            @if ($technician->certification)
                · {{ $technician->certification }}
            @endif
            ·
            <span class="font-semibold {{ $technician->status === 'On Duty' ? 'text-[#115640]' : 'text-[#757575]' }}">{{ $technician->status }}</span>
        </p>
    </div>
    <select id="td-period" class="border border-[#D9D9D9] rounded-lg px-3 py-2 bg-white cursor-pointer">
        <option value="all">All time</option>
        <option value="30">Last 30 days</option>
        <option value="90">Last 90 days</option>
        <option value="365">Last 12 months</option>
    </select>
</div>

@push('scripts')
<script>
    (function () {
        // card lain (summary, aspek, recoming, survey) mendengarkan event ini
        $(function () {
            $('#td-period').on('change', function () {
                $(document).trigger('technician:period', [this.value]);
            });
        });
    })();
</script>
@endpush
