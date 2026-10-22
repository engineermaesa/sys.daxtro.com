{{-- Import Customer modal (static FE; backend wiring pending) --}}
<div class="modal fade" id="customerImportModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                        <a href="#" style="padding:10px 20px;font-size:12px" class="bg-[#115640] text-white rounded-lg whitespace-nowrap font-semibold">
                            <i class="bi bi-file-earmark-text"></i> Download .xlsx
                        </a>
                    </div>

                    <div class="bg-white border border-[#D9D9D9] rounded-xl mt-3" style="padding:20px">
                        <div class="flex items-center justify-between" style="margin-bottom:14px">
                            <p class="text-[#1E1E1E] mb-0" style="font-size:12px">Before you fill the file</p>
                            <span class="bg-[#F0F0F0] text-[#757575] rounded-full" style="font-size:9px;padding:4px 10px">4 formatting rules</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2" style="gap:10px">
                            @foreach ([
                                ['01', 'One machine per row', 'Repeat the customer details when a customer has multiple machines.'],
                                ['02', 'Complete required fields', 'Customer Name, Machine Category, and Machine Name are required.'],
                                ['03', 'Keep formats consistent', 'Phone and Serial Number are text. Dates use dd/mm/yyyy.'],
                                ['04', 'Use valid contract values', 'Enter Basic or Premium. Leave the cell empty for None.'],
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
                            <p class="text-[#EC221F] mb-0 hidden" style="font-size:11px;margin-top:8px" id="ci-file-error"></p>
                        </div>
                        <p class="text-center text-[#9A9A9A] mb-0" style="font-size:10px;margin-top:12px">You can continue without selecting a file to preview the review experience.</p>
                    </div>
                </div>

                {{-- STEP 3 --}}
                <div class="ci-panel hidden" data-panel="3">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;margin-bottom:14px">
                        @foreach ([
                            ['18', 'Exact match', '#115640'],
                            ['1', 'Possible match', '#7A5F00'],
                            ['15', 'New', '#115640'],
                            ['1', 'Already exists', '#2D3748'],
                            ['1', 'Warning', '#7A5F00'],
                            ['1', 'Error', '#C0281F'],
                        ] as [$n, $label, $color])
                            <div class="bg-white text-center" style="border:1px solid #E3E3E3;border-radius:8px;padding:10px 4px">
                                <p class="font-bold mb-0" {!! 'style="font-size:18px;line-height:1.2;color:' . $color . '"' !!}>{{ $n }}</p>
                                <p class="mb-0" style="font-size:9.5px;color:#9A9A9A">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>

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

                        <table class="w-full" style="font-size:10.5px;color:#2D3748">
                            <thead style="background:#F7F9F8;color:#5F6B66;font-size:9.5px;text-transform:uppercase">
                                <tr style="border-top:1px solid #EDEDED;border-bottom:1px solid #EDEDED">
                                    @foreach (['Row', 'Customer', 'Phone', 'Machine', 'Serial Number', 'Status', 'Detail / Decision'] as $h)
                                        <th class="font-semibold" style="padding:9px 12px;text-align:left;white-space:nowrap">{{ $h }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $badge = fn ($bg, $fg) => 'style="background:' . $bg . ';color:' . $fg . ';font-size:9px;padding:3px 8px;border-radius:5px;font-weight:600;white-space:nowrap"';
                                    $detail = fn ($extra) => 'style="padding:10px 12px;' . $extra . '"';
                                    $rows = [
                                        ['exact', 2, 'Global Dynamic', '+62 8578-1357-651', 'RD 1445 · Cold Room', 'DX-CR-10441', 'Exact match', $badge('#D3E8DE', '#115640'), 'Linked automatically', $detail('')],
                                        ['decision', 3, 'PT Sejuk Sentosa', '+62 812-9876-420', 'CD 10 · Cube Ice Machine', 'DX-CD-88210', 'Possible match', $badge('#FBEFC8', '#7A5F00'), 'Decision required', $detail('')],
                                        'DECISION',
                                        ['new', 4, 'CV Tirta Abadi', '+62 813-7221-880', 'FM 40 · Flake Ice Machine', 'DX-FM-33018', 'New', $badge('#D3E8DE', '#115640'), 'Ready to create', $detail('')],
                                        ['warning', 5, 'UD Beku Jaya', '+62 811-2233-445', 'RD 900 · Cold Room', 'DX-CR-20015', 'Warning', $badge('#FBEFC8', '#7A5F00'), 'Province follows City (Bandung → Jawa Barat)', $detail('')],
                                        ['warning', 7, 'PT Dingin Makmur', '+62 821-4455-667', 'TD 20 · Tube Ice Machine', 'DX-TD-55120', 'Warning', $badge('#FBEFC8', '#7A5F00'), 'CITY_SELECT', $detail('')],
                                        ['exists', 8, 'Global Dynamic', '+62 8578-1357-651', 'RD 1445 · Cold Room', 'DX-CR-10441', 'Already exists', $badge('#E3E3E3', '#5F6B66'), 'Serial already in system, skipped', $detail('')],
                                        ['warning', 9, 'CV Es Nusantara', '+62 812-3300-910', 'CD 50 · Cube Ice Machine', 'DX-CD-77001', 'Warning', $badge('#FBEFC8', '#7A5F00'), 'Same serial in rows 9 and 12: owner set to last row, note "Previously owned by PT Es Lama"', $detail('')],
                                        ['error', 6, '-', '+62 819-0000-111', 'CD 10 · Cube Ice Machine', 'DX-CD-00001', 'Error', $badge('#FBE0DF', '#C0281F'), 'Customer Name is required', $detail('color:#C0281F')],
                                    ];
                                @endphp
                                @foreach ($rows as $r)
                                    @if ($r === 'DECISION')
                                        <tr class="ci-row ci-decision" data-type="decision" style="background:#FFFCF3;border-top:1px solid #EDEDED">
                                            <td colspan="7" style="padding:10px 12px">
                                                <div class="flex items-stretch" style="gap:8px">
                                                    <div style="width:110px;flex-shrink:0">
                                                        <p class="font-bold uppercase mb-1" style="font-size:8.5px;color:#7A5F00">Possible duplicate</p>
                                                        <p class="font-bold mb-0" style="font-size:10.5px;color:#1E1E1E">Choose how to handle row 3</p>
                                                    </div>
                                                    @foreach ([['From file', 'PT Sejuk Sentosa'], ['Existing customer', 'PT. Sejuk Sentosa']] as [$cap, $nm])
                                                        <div class="bg-white" style="flex:1.4;border:1px solid #EBE3C8;border-radius:8px;padding:8px 10px">
                                                            <p class="uppercase mb-1" style="font-size:8.5px;color:#9A9A9A">{{ $cap }}</p>
                                                            <p class="font-bold mb-0" style="font-size:10.5px">{{ $nm }}</p>
                                                            <p class="mb-0" style="font-size:9.5px;color:#757575">+62 812-9876-420 · Aceh</p>
                                                        </div>
                                                    @endforeach
                                                    @foreach (['link' => 'Link to existing', 'new' => 'Create new', 'skip' => 'Skip row'] as $v => $l)
                                                        <label class="ci-choice bg-white flex items-center justify-center mb-0" style="flex:1;gap:6px;border:1px solid #E3E3E3;border-radius:8px;padding:8px;font-size:10px;white-space:nowrap">
                                                            <input type="radio" name="ci-decision-3" value="{{ $v }}" {{ $v === 'link' ? 'checked' : '' }} style="accent-color:#115640">
                                                            <span class="font-semibold">{{ $l }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        <tr class="ci-row" data-type="{{ $r[0] }}" style="border-top:1px solid #EDEDED">
                                            <td style="padding:10px 12px">{{ $r[1] }}</td>
                                            <td style="padding:10px 12px">{{ $r[2] }}</td>
                                            <td style="padding:10px 12px;white-space:nowrap">{{ $r[3] }}</td>
                                            <td style="padding:10px 12px">{{ $r[4] }}</td>
                                            <td style="padding:10px 12px;white-space:nowrap">{{ $r[5] }}</td>
                                            <td style="padding:10px 12px"><span {!! $r[7] !!}>{{ $r[6] }}</span></td>
                                            <td {!! $r[9] !!}>
                                                @if ($r[8] === 'CITY_SELECT')
                                                    <div style="font-size:9.5px;color:#7A5F00;margin-bottom:3px">City "Bandng" not found. Choose one:</div>
                                                    <select style="font-size:10px;padding:4px 8px;border:1px solid #D9D9D9;border-radius:6px;max-width:200px">
                                                        <option value="">Select City</option>
                                                        <option>Kota Bandung</option>
                                                        <option>Kabupaten Bandung</option>
                                                        <option>Kabupaten Bandung Barat</option>
                                                    </select>
                                                @else
                                                    {{ $r[8] }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
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
    var $modal = $('#customerImportModal');
    var step = 1;
    var MAX_SIZE = 20 * 1024 * 1024;

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
        $('#ci-next-btn').text(step === 3 ? 'Apply' : 'Next');
        $('#ci-error-btn').toggleClass('hidden', step !== 3);
    }

    function reset() {
        step = 1;
        $('#ci-file-input').val('');
        $('#ci-file-error').addClass('hidden').text('');
        $('#ci-drop-title').text('Drop your completed template here');
        $('#ci-drop-sub').text('or choose it from your computer');
        $('#ci-search').val('');
        applyFilter('all');
        render();
    }

    $('#ci-back-btn').on('click', function () {
        if (step === 1) { $modal.modal('hide'); return; }
        step--; render();
    });

    $('#ci-next-btn').on('click', function () {
        // TODO: wire to backend preview (step 2 -> 3) and apply (step 3).
        if (step === 3) { $modal.modal('hide'); return; }
        step++; render();
    });

    // Upload
    function setFile(file) {
        var $err = $('#ci-file-error');
        if (!file) return;
        if (!/\.xlsx$/i.test(file.name)) {
            $err.text('Only .xlsx files are accepted.').removeClass('hidden');
            $('#ci-file-input').val('');
            return;
        }
        if (file.size > MAX_SIZE) {
            $err.text('File size exceeds 20 MB.').removeClass('hidden');
            $('#ci-file-input').val('');
            return;
        }
        $err.addClass('hidden');
        $('#ci-drop-title').text(file.name);
        $('#ci-drop-sub').text((file.size / 1024 / 1024).toFixed(2) + ' MB');
    }

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

    // Preview filter + search (client-side over dummy rows)
    var currentFilter = 'all';

    function applyFilter(filter) {
        currentFilter = filter;
        var q = ($('#ci-search').val() || '').toLowerCase();

        $('.ci-filter-btn').each(function () {
            var on = $(this).data('filter') === filter;
            $(this).toggleClass('bg-white text-[#1E1E1E] shadow-sm', on).toggleClass('text-[#757575]', !on);
        });

        $modal.find('.ci-row').each(function () {
            var $r = $(this);
            var typeOk = filter === 'all' || $r.data('type') === filter;
            var textOk = !q || $r.text().toLowerCase().indexOf(q) !== -1 || $r.hasClass('ci-decision');
            $r.toggleClass('hidden', !(typeOk && textOk));
        });
    }

    $('.ci-filter-btn').on('click', function () { applyFilter($(this).data('filter')); });
    $('#ci-search').on('input', function () { applyFilter(currentFilter); });

    $('input[name="ci-decision-3"]').on('change', function () {
        $('.ci-choice').css({borderColor: '#E3E3E3', background: '#fff'});
        $(this).closest('.ci-choice').css({borderColor: '#115640', background: '#EAF5F0'});
    }).filter(':checked').trigger('change');

    // Collapse the sidebar while the modal is open so it doesn't overlap, then restore.
    var sidebarWasOpen = false;
    function toggleSidebar() {
        var saved = localStorage.getItem('sidebar-collapsed');
        var btn = document.getElementById('toggleSidepanel');
        if (btn) btn.click();
        if (saved === null) localStorage.removeItem('sidebar-collapsed');
        else localStorage.setItem('sidebar-collapsed', saved);
    }

    $modal.on('show.bs.modal', function () {
        var wrap = document.getElementById('sidebarWrapper');
        sidebarWasOpen = !!wrap && !wrap.classList.contains('sidebar-collapsed');
        if (sidebarWasOpen) toggleSidebar();
    });

    $modal.on('hidden.bs.modal', function () {
        if (sidebarWasOpen) toggleSidebar();
        sidebarWasOpen = false;
        reset();
    });
    render();
})();
</script>
@endpush

<style>
    #customerImportModal button,
    #customerImportModal a,
    #customerImportModal label.ci-choice { cursor: pointer; transition: background-color .15s, border-color .15s; }
    #customerImportModal #ci-next-btn:hover,
    #customerImportModal #ci-browse-btn:hover,
    #customerImportModal a.bg-\[\#115640\]:hover { background-color: #0a3b2b !important; }
    #customerImportModal #ci-back-btn:hover,
    #customerImportModal #ci-error-btn:hover,
    #customerImportModal button[data-dismiss="modal"]:hover { background-color: #E8E8E8 !important; }
    #customerImportModal .ci-filter-btn:hover { background-color: #E3E3E3; }
    #customerImportModal label.ci-choice:hover { background-color: #F1F7F4; border-color: #115640; }
</style>
