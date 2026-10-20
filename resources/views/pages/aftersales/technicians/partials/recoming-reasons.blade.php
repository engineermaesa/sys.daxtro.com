{{-- RECOMING REASONS --}}
<div class="bg-white rounded-lg border border-[#D9D9D9] p-6 mt-4">
    <h2 class="font-semibold text-lg">Recoming Reasons</h2>
    <div class="overflow-x-auto mt-4">
        <table class="w-full table-fixed">
            <thead class="text-[#1E1E1E]">
                <tr>
                    <th class="p-3 text-left font-semibold" style="background-color: #F3F4F6; width: 45%;">Reason</th>
                    <th class="p-3 text-center font-semibold" style="background-color: #F3F4F6; width: 25%;">Tickets</th>
                    <th class="p-3 text-center font-semibold" style="background-color: #F3F4F6; width: 30%;">Status</th>
                </tr>
            </thead>
            <tbody id="td-recoming-body" class="text-[#757575] text-center"></tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        function render(rows) {
            $('#td-recoming-body').html((rows || []).map(r => `
                <tr class="border-t border-t-[#D9D9D9]">
                    <td class="p-3 text-left">${escapeHtml(r.label || r.reason)}</td>
                    <td class="p-3">${escapeHtml(r.tickets)}</td>
                    <td class="p-3">${r.counted ? 'Counted' : 'Not counted'}</td>
                </tr>
            `).join('') || '<tr class="border-t border-t-[#D9D9D9]"><td colspan="3" class="p-3 text-[#9CA3AF]">No recoming.</td></tr>');
        }

        $(function () {
            $('#td-recoming-body').html('<tr class="border-t border-t-[#D9D9D9]"><td colspan="3" class="p-3 text-[#9CA3AF]">Loading...</td></tr>');
            $(document).on('technician:score', function (e, score) { render(score.recoming_reasons); });
            $(document).on('technician:score:error', function (e, message) {
                $('#td-recoming-body').html(`<tr class="border-t border-t-[#D9D9D9]"><td colspan="3" class="p-3 text-[#EC221F]">${escapeHtml(message)}</td></tr>`);
            });
        });
    })();
</script>
@endpush
