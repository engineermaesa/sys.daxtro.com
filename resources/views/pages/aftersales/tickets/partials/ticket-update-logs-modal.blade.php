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
            <div id="ticketLogsHistoryPanel" class="modal-body px-6! pb-6! pt-0!">
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

                    {{-- Gate Closed (kontrak 6.5): respons current Needs review / Rejected --}}
                    <div id="ticketLogGateBox" class="hidden rounded-md mb-2" style="padding: 10px 14px; background:#FBEAEA; color:#900B09; font-size: 13px;">
                        <p id="ticketLogGateMessage" class="mb-2"></p>
                        <a id="ticketLogGateLink" href="#" class="inline-block border rounded-md font-semibold" style="padding: 6px 12px; border-color:#900B09; color:#900B09;">Open Review Response</a>
                    </div>

                    <button type="button" id="btn-add-ticket-log"
                        class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors cursor-pointer">
                        <i class="bi bi-plus-lg"></i> Activity
                    </button>
                    <button type="button" id="ticketLogsScheduleRecoming" class="border border-[#D9D9D9] rounded-lg cursor-pointer" style="margin-left:8px;padding:8px 16px;background:#fff4cf;color:#856400;">Schedule Recoming</button>
                </div>
            </div>
            <form id="ticketLogsRecomingForm" class="modal-body px-6! pb-6! pt-0!" style="display:none;">
                <hr class="border-[#D9D9D9] mb-4">
                <h6 class="font-bold" style="margin-bottom:20px;">Job not finished (Recoming)</h6>
                <fieldset style="margin-bottom:20px;">
                    <legend style="font-size:14px;margin-bottom:10px;">Reason</legend>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        @foreach (['Spare part not available yet', 'Further work needed', 'Site access / conditions', 'Other'] as $reason)
                            <label style="cursor:pointer;display:flex;align-items:center;gap:7px;border:1px solid #D9D9D9;border-radius:24px;padding:8px 12px;">
                                <input type="radio" name="ticket_logs_recoming_reason" value="{{ $reason }}" required @checked($loop->first)>
                                <span>{{ $reason }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label for="ticketLogsRecomingDate" class="block" style="margin-bottom:8px;">Follow-up visit date <span style="color:#EC221F;">*</span></label>
                <input type="date" id="ticketLogsRecomingDate" required class="w-full border border-[#D9D9D9] rounded-lg" style="padding:10px 12px;margin-bottom:20px;">
                <label for="ticketLogsRecomingNotes" class="block" style="margin-bottom:8px;">Notes</label>
                <textarea id="ticketLogsRecomingNotes" rows="3" maxlength="5000" placeholder="Details of the remaining work..." class="w-full border border-[#D9D9D9] rounded-lg" style="padding:10px 12px;margin-bottom:20px;"></textarea>
                <div style="padding:16px;background:#EEF4FF;color:#2F5BD3;border-radius:8px;font-size:13px;">
                    <p class="font-bold" style="margin-bottom:8px;">AFTER SAVING</p>
                    <ul style="padding-left:18px;list-style:disc;line-height:1.8;">
                        <li>Ticket progress changes to Recoming (second visit scheduled).</li>
                        <li>Service Form remains in Draft status.</li>
                        <li>The satisfaction survey link is temporarily disabled.</li>
                        <li>Ticket status returns to On Site on the follow-up visit date.</li>
                    </ul>
                </div>
                <p id="ticketLogsRecomingFeedback" role="status" style="margin-top:16px;color:#EC221F;font-size:13px;"></p>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                    <button type="button" id="ticketLogsRecomingCancel" class="border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding:8px 16px;">Cancel</button>
                    <button type="submit" class="bg-[#115640] text-white rounded-lg cursor-pointer" style="padding:8px 16px;">Save Recoming</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let recomingSaving = false;
        function showTicketLogsRecoming(show) {
            $('#ticketLogsHistoryPanel').toggle(!show);
            $('#ticketLogsRecomingForm').toggle(show);
            if (show) {
                $('#ticketLogsRecomingDate').attr('min', new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })).trigger('focus');
            }
        }
        $('#ticketLogsScheduleRecoming').on('click', function () { showTicketLogsRecoming(true); });
        $('#ticketLogsRecomingCancel').on('click', function () { showTicketLogsRecoming(false); });
        $('#ticketUpdateLogsModal').on('show.bs.modal', function () {
            $('#ticketLogsRecomingForm')[0].reset();
            $('#ticketLogsRecomingFeedback').text('');
            showTicketLogsRecoming(false);
        });
        $('#ticketLogsRecomingForm').on('submit', function (event) {
            event.preventDefault();
            if (recomingSaving || !currentUpdateLogsTicketId || !this.reportValidity()) return;
            const reasons = {
                'Spare part not available yet': 'sparepart_unavailable',
                'Further work needed': 'further_work_needed',
                'Site access / conditions': 'site_access',
                'Other': 'other',
            };
            const ticketId = currentUpdateLogsTicketId;
            const $buttons = $('#ticketLogsRecomingForm button');
            recomingSaving = true;
            $buttons.prop('disabled', true);
            $('#ticketLogsRecomingFeedback').text('');
            $.ajax({
                url: '/api/aftersales/tickets/' + ticketId + '/recoming',
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: {
                    reason: reasons[$('input[name="ticket_logs_recoming_reason"]:checked').val()],
                    planned_visit_at: $('#ticketLogsRecomingDate').val(),
                    notes: $('#ticketLogsRecomingNotes').val().trim(),
                },
                success: function () {
                    showTicketLogsRecoming(false);
                    loadTickets();
                    $.ajax({
                        url: '/api/aftersales/tickets/' + ticketId,
                        method: 'GET',
                        success: function (response) {
                            if (currentUpdateLogsTicketId === ticketId) renderTicketUpdateLogsRows(response.data?.logs || []);
                        },
                        error: function () {
                            if (currentUpdateLogsTicketId === ticketId) $('#ticketUpdateLogsRows').html('<tr><td colspan="4" class="p-2">Recoming saved. Reopen Update Logs to refresh the history.</td></tr>');
                        },
                    });
                    Swal.fire({ title: 'Recoming scheduled', icon: 'success', confirmButtonColor: '#115640', timer: 1500 });
                },
                error: function (xhr) {
                    const body = xhr.responseJSON || {};
                    $('#ticketLogsRecomingFeedback').text(Object.values(body.errors || {}).flat()[0] || body.message || 'Failed to schedule Recoming. Please try again.');
                },
                complete: function () { recomingSaving = false; $buttons.prop('disabled', false); },
            });
        });
        $('#ticketUpdateLogsModal').on('hide.bs.modal', function (event) {
            if (recomingSaving) event.preventDefault();
        });
    });

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
        $('#ticketLogGateBox').addClass('hidden');
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

        $('#ticketLogGateBox').addClass('hidden');
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
                // gate Closed: 422 dengan satisfaction_id -> kotak merah + tombol menuju Review Response
                const gateId = xhr.status === 422 ? xhr.responseJSON?.satisfaction_id : null;
                if (gateId) {
                    $('#ticketLogGateMessage').text(xhr.responseJSON.message || 'Cannot set to Closed.');
                    $('#ticketLogGateLink').attr('href', `/satisfaction-survey#${gateId}/review`);
                    $('#ticketLogGateBox').removeClass('hidden');
                    return;
                }

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
