{{-- TICKET LOG PREVIEW MODAL (Documentation photos / Satisfaction ratings) --}}
<div class="modal fade" id="ticketLogPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! items-start px-6! pt-6! pb-4!">
                <h5 id="ticketLogPreviewTitle" class="modal-title text-[#115640] text-xl font-bold mb-0">Preview</h5>
                <button type="button" class="close cursor-pointer opacity-100! text-[#1E1E1E]!" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-2xl font-light leading-none">&times;</span>
                </button>
            </div>
            <div class="modal-body px-6! pb-6! pt-0!">
                <hr class="border-[#D9D9D9] mb-4">
                <div id="ticketLogPreviewBody"></div>
                <button type="button" data-dismiss="modal"
                    class="mt-4 border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 cursor-pointer">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const PHOTO_CATEGORY_LABEL = {
        problem: 'Problem',
        analysis: 'Analysis',
        repair: 'Repair',
    };

    const SATISFACTION_LABEL = {
        timeliness: 'Timeliness',
        technician_attitude: 'Technician Attitude',
        technical_knowledge: 'Technical Knowledge',
        work_neatness: 'Work Neatness',
        solution_quality: 'Solution Quality',
    };

    function openTicketLogPreview(step) {
        if (step === 'documentation_published') {
            $('#ticketLogPreviewTitle').text('Documentation Attachments');

            if (!currentTicketPhotos || currentTicketPhotos.length === 0) {
                $('#ticketLogPreviewBody').html('<p class="text-[#757575] text-sm mb-0">No attachments.</p>');
            } else {
                const groups = {};
                currentTicketPhotos.forEach(photo => {
                    groups[photo.category] = groups[photo.category] || [];
                    groups[photo.category].push(photo);
                });

                const html = Object.keys(groups).map(category => `
                    <div class="mb-3">
                        <label class="block text-xs uppercase text-[#757575] font-semibold mb-2">${escapeHtml(PHOTO_CATEGORY_LABEL[category] || category)}</label>
                        <div class="flex flex-wrap gap-2">
                            ${groups[category].map(photo => `
                                <a href="/storage/${photo.file_path}" target="_blank" rel="noopener">
                                    <img src="/storage/${photo.file_path}" alt="${escapeHtml(photo.file_name || '')}" class="w-20 h-20 object-cover rounded-lg border border-[#D9D9D9]">
                                </a>
                            `).join('')}
                        </div>
                    </div>
                `).join('');

                $('#ticketLogPreviewBody').html(html);
            }
        } else if (step === 'satisfaction_submitted') {
            $('#ticketLogPreviewTitle').text('Satisfaction Result');

            if (!currentTicketSatisfaction) {
                $('#ticketLogPreviewBody').html('<p class="text-[#757575] text-sm mb-0">No satisfaction data.</p>');
            } else {
                const html = Object.keys(SATISFACTION_LABEL).map(key => `
                    <div class="flex items-center justify-between border-b border-[#F0F0F0] py-2">
                        <span class="text-sm text-[#1E1E1E]">${escapeHtml(SATISFACTION_LABEL[key])}</span>
                        <span class="font-semibold text-[#115640]">${escapeHtml(currentTicketSatisfaction[key] ?? '-')} / 5</span>
                    </div>
                `).join('');

                $('#ticketLogPreviewBody').html(html);
            }
        }

        $('#ticketLogPreviewModal').modal('show');
    }
</script>
