{{-- SUMMARY CARDS: Average Rating, First-Visit Completion, Total Tickets --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div class="bg-white rounded-lg border border-[#D9D9D9] p-6">
        <p class="text-[#757575]">Average Rating</p>
        <p class="mt-3"><span id="td-avg-rating" class="text-[#115640] font-bold text-4xl">-</span><span class="text-[#115640] font-semibold text-sm"> / 5</span></p>
        <p id="td-avg-rating-note" class="text-[#9CA3AF] text-xs mt-3"></p>
    </div>
    <div class="bg-white rounded-lg border border-[#D9D9D9] p-6">
        <p class="text-[#757575]">First-Visit Completion</p>
        <p id="td-first-visit" class="text-[#115640] font-bold text-4xl mt-3">-</p>
        <p id="td-first-visit-note" class="text-[#9CA3AF] text-xs mt-3"></p>
    </div>
    <div class="bg-white rounded-lg border border-[#D9D9D9] p-6">
        <p class="text-[#757575]">Total Tickets</p>
        <p id="td-total-tickets" class="text-[#115640] font-bold text-4xl mt-3">-</p>
        <p class="text-[#9CA3AF] text-xs mt-3">Across selected period</p>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const TECHNICIAN_ID = Number('{{ $technician->id }}'); // id profil teknisi, bukan user_id
        const dash = '—';

        function errMsg(xhr, fallback) {
            const body = xhr.responseJSON || {};
            return Object.values(body.errors || {}).flat()[0] || body.message || fallback;
        }

        function render(data) {
            const score = data.score || {};
            const kpi = data.kpi || {};
            const rating = score.rating;
            const uncounted = (score.responses || []).filter(r => !r.counted).length;

            $('#td-avg-rating').text(rating === null || rating === undefined ? dash : Number(rating).toFixed(1));
            $('#td-avg-rating-note').text(`${score.rating_count || 0} counted responses` + (uncounted ? ` · ${uncounted} not counted` : ''));

            if (score.completion === null || score.completion === undefined) {
                $('#td-first-visit').text(dash);
                $('#td-first-visit-note').text('No completed or recoming ticket yet');
            } else {
                $('#td-first-visit').text(`${score.completion}%`);
                $('#td-first-visit-note').text(`${score.completion_closed} of ${score.completion_total} completed on first visit`);
            }

            $('#td-total-tickets').text(kpi.total_tickets ?? dash);
        }

        // satu request, hasilnya dibagikan ke card lain lewat event technician:score
        function load(period) {
            $.ajax({
                url: `/api/aftersales/technicians/${TECHNICIAN_ID}`,
                method: 'GET',
                dataType: 'json',
                headers: { Accept: 'application/json' },
                data: { period: period || 'all' },
                success: function (response) {
                    const data = response.data || {};
                    render(data);
                    $(document).trigger('technician:score', [data.score || {}]);
                },
                error: function (xhr) {
                    const message = errMsg(xhr, 'Failed to load technician performance.');
                    $('#td-avg-rating-note, #td-first-visit-note').text(message);
                    $(document).trigger('technician:score:error', [message]);
                }
            });
        }

        $(function () {
            load($('#td-period').val());
            $(document).on('technician:period', function (e, period) { load(period); });
        });
    })();
</script>
@endpush
