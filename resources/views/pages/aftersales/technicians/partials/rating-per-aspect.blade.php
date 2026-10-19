{{-- RATING PER ASPECT --}}
<div class="bg-white rounded-lg border border-[#D9D9D9] p-6 mt-4">
    <h2 class="font-medium text-lg">Rating per Aspect</h2>
    <div id="td-aspects" class="mt-5 space-y-4"></div>
</div>

@push('scripts')
<script>
    (function () {
        // TODO(API): ganti data statis ini dengan
        //   GET /api/aftersales/technicians/{id}/performance?period=<#td-period>
        // bentuk: aspects: [{ label, score }]
        const aspects = [
            { label: 'Arrival Punctuality', score: 4.6 },
            { label: 'Technician Attitude', score: 4.7 },
            { label: 'Technical Explanation', score: 4.2 },
            { label: 'Service Quality', score: 4.3 },
        ];

        function render(rows) {
            $('#td-aspects').html((rows || []).map(a => `
                <div class="grid items-center gap-x-6" style="grid-template-columns: 180px 1fr 32px;">
                    <span class="text-[#374151] truncate">${escapeHtml(a.label)}</span>
                    <div class="h-2 rounded-full bg-[#E5E7EB] overflow-hidden">
                        <div class="h-full rounded-full bg-[#115640]" style="width: ${Math.min(100, (Number(a.score) / 5) * 100)}%;"></div>
                    </div>
                    <span class="text-right font-bold text-[#1E1E1E]">${Number(a.score).toFixed(1)}</span>
                </div>
            `).join('') || '<p class="text-[#9CA3AF]">No rating yet.</p>');
        }

        $(function () {
            render(aspects);
            // TODO(API): muat ulang dengan period dari event
            $(document).on('technician:period', function (e, period) { render(aspects); });
        });
    })();
</script>
@endpush
