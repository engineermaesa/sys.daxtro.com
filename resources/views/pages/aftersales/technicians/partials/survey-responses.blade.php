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
        const REVIEW_LABEL = { normal: 'Normal', needs_review: 'Needs review', reviewed: 'Reviewed', rejected: 'Rejected' };

        // tanggal selalu WIB (kontrak: jangan pakai toISOString)
        function formatLongDate(value) {
            const d = new Date(value);
            if (!value || isNaN(d)) return '—';
            return d.toLocaleDateString('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric' });
        }

        function inRating(r) {
            if (r.counted) return 'Counted';
            return 'Not counted' + (r.not_counted_reason ? ` · ${escapeHtml(r.not_counted_reason)}` : '');
        }

        function render(rows) {
            $('#td-survey-body').html((rows || []).map(s => `
                <tr class="border-t border-t-[#D9D9D9]">
                    <td class="p-3">${escapeHtml(s.ticket_code)}</td>
                    <td class="p-3 text-center">${formatLongDate(s.submitted_at)}</td>
                    <td class="p-3 text-center">${Number(s.score).toFixed(1)}</td>
                    <td class="p-3 text-center">${escapeHtml(REVIEW_LABEL[s.review_status] || s.review_status)}</td>
                    <td class="p-3 text-center">${inRating(s)}</td>
                </tr>
            `).join('') || '<tr class="border-t border-t-[#D9D9D9]"><td colspan="5" class="p-3 text-[#9CA3AF]">No survey response.</td></tr>');
        }

        $(function () {
            $('#td-survey-body').html('<tr class="border-t border-t-[#D9D9D9]"><td colspan="5" class="p-3 text-[#9CA3AF]">Loading...</td></tr>');
            $(document).on('technician:score', function (e, score) { render(score.responses); });
            $(document).on('technician:score:error', function (e, message) {
                $('#td-survey-body').html(`<tr class="border-t border-t-[#D9D9D9]"><td colspan="5" class="p-3 text-[#EC221F]">${escapeHtml(message)}</td></tr>`);
            });
        });
    })();
</script>
@endpush
