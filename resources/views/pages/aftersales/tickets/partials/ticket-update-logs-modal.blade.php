{{-- TICKET UPDATE LOGS MODAL --}}
<div class="modal fade" id="ticketUpdateLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! items-start px-6! pt-6! pb-4!">
                <div>
                    <h5 id="ticketUpdateLogsCode" class="modal-title text-[#115640] text-xl font-bold mb-0">Update Logs</h5>
                    <p class="text-[#757575] text-sm mb-0">Ticket progress history</p>
                </div>
                <button type="button" class="close cursor-pointer opacity-100! text-[#1E1E1E]!" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-2xl font-light leading-none">&times;</span>
                </button>
            </div>
            <div class="modal-body px-6! pb-6! pt-0!">
                <hr class="border-[#D9D9D9] mb-4">

                <div class="overflow-x-auto mb-4">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                                <th class="p-2">Date</th>
                                <th class="p-2">Activity</th>
                                <th class="p-2">Note</th>
                                <th class="p-2">User</th>
                            </tr>
                        </thead>
                        <tbody id="ticketUpdateLogsRows"></tbody>
                    </table>
                </div>

                <div class="border-t border-[#D9D9D9] pt-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                        <select id="ticketLogStep" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="on_site">On Site</option>
                            <option value="repair">Repair</option>
                            <option value="waiting_sparepart">Waiting Sparepart</option>
                            <option value="documentation_published">Documentation Published</option>
                            <option value="satisfaction_submitted">Satisfaction Submitted</option>
                            <option value="closed">Closed</option>
                        </select>
                        <input type="text" id="ticketLogNote" placeholder="Note..."
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>

                    <div id="ticketLogSatisfactionFields" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                        @foreach ([
                            'timeliness' => 'Timeliness',
                            'technician_attitude' => 'Technician Attitude',
                            'technical_knowledge' => 'Technical Knowledge',
                            'work_neatness' => 'Work Neatness',
                            'solution_quality' => 'Solution Quality',
                        ] as $key => $label)
                            <div>
                                <label class="block text-xs text-[#757575] mb-1">{{ $label }}</label>
                                <select class="ticket-log-satisfaction-field w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" data-field="{{ $key }}">
                                    <option value="">Rate 1-5</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div id="ticketLogPhotoFields" class="hidden mb-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs text-[#757575]">Attachments (optional)</label>
                            <button type="button" id="btn-add-ticket-log-photo" class="text-xs text-[#115640] underline cursor-pointer">
                                <i class="bi bi-plus-lg"></i> Add photo
                            </button>
                        </div>
                        <div id="ticketLogPhotoRows" class="space-y-2"></div>
                    </div>

                    <button type="button" id="btn-add-ticket-log"
                        class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors cursor-pointer">
                        <i class="bi bi-plus-lg"></i> Activity
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Maps each loggable step to the existing, already-built ticket endpoint
    // that actually persists it (repair/on-site/waiting-sparepart/close just
    // take a note; documentation/satisfaction have their own real payloads).
    const STEP_ENDPOINT = {
        on_site: 'on-site',
        repair: 'repair',
        waiting_sparepart: 'waiting-sparepart',
        documentation_published: 'documentation',
        satisfaction_submitted: 'satisfaction',
        closed: 'close',
    };

    let currentUpdateLogsTicketId = null;
    let ticketLogPhotoRowCount = 0;
    let currentTicketPhotos = [];
    let currentTicketSatisfaction = null;

    const PREVIEWABLE_STEPS = ['documentation_published', 'satisfaction_submitted'];

    function formatDateTime(value) {
        if (!value) return '—';
        return new Date(value).toLocaleString('id-ID');
    }

    function addTicketLogPhotoRow() {
        ticketLogPhotoRowCount += 1;
        const rowId = ticketLogPhotoRowCount;

        const html = `
            <div class="flex items-center gap-2" data-photo-row="${rowId}">
                <select class="ticket-log-photo-category border border-[#D9D9D9] rounded-lg px-2 py-1.5 text-sm focus:outline-none!">
                    <option value="problem">Problem</option>
                    <option value="analysis">Analysis</option>
                    <option value="repair">Repair</option>
                </select>
                <input type="file" accept="image/*" class="ticket-log-photo-file flex-1 border border-[#D9D9D9] rounded-lg px-2 py-1.5 text-sm focus:outline-none!">
                <button type="button" class="btn-remove-ticket-log-photo text-[#900B09] cursor-pointer" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        $('#ticketLogPhotoRows').append(html);
    }

    function renderTicketUpdateLogsRows(logs) {
        logs = (logs || []).slice().sort((a, b) => a.id - b.id);

        const html = logs.length > 0
            ? logs.map(log => `
                <tr class="border-t border-[#D9D9D9]">
                    <td class="p-2 whitespace-nowrap">${formatDateTime(log.created_at)}</td>
                    <td class="p-2 font-semibold text-[#1E1E1E]">
                        ${escapeHtml(STEP_LABEL[log.step] || log.step)}
                        ${PREVIEWABLE_STEPS.includes(log.step)
                            ? `<button type="button" class="btn-ticket-log-preview text-xs text-[#115640] underline cursor-pointer ml-2" data-step="${log.step}">Preview</button>`
                            : ''}
                    </td>
                    <td class="p-2 text-[#757575]">${escapeHtml(log.note || '-')}</td>
                    <td class="p-2">${escapeHtml(log.actor?.name || '-')}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="4" class="p-2 text-center text-[#757575]">No update logs yet.</td></tr>';

        $('#ticketUpdateLogsRows').html(html);
    }

    function resetTicketLogForm() {
        $('#ticketLogStep').val('on_site');
        $('#ticketLogNote').val('');
        $('.ticket-log-satisfaction-field').val('');
        $('#ticketLogSatisfactionFields').addClass('hidden');
        $('#ticketLogPhotoFields').addClass('hidden');
        $('#ticketLogPhotoRows').empty();
        $('#ticketLogNote').removeClass('hidden');
    }

    function openTicketUpdateLogs(ticket) {
        currentUpdateLogsTicketId = ticket.id;
        currentTicketPhotos = [];
        currentTicketSatisfaction = null;
        $('#ticketUpdateLogsCode').text(ticket.ticket_code || 'Update Logs');
        renderTicketUpdateLogsRows(ticket.logs);
        resetTicketLogForm();
        $('#ticketUpdateLogsModal').modal('show');

        $.ajax({
            url: `/api/aftersales/tickets/${ticket.id}`,
            method: 'GET',
            success: function (response) {
                currentTicketPhotos = response.data?.photos || [];
                currentTicketSatisfaction = response.data?.satisfaction || null;
                renderTicketUpdateLogsRows(response.data?.logs || ticket.logs);
            }
        });
    }

    function submitTicketLog() {
        if (!currentUpdateLogsTicketId) return;

        const step = $('#ticketLogStep').val();
        const endpoint = STEP_ENDPOINT[step];
        let data = { note: $('#ticketLogNote').val() || null };
        let ajaxOptions = { data: data };

        if (step === 'satisfaction_submitted') {
            data = {};
            let missing = false;
            $('.ticket-log-satisfaction-field').each(function () {
                const value = $(this).val();
                if (!value) missing = true;
                data[$(this).data('field')] = value;
            });

            if (missing) {
                Swal.fire({
                    title: 'Data incomplete',
                    text: 'Please rate all five satisfaction criteria.',
                    icon: 'warning',
                    confirmButtonColor: '#115640'
                });
                return;
            }

            ajaxOptions = { data: data };
        }

        if (step === 'documentation_published') {
            const formData = new FormData();
            formData.append('note', $('#ticketLogNote').val() || '');

            let photoIndex = 0;
            $('#ticketLogPhotoRows [data-photo-row]').each(function () {
                const file = $(this).find('.ticket-log-photo-file')[0].files[0];
                if (!file) return;
                formData.append(`photos[${photoIndex}][category]`, $(this).find('.ticket-log-photo-category').val());
                formData.append(`photos[${photoIndex}][file]`, file);
                photoIndex++;
            });

            ajaxOptions = { data: formData, processData: false, contentType: false };
        }

        $.ajax({
            url: `/api/aftersales/tickets/${currentUpdateLogsTicketId}/${endpoint}`,
            method: 'POST',
            ...ajaxOptions,
            success: function () {
                $.ajax({
                    url: `/api/aftersales/tickets/${currentUpdateLogsTicketId}`,
                    method: 'GET',
                    success: function (response) {
                        currentTicketPhotos = response.data?.photos || [];
                        currentTicketSatisfaction = response.data?.satisfaction || null;
                        renderTicketUpdateLogsRows(response.data?.logs);
                    }
                });
                resetTicketLogForm();
                loadTickets();
            },
            error: function (xhr) {
                Swal.fire({
                    title: 'Failed to add activity',
                    text: xhr.responseJSON?.message || 'An error occurred, please try again.',
                    icon: 'error',
                    confirmButtonColor: '#115640'
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#ticketLogStep').on('change', function () {
            const step = $(this).val();
            const isSatisfaction = step === 'satisfaction_submitted';
            const isDocumentation = step === 'documentation_published';

            $('#ticketLogSatisfactionFields').toggleClass('hidden', !isSatisfaction);
            $('#ticketLogNote').toggleClass('hidden', isSatisfaction);
            $('#ticketLogPhotoFields').toggleClass('hidden', !isDocumentation);

            if (isDocumentation && $('#ticketLogPhotoRows [data-photo-row]').length === 0) {
                addTicketLogPhotoRow();
            }
        });

        $('#btn-add-ticket-log-photo').on('click', addTicketLogPhotoRow);

        $(document).on('click', '.btn-remove-ticket-log-photo', function () {
            $(this).closest('[data-photo-row]').remove();
        });

        $('#btn-add-ticket-log').on('click', submitTicketLog);
    });
</script>
