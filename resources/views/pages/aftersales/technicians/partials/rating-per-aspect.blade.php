{{-- RATING PER ASPECT --}}
<div class="bg-white rounded-lg border border-[#D9D9D9] p-6 mt-4">
    <div class="flex items-center justify-between"><h2 class="font-medium text-lg">Rating per Aspect</h2><span class="text-[10px] text-[#9CA3AF]">Sorted from lowest score</span></div>
    <div id="td-aspects" class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
</div>

@push('scripts')
<script>
    (function () {
        function render(aspects) {
            const list = (aspects || []).map(a => ({
                label: a.label,
                avg: a.average === null || a.average === undefined ? null : Number(a.average),
            })).sort((x, y) => (x.avg ?? Infinity) - (y.avg ?? Infinity));

            $('#td-aspects').html(list.map((a, i) => {
                const rounded = a.avg === null ? null : Math.round(a.avg);
                const tag = rounded === null ? '' : (rounded <= 1 ? 'Needs attention' : (rounded === 2 ? 'Fair' : 'Good'));
                const span = (list.length % 2 === 1 && i === list.length - 1) ? ' sm:col-span-2' : '';
                return `
                <div class="p-4 border border-[#D9D9D9] rounded-lg${span}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm">${escapeHtml(a.label)}</span>
                        ${tag ? `<span class="px-2 py-0.5 rounded-md bg-[#E6F4EF] text-[#115640] text-[10px]">${tag}</span>` : ''}
                    </div>
                    <p class="mt-2"><span class="text-2xl font-bold text-[#115640]">${a.avg === null ? '—' : a.avg.toFixed(1).replace(/\.0$/, '')}</span> <span class="text-[10px] text-[#9CA3AF]">/ 3</span></p>
                    <div class="relative mt-2 h-2 rounded-full bg-[#EDEDED] overflow-hidden">
                        <div class="h-full rounded-full bg-[#115640]" style="width: ${a.avg === null ? 0 : Math.min(100, (a.avg / 3) * 100)}%;"></div>
                        <span class="absolute top-0 h-full w-0.5 bg-white" style="left:33.33%"></span>
                        <span class="absolute top-0 h-full w-0.5 bg-white" style="left:66.66%"></span>
                    </div>
                    <div class="flex justify-between mt-1 text-[9px] text-[#9CA3AF]"><span>0</span><span>1</span><span>2</span><span>3</span></div>
                </div>`;
            }).join('') || '<p class="text-[#9CA3AF] sm:col-span-2">No rating yet.</p>');
        }

        $(function () {
            $('#td-aspects').html('<p class="text-[#9CA3AF]">Loading...</p>');
            $(document).on('technician:score', function (e, score) { render(score.aspects); });
            $(document).on('technician:score:error', function (e, message) {
                $('#td-aspects').html(`<p class="text-[#EC221F]">${escapeHtml(message)}</p>`);
            });
        });
    })();
</script>
@endpush
