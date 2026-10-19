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
        // TODO(API): ganti data statis ini dengan
        //   GET /api/aftersales/technicians/{id}/performance?period=<#td-period>
        // bentuk: { avg_rating, rating_count, first_visit: {done, total}, total_tickets }
        const summary = {
            avg_rating: 4.4,
            rating_count: 3,
            first_visit: { done: 8, total: 12 },
            total_tickets: 24,
        };

        function render(data) {
            const fv = data.first_visit || {};
            $('#td-avg-rating').text(data.avg_rating != null ? Number(data.avg_rating).toFixed(1) : '-');
            $('#td-avg-rating-note').text(`${data.rating_count || 0} counted responses`);
            $('#td-first-visit').text(fv.total ? Math.round((fv.done / fv.total) * 100) + '%' : '-');
            $('#td-first-visit-note').text(`${fv.done || 0} of ${fv.total || 0} completed on first visit`);
            $('#td-total-tickets').text(data.total_tickets ?? '-');
        }

        $(function () {
            render(summary);
            // TODO(API): muat ulang dengan period dari event
            $(document).on('technician:period', function (e, period) { render(summary); });
        });
    })();
</script>
@endpush
