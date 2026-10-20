{{-- RATING PER ASPECT --}}
<div class="bg-white rounded-lg border border-[#D9D9D9] p-6 mt-4">
    <h2 class="font-medium text-lg">Rating per Aspect</h2>
    <div id="td-aspects" class="mt-5 space-y-4"></div>
</div>

@push('scripts')
<script>
    (function () {
        function render(aspects) {
            $('#td-aspects').html((aspects || []).map(a => {
                const avg = a.average === null || a.average === undefined ? null : Number(a.average);
                return `
                <div class="grid items-center gap-x-6" style="grid-template-columns: 180px 1fr 32px;">
                    <span class="text-[#374151] truncate">${escapeHtml(a.label)}</span>
                    <div class="h-2 rounded-full bg-[#E5E7EB] overflow-hidden">
                        <div class="h-full rounded-full bg-[#115640]" style="width: ${avg === null ? 0 : Math.min(100, (avg / 5) * 100)}%;"></div>
                    </div>
                    <span class="text-right font-bold text-[#1E1E1E]">${avg === null ? '—' : avg.toFixed(1)}</span>
                </div>`;
            }).join('') || '<p class="text-[#9CA3AF]">No rating yet.</p>');
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
