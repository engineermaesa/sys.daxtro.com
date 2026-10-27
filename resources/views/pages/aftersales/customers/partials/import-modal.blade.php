{{-- Import Customer modal: Download Template -> Upload File -> Preview & Apply (API contract section 4) --}}
<div class="modal fade" id="customerImportModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 800px;">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">

            {{-- HEADER --}}
            <div class="border-b border-b-[#D9D9D9]" style="padding:22px 28px 14px">
                <div class="flex items-start justify-between">
                    <div>
                        <h5 class="text-[#115640] font-bold mb-1" style="font-size:20px">Import Customer</h5>
                        <p class="text-[#9A9A9A] mb-0" style="font-size:11px">Upload, review, and apply customer and machine data.</p>
                    </div>
                    <button type="button" data-dismiss="modal" aria-label="Close"
                        style="width:32px;height:32px;font-size:12px" class="border border-[#D9D9D9] rounded-lg bg-white text-[#1E1E1E] flex items-center justify-center">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- STEPPER --}}
                <div class="flex items-center justify-center mt-3" style="gap:14px" id="ci-stepper">
                    @foreach (['Download Template', 'Upload File', 'Preview & Apply'] as $i => $label)
                        <div class="flex items-center gap-2 ci-step-item" data-step="{{ $i + 1 }}">
                            <span style="width:26px;height:26px;font-size:11px" class="ci-step-dot rounded-full flex items-center justify-center font-bold border border-[#D9D9D9] text-[#757575] bg-white">{{ $i + 1 }}</span>
                            <span style="font-size:11px" class="ci-step-label font-semibold text-[#757575]">{{ $label }}</span>
                        </div>
                        @if ($i < 2)
                            <div class="h-px bg-[#D9D9D9]" style="width:90px"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- BODY --}}
            <div class="modal-body bg-[#F9FAFB] text-xs" style="padding:22px 28px;max-height: 70vh; overflow-y: auto;">

                {{-- STEP 1 --}}
                <div class="ci-panel" data-panel="1">
                    <div class="flex items-center justify-between border border-[#D9D9D9] rounded-xl" style="gap:16px;padding:18px 20px;background:linear-gradient(120deg,#EAF5F0,#F8FBFA)">
                        <div class="flex items-center" style="gap:16px">
                            <div style="width:46px;height:46px;background:#CFE5DB;color:#115640;font-size:20px" class="rounded-xl flex items-center justify-center shrink-0">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#115640] mb-1">Start with our template</p>
                                <p class="text-[#1E1E1E] mb-1" style="font-size:15px">Prepare customer and machine data</p>
                                <p class="text-[#757575] mb-0" style="font-size:10.5px">Use the latest template so every column can be validated correctly during import.</p>
                            </div>
                        </div>
                        {{-- I01: direct download of the real template (no batch is created) --}}
                        <a href="/api/aftersales/customers/imports/template" style="padding:10px 20px;font-size:12px" class="bg-[#115640] text-white rounded-lg whitespace-nowrap font-semibold">
                            <i class="bi bi-file-earmark-text"></i> Download .xlsx
                        </a>
                    </div>

                    <div class="bg-white border border-[#D9D9D9] rounded-xl mt-3" style="padding:20px">
                        <div class="flex items-center justify-between" style="margin-bottom:14px">
                            <p class="text-[#1E1E1E] mb-0" style="font-size:12px">Before you fill the file</p>
                            <span class="bg-[#F0F0F0] text-[#757575] rounded-full" style="font-size:9px;padding:4px 10px">5 formatting rules</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2" style="gap:10px">
                            {{-- TODO(API): rule texts follow the v2.2 template (IM-84). The template file and its Guide sheet come from GET /api/aftersales/customers/imports/template. --}}
                            @foreach ([
                                ['01', 'One machine per row', 'Repeat the customer details when a customer has multiple machines.'],
                                ['02', 'Complete required fields', 'Customer Name, Machine Category, and Capacity (Daxtro machines) are required.'],
                                ['03', 'Daxtro Machine column', 'Yes or empty = Daxtro. For No, Brand and Machine Name are required; Serial Number, Warranty, and PM Contract are ignored.'],
                                ['04', 'Keep formats consistent', 'Phone and Serial Number are text. Dates use dd/mm/yyyy. Machine Name is optional for Daxtro machines (generated automatically).'],
                                ['05', 'Use valid contract values', 'Enter Basic or Premium. Leave the cell empty for None.'],
                            ] as [$no, $title, $desc])
                                <div class="flex items-center" style="gap:12px;padding:10px 12px;background:#FAFAFA;border:1px solid #EDEDED;border-radius:8px">
                                    <span style="width:28px;height:28px;font-size:10px;background:#CFE5DB;color:#115640" class="rounded-md font-bold flex items-center justify-center shrink-0">{{ $no }}</span>
                                    <div>
                                        <p class="font-semibold text-[#1E1E1E] mb-0" style="font-size:11px">{{ $title }}</p>
                                        <p class="text-[#757575] mb-0" style="font-size:10px">{{ $desc }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div style="margin-top:10px;padding:10px 12px;background:#FFFCF3;border:1px solid #EBE3C8;border-radius:8px">
                            <p class="font-semibold mb-1" style="font-size:11px;color:#7A5F00">Capacity is in tons</p>
                            <p class="mb-0" style="font-size:10px;color:#757575">When converting an old spreadsheet, divide the number in the TD, CD, BD, FD, SD, and RO codes by 10 (TD 150 = 15 ton). For RD/VCD codes, divide by 10 then multiply by 3 (RD 20 = 6 ton).</p>
                        </div>
                        <p class="text-[#9A9A9A] mb-0" style="font-size:9.5px;margin:18px 0 0">Do not rename, remove, or reorder columns in the downloaded template. Leave Warranty End empty to use Warranty Start + 12 months.</p>
                    </div>
                </div>

                {{-- STEP 2 --}}
                <div class="ci-panel hidden" data-panel="2">
                    <div class="bg-white border border-[#D9D9D9] rounded-xl" style="padding:20px">
                        <p class="font-bold uppercase text-[#115640] mb-1" style="font-size:9px;letter-spacing:.06em">Upload file</p>
                        <p class="text-[#1E1E1E] mb-1" style="font-size:15px">Upload your completed template</p>
                        <p class="text-[#9A9A9A]" style="font-size:9.5px;margin-bottom:14px">.xlsx format only, with a maximum file size of 20 MB.</p>

                        <div id="ci-dropzone"
                            class="flex flex-col items-center justify-center text-center"
                            style="border:1px dashed #7FB5A0;border-radius:10px;background:#F8FAF9;padding:34px 16px">
                            <div style="width:48px;height:48px;background:#CFE5DB;color:#115640;font-size:20px;margin-bottom:14px" class="rounded-full flex items-center justify-center">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <p class="font-bold text-[#2D3748]" style="font-size:13px;margin-bottom:4px" id="ci-drop-title">Drop your completed template here</p>
                            <p class="text-[#9A9A9A]" style="font-size:10.5px;margin-bottom:12px" id="ci-drop-sub">or choose it from your computer</p>
                            <button type="button" id="ci-browse-btn" style="padding:8px 18px;font-size:11px"
                                class="bg-[#115640] text-white rounded-lg font-semibold">Browse file</button>
                            <input type="file" id="ci-file-input" accept=".xlsx" class="hidden">
                            <p class="text-[#9A9A9A] mb-0" style="font-size:8.5px;margin-top:12px">.xlsx only · maximum 20 MB</p>
                        </div>
                        <p class="text-[#EC221F] mb-0 hidden text-center" style="font-size:11px;margin-top:12px" id="ci-file-error"></p>
                    </div>
                </div>

                {{-- STEP 3 (rendered from the API response) --}}
                <div class="ci-panel hidden" data-panel="3">
                    <div id="ci-result-banner" class="hidden" style="margin-bottom:12px;padding:10px 14px;border-radius:8px;background:#E6F4EF;color:#115640;font-size:11px"></div>
                    <div id="ci-step3-error" class="hidden" style="margin-bottom:12px;padding:10px 14px;border-radius:8px;background:#FBE0DF;color:#C0281F;font-size:11px"></div>

                    <div id="ci-summary" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;margin-bottom:14px"></div>

                    <div class="bg-white overflow-hidden" style="border:1px solid #E3E3E3;border-radius:10px">
                        <div class="flex items-center justify-between" style="padding:10px 12px;gap:12px">
                            <div class="inline-flex" style="background:#F0F0F0;border-radius:8px;padding:3px;gap:2px" id="ci-filters">
                                @foreach (['all' => 'All', 'decision' => 'Needs decision', 'warning' => 'Warning', 'error' => 'Error'] as $k => $l)
                                    <button type="button" data-filter="{{ $k }}" style="font-size:10.5px;padding:5px 12px;border-radius:6px"
                                        class="ci-filter-btn font-semibold {{ $k === 'all' ? 'bg-white text-[#1E1E1E] shadow-sm' : 'text-[#757575]' }}">{{ $l }}</button>
                                @endforeach
                            </div>
                            <div class="relative" style="width:220px">
                                <i class="bi bi-search absolute text-[#757575]" style="left:10px;top:50%;transform:translateY(-50%);font-size:11px"></i>
                                <input type="text" id="ci-search" placeholder="Search customer or serial"
                                    style="width:100%;font-size:10.5px;padding:7px 10px 7px 30px;border:1px solid #D9D9D9;border-radius:8px"
                                    class="focus:outline-none!">
                            </div>
                        </div>

                        <div style="overflow-x:auto">
                            <table class="w-full" data-no-cards style="font-size:10.5px;color:#2D3748">
                                <thead style="background:#F7F9F8;color:#5F6B66;font-size:9.5px;text-transform:uppercase">
                                    <tr style="border-top:1px solid #EDEDED;border-bottom:1px solid #EDEDED">
                                        @foreach (['Row', 'Customer', 'Phone', 'Machine', 'Serial Number', 'Status', 'Detail / Decision'] as $h)
                                            <th class="font-semibold" style="padding:9px 12px;text-align:left;white-space:nowrap">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody id="ci-rows"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="flex items-center justify-between border-t border-t-[#D9D9D9] bg-white text-xs" style="padding:14px 28px">
                <div>
                    <button type="button" id="ci-error-btn"
                        class="hidden bg-white border border-[#D9D9D9] px-3 py-1.5 rounded-lg text-xs font-semibold">Download Error File</button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="ci-back-btn"
                        class="bg-white border border-[#D9D9D9] text-[#1E1E1E] px-3 py-1.5 rounded-lg text-xs">Back</button>
                    <button type="button" id="ci-next-btn"
                        class="bg-[#115640] text-white px-4 py-1.5 rounded-lg text-xs hover:bg-[#0d4633]">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var API = '/api/aftersales/customers';
    var $modal = $('#customerImportModal');
    var MAX_SIZE = 20 * 1024 * 1024;

    var step = 1;
    var selectedFile = null;
    var preview = null;          // { import, summary, rows } = response.data of I02/I03/I04/I05
    var busy = false;
    var currentFilter = 'all';
    var provinces = null;        // C05 options, loaded once
    var queue = Promise.resolve(); // serializes review requests (never send two revisions in parallel)

    var STATUS = {
        exact: { label: 'Exact match', bg: '#D3E8DE', fg: '#115640' },
        possible: { label: 'Possible match', bg: '#FBEFC8', fg: '#7A5F00' },
        'new': { label: 'New', bg: '#DCEBFA', fg: '#1D5FA8' },
        already_exists: { label: 'Already exists', bg: '#E3E3E3', fg: '#5F6B66' },
        error: { label: 'Error', bg: '#FBE0DF', fg: '#C0281F' }
    };

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function errorText(xhr, fallback) {
        var body = xhr && xhr.responseJSON ? xhr.responseJSON : {};
        var fields = [];
        Object.keys(body.errors || {}).forEach(function (k) { fields = fields.concat(body.errors[k]); });
        return fields.length ? fields.join(' ') : (body.message || fallback);
    }

    function isApplied() { return !!preview && preview.import.status === 'applied'; }

    // ---------- step navigation ----------
    function render() {
        $modal.find('.ci-panel').addClass('hidden').filter('[data-panel="' + step + '"]').removeClass('hidden');

        $modal.find('.ci-step-item').each(function () {
            var n = +$(this).data('step');
            var active = n <= step;
            $(this).find('.ci-step-dot')
                .toggleClass('bg-[#115640] text-white border-[#115640]', active)
                .toggleClass('bg-white text-[#757575] border-[#D9D9D9]', !active);
            $(this).find('.ci-step-label')
                .toggleClass('text-[#115640]', active)
                .toggleClass('text-[#757575]', !active);
        });

        $modal.find('.modal-dialog').css('max-width', step === 3 ? '960px' : '800px');
        $('#ci-error-btn').toggleClass('hidden', step !== 3);

        var $next = $('#ci-next-btn');
        if (step === 3) {
            $next.text(isApplied() ? 'Done' : 'Apply');
            $('#ci-back-btn').toggleClass('hidden', isApplied());
        } else {
            $next.text('Next');
            $('#ci-back-btn').removeClass('hidden');
        }
        $next.prop('disabled', busy || (step === 2 && !selectedFile) || (step === 3 && !isApplied() && !canApply()));
        $next.css('opacity', $next.prop('disabled') ? '.5' : '1');
    }

    function setBusy(value) {
        busy = value;
        render();
    }

    function resetAll() {
        step = 1;
        selectedFile = null;
        preview = null;
        busy = false;
        currentFilter = 'all';
        queue = Promise.resolve();
        $('#ci-file-input').val('');
        $('#ci-file-error').addClass('hidden').text('');
        $('#ci-drop-title').text('Drop your completed template here');
        $('#ci-drop-sub').text('or choose it from your computer');
        $('#ci-search').val('');
        $('#ci-rows, #ci-summary').empty();
        $('#ci-result-banner, #ci-step3-error').addClass('hidden').text('');
        render();
    }

    // ---------- step 2: upload (I02) ----------
    function setFile(file) {
        var $err = $('#ci-file-error');
        if (!file) return;
        if (!/\.xlsx$/i.test(file.name)) {
            $err.text('Only .xlsx files are accepted.').removeClass('hidden');
            selectedFile = null;
            $('#ci-file-input').val('');
            render();
            return;
        }
        if (file.size > MAX_SIZE) {
            $err.text('File size exceeds 20 MB.').removeClass('hidden');
            selectedFile = null;
            $('#ci-file-input').val('');
            render();
            return;
        }
        $err.addClass('hidden');
        selectedFile = file;
        $('#ci-drop-title').text(file.name);
        $('#ci-drop-sub').text((file.size / 1024 / 1024).toFixed(2) + ' MB');
        render();
    }

    function upload() {
        var form = new FormData();
        form.append('file', selectedFile);
        $('#ci-file-error').addClass('hidden').text('');
        setBusy(true);

        $.ajax({
            url: API + '/imports',
            method: 'POST',
            data: form,
            processData: false,
            contentType: false, // let the browser set the multipart boundary
            dataType: 'json'
        }).done(function (response) {
            preview = response.data;
            step = 3;
            renderPreview();
        }).fail(function (xhr) {
            // stay on step 2 and show errors.file / message
            $('#ci-file-error').text(errorText(xhr, 'Failed to upload file.')).removeClass('hidden');
        }).always(function () {
            setBusy(false);
        });
    }

    // ---------- step 3: preview ----------
    function badge(status) {
        var s = STATUS[status] || { label: status, bg: '#E3E3E3', fg: '#5F6B66' };
        return '<span style="background:' + s.bg + ';color:' + s.fg + ';font-size:9px;padding:3px 8px;border-radius:5px;font-weight:600;white-space:nowrap">' + esc(s.label) + '</span>';
    }

    function needsDecision(row) {
        return row.review.status === 'possible' && !row.decision;
    }

    function canApply() {
        if (!preview || busy) return false;
        return !preview.rows.some(needsDecision);
    }

    function renderSummary() {
        var s = preview.summary || {};
        var cards = [
            ['exact', 'Exact match', '#115640'], ['possible', 'Possible match', '#7A5F00'],
            ['new', 'New', '#115640'], ['already_exists', 'Already exists', '#2D3748'],
            ['warning', 'Warning', '#7A5F00'], ['error', 'Error', '#C0281F']
        ];
        $('#ci-summary').html(cards.map(function (c) {
            return '<div class="bg-white text-center" style="border:1px solid #E3E3E3;border-radius:8px;padding:10px 4px">' +
                '<p class="font-bold mb-0" style="font-size:18px;line-height:1.2;color:' + c[2] + '">' + (s[c[0]] || 0) + '</p>' +
                '<p class="mb-0" style="font-size:9.5px;color:#9A9A9A">' + c[1] + '</p></div>';
        }).join(''));
    }

    function rowCustomer(row) {
        return (row.review.customer && row.review.customer.name) || row.input['Customer Name*'] || '-';
    }

    // TODO(API): import preview rows (I02-I05) should return review.machine.is_daxtro, compressor and refrigerant.
    // Until then the Daxtro Machine column of the uploaded template (row.input) is read directly; empty = Yes.
    function rowIsDaxtro(row) {
        var m = row.review.machine;
        if (m && typeof m.is_daxtro !== 'undefined' && m.is_daxtro !== null) return !!m.is_daxtro && m.is_daxtro !== '0';
        var raw = String(row.input['Daxtro Machine'] || '').trim().toLowerCase();
        return raw !== 'no' && raw !== 'n' && raw !== '0';
    }

    function rowBrand(row) {
        return (row.review.machine && row.review.machine.brand) || row.input['Brand'] || '';
    }

    function rowSerial(row) {
        if (!rowIsDaxtro(row)) return '-';
        return (row.review.machine && row.review.machine.serial_number) || row.input['Serial Number'] || '-';
    }

    function decisionLabel(row) {
        var d = row.decision;
        if (!d) return '';
        if (d.action === 'skip') return 'Skip row';
        if (d.action === 'create_new') return 'Create new';
        if (d.customer_id) return 'Link to existing customer #' + d.customer_id;
        if (d.lead_id) return 'Link to Sales Deal #' + d.lead_id;
        if (d.row_id) return 'Merge with row ' + d.row_id;
        return 'Link';
    }

    function detailText(row) {
        var r = row.review;
        if (r.errors && r.errors.length) return '<span style="color:#C0281F">' + esc(r.errors.join('; ')) + '</span>';
        if (row.decision) return esc(decisionLabel(row) + (r.keep_machine_archived && row.decision.action !== 'skip' ? '; existing machine stays archived and will not be copied or moved.' : ''));
        if (r.keep_machine_archived) return 'Customer will be matched or created as new; existing machine stays archived and will not be copied or moved.';
        if (r.warnings && r.warnings.length) return esc(r.warnings.slice(0, 2).join('; ')) + (r.warnings.length > 2 ? ' (+' + (r.warnings.length - 2) + ')' : '');
        return esc({ exact: 'Linked automatically', 'new': 'Ready to create', already_exists: 'Serial already in system, skipped', possible: 'Decision required' }[r.status] || '');
    }

    function candidateLabel(c) {
        if (c.type === 'customer') return 'Customer: ' + (c.name || '') + ' (' + (c.phone || '-') + ')';
        if (c.type === 'lead') return 'Sales Deal: ' + (c.name || '') + ' (' + (c.phone || '-') + ')';
        return 'Row ' + (c.row_number || c.id) + ': ' + (c.name || '');
    }

    function candidateValue(c) {
        return (c.type === 'customer' ? 'customer_id' : c.type === 'lead' ? 'lead_id' : 'row_id') + ':' + c.id;
    }

    // Decision card shown under a Possible match row (Link to existing / Create new / Skip row).
    function decisionRow(row) {
        var cands = (row.review.candidates || []);
        var d = row.decision || {};
        var linkValue = d.customer_id ? 'customer_id:' + d.customer_id : d.lead_id ? 'lead_id:' + d.lead_id : d.row_id ? 'row_id:' + d.row_id : '';
        var action = d.action || '';
        var locked = isApplied();

        var options = cands.map(function (c) {
            var v = candidateValue(c);
            return '<option value="' + v + '"' + (v === linkValue ? ' selected' : '') + '>' + esc(candidateLabel(c)) + '</option>';
        }).join('');

        function choice(value, label) {
            var on = action === value;
            return '<label class="ci-choice bg-white flex items-center justify-center mb-0" style="flex:1;gap:6px;border:1px solid ' + (on ? '#115640' : '#E3E3E3') + ';background:' + (on ? '#EAF5F0' : '#fff') + ';border-radius:8px;padding:8px;font-size:10px;white-space:nowrap">' +
                '<input type="radio" name="ci-decision-' + row.id + '" value="' + value + '" data-row="' + row.id + '"' + (on ? ' checked' : '') + (locked ? ' disabled' : '') + ' style="accent-color:#115640">' +
                '<span class="font-semibold">' + label + '</span></label>';
        }

        return '<tr class="ci-sub" data-parent="' + row.id + '" data-type="decision" style="background:#FFFCF3;border-top:1px solid #EDEDED"><td colspan="7" style="padding:10px 12px">' +
            '<div class="flex items-stretch" style="gap:8px;flex-wrap:wrap">' +
            '<div style="width:110px;flex-shrink:0"><p class="font-bold uppercase mb-1" style="font-size:8.5px;color:#7A5F00">Possible duplicate</p>' +
            '<p class="font-bold mb-0" style="font-size:10.5px;color:#1E1E1E">Choose how to handle row ' + esc(row.row_number) + '</p></div>' +
            '<div class="bg-white" style="flex:1.4;min-width:170px;border:1px solid #EBE3C8;border-radius:8px;padding:8px 10px"><p class="uppercase mb-1" style="font-size:8.5px;color:#9A9A9A">From file</p>' +
            '<p class="font-bold mb-0" style="font-size:10.5px">' + esc(rowCustomer(row)) + '</p>' +
            '<p class="mb-0" style="font-size:9.5px;color:#757575">' + esc((row.review.customer && row.review.customer.phone) || row.input.Phone || '-') + '</p></div>' +
            '<div class="bg-white" style="flex:1.4;min-width:170px;border:1px solid #EBE3C8;border-radius:8px;padding:8px 10px"><p class="uppercase mb-1" style="font-size:8.5px;color:#9A9A9A">Existing match</p>' +
            (cands.length ? '<select class="ci-candidate" data-row="' + row.id + '"' + (locked ? ' disabled' : '') + ' style="font-size:10px;max-width:100%;border:1px solid #D9D9D9;border-radius:6px;padding:3px 6px">' + options + '</select>'
                : '<p class="mb-0" style="font-size:10px;color:#757575">No candidate found</p>') + '</div>' +
            choice('link', 'Link to existing') + choice('create_new', 'Create new') + choice('skip', 'Skip row') +
            '</div></td></tr>';
    }

    // Province / City correction (names go into input["Province"] / input["City / Region"]).
    function locationRow(row) {
        return '<tr class="ci-sub" data-parent="' + row.id + '" data-type="' + (row.review.errors.length ? 'error' : 'warning') + '" style="background:#FFFCF3;border-top:1px solid #EDEDED"><td colspan="7" style="padding:8px 12px">' +
            '<div class="flex items-center" style="gap:8px;flex-wrap:wrap"><span style="font-size:10px;color:#7A5F00;font-weight:600">Fix location</span>' +
            '<select class="ci-province" data-row="' + row.id + '" style="font-size:10px;border:1px solid #D9D9D9;border-radius:6px;padding:4px 8px;max-width:180px"><option value="">Select Province</option></select>' +
            '<select class="ci-city" data-row="' + row.id + '" disabled style="font-size:10px;border:1px solid #D9D9D9;border-radius:6px;padding:4px 8px;max-width:200px"><option value="">Select City</option></select>' +
            '<button type="button" class="ci-save-location bg-white border border-[#D9D9D9] rounded-lg" data-row="' + row.id + '" style="font-size:10px;padding:4px 12px" disabled>Save</button>' +
            '</div></td></tr>';
    }

    function needsLocationFix(row) {
        var all = (row.review.warnings || []).concat(row.review.errors || []).join(' ');
        return !isApplied() && /province|city|region/i.test(all);
    }

    function renderRows() {
        var html = '';
        preview.rows.forEach(function (row) {
            var r = row.review;
            var type = needsDecision(row) ? 'decision' : (r.errors.length ? 'error' : (r.warnings.length ? 'warning' : r.status));
            var machineName = (r.machine && r.machine.machine_name) || row.input['Machine Name*'] || row.input['Machine Name'] || '-';
            var category = row.input['Machine Category*'] ? ' · ' + row.input['Machine Category*'] : '';
            var nonDaxtro = rowIsDaxtro(row) ? '' : '<div style="font-size:9.5px;color:#757575">Non-Daxtro' + (rowBrand(row) ? ' · ' + esc(rowBrand(row)) : '') + '</div>';
            var resultLabel = row.result && row.result.machine_status === 'kept_archived' ? 'Customer saved; machine kept archived' : (row.result && row.result.status);
            var result = row.result ? ' <span style="color:#115640;font-size:9px">[' + esc(resultLabel) + ']</span>' : '';

            html += '<tr class="ci-row" data-id="' + row.id + '" data-type="' + type + '" data-status="' + esc(r.status) + '" data-warning="' + (r.warnings.length ? 1 : 0) + '" data-error="' + (r.errors.length ? 1 : 0) + '" data-decision="' + (needsDecision(row) ? 1 : 0) + '" style="border-top:1px solid #EDEDED">' +
                '<td style="padding:10px 12px">' + esc(row.row_number) + '</td>' +
                '<td style="padding:10px 12px">' + esc(rowCustomer(row)) + '</td>' +
                '<td style="padding:10px 12px;white-space:nowrap">' + esc((r.customer && r.customer.phone) || row.input.Phone || '-') + '</td>' +
                '<td style="padding:10px 12px">' + esc(machineName + category) + nonDaxtro + '</td>' +
                '<td style="padding:10px 12px;white-space:nowrap">' + esc(rowSerial(row)) + '</td>' +
                '<td style="padding:10px 12px">' + badge(r.status) + result + '</td>' +
                '<td style="padding:10px 12px">' + detailText(row) + '</td></tr>';

            if (r.status === 'possible') html += decisionRow(row);
            if (needsLocationFix(row)) html += locationRow(row);
        });
        $('#ci-rows').html(html || '<tr><td colspan="7" style="padding:16px;text-align:center;color:#757575">No rows</td></tr>');
        applyFilter(currentFilter);
        hydrateLocationRows();
    }

    function renderBanner() {
        var $b = $('#ci-result-banner');
        if (!isApplied()) { $b.addClass('hidden').text(''); return; }
        var i = preview.import;
        var archivedMachines = preview.rows.filter(function (row) { return row.result && row.result.machine_status === 'kept_archived'; }).length;
        $b.text('Import applied: ' + i.created_count + ' new customer(s), ' + i.matched_count + ' row(s) linked to existing customers, ' + i.error_count + ' error row(s) skipped.' + (archivedMachines ? ' ' + archivedMachines + ' machine row(s) kept in archive without copying or moving.' : '')).removeClass('hidden');
    }

    function renderPreview() {
        $('#ci-step3-error').addClass('hidden').text('');
        renderSummary();
        renderRows();
        renderBanner();
        render();
    }

    function applyFilter(filter) {
        currentFilter = filter;
        var q = ($('#ci-search').val() || '').toLowerCase();

        $('.ci-filter-btn').each(function () {
            var on = $(this).data('filter') === filter;
            $(this).toggleClass('bg-white text-[#1E1E1E] shadow-sm', on).toggleClass('text-[#757575]', !on);
        });

        // Filtering is done here: backend has no filter on the preview.
        $('#ci-rows .ci-row').each(function () {
            var $r = $(this);
            var ok = filter === 'all' ||
                (filter === 'decision' && $r.data('decision') === 1) ||
                (filter === 'warning' && $r.data('warning') === 1) ||
                (filter === 'error' && $r.data('error') === 1);
            var textOk = !q || $r.text().toLowerCase().indexOf(q) !== -1;
            var show = ok && textOk;
            $r.toggleClass('hidden', !show);
            $('#ci-rows .ci-sub[data-parent="' + $r.data('id') + '"]').toggleClass('hidden', !show);
        });
    }

    // ---------- review (I04) ----------
    function sendReview(rows) {
        // serialize: each request waits for the previous one and always uses the latest lock_version
        queue = queue.then(function () {
            return new Promise(function (resolve) {
                setBusy(true);
                $.ajax({
                    url: API + '/imports/' + preview.import.id + '/review',
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ lock_version: preview.import.lock_version, rows: rows }),
                    dataType: 'json'
                }).done(function (response) {
                    preview = response.data; // replace the whole state: other rows may change too
                    renderPreview();
                }).fail(function (xhr) {
                    $('#ci-step3-error').text(errorText(xhr, 'Failed to save review.')).removeClass('hidden');
                    if (xhr.status === 409) refreshPreview();
                }).always(function () {
                    setBusy(false);
                    resolve();
                });
            });
        });
        return queue;
    }

    function refreshPreview() {
        return $.getJSON(API + '/imports/' + preview.import.id).done(function (response) {
            preview = response.data;
            renderPreview();
        });
    }

    function buildDecision(rowId, action) {
        if (action === 'create_new') return { action: 'create_new' };
        if (action === 'skip') return { action: 'skip' };
        var $sel = $('.ci-candidate[data-row="' + rowId + '"]');
        var raw = $sel.val();
        if (!raw) return null; // no candidate to link
        var parts = raw.split(':');
        var d = { action: 'link' };
        d[parts[0]] = parseInt(parts[1], 10);
        return d;
    }

    $modal.on('change', 'input[type="radio"][data-row]', function () {
        if (isApplied()) return;
        var rowId = parseInt($(this).data('row'), 10);
        var decision = buildDecision(rowId, $(this).val());
        if (!decision) {
            $('#ci-step3-error').text('There is no existing customer to link to. Choose Create new or Skip row.').removeClass('hidden');
            renderRows();
            return;
        }
        sendReview([{ id: rowId, decision: decision }]);
    });

    $modal.on('change', '.ci-candidate', function () {
        var rowId = parseInt($(this).data('row'), 10);
        var $checked = $('input[name="ci-decision-' + rowId + '"]:checked');
        if ($checked.val() === 'link') {
            sendReview([{ id: rowId, decision: buildDecision(rowId, 'link') }]);
        }
    });

    // ---------- Province / City correction (C05/C06 -> input names) ----------
    function loadProvinces() {
        if (provinces) return Promise.resolve(provinces);
        return Promise.resolve($.getJSON(API + '/provinces')).then(function (response) {
            provinces = response.data || [];
            return provinces;
        });
    }

    function hydrateLocationRows() {
        var $selects = $('#ci-rows .ci-province');
        if (!$selects.length) return;
        loadProvinces().then(function (list) {
            var opts = '<option value="">Select Province</option>' + list.map(function (p) {
                return '<option value="' + p.id + '">' + esc(p.name) + '</option>';
            }).join('');
            $selects.each(function () { $(this).html(opts); });
        });
    }

    $modal.on('change', '.ci-province', function () {
        var rowId = $(this).data('row');
        var $city = $('.ci-city[data-row="' + rowId + '"]').prop('disabled', true).html('<option value="">Select City</option>');
        $('.ci-save-location[data-row="' + rowId + '"]').prop('disabled', true);
        var provinceId = $(this).val();
        if (!provinceId) return;
        $.getJSON(API + '/regions', { province_id: provinceId }).done(function (response) {
            $city.html('<option value="">Select City</option>' + (response.data || []).map(function (r) {
                return '<option value="' + esc(r.name) + '">' + esc(r.name) + '</option>';
            }).join('')).prop('disabled', false);
        });
    });

    $modal.on('change', '.ci-city', function () {
        $('.ci-save-location[data-row="' + $(this).data('row') + '"]').prop('disabled', !$(this).val());
    });

    $modal.on('click', '.ci-save-location', function () {
        var rowId = parseInt($(this).data('row'), 10);
        var province = $('.ci-province[data-row="' + rowId + '"] option:selected').text();
        var city = $('.ci-city[data-row="' + rowId + '"]').val();
        // header names must match the template exactly
        sendReview([{ id: rowId, input: { 'Province': province, 'City / Region': city } }]);
    });

    // ---------- apply (I05) ----------
    function apply() {
        setBusy(true);
        $.ajax({
            url: API + '/imports/' + preview.import.id + '/apply',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ lock_version: preview.import.lock_version }),
            dataType: 'json'
        }).done(function (response) {
            preview = response.data;
            renderPreview();
            if (typeof loadCustomers === 'function') loadCustomers();
        }).fail(function (xhr) {
            var msg = errorText(xhr, 'Failed to apply import.');
            if (xhr.status === 409) {
                // a conflict appeared after the preview: reload, then ask the user to check the decisions again
                msg += ' The preview was reloaded; please review the decisions again.';
                refreshPreview();
            }
            $('#ci-step3-error').text(msg).removeClass('hidden');
        }).always(function () {
            setBusy(false);
        });
    }

    // ---------- export (I06) ----------
    function downloadReview() {
        if (!preview) return;
        fetch(API + '/imports/' + preview.import.id + '/export', { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('Download failed');
                var disposition = res.headers.get('Content-Disposition') || '';
                var match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition);
                var name = match ? decodeURIComponent(match[1]) : 'customer-import-' + preview.import.id + '-review.xlsx';
                return res.blob().then(function (blob) { return { blob: blob, name: name }; });
            })
            .then(function (file) {
                var url = URL.createObjectURL(file.blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = file.name;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            })
            .catch(function () {
                $('#ci-step3-error').text('Failed to download the review file.').removeClass('hidden');
            });
    }

    // ---------- footer and upload wiring ----------
    $('#ci-back-btn').on('click', function () {
        if (step === 1) { $modal.modal('hide'); return; }
        step--;
        render();
    });

    $('#ci-next-btn').on('click', function () {
        if (busy) return;
        if (step === 1) { step = 2; render(); return; }
        if (step === 2) { if (selectedFile) upload(); return; }
        if (isApplied()) { $modal.modal('hide'); return; }
        if (canApply()) apply();
    });

    $('#ci-error-btn').on('click', downloadReview);

    $('#ci-browse-btn').on('click', function () { $('#ci-file-input').trigger('click'); });
    $('#ci-file-input').on('change', function () { setFile(this.files[0]); });

    $('#ci-dropzone').on('dragover dragenter', function (e) {
        e.preventDefault();
        $(this).addClass('bg-[#E7F3EE]');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('bg-[#E7F3EE]');
        if (e.type === 'drop') {
            var files = e.originalEvent.dataTransfer.files;
            if (files.length) setFile(files[0]);
        }
    });

    $('.ci-filter-btn').on('click', function () { applyFilter($(this).data('filter')); });
    $('#ci-search').on('input', function () { applyFilter(currentFilter); });

    // (The sidebar collapses for every modal through the global handler in layouts/app.blade.php.)
    $modal.on('hidden.bs.modal', resetAll);

    render();
})();
</script>
@endpush

<style>
    #customerImportModal button,
    #customerImportModal a,
    #customerImportModal label.ci-choice { cursor: pointer; transition: background-color .15s, border-color .15s; }
    #customerImportModal #ci-next-btn:hover:not(:disabled),
    #customerImportModal #ci-browse-btn:hover,
    #customerImportModal a.bg-\[\#115640\]:hover { background-color: #0a3b2b !important; }
    #customerImportModal #ci-next-btn:disabled { cursor: not-allowed; }
    #customerImportModal #ci-back-btn:hover,
    #customerImportModal #ci-error-btn:hover,
    #customerImportModal button[data-dismiss="modal"]:hover { background-color: #E8E8E8 !important; }
    #customerImportModal .ci-filter-btn:hover { background-color: #E3E3E3; }
    #customerImportModal label.ci-choice:hover { background-color: #F1F7F4; border-color: #115640; }
</style>
