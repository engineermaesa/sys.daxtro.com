{{-- Fill the satisfaction survey on behalf of the customer (API contract section 8: POST /tickets/{ticket}/survey-response).
     The form is built from the pinned template returned by GET /tickets/{ticket}/survey-link (data.template). --}}
<div class="modal fade" id="surveyOnBehalfModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 640px;">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="flex items-start justify-between" style="padding:22px 28px 12px">
                <div>
                    <h5 id="sob-title" class="text-[#115640] font-bold mb-1" style="font-size:20px">Fill Survey on Behalf of Customer</h5>
                    <p id="sob-subtitle" class="text-[#9A9A9A] mb-0" style="font-size:11px">The response is saved as Needs review. A reason is required.</p>
                </div>
                <button type="button" data-dismiss="modal" aria-label="Close"
                    style="width:32px;height:32px;font-size:12px" class="border border-[#D9D9D9] rounded-lg bg-white flex items-center justify-center cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="surveyOnBehalfForm" class="modal-body bg-[#F9FAFB] text-xs" style="padding:18px 28px;max-height:65vh;overflow-y:auto">
                <div id="sob-error" class="hidden" style="margin-bottom:10px;padding:10px 14px;border-radius:8px;background:#FBE0DF;color:#C0281F;font-size:11px"></div>
                <div id="sob-questions"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2" style="gap:10px;margin-top:6px">
                    <div>
                        <label class="block font-semibold mb-1" for="sob-signer-name">Signer name<span class="text-red-600">*</span></label>
                        <input type="text" id="sob-signer-name" required maxlength="255" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1" for="sob-signer-position">Signer position<span class="text-red-600">*</span></label>
                        <input type="text" id="sob-signer-position" required maxlength="255" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                </div>
                <div style="margin-top:10px">
                    <label class="block font-semibold mb-1" for="sob-reason">Reason<span class="text-red-600">*</span></label>
                    <input type="text" id="sob-reason" required maxlength="255" placeholder="Why is the survey filled by After Sales?"
                        class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                </div>
            </form>
            <div class="flex items-center justify-end gap-2 bg-white border-t border-t-[#D9D9D9]" style="padding:14px 28px">
                <button type="button" data-dismiss="modal" class="bg-white border border-[#D9D9D9] rounded-lg cursor-pointer hover:bg-[#E8E8E8]" style="font-size:12px;padding:8px 18px">Cancel</button>
                <button type="submit" form="surveyOnBehalfForm" id="sob-submit" class="bg-[#115640] text-white rounded-lg cursor-pointer hover:bg-[#0a3b2b] font-semibold" style="font-size:12px;padding:8px 18px">Submit</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var ticketId = null;
    var template = null;

    function esc(v) {
        return String(v === null || v === undefined ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // The survey-link response is the only source of the pinned template: keep it when the ticket detail loads it.
    $(document).on('ticket:survey-link', function (e, id, link) {
        ticketId = id;
        template = link && link.template ? link.template : null;
        var canManage = typeof CAN_MANAGE !== 'undefined' && CAN_MANAGE;
        $('#tdFillOnBehalf').toggleClass('hidden', !(canManage && template));
        // v3.0 AS-SVY-02: varian phone call diisi langsung setelah panggilan (tanpa link). [API] link.template.variant: site | phone_call
        var phone = !!template && template.variant === 'phone_call';
        $('#tdFillOnBehalf').text(phone ? 'Fill Phone Call Survey' : 'Fill on Behalf');
        $('#sob-title').text(phone ? 'Fill Phone Call Survey' : 'Fill Survey on Behalf of Customer');
        $('#sob-subtitle').text(phone
            ? 'Record the customer\'s answers given by phone. The ticket becomes Satisfaction Submitted. A reason is required.'
            : 'The response is saved as Needs review. A reason is required.');
    });

    function questionField(q) {
        var name = 'answer:' + q.question_key;
        var req = q.required ? ' required' : '';
        var label = '<label class="block font-semibold mb-1">' + esc(q.label) + (q.required ? '<span class="text-red-600">*</span>' : '') + '</label>' +
            (q.hint ? '<p class="mb-1" style="font-size:10px;color:#9A9A9A">' + esc(q.hint) + '</p>' : '');
        var input;

        if (q.type === 'rating') {
            // rating value = scale label index + 1 (integer 1-3)
            var labels = template.scale_labels || [];
            input = '<select name="' + name + '" data-key="' + esc(q.question_key) + '" data-type="rating"' + req + ' class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2"><option value="">Select an answer</option>' +
                labels.map(function (l, i) { return '<option value="' + (i + 1) + '">' + (i + 1) + ' · ' + esc(l) + '</option>'; }).join('') + '</select>';
        } else if (q.type === 'yes_no') {
            input = '<select name="' + name + '" data-key="' + esc(q.question_key) + '" data-type="yes_no"' + req + ' class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2"><option value="">Select an answer</option><option value="1">Yes</option><option value="0">No</option></select>';
        } else if (q.type === 'choice') {
            input = '<select name="' + name + '" data-key="' + esc(q.question_key) + '" data-type="choice"' + req + ' class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2"><option value="">Select an answer</option>' +
                (q.options || []).map(function (o) { return '<option value="' + esc(o) + '">' + esc(o) + '</option>'; }).join('') + '</select>';
        } else {
            input = '<textarea name="' + name + '" data-key="' + esc(q.question_key) + '" data-type="text" rows="2"' + req + ' class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2"></textarea>';
        }
        return '<div style="margin-bottom:10px">' + label + input + '</div>';
    }

    function renderQuestions() {
        // Iterate the template; never hardcode question keys or the number of questions.
        var html = (template.sections || []).filter(function (s) { return s.is_active !== false; }).map(function (s) {
            var qs = (s.questions || []).filter(function (q) { return q.is_active !== false; });
            if (!qs.length) return '';
            return '<p class="font-bold uppercase" style="font-size:9.5px;letter-spacing:.05em;color:#115640;margin:6px 0 8px">' + esc(s.title || '') + '</p>' + qs.map(questionField).join('');
        }).join('');

        html += '<div id="sob-issue-wrap" class="hidden" style="margin-bottom:10px"><label class="block font-semibold mb-1">Machine issue note<span class="text-red-600">*</span></label>' +
            '<textarea id="sob-machine-issue" rows="2" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2"></textarea></div>';
        $('#sob-questions').html(html);
    }

    // machine_ok = false requires machine_issue_note
    $(document).on('change', '#sob-questions select[data-key="machine_ok"]', function () {
        $('#sob-issue-wrap').toggleClass('hidden', $(this).val() !== '0');
    });

    $(document).on('click', '#tdFillOnBehalf', function () {
        if (!template) return;
        $('#sob-error').addClass('hidden').text('');
        $('#sob-signer-name, #sob-signer-position, #sob-reason').val('');
        renderQuestions();
        $('#surveyOnBehalfModal').modal('show');
    });

    $('#surveyOnBehalfForm').on('submit', function (e) {
        e.preventDefault();
        var answers = {};
        $('#sob-questions [data-key]').each(function () {
            var $f = $(this);
            var v = $f.val();
            if (v === '' || v === null) return;
            var t = $f.data('type');
            answers[$f.data('key')] = t === 'rating' ? parseInt(v, 10) : t === 'yes_no' ? v === '1' : v;
        });

        var payload = {
            signer_name: $('#sob-signer-name').val(),
            signer_position: $('#sob-signer-position').val(),
            reason: $('#sob-reason').val(),
            answers: answers,
            machine_issue_note: answers.machine_ok === false ? ($('#sob-machine-issue').val() || null) : null
        };

        var $btn = $('#sob-submit').prop('disabled', true);
        $('#sob-error').addClass('hidden').text('');

        $.ajax({
            url: '/api/aftersales/tickets/' + ticketId + '/survey-response',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            headers: { Accept: 'application/json' }
        }).done(function () {
            $('#surveyOnBehalfModal').modal('hide');
            Swal.fire({ title: 'Saved', text: 'The survey was recorded and is waiting for review.', icon: 'success', confirmButtonColor: '#115640' });
            if (typeof loadTickets === 'function') loadTickets();
            if (typeof loadTicketSurveyLink === 'function') loadTicketSurveyLink(ticketId);
        }).fail(function (xhr) {
            var body = xhr.responseJSON || {};
            var fields = [];
            Object.keys(body.errors || {}).forEach(function (k) { fields = fields.concat(body.errors[k]); });
            $('#sob-error').text(fields.length ? fields.join(' ') : (body.message || 'Failed to save the survey response.')).removeClass('hidden');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
})();
</script>
@endpush
