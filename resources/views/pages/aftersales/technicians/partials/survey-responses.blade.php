{{-- SURVEY RESPONSES --}}
<div class="bg-white rounded-lg border border-[#D9D9D9] p-6 mt-4">
    <h2 class="font-semibold text-lg">Survey Responses</h2>
    <div class="overflow-x-auto mt-4">
        <table class="w-full">
            <thead class="text-[#1E1E1E]">
                <tr>
                    <th style="background-color: #F3F4F6;" class="p-3 text-left font-semibold">Ticket</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Submitted</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Score</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Review</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">In Rating</th>
                </tr>
            </thead>
            <tbody id="td-survey-body" class="text-[#757575]"></tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        // TODO(API): ganti data statis ini dengan
        //   GET /api/aftersales/technicians/{id}/performance?period=<#td-period>
        // bentuk: survey_responses: [{ ticket_code, submitted_at, score, review, in_rating }]
        const responses = [
            { ticket_code: 'TKT-2024-001', submitted_at: '2025-01-12', score: 4.7, review: 'Approved', in_rating: true },
            { ticket_code: 'TKT-2024-014', submitted_at: '2025-02-03', score: 2.1, review: 'Needs review', in_rating: false },
        ];

        function formatLongDate(value) {
            const d = new Date(value);
            if (isNaN(d)) return '—';
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function render(rows) {
            $('#td-survey-body').html((rows || []).map(s => `
                <tr class="border-t border-t-[#D9D9D9]">
                    <td class="p-3">${escapeHtml(s.ticket_code)}</td>
                    <td class="p-3 text-center">${formatLongDate(s.submitted_at)}</td>
                    <td class="p-3 text-center">${Number(s.score).toFixed(1)}</td>
                    <td class="p-3 text-center">${escapeHtml(s.review)}</td>
                    <td class="p-3 text-center">${s.in_rating ? 'Yes' : 'No'}</td>
                </tr>
            `).join('') || '<tr class="border-t border-t-[#D9D9D9]"><td colspan="5" class="p-3 text-[#9CA3AF]">No survey response.</td></tr>');
        }

        $(function () {
            render(responses);
            // TODO(API): muat ulang dengan period dari event
            $(document).on('technician:period', function (e, period) { render(responses); });
        });
    })();
</script>
@endpush
