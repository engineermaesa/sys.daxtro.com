@extends('layouts.app')

@section('content')
<section id="surveyRoot" class="text-xs! lg:text-sm! text-[#1E1E1E]"
    data-can-review="{{ auth()->user()?->hasPermission('satisfaction-survey.review') ? 1 : 0 }}">
    {{-- LIST VIEW --}}
    <div id="surveyListView">
        <div class="pt-4">
            <h1 class="font-bold text-xl lg:text-2xl text-[#115640]">Satisfaction Survey</h1>
            <p class="text-[#757575] mt-1">Review customer feedback and service quality responses.</p>
        </div>

        <form id="surveySearchForm" class="mt-4 p-4 bg-white border border-[#D9D9D9] rounded-lg flex flex-col lg:flex-row lg:items-center gap-3">
            <input type="text" id="surveySearchInput" placeholder="Search ticket, customer, or technician"
                class="flex-1 border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none focus:border-[#115640]" />
            <select id="surveyReviewFilter" class="border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none focus:border-[#115640]">
                <option value="">All review status</option>
                <option value="normal">Normal</option>
                <option value="needs_review">Needs review</option>
                <option value="reviewed">Reviewed</option>
            </select>
            <button type="submit" class="px-5 py-2 bg-[#115640] text-white font-semibold rounded-lg cursor-pointer">Search</button>
        </form>

        <div class="mt-4 bg-white border border-[#D9D9D9] rounded-lg">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-[#D9D9D9] text-[#757575] bg-[#FAFAFA] text-center">
                            <th class="p-3 font-semibold">Ticket ID</th>
                            <th class="p-3 font-semibold">Customer</th>
                            <th class="p-3 font-semibold">Technician</th>
                            <th class="p-3 font-semibold">Service Date</th>
                            <th class="p-3 font-semibold">Submitted On</th>
                            <th class="p-3 font-semibold">Average</th>
                            <th class="p-3 font-semibold">Machine OK</th>
                            <th class="p-3 font-semibold">Source</th>
                            <th class="p-3 font-semibold">Review</th>
                            <th class="p-3 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody id="surveyTableBody">
                        <tr><td class="p-10 text-center text-[#757575]" colspan="10">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between gap-3 p-3 border-t border-[#D9D9D9]">
                <p id="surveyPageInfo" class="text-[#757575]"></p>
                <div class="flex gap-2">
                    <button type="button" id="surveyPrev" class="px-3 py-1.5 bg-white border border-[#D9D9D9] rounded-lg cursor-pointer">Previous</button>
                    <button type="button" id="surveyNext" class="px-3 py-1.5 bg-white border border-[#D9D9D9] rounded-lg cursor-pointer">Next</button>
                </div>
            </div>
        </div>
    </div>

    @include('pages.satisfaction-survey.details')
    @include('pages.satisfaction-survey.view-form')
    @include('pages.satisfaction-survey.recoming')
</section>
@endsection

@section('scripts')
<script>
    (function () {
        const API = '/api/aftersales';
        const root = document.getElementById('surveyRoot');
        const canReview = root.dataset.canReview === '1';
        const listView = document.getElementById('surveyListView');
        const detailView = document.getElementById('surveyDetailView');
        const formView = document.getElementById('surveyFormView');
        const tbody = document.getElementById('surveyTableBody');

        const state = { page: 1, lastPage: 1, search: '', review: '' };
        let rows = [];          // list rows of the current page
        let current = null;     // { id, row, detail }
        const recomingIds = {}; // response ids that were scheduled for Recoming in this session

        const REVIEW_LABEL = { normal: 'Normal', needs_review: 'Needs review', reviewed: 'Reviewed' };
        const REVIEW_REASON_TEXT = {
            fast_completion: 'Completed in under 30 seconds',
            same_network_as_technician: 'Same network as the technician',
            phone_changed_recently: 'Customer WhatsApp number changed in the last 24 hours',
            admin_input: 'Entered by admin',
        };
        const RECOMING_REASON = {
            'Spare part not available yet': 'sparepart_unavailable',
            'Further work needed': 'further_work_needed',
            'Site access / conditions': 'site_access',
            'Other': 'other',
        };

        const set = (id, text) => { document.getElementById(id).textContent = text; };
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const dash = (v) => (v === null || v === undefined || v === '') ? '-' : v;

        function errMsg(xhr, fallback) {
            return (xhr.responseJSON && (xhr.responseJSON.message || Object.values(xhr.responseJSON.errors || {}).flat()[0])) || fallback;
        }
        function fail(xhr, fallback) {
            Swal.fire({ title: 'Error', text: errMsg(xhr, fallback), icon: 'error', confirmButtonColor: '#115640' });
        }

        function fmtDateTime(iso) {
            if (! iso) return '-';
            const d = new Date(iso);
            if (isNaN(d)) return iso;
            return d.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
        }
        function fmtDuration(sec) {
            if (sec === null || sec === undefined) return '-';
            return String(Math.floor(sec / 60)).padStart(2, '0') + 'm ' + String(sec % 60).padStart(2, '0') + 's';
        }
        function parseBrowser(ua) {
            if (! ua) return '';
            const mobile = /Mobile|Android|iPhone/i.test(ua) ? ' Mobile' : '';
            if (/Edg\//.test(ua)) return 'Edge' + mobile;
            if (/OPR\/|Opera/.test(ua)) return 'Opera' + mobile;
            if (/Chrome\//.test(ua)) return 'Chrome' + mobile;
            if (/Firefox\//.test(ua)) return 'Firefox' + mobile;
            if (/Safari\//.test(ua)) return 'Safari' + mobile;
            return 'Unknown browser';
        }
        function machineBadge(v) {
            if (v === true) return '<span class="px-2.5 py-1 rounded-full bg-[#E7F3EE] text-[#115640] font-semibold">Yes</span>';
            if (v === false) return '<span class="px-2.5 py-1 rounded-full bg-[#FBEAEA] text-[#EC221F] font-semibold">No</span>';
            return '<span class="text-[#757575]">-</span>';
        }
        function reviewText(row) {
            return recomingIds[row.id] ? 'Recoming' : (REVIEW_LABEL[row.review_status] || '-');
        }
        function reviewColor(row) {
            return (row.review_status === 'reviewed' && ! recomingIds[row.id]) ? 'text-[#115640]'
                : (row.review_status === 'normal' ? 'text-[#757575]' : 'text-[#D97706]');
        }
        function scoreColor(score) {
            return score >= 4 ? '#115640' : (score >= 3 ? '#E8B931' : '#EC221F');
        }
        function machineText(v, long) {
            if (v === true) return long ? 'Yes, machine is working properly' : 'Working properly';
            if (v === false) return long ? 'No, machine is not working properly' : 'Not working properly';
            return '-';
        }
        function machineColor(v) { return v === true ? '#115640' : (v === false ? '#EC221F' : ''); }

        // ---------- LIST ----------
        function renderList(meta) {
            if (! rows.length) {
                tbody.innerHTML = '<tr><td class="p-10 text-center text-[#757575]" colspan="10">No survey responses found.</td></tr>';
            } else {
                tbody.innerHTML = rows.map(function (r) {
                    return `<tr class="border-b border-[#D9D9D9] last:border-b-0">
                        <td class="p-3 font-semibold text-[#115640] text-center">${esc(r.ticket_code)}</td>
                        <td class="p-3 font-semibold text-center">${esc(r.customer)}</td>
                        <td class="text-center p-3 text-[#757575]">${esc(dash(r.technician))}</td>
                        <td class="p-3 text-center text-[#757575]">${esc(dash(r.service_date))}</td>
                        <td class="p-3 text-center text-[#757575]">${esc(fmtDateTime(r.submitted_at))}</td>
                        <td class="p-3 font-semibold text-[#115640] text-center">${r.average_score === null ? '-' : Number(r.average_score).toFixed(1) + ' / 5'}</td>
                        <td class="p-3 text-center">${machineBadge(r.machine_ok)}</td>
                        <td class="p-3 text-center text-[#757575]">${esc(dash(r.source_label))}</td>
                        <td class="p-3 text-center font-semibold ${reviewColor(r)}">${esc(reviewText(r))}</td>
                        <td class="p-3 text-center">
                            <div class="relative inline-block">
                                <button type="button" class="action-toggle w-9 h-9 flex items-center justify-center rounded-lg border border-[#D9D9D9] bg-[#F5F5F5] text-[#757575] cursor-pointer">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="action-menu hidden fixed w-52 bg-white border border-[#D9D9D9] rounded-lg shadow-lg z-50 p-1">
                                    <button type="button" data-id="${r.id}" class="view-detail w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-[#F5F5F5] text-left cursor-pointer">
                                        <i class="far fa-copy text-[#757575]"></i><span>View Detail</span>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }
            state.lastPage = meta.last_page || 1;
            set('surveyPageInfo', 'Page ' + (meta.current_page || 1) + ' of ' + state.lastPage + ' · ' + (meta.total || 0) + ' responses');
            document.getElementById('surveyPrev').disabled = state.page <= 1;
            document.getElementById('surveyNext').disabled = state.page >= state.lastPage;
        }

        function loadList() {
            tbody.innerHTML = '<tr><td class="p-10 text-center text-[#757575]" colspan="10">Loading...</td></tr>';
            $.ajax({
                url: API + '/satisfaction-surveys',
                method: 'GET',
                headers: { Accept: 'application/json' },
                data: { search: state.search, review_status: state.review, page: state.page, per_page: 15 },
                success: function (res) {
                    rows = res.data || [];
                    renderList(res);
                },
                error: function (xhr) {
                    rows = [];
                    tbody.innerHTML = '<tr><td class="p-10 text-center text-[#EC221F]" colspan="10">' + esc(errMsg(xhr, 'Failed to load survey responses.')) + '</td></tr>';
                },
            });
        }

        document.getElementById('surveySearchForm').addEventListener('submit', function (e) {
            e.preventDefault();
            state.search = document.getElementById('surveySearchInput').value.trim();
            state.review = document.getElementById('surveyReviewFilter').value;
            state.page = 1;
            loadList();
        });
        document.getElementById('surveyReviewFilter').addEventListener('change', function () {
            state.review = this.value;
            state.page = 1;
            loadList();
        });
        document.getElementById('surveyPrev').addEventListener('click', function () {
            if (state.page > 1) { state.page--; loadList(); }
        });
        document.getElementById('surveyNext').addEventListener('click', function () {
            if (state.page < state.lastPage) { state.page++; loadList(); }
        });

        // ---------- ACTION MENU (delegated; rows are rendered dynamically) ----------
        function closeMenus() {
            document.querySelectorAll('.action-menu').forEach(function (m) { m.classList.add('hidden'); });
        }
        tbody.addEventListener('click', function (e) {
            const toggle = e.target.closest('.action-toggle');
            const view = e.target.closest('.view-detail');
            if (toggle) {
                e.stopPropagation();
                const menu = toggle.nextElementSibling;
                const wasHidden = menu.classList.contains('hidden');
                closeMenus();
                if (wasHidden) {
                    const rect = toggle.getBoundingClientRect();
                    menu.style.top = (rect.bottom + 4) + 'px';
                    // anchor by the right edge so it does not depend on the menu's rendered width
                    menu.style.left = 'auto';
                    menu.style.right = (document.documentElement.clientWidth - rect.right) + 'px';
                    menu.classList.remove('hidden');
                }
            } else if (view) {
                location.hash = view.dataset.id;
                showDetail(view.dataset.id);
            }
        });
        document.addEventListener('click', closeMenus);
        window.addEventListener('scroll', closeMenus, true);
        window.addEventListener('resize', closeMenus);

        // ---------- DETAIL ----------
        function showView(which) {
            listView.classList.toggle('hidden', which !== 'list');
            detailView.classList.toggle('hidden', which !== 'detail');
            formView.classList.toggle('hidden', which !== 'form');
            window.scrollTo(0, 0);
        }

        function showList() {
            current = null;
            showView('list');
            if (location.hash) history.replaceState(null, '', location.pathname + location.search);
        }

        function setSignature(imgId, url) {
            const img = document.getElementById(imgId);
            img.classList.toggle('hidden', ! url);
            if (url) img.src = url; else img.removeAttribute('src');
        }

        function showDetail(id) {
            id = parseInt(id, 10);
            if (! id) return showList();

            $.ajax({
                url: API + '/satisfaction-surveys/' + id,
                method: 'GET',
                headers: { Accept: 'application/json' },
                success: function (res) {
                    const d = res.data;
                    const row = rows.find(function (r) { return r.id === d.id; }) || {};
                    current = { id: d.id, row: row, detail: d };
                    renderDetail();
                    showView('detail');
                },
                error: function (xhr) {
                    fail(xhr, 'Failed to load survey response.');
                    showList();
                },
            });
        }

        function renderDetail() {
            const d = current.detail, f = d.feedback || {}, s = d.summary || {}, t = d.security_trail || {};
            const machine = s.machine_ok;

            set('dTicketCrumb', d.ticket_code);
            set('dAvg', s.average_score === null || s.average_score === undefined ? '-' : Number(s.average_score).toFixed(1));
            set('dCustomer', dash(s.customer));
            set('dMachineTop', machineText(machine, false));
            set('dMachine', machineText(machine, false));
            document.getElementById('dMachine').style.color = machineColor(machine);
            set('dSource', dash(s.source_label));
            set('dExplanation', dash(f.machine_issue_note));
            set('dComments', dash(f.comments));
            set('dSigner', [f.signer_name, f.signer_position].filter(Boolean).join(' · ') || '-');
            setSignature('dSignature', f.signature_url);

            document.getElementById('dAdminReasonWrap').classList.toggle('hidden', ! s.admin_input_reason);
            set('dAdminReason', s.admin_input_reason || '');

            set('dOtp', t.otp_verified === null || t.otp_verified === undefined ? '-'
                : (t.otp_verified ? 'Yes' : 'No') + (t.otp_phone_masked ? ' · ' + t.otp_phone_masked : ''));
            set('dLinkBy', dash(t.link_created_by));
            set('dSharedVia', t.shared_via === 'copy' ? 'Copied link' : (t.shared_via === 'whatsapp' ? 'WhatsApp' : '-'));
            set('dDevice', [t.ip_address, parseBrowser(t.user_agent)].filter(Boolean).join(' · ') || '-');
            set('dDuration', fmtDuration(t.duration_seconds));
            const reasons = t.review_reasons || [];
            set('dReason', reasons.length ? reasons.map(function (c) { return REVIEW_REASON_TEXT[c] || c; }).join(', ') : 'High confidence response');

            document.getElementById('dBreakdown').innerHTML = (f.ratings || []).map(function (item) {
                const width = item.score / 5 * 100;
                return `<li class="flex items-center gap-4">
                    <span class="w-48 shrink-0">${esc(item.label)}</span>
                    <span class="flex-1 h-1.5 bg-[#EDEDED] rounded-full overflow-hidden">
                        <span class="block h-full rounded-full" style="width:${width}%; background-color:${scoreColor(item.score)}"></span>
                    </span>
                    <span class="w-8 text-right font-bold text-[#115640]">${Number(item.score).toFixed(1)}</span>
                </li>`;
            }).join('');

            renderActions();
        }

        function renderActions() {
            const d = current.detail;
            const status = d.security_trail ? d.security_trail.review_status : null;
            const reviewBtn = document.getElementById('btnMarkReviewed');
            const needs = status === 'needs_review';
            reviewBtn.classList.toggle('hidden', ! canReview);
            reviewBtn.textContent = status === 'reviewed' ? 'Reviewed' : 'Mark as reviewed';
            reviewBtn.disabled = ! needs;
            reviewBtn.classList.toggle('opacity-60', ! needs);
            reviewBtn.classList.toggle('cursor-not-allowed', ! needs);

            const done = !! recomingIds[d.id];
            const rcBtn = document.getElementById('btnScheduleRecoming');
            rcBtn.textContent = done ? 'Recoming scheduled' : 'Schedule Recoming';
            rcBtn.disabled = done;
            rcBtn.classList.toggle('opacity-60', done);
            rcBtn.classList.toggle('cursor-not-allowed', done);
        }

        // ---------- MARK AS REVIEWED ----------
        document.getElementById('btnMarkReviewed').addEventListener('click', function () {
            if (! current) return;
            const btn = this;
            btn.disabled = true;
            $.ajax({
                url: API + '/satisfaction-surveys/' + current.id + '/review',
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function () {
                    if (current.detail.security_trail) current.detail.security_trail.review_status = 'reviewed';
                    if (current.row) current.row.review_status = 'reviewed';
                    renderActions();
                    loadList();
                    Swal.fire({ title: 'Reviewed', icon: 'success', confirmButtonColor: '#115640', timer: 1500 });
                },
                error: function (xhr) {
                    fail(xhr, 'Failed to mark as reviewed.');
                    renderActions();
                },
            });
        });

        // ---------- SCHEDULE RECOMING ----------
        const recomingModal = document.getElementById('recomingModal');
        const rcDate = document.getElementById('rcDate');
        const rcDateError = document.getElementById('rcDateError');
        let rcReason = null;

        function setReason(btn) {
            rcReason = btn.textContent.trim();
            document.querySelectorAll('.rc-reason').forEach(function (b) {
                const on = b === btn;
                b.classList.toggle('bg-[#115640]', on);
                b.classList.toggle('text-white', on);
                b.classList.toggle('border-[#115640]', on);
                b.classList.toggle('bg-white', ! on);
                b.classList.toggle('border-[#D9D9D9]', ! on);
            });
        }
        function closeRecoming() { recomingModal.classList.add('hidden'); }

        document.getElementById('btnScheduleRecoming').addEventListener('click', function () {
            if (! current) return;
            set('rcTicket', current.detail.ticket_code);
            setReason(document.querySelector('.rc-reason'));
            rcDate.value = '';
            rcDate.min = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
            document.getElementById('rcNotes').value = '';
            rcDateError.classList.add('hidden');
            recomingModal.classList.remove('hidden');
        });
        document.querySelectorAll('.rc-reason').forEach(function (b) {
            b.addEventListener('click', function () { setReason(this); });
        });
        document.getElementById('rcCancel').addEventListener('click', closeRecoming);
        recomingModal.addEventListener('click', function (e) { if (e.target === recomingModal) closeRecoming(); });

        document.getElementById('rcSave').addEventListener('click', function () {
            if (! current) return;
            if (! rcDate.value || rcDate.value < rcDate.min) return rcDateError.classList.remove('hidden');

            const saveBtn = this;
            saveBtn.disabled = true;
            $.ajax({
                url: API + '/tickets/' + current.detail.ticket_id + '/recoming',
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: {
                    reason: RECOMING_REASON[rcReason] || 'other',
                    planned_visit_at: rcDate.value,
                    notes: document.getElementById('rcNotes').value.trim(),
                },
                success: function () {
                    recomingIds[current.id] = true;
                    closeRecoming();
                    renderActions();
                    loadList();
                    Swal.fire({ title: 'Recoming scheduled', icon: 'success', confirmButtonColor: '#115640', timer: 1500 });
                },
                error: function (xhr) { fail(xhr, 'Failed to schedule Recoming.'); },
                complete: function () { saveBtn.disabled = false; },
            });
        });

        // ---------- VIEW FORM (read-only) ----------
        document.getElementById('btnViewForm').addEventListener('click', function () {
            if (! current) return;
            $.ajax({
                url: API + '/satisfaction-surveys/' + current.id + '/form',
                method: 'GET',
                headers: { Accept: 'application/json' },
                success: function (res) { renderForm(res.data); showView('form'); },
                error: function (xhr) { fail(xhr, 'Failed to load survey form.'); },
            });
        });

        function renderForm(f) {
            const job = f.job_information || {}, signer = f.signer || {};
            set('vfTicket', dash(job.ticket_code));
            set('vfCustomer', dash(job.customer));
            set('vfTechnician', dash(job.technician));
            set('vfServiceDate', dash(job.service_date));
            set('vfComments', dash(f.comments));
            set('vfMachineBadge', 'Machine: ' + (f.machine_ok === true ? 'Yes' : (f.machine_ok === false ? 'No' : '-')));
            set('vfMachine', machineText(f.machine_ok, true));
            document.getElementById('vfMachine').style.color = machineColor(f.machine_ok);
            document.getElementById('vfExplanationWrap').classList.toggle('hidden', ! f.machine_issue_note);
            set('vfExplanation', f.machine_issue_note || '');
            set('vfSigner', [signer.name, signer.position].filter(Boolean).join(' · ') || '-');
            setSignature('vfSignature', signer.signature_url);

            document.getElementById('vfRatings').innerHTML = (f.ratings || []).map(function (item) {
                const boxes = [1, 2, 3, 4, 5].map(function (n) {
                    const on = n === Math.round(item.score);
                    return `<span class="flex items-center justify-center h-14 rounded-lg border font-semibold ${on ? 'bg-[#115640] border-[#115640] text-white' : 'bg-white border-[#D9D9D9] text-[#4B5563]'}">${n}</span>`;
                }).join('');
                return `<div class="py-4 border-b border-[#EDEDED] last:border-b-0">
                    <p class="font-medium mb-3">${esc(item.label)}</p>
                    <div class="grid grid-cols-5 gap-2">${boxes}</div>
                </div>`;
            }).join('');
        }

        document.querySelectorAll('.back-to-response').forEach(function (btn) {
            btn.addEventListener('click', function () { showView('detail'); });
        });
        document.querySelectorAll('.back-to-list').forEach(function (btn) {
            btn.addEventListener('click', showList);
        });

        // browser back/forward: keep the visible view in sync with the URL hash (#id)
        window.addEventListener('hashchange', function () {
            if (location.hash) showDetail(location.hash.slice(1));
            else showList();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeRecoming();
        });

        loadList();
        if (location.hash) showDetail(location.hash.slice(1));
    })();
</script>
@endsection
