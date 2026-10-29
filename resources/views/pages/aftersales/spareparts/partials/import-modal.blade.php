{{-- Import Sparepart modal (PRD v2.2 IM-94..98): Download Template -> Upload File -> Review & Apply. Nothing is saved before Apply. --}}
<div class="modal fade" id="sparepartImportModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 800px;">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">

            {{-- HEADER --}}
            <div class="border-b border-b-[#D9D9D9]" style="padding:22px 28px 14px">
                <div class="flex items-start justify-between">
                    <div>
                        <h5 class="text-[#115640] font-bold mb-1" style="font-size:20px">Import Sparepart</h5>
                        <p class="text-[#9A9A9A] mb-0" style="font-size:11px" id="si-subtitle">Upload, review, and apply sparepart data.</p>
                    </div>
                    <button type="button" data-dismiss="modal" aria-label="Close"
                        style="width:32px;height:32px;font-size:12px" class="border border-[#D9D9D9] rounded-lg bg-white text-[#1E1E1E] flex items-center justify-center">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- STEPPER --}}
                <div class="flex items-center justify-center mt-3" style="gap:14px">
                    @foreach (['Download Template', 'Upload File', 'Review & Apply'] as $i => $label)
                        <div class="flex items-center gap-2 si-step-item" data-step="{{ $i + 1 }}">
                            <span style="width:26px;height:26px;font-size:11px" class="si-step-dot rounded-full flex items-center justify-center font-bold border border-[#D9D9D9] text-[#757575] bg-white">{{ $i + 1 }}</span>
                            <span style="font-size:11px" class="si-step-label font-semibold text-[#757575]">{{ $label }}</span>
                        </div>
                        @if ($i < 2)
                            <div class="h-px bg-[#D9D9D9]" style="width:90px"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- BODY --}}
            <div class="modal-body bg-[#F9FAFB] text-xs" style="padding:22px 28px;max-height:70vh;overflow-y:auto">

                {{-- STEP 1 --}}
                <div class="si-panel" data-panel="1">
                    <div class="flex items-center justify-between border border-[#D9D9D9] rounded-xl" style="gap:16px;padding:18px 20px;background:linear-gradient(120deg,#EAF5F0,#F8FBFA)">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[#115640] mb-1">Start with our template</p>
                            <p class="text-[#1E1E1E] mb-1" style="font-size:15px">Prepare sparepart data</p>
                            <p class="text-[#757575] mb-0" style="font-size:10.5px">The template has two sheets: Import Sparepart (header on row 1) and Guide. A file with different headers is rejected.</p>
                        </div>
                        <a href="/api/aftersales/spareparts/imports/template" style="padding:10px 20px;font-size:12px" class="bg-[#115640] text-white rounded-lg whitespace-nowrap font-semibold">
                            <i class="bi bi-file-earmark-text"></i> Download .xlsx
                        </a>
                    </div>

                    <div class="bg-white border border-[#D9D9D9] rounded-xl mt-3" style="padding:20px">
                        <p class="text-[#1E1E1E] mb-3" style="font-size:12px">Template columns</p>
                        <table class="w-full" data-no-cards style="font-size:10.5px">
                            <thead style="color:#5F6B66;font-size:9.5px;text-transform:uppercase">
                                <tr><th style="padding:6px 8px;text-align:left">Column</th><th style="padding:6px 8px;text-align:left">Rule</th></tr>
                            </thead>
                            <tbody>
                                @foreach ([
                                    ['Part Number*', 'Required. Unique in the file; matched by trimmed, upper-case value.'],
                                    ['Name*', 'Required.'],
                                    ['Brand', 'Optional.'],
                                    ['Supplier', 'Optional.'],
                                    ['Price', 'Number ≥ 0. Empty = Warning.'],
                                    ['Stock', 'Integer ≥ 0. Empty = 0.'],
                                    ['Min Stock', 'Integer ≥ 0. Empty = 0.'],
                                    ['Rack Location', 'Optional.'],
                                ] as [$col, $rule])
                                    <tr style="border-top:1px solid #EDEDED">
                                        <td style="padding:6px 8px;font-weight:600;white-space:nowrap">{{ $col }}</td>
                                        <td style="padding:6px 8px;color:#757575">{{ $rule }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="text-[#9A9A9A] mb-0" style="font-size:9.5px;margin-top:14px">A Part Number that already exists is skipped, not updated. Stock above 0 is recorded as opening stock for new parts.</p>
                    </div>
                </div>

                {{-- STEP 2 --}}
                <div class="si-panel hidden" data-panel="2">
                    <div class="bg-white border border-[#D9D9D9] rounded-xl" style="padding:20px">
                        <p class="font-bold uppercase text-[#115640] mb-1" style="font-size:9px;letter-spacing:.06em">Upload file</p>
                        <p class="text-[#1E1E1E] mb-1" style="font-size:15px">Upload your completed template</p>
                        <p class="text-[#9A9A9A]" style="font-size:9.5px;margin-bottom:14px">.xlsx format only, with a maximum file size of 20 MB.</p>

                        <div id="si-dropzone" class="flex flex-col items-center justify-center text-center"
                            style="border:1px dashed #7FB5A0;border-radius:10px;background:#F8FAF9;padding:34px 16px">
                            <div style="width:48px;height:48px;background:#CFE5DB;color:#115640;font-size:20px;margin-bottom:14px" class="rounded-full flex items-center justify-center">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <p class="font-bold text-[#2D3748]" style="font-size:13px;margin-bottom:4px" id="si-drop-title">Drop your completed template here</p>
                            <p class="text-[#9A9A9A]" style="font-size:10.5px;margin-bottom:12px" id="si-drop-sub">or choose it from your computer</p>
                            <button type="button" id="si-browse-btn" style="padding:8px 18px;font-size:11px" class="bg-[#115640] text-white rounded-lg font-semibold">Browse file</button>
                            <input type="file" id="si-file-input" accept=".xlsx" class="hidden">
                        </div>
                        <p class="text-[#EC221F] mb-0 hidden text-center" style="font-size:11px;margin-top:12px" id="si-file-error"></p>
                    </div>
                </div>

                {{-- STEP 3 (rendered from the API response) --}}
                <div class="si-panel hidden" data-panel="3">
                    <div id="si-result-banner" class="hidden" style="margin-bottom:12px;padding:10px 14px;border-radius:8px;background:#E6F4EF;color:#115640;font-size:11px"></div>
                    <div id="si-step3-error" class="hidden" style="margin-bottom:12px;padding:10px 14px;border-radius:8px;background:#FBE0DF;color:#C0281F;font-size:11px"></div>
                    <p class="text-[#757575]" id="si-file-info" style="margin-bottom:10px"></p>

                    <div id="si-summary" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px"></div>

                    <div class="bg-white overflow-hidden" style="border:1px solid #E3E3E3;border-radius:10px">
                        <div style="overflow-x:auto">
                            <table class="w-full" data-no-cards style="font-size:10.5px;color:#2D3748">
                                <thead style="background:#F7F9F8;color:#5F6B66;font-size:9.5px;text-transform:uppercase">
                                    <tr style="border-bottom:1px solid #EDEDED">
                                        @foreach (['Row', 'Part Number', 'Name', 'Stock', 'Status', 'Detail'] as $h)
                                            <th class="font-semibold" style="padding:9px 12px;text-align:left;white-space:nowrap">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody id="si-rows"></tbody>
                            </table>
                        </div>
                    </div>
                    <p class="text-[#757575] text-right mb-0" id="si-apply-note" style="font-size:10.5px;margin-top:10px"></p>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="flex items-center justify-between border-t border-t-[#D9D9D9] bg-white text-xs" style="padding:14px 28px">
                <div>
                    <button type="button" id="si-error-btn" class="hidden bg-white border border-[#D9D9D9] px-3 py-1.5 rounded-lg text-xs font-semibold">Download Error File</button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="si-back-btn" class="bg-white border border-[#D9D9D9] text-[#1E1E1E] px-3 py-1.5 rounded-lg text-xs">Back</button>
                    <button type="button" id="si-next-btn" class="bg-[#115640] text-white px-4 py-1.5 rounded-lg text-xs hover:bg-[#0d4633]">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var API = '/api/aftersales/spareparts/imports';
    var $modal = $('#sparepartImportModal');
    var MAX_SIZE = 20 * 1024 * 1024;

    var step = 1;
    var selectedFile = null;
    var preview = null;
    var busy = false;

    var STATUS = {
        'new': { label: 'New', bg: '#DCEBFA', fg: '#1D5FA8' },
        already_exists: { label: 'Already exists', bg: '#E3E3E3', fg: '#5F6B66' },
        warning: { label: 'Warning', bg: '#FBEFC8', fg: '#7A5F00' },
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

    function appliedRows() {
        if (!preview) return [];
        return preview.rows.filter(function (r) { return r.review && r.review.can_apply; });
    }

    function render() {
        $modal.find('.si-panel').addClass('hidden').filter('[data-panel="' + step + '"]').removeClass('hidden');

        $modal.find('.si-step-item').each(function () {
            var active = +$(this).data('step') <= step;
            $(this).find('.si-step-dot')
                .toggleClass('bg-[#115640] text-white border-[#115640]', active)
                .toggleClass('bg-white text-[#757575] border-[#D9D9D9]', !active);
            $(this).find('.si-step-label')
                .toggleClass('text-[#115640]', active)
                .toggleClass('text-[#757575]', !active);
        });

        $modal.find('.modal-dialog').css('max-width', step === 3 ? '960px' : '800px');
        $('#si-error-btn').toggleClass('hidden', step !== 3 || !preview || !preview.summary.error);

        var $next = $('#si-next-btn');
        if (step === 3) {
            $next.text(isApplied() ? 'Done' : 'Apply');
            $('#si-back-btn').text('Cancel').toggleClass('hidden', isApplied());
        } else {
            $next.text('Next');
            $('#si-back-btn').text('Back').removeClass('hidden');
        }
        var nothingToApply = step === 3 && !isApplied() && appliedRows().length === 0;
        $next.prop('disabled', busy || (step === 2 && !selectedFile) || nothingToApply);
        $next.css('opacity', $next.prop('disabled') ? '.5' : '1');
    }

    function setBusy(value) { busy = value; render(); }

    function resetAll() {
        step = 1;
        selectedFile = null;
        preview = null;
        busy = false;
        $('#si-file-input').val('');
        $('#si-file-error').addClass('hidden').text('');
        $('#si-drop-title').text('Drop your completed template here');
        $('#si-drop-sub').text('or choose it from your computer');
        $('#si-rows, #si-summary').empty();
        $('#si-result-banner, #si-step3-error').addClass('hidden').text('');
        render();
    }

    // ---------- step 2: upload ----------
    function setFile(file) {
        var $err = $('#si-file-error');
        if (!file) return;
        if (!/\.xlsx$/i.test(file.name)) {
            $err.text('Only .xlsx files are accepted.').removeClass('hidden');
            selectedFile = null;
            $('#si-file-input').val('');
            render();
            return;
        }
        if (file.size > MAX_SIZE) {
            $err.text('File size exceeds 20 MB.').removeClass('hidden');
            selectedFile = null;
            $('#si-file-input').val('');
            render();
            return;
        }
        $err.addClass('hidden');
        selectedFile = file;
        $('#si-drop-title').text(file.name);
        $('#si-drop-sub').text((file.size / 1024 / 1024).toFixed(2) + ' MB');
        render();
    }

    function upload() {
        var form = new FormData();
        form.append('file', selectedFile);
        $('#si-file-error').addClass('hidden').text('');
        setBusy(true);

        $.ajax({
            url: API,
            method: 'POST',
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (response) {
            preview = response.data;
            step = 3;
            renderPreview();
        }).fail(function (xhr) {
            // header mismatch / wrong file: stay on step 2 and show the message
            $('#si-file-error').text(errorText(xhr, 'Failed to upload file.')).removeClass('hidden');
        }).always(function () {
            setBusy(false);
        });
    }

    // ---------- step 3: review ----------
    function badge(status) {
        var s = STATUS[status] || { label: status, bg: '#E3E3E3', fg: '#5F6B66' };
        return '<span style="background:' + s.bg + ';color:' + s.fg + ';font-size:9px;padding:3px 8px;border-radius:5px;font-weight:600;white-space:nowrap">' + esc(s.label) + '</span>';
    }

    function renderSummary() {
        var s = preview.summary || {};
        var cards = [['new', 'New', '#115640'], ['already_exists', 'Already exists', '#2D3748'], ['warning', 'Warning', '#7A5F00'], ['error', 'Error', '#C0281F']];
        $('#si-summary').html(cards.map(function (c) {
            return '<div class="bg-white text-center" style="border:1px solid #E3E3E3;border-radius:8px;padding:10px 4px">' +
                '<p class="font-bold mb-0" style="font-size:18px;line-height:1.2;color:' + c[2] + '">' + (s[c[0]] || 0) + '</p>' +
                '<p class="mb-0" style="font-size:9.5px;color:#9A9A9A">' + c[1] + '</p></div>';
        }).join(''));
    }

    var SKIP_LABEL = {
        already_exists: 'Skipped: Part Number already exists, not updated',
        soft_deleted: 'Skipped: Part Number belongs to an archived sparepart',
        duplicate_part_number: 'Skipped: duplicate Part Number in this file',
        validation_error: 'Skipped: invalid data'
    };

    function detailText(row) {
        var r = row.review || {};
        var res = row.result;
        var parts = [];
        if (res && res.status === 'applied') {
            parts.push(res.opening_stock > 0 ? 'Created, opening stock ' + esc(res.opening_stock) + ' recorded' : 'Created');
        } else if (res && res.status === 'skipped') {
            parts.push(esc(SKIP_LABEL[res.reason] || 'Skipped'));
        } else if (r.errors && r.errors.length) {
            return '<span style="color:#C0281F">' + esc(r.errors.join('; ')) + '</span>';
        } else {
            if (r.warnings && r.warnings.length) parts.push(esc(r.warnings.join('; ')));
            if (!r.can_apply) parts.push(esc(SKIP_LABEL[r.skip_reason] || 'Will be skipped'));
            else if (r.part && r.part.stock > 0) parts.push('Opening stock ' + esc(r.part.stock) + ' will be recorded');
            else parts.push('Ready to create');
        }
        if (r.stock_status === 'REORDER') {
            parts.push('<span style="background:#FFF3CD;color:#8A6D00;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:600">REORDER</span>');
        }
        return parts.join(' ');
    }

    function renderRows() {
        var html = preview.rows.map(function (row) {
            var r = row.review || {};
            var p = r.part || {};
            var stock = p.stock !== null && p.stock !== undefined ? p.stock : (row.input['Stock'] || '-');
            return '<tr style="border-top:1px solid #EDEDED">' +
                '<td style="padding:10px 12px">' + esc(row.row_number) + '</td>' +
                '<td style="padding:10px 12px;white-space:nowrap">' + esc(p.part_number || row.input['Part Number*'] || '-') + '</td>' +
                '<td style="padding:10px 12px">' + esc(p.name || row.input['Name*'] || '-') + '</td>' +
                '<td style="padding:10px 12px">' + esc(stock) + '</td>' +
                '<td style="padding:10px 12px">' + badge(r.status) + '</td>' +
                '<td style="padding:10px 12px">' + detailText(row) + '</td></tr>';
        }).join('');
        $('#si-rows').html(html || '<tr><td colspan="6" style="padding:16px;text-align:center;color:#757575">No rows</td></tr>');
    }

    function renderPreview() {
        $('#si-step3-error').addClass('hidden').text('');
        var imp = preview.import;
        $('#si-file-info').text((imp.file_name || '') + ' · ' + preview.rows.length + ' rows.' + (isApplied() ? '' : ' Nothing is saved until you press Apply.'));
        renderSummary();
        renderRows();

        var $b = $('#si-result-banner');
        if (isApplied()) {
            $b.text('Import applied: ' + (imp.created_count || 0) + ' created, ' + (imp.matched_count || 0) + ' skipped (already exists), ' + (imp.error_count || 0) + ' error row(s) not applied.').removeClass('hidden');
            $('#si-apply-note').text('');
        } else {
            $b.addClass('hidden').text('');
            var n = appliedRows().length;
            var reorder = (preview.summary || {}).reorder || 0;
            $('#si-apply-note').text('Apply will save ' + n + ' row' + (n === 1 ? '' : 's') + '. Error and skipped rows are not applied.' + (reorder ? ' ' + reorder + ' row(s) marked REORDER (info only).' : ''));
        }
        render();
    }

    function refreshPreview() {
        return $.getJSON(API + '/' + preview.import.id).done(function (response) {
            preview = response.data;
            renderPreview();
        });
    }

    function apply() {
        setBusy(true);
        $.ajax({
            url: API + '/' + preview.import.id + '/apply',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ lock_version: preview.import.lock_version }),
            dataType: 'json'
        }).done(function (response) {
            preview = response.data;
            renderPreview();
            if (typeof loadSpareparts === 'function') loadSpareparts();
        }).fail(function (xhr) {
            var msg = errorText(xhr, 'Failed to apply import.');
            if (xhr.status === 409) {
                msg += ' The preview was reloaded; please review it again.';
                refreshPreview();
            } else if (!xhr.status || xhr.status >= 500) {
                refreshPreview();
            }
            $('#si-step3-error').text(msg).removeClass('hidden');
        }).always(function () {
            setBusy(false);
        });
    }

    function downloadErrors() {
        if (!preview) return;
        fetch(API + '/' + preview.import.id + '/export', { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('Download failed');
                var disposition = res.headers.get('Content-Disposition') || '';
                var match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition);
                var name = match ? decodeURIComponent(match[1]) : 'sparepart-import-' + preview.import.id + '-errors.xlsx';
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
                $('#si-step3-error').text('Failed to download the error file.').removeClass('hidden');
            });
    }

    // ---------- footer and upload wiring ----------
    $('#si-back-btn').on('click', function () {
        if (step === 1 || step === 3) {          // step 3: Cancel discards the preview (nothing was saved)
            $modal.modal('hide');
            return;
        }
        step--;
        render();
    });

    $('#si-next-btn').on('click', function () {
        if (busy) return;
        if (step === 1) { step = 2; render(); return; }
        if (step === 2) { if (selectedFile) upload(); return; }
        if (isApplied()) { $modal.modal('hide'); return; }
        if (appliedRows().length) apply();
    });

    $('#si-error-btn').on('click', downloadErrors);
    $('#si-browse-btn').on('click', function () { $('#si-file-input').trigger('click'); });
    $('#si-file-input').on('change', function () { setFile(this.files[0]); });

    $('#si-dropzone').on('dragover dragenter', function (e) {
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

    $modal.on('hidden.bs.modal', resetAll);
    render();
})();
</script>
@endpush

<style>
    #sparepartImportModal button,
    #sparepartImportModal a { cursor: pointer; transition: background-color .15s, border-color .15s; }
    #sparepartImportModal #si-next-btn:hover:not(:disabled),
    #sparepartImportModal #si-browse-btn:hover,
    #sparepartImportModal a.bg-\[\#115640\]:hover { background-color: #0a3b2b !important; }
    #sparepartImportModal #si-next-btn:disabled { cursor: not-allowed; }
    #sparepartImportModal #si-back-btn:hover,
    #sparepartImportModal #si-error-btn:hover,
    #sparepartImportModal button[data-dismiss="modal"]:hover { background-color: #E8E8E8 !important; }
</style>
