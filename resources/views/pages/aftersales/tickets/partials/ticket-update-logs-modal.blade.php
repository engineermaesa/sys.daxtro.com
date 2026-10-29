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
                            <option value="happy_call_completed">Happy Call Completed</option>
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
                                    {{-- 3-level scale (API contract section 8): value 1-3, shown as x/3 --}}
                                    <option value="">Rate 1-3</option>
                                    <option value="1">1 · Kurang Puas</option>
                                    <option value="2">2 · Puas</option>
                                    <option value="3">3 · Sangat Puas</option>
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
                    <div id="ticketLogGateBox" class="hidden items-center justify-between rounded-lg mb-2" style="gap: 12px; padding: 14px 18px; background:#FFF8EC; border:1px solid #F5DDB3; color:#8A6D00; font-size: 13px;">
                        <div>
                            <p id="ticketLogGateTitle" class="font-bold mb-1" style="font-size: 14px;">Ticket cannot be closed</p>
                            <p id="ticketLogGateMessage" class="mb-0"></p>
                        </div>
                        <a id="ticketLogGateLink" href="#" class="hidden rounded-md font-semibold shrink-0" style="padding: 8px 14px; border: 1px solid #8A6D00; color:#8A6D00; background:#fff;">Open Review Response</a>
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
                {{-- AS-REC-01: tipe Recoming --}}
                <fieldset style="margin-bottom:20px;">
                    <legend style="font-size:14px;margin-bottom:10px;">Recoming type <span style="color:#EC221F;">*</span></legend>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        @foreach (['visit_site' => 'Recoming by visit site', 'phone_call' => 'Recoming by phone call'] as $typeValue => $typeLabel)
                            <label style="cursor:pointer;display:flex;align-items:center;gap:7px;border:1px solid #D9D9D9;border-radius:24px;padding:8px 12px;">
                                <input type="radio" name="ticket_logs_recoming_type" value="{{ $typeValue }}" required @checked($loop->first)>
                                <span>{{ $typeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset style="margin-bottom:20px;">
                    <legend style="font-size:14px;margin-bottom:10px;">Reason</legend>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        @foreach (['Spare part not available yet', 'Further work needed', 'Site access / conditions', 'Unfinished from previous work'] as $reason)
                            <label style="cursor:pointer;display:flex;align-items:center;gap:7px;border:1px solid #D9D9D9;border-radius:24px;padding:8px 12px;">
                                <input type="radio" name="ticket_logs_recoming_reason" value="{{ $reason }}" required @checked($loop->first)>
                                <span>{{ $reason }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label for="ticketLogsRecomingDate" id="ticketLogsRecomingDateLabel" class="block" style="margin-bottom:8px;">Follow-up visit date &amp; time <span style="color:#EC221F;">*</span></label>
                <input type="datetime-local" id="ticketLogsRecomingDate" required class="w-full border border-[#D9D9D9] rounded-lg" style="padding:10px 12px;margin-bottom:20px;">
                <label for="ticketLogsRecomingNotes" class="block" style="margin-bottom:8px;">Notes</label>
                <textarea id="ticketLogsRecomingNotes" rows="3" maxlength="5000" placeholder="Details of the remaining work..." class="w-full border border-[#D9D9D9] rounded-lg" style="padding:10px 12px;margin-bottom:20px;"></textarea>

                {{-- AS-REC-04 / AS-SLA-02: setup SLA siklus ini wajib. [API] sla_value + sla_unit (hour|business_day) atau sla_due_at --}}
                <fieldset style="margin-bottom:20px;">
                    <legend style="font-size:14px;margin-bottom:10px;">SLA for this cycle <span style="color:#EC221F;">*</span></legend>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
                        <label style="cursor:pointer;display:flex;align-items:center;gap:7px;border:1px solid #D9D9D9;border-radius:24px;padding:8px 12px;">
                            <input type="radio" name="ticket_logs_recoming_sla_mode" value="duration" checked> <span>Duration</span>
                        </label>
                        <label style="cursor:pointer;display:flex;align-items:center;gap:7px;border:1px solid #D9D9D9;border-radius:24px;padding:8px 12px;">
                            <input type="radio" name="ticket_logs_recoming_sla_mode" value="deadline"> <span>Deadline</span>
                        </label>
                    </div>
                    <div id="ticketLogsRecomingSlaDuration" style="display:flex;gap:10px;">
                        <input type="number" min="1" step="1" id="ticketLogsRecomingSlaValue" placeholder="Duration" class="border border-[#D9D9D9] rounded-lg" style="padding:10px 12px;flex:1;">
                        <select id="ticketLogsRecomingSlaUnit" class="border border-[#D9D9D9] rounded-lg" style="padding:10px 12px;flex:1;">
                            <option value="hour">Hour(s)</option>
                            <option value="business_day">Business day(s)</option>
                        </select>
                    </div>
                    <input type="datetime-local" id="ticketLogsRecomingSlaDeadline" class="w-full border border-[#D9D9D9] rounded-lg" style="padding:10px 12px;display:none;">
                    <p style="font-size:12px;color:#757575;margin-top:6px;">Counted from this schedule until the last valid survey. The SLA of the previous cycle is closed when you save.</p>
                </fieldset>

                <div style="padding:16px;background:#EEF4FF;color:#2F5BD3;border-radius:8px;font-size:13px;">
                    <p class="font-bold" style="margin-bottom:8px;">AFTER SAVING</p>
                    <ul style="padding-left:18px;list-style:disc;line-height:1.8;">
                        <li>Ticket progress changes to Recoming (<span id="ticketLogsRecomingAfterType">second visit scheduled</span>).</li>
                        <li>Unlock Service Form: the same form is reopened. The existing SPV Technician signature stays valid and the Agreement is not filled again.</li>
                        <li>The Technician stays the same as on the original ticket (no new assignment).</li>
                        <li id="ticketLogsRecomingAfterSurvey">A new satisfaction survey link is issued when the Service Form is saved completely.</li>
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
                $('#ticketLogsRecomingDate').attr('min', new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }) + 'T00:00').trigger('focus');
            }
        }
        // Tipe phone call: tanggal + jam telepon, bukan jadwal kunjungan. Survey phone call diisi langsung (tanpa link).
        function syncRecomingType() {
            const phone = $('input[name="ticket_logs_recoming_type"]:checked').val() === 'phone_call';
            $('#ticketLogsRecomingDateLabel').html((phone ? 'Call date &amp; time' : 'Follow-up visit date &amp; time') + ' <span style="color:#EC221F;">*</span>');
            $('#ticketLogsRecomingAfterType').text(phone ? 'phone call scheduled' : 'second visit scheduled');
            $('#ticketLogsRecomingAfterSurvey').text(phone
                ? 'The phone call survey is filled right after the call (no link), then the ticket becomes Satisfaction Submitted.'
                : 'A new satisfaction survey link is issued when the Service Form is saved completely.');
        }
        function syncRecomingSlaMode() {
            const deadline = $('input[name="ticket_logs_recoming_sla_mode"]:checked').val() === 'deadline';
            $('#ticketLogsRecomingSlaDuration').css('display', deadline ? 'none' : 'flex');
            $('#ticketLogsRecomingSlaDeadline').css('display', deadline ? 'block' : 'none');
        }
        $(document).on('change', 'input[name="ticket_logs_recoming_type"]', syncRecomingType);
        $(document).on('change', 'input[name="ticket_logs_recoming_sla_mode"]', syncRecomingSlaMode);

        $('#ticketLogsScheduleRecoming').on('click', function () { showTicketLogsRecoming(true); });
        $('#ticketLogsRecomingCancel').on('click', function () { showTicketLogsRecoming(false); });
        $('#ticketUpdateLogsModal').on('show.bs.modal', function () {
            $('#ticketLogsRecomingForm')[0].reset();
            $('#ticketLogsRecomingFeedback').text('');
            syncRecomingType();
            syncRecomingSlaMode();
            showTicketLogsRecoming(false);
        });
        $('#ticketLogsRecomingForm').on('submit', function (event) {
            event.preventDefault();
            if (recomingSaving || !currentUpdateLogsTicketId || !this.reportValidity()) return;
            const reasons = {
                'Spare part not available yet': 'sparepart_unavailable',
                'Further work needed': 'further_work_needed',
                'Site access / conditions': 'site_access',
                'Unfinished from previous work': 'unfinished_previous_work', // [API] kode diasumsikan
            };

            // AS-REC-04 / AC-REC-04: setup SLA wajib, ditolak di UI bila kosong (BE tetap memvalidasi)
            const slaDeadline = $('input[name="ticket_logs_recoming_sla_mode"]:checked').val() === 'deadline';
            const slaPayload = slaDeadline
                ? { sla_due_at: $('#ticketLogsRecomingSlaDeadline').val() }
                : { sla_value: $('#ticketLogsRecomingSlaValue').val(), sla_unit: $('#ticketLogsRecomingSlaUnit').val() };
            if (!(slaDeadline ? slaPayload.sla_due_at : Number(slaPayload.sla_value) >= 1)) {
                $('#ticketLogsRecomingFeedback').text('SLA for this cycle is required (duration or deadline).');
                return;
            }

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
                    recoming_type: $('input[name="ticket_logs_recoming_type"]:checked').val(),
                    reason: reasons[$('input[name="ticket_logs_recoming_reason"]:checked').val()],
                    planned_visit_at: $('#ticketLogsRecomingDate').val(),
                    notes: $('#ticketLogsRecomingNotes').val().trim(),
                    ...slaPayload,
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
        happy_call_completed: 'happy-call', // T04: records the log only; it never closes the ticket
        closed: 'close',                    // T05: separate request, guarded by the backend
    };

    let currentUpdateLogsTicketId = null;
    let ticketLogPhotoRowCount = 0;
    let currentTicketPhotos = [];
    let currentTicketSatisfaction = null;
    // survey tiket (dari GET /tickets/{id}): dipakai untuk gate Closed
    let currentTicketSurvey = { loaded: false, responses: null, filled: false };
    let closeGateActive = false;

    function showCloseGate(message, linkText, linkHref) {
        $('#ticketLogGateMessage').text(message);
        $('#ticketLogGateLink').toggleClass('hidden', !linkHref).attr('href', linkHref || '#').text(linkText || '');
        $('#ticketLogGateBox').removeClass('hidden').css('display', 'flex');
    }

    function hideCloseGate() {
        $('#ticketLogGateBox').addClass('hidden').css('display', '');
    }

    // Closed hanya boleh bila survey sudah disubmit dan tidak Needs review / Rejected
    function evaluateCloseGate() {
        const survey = currentTicketSurvey;
        if (!survey.loaded) return { blocked: true, silent: true };

        if (Array.isArray(survey.responses)) {
            const current = survey.responses[0];
            if (!current) {
                return { blocked: true, message: 'The satisfaction survey has not been submitted yet. Share the survey link with the customer first.' };
            }
            if (current.review_status === 'needs_review') {
                return {
                    blocked: true,
                    message: 'The latest Satisfaction Survey response is marked Needs review. Approve or reject it first.',
                    linkText: 'Open Review Response',
                    linkHref: `/satisfaction-survey#${current.id}/review`,
                };
            }
            if (current.review_status === 'rejected') {
                return {
                    blocked: true,
                    message: 'The latest response was rejected. Wait for the customer to submit a new response.',
                    linkText: 'Open Response',
                    linkHref: `/satisfaction-survey#${current.id}`,
                };
            }
            return { blocked: false };
        }

        // teknisi: hanya tahu sudah diisi atau belum (tanpa skor)
        return survey.filled
            ? { blocked: false }
            : { blocked: true, message: 'The satisfaction survey has not been submitted yet. Share the survey link with the customer first.' };
    }

    function updateCloseGate() {
        const isClosed = $('#ticketLogStep').val() === 'closed';
        const gate = isClosed ? evaluateCloseGate() : { blocked: false };
        closeGateActive = gate.blocked;

        if (gate.blocked && !gate.silent) {
            showCloseGate(gate.message, gate.linkText, gate.linkHref);
        } else {
            hideCloseGate();
        }

        $('#btn-add-ticket-log').prop('disabled', gate.blocked).css({ opacity: gate.blocked ? 0.5 : '', cursor: gate.blocked ? 'not-allowed' : '' });
    }

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

    // v3.0 bab 3: Happy Call & Schedule Recoming hanya Admin (manage), Close hanya Head (CAN_CLOSE). BE tetap menolak dengan 403.
    function applyTicketLogRoleGates() {
        const canManage = typeof CAN_MANAGE !== 'undefined' && CAN_MANAGE;
        const canClose = typeof CAN_CLOSE !== 'undefined' && CAN_CLOSE;
        $('#ticketLogStep option[value="happy_call_completed"]').prop('disabled', !canManage).toggleClass('hidden', !canManage);
        $('#ticketLogStep option[value="closed"]').prop('disabled', !canClose).toggleClass('hidden', !canClose);
        $('#ticketLogsScheduleRecoming').toggleClass('hidden', !canManage);
    }

    function resetTicketLogForm() {
        applyTicketLogRoleGates();
        $('#ticketLogStep').val('on_site');
        $('#ticketLogNote').val('');
        $('.ticket-log-satisfaction-field').val('');
        $('#ticketLogSatisfactionFields').addClass('hidden');
        $('#ticketLogPhotoFields').addClass('hidden');
        $('#ticketLogPhotoRows').empty();
        $('#ticketLogNote').removeClass('hidden');
        updateCloseGate();
    }

    function openTicketUpdateLogs(ticket) {
        currentUpdateLogsTicketId = ticket.id;
        currentTicketPhotos = [];
        currentTicketSatisfaction = null;
        currentTicketSurvey = { loaded: false, responses: null, filled: false };
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
                currentTicketSurvey = {
                    loaded: true,
                    responses: response.data?.survey_responses ?? null,
                    filled: !!response.data?.survey_filled,
                };
                renderTicketUpdateLogsRows(response.data?.logs || ticket.logs);
                updateCloseGate();
            },
            error: function () {
                // gagal memuat: jangan blokir selamanya, BE tetap menjaga gate Closed
                currentTicketSurvey = { loaded: true, responses: [], filled: true };
                updateCloseGate();
            }
        });
    }

    function submitTicketLog() {
        if (!currentUpdateLogsTicketId) return;

        const step = $('#ticketLogStep').val();
        if (step === 'closed' && closeGateActive) return;
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
                    text: 'Please rate all five satisfaction criteria (1-3).',
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
                    showCloseGate(xhr.responseJSON.message || 'Cannot set to Closed.', 'Open Review Response', `/satisfaction-survey#${gateId}/review`);
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
            updateCloseGate();

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
