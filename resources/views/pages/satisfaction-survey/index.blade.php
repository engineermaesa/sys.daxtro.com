@extends('layouts.app')

@section('content')
@php
    // TODO: DUMMY DATA (frontend only) — ganti dengan data dari backend saat survey otomatis dari ticket aftersales siap.
    $criteria = [
        'Punctuality', 'Technician Attitude', 'Technical Knowledge', 'Work Speed', 'Service Quality',
        'Cleanliness', 'Work Explanation', 'Overall Satisfaction', 'Technician Appearance',
    ];

    $surveys = [
        ['ticket' => 'DAX-AF-20261012-1', 'customer' => 'PT Global Dynamics', 'technician' => 'Ahmad Fauzi', 'service_date' => '2026-10-12', 'submitted' => '2026-10-12 16:42', 'scores' => [5,5,5,4,5,5,5,5,4], 'machine_ok' => true, 'source' => 'Customer link', 'reviewed' => false, 'explanation' => 'The machine is stable after servicing. Cooling performance should be monitored for the next 24 hours.', 'comments' => 'Technician was helpful and explained the repair clearly. Please improve arrival time updates.', 'signer' => 'Budi Hartono · Customer PIC', 'otp' => 'Yes · customer phone', 'link_by' => 'Novia Rajmi', 'shared_via' => 'WhatsApp', 'device' => '103.28.xxx.xxx · Chrome Mobile', 'duration' => '04m 18s', 'reason' => 'High confidence response'],
        ['ticket' => 'DAX-AF-20261010-2', 'customer' => 'CV Heavy Logistics', 'technician' => 'Rizky Pratama', 'service_date' => '2026-10-10', 'submitted' => '2026-10-10 11:10', 'scores' => [4,4,4,4,5,4,4,4,4], 'machine_ok' => false, 'source' => 'Admin assisted', 'reviewed' => true, 'explanation' => 'Machine still makes noise under full load. Sparepart follow up required.', 'comments' => 'Perlu follow up sparepart.', 'signer' => 'Rina Wijaya · Supervisor', 'otp' => 'No · admin assisted', 'link_by' => 'Novia Rajmi', 'shared_via' => 'Phone call', 'device' => '103.28.xxx.xxx · Chrome Desktop', 'duration' => '06m 02s', 'reason' => 'Machine not working'],
        ['ticket' => 'DAX-AF-20261008-3', 'customer' => 'PT Maju Bersama', 'technician' => 'Dedi Kurniawan', 'service_date' => '2026-10-08', 'submitted' => '2026-10-09 09:15', 'scores' => [3,4,3,3,4,3,4,3,4], 'machine_ok' => true, 'source' => 'Customer link', 'reviewed' => false, 'explanation' => 'Preventive maintenance completed, machine running normally.', 'comments' => '', 'signer' => 'Hendra Gunawan · Owner', 'otp' => 'Yes · customer phone', 'link_by' => 'Novia Rajmi', 'shared_via' => 'WhatsApp', 'device' => '114.10.xxx.xxx · Safari Mobile', 'duration' => '03m 40s', 'reason' => 'Low average score'],
        ['ticket' => 'DAX-AF-20261005-4', 'customer' => 'PT Sinar Abadi', 'technician' => 'Ahmad Fauzi', 'service_date' => '2026-10-05', 'submitted' => '2026-10-05 14:30', 'scores' => [5,5,4,5,5,5,5,5,5], 'machine_ok' => true, 'source' => 'Admin assisted', 'reviewed' => true, 'explanation' => 'Installation completed and tested, machine ready for production.', 'comments' => 'Instalasi rapi, terima kasih.', 'signer' => 'Siti Aminah · Factory Manager', 'otp' => 'No · admin assisted', 'link_by' => 'Novia Rajmi', 'shared_via' => 'Email', 'device' => '103.28.xxx.xxx · Chrome Desktop', 'duration' => '05m 11s', 'reason' => 'High confidence response'],
        ['ticket' => 'DAX-AF-20261003-5', 'customer' => 'UD Berkah Jaya', 'technician' => 'Rizky Pratama', 'service_date' => '2026-10-03', 'submitted' => '2026-10-04 08:05', 'scores' => [2,3,3,2,3,3,2,2,3], 'machine_ok' => false, 'source' => 'Customer link', 'reviewed' => false, 'explanation' => 'Technician arrived late and the issue is not fully resolved.', 'comments' => 'Mohon ketepatan waktu ditingkatkan.', 'signer' => 'Agus Salim · Operator', 'otp' => 'Yes · customer phone', 'link_by' => 'Novia Rajmi', 'shared_via' => 'WhatsApp', 'device' => '36.80.xxx.xxx · Chrome Mobile', 'duration' => '02m 55s', 'reason' => 'Low score and machine not working'],
        ['ticket' => 'DAX-AF-20260929-6', 'customer' => 'PT Nusantara Pack', 'technician' => 'Dedi Kurniawan', 'service_date' => '2026-09-29', 'submitted' => '2026-09-29 17:20', 'scores' => [4,5,5,4,4,4,5,4,5], 'machine_ok' => true, 'source' => 'Customer link', 'reviewed' => true, 'explanation' => 'Repair finished, all parts working properly.', 'comments' => 'Penjelasan hasil pekerjaan jelas.', 'signer' => 'Dewi Lestari · Maintenance Head', 'otp' => 'Yes · customer phone', 'link_by' => 'Novia Rajmi', 'shared_via' => 'WhatsApp', 'device' => '103.28.xxx.xxx · Chrome Mobile', 'duration' => '04m 02s', 'reason' => 'High confidence response'],
    ];

    foreach ($surveys as &$s) {
        $s['avg'] = round(array_sum($s['scores']) / count($s['scores']), 1);
        $s['breakdown'] = array_map(fn ($l, $v) => ['label' => $l, 'score' => $v], $criteria, $s['scores']);
    }
    unset($s);
@endphp

<section id="surveyRoot" class="text-xs! lg:text-sm! text-[#1E1E1E]" data-surveys="{{ json_encode($surveys) }}">
    {{-- LIST VIEW --}}
    <div id="surveyListView">
        <div class="pt-4">
            <h1 class="font-bold text-xl lg:text-2xl text-[#115640]">Satisfaction Survey</h1>
            <p class="text-[#757575] mt-1">Review customer feedback and service quality responses.</p>
        </div>

        <form id="surveySearchForm" class="mt-4 p-4 bg-white border border-[#D9D9D9] rounded-lg flex flex-col lg:flex-row lg:items-center gap-3">
            <input type="text" id="surveySearchInput" placeholder="Search ticket, customer, or technician"
                class="flex-1 border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none focus:border-[#115640]" />
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
                        @foreach ($surveys as $s)
                            <tr class="survey-row border-b border-[#D9D9D9] last:border-b-0"
                                data-search="{{ strtolower($s['ticket'].' '.$s['customer'].' '.$s['technician']) }}">
                                <td class="p-3 font-semibold text-[#115640] text-center">{{ $s['ticket'] }}</td>
                                <td class="p-3 font-semibold text-center">{{ $s['customer'] }}</td>
                                <td class="text-center p-3 text-[#757575]">{{ $s['technician'] }}</td>
                                <td class="p-3 text-center text-[#757575]">{{ $s['service_date'] }}</td>
                                <td class="p-3 text-center text-[#757575]">{{ $s['submitted'] }}</td>
                                <td class="p-3 font-semibold text-[#115640] text-center">{{ number_format($s['avg'], 1) }} / 5</td>
                                <td class="p-3 text-center">
                                    @if ($s['machine_ok'])
                                        <span class="px-2.5 py-1 rounded-full bg-[#E7F3EE] text-[#115640] font-semibold">Yes</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-[#FBEAEA] text-[#EC221F] font-semibold">No</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center text-[#757575]">{{ $s['source'] }}</td>
                                <td data-ticket="{{ $s['ticket'] }}"
                                    class="review-cell p-3 text-center font-semibold {{ $s['reviewed'] ? 'text-[#115640]' : 'text-[#D97706]' }}">
                                    {{ $s['reviewed'] ? 'Reviewed' : 'Needs review' }}
                                </td>
                                <td class="p-3 text-center">
                                    <div class="relative inline-block">
                                        <button type="button"
                                            class="action-toggle w-9 h-9 flex items-center justify-center rounded-lg border border-[#D9D9D9] bg-[#F5F5F5] text-[#757575] cursor-pointer">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <div class="action-menu hidden fixed w-52 bg-white border border-[#D9D9D9] rounded-lg shadow-lg z-50 p-1">
                                            <button type="button" data-ticket="{{ $s['ticket'] }}"
                                                class="view-detail w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-[#F5F5F5] text-left cursor-pointer">
                                                <i class="far fa-copy text-[#757575]"></i>
                                                <span>View Detail</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="surveyEmptyRow" class="hidden">
                            <td class="p-10 text-center text-[#757575]" colspan="10">No survey responses found.</td>
                        </tr>
                    </tbody>
                </table>
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
        // data dummy dibaca dari atribut HTML supaya tidak ada sintaks Blade di dalam <script>
        const surveys = JSON.parse(document.getElementById('surveyRoot').dataset.surveys);
        const listView = document.getElementById('surveyListView');
        const detailView = document.getElementById('surveyDetailView');
        const formView = document.getElementById('surveyFormView');
        let currentTicket = null;
        const set = (id, text) => { document.getElementById(id).textContent = text; };

        // ACTION MENU
        function closeMenus() {
            document.querySelectorAll('.action-menu').forEach(function (m) { m.classList.add('hidden'); });
        }

        document.querySelectorAll('.action-toggle').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const menu = this.nextElementSibling;
                const wasHidden = menu.classList.contains('hidden');
                closeMenus();
                if (wasHidden) {
                    // fixed positioning so the menu is never clipped or covered by the next row
                    const rect = this.getBoundingClientRect();
                    menu.style.top = (rect.bottom + 4) + 'px';
                    menu.style.left = (rect.right - 208) + 'px';
                    menu.classList.remove('hidden');
                }
            });
        });
        document.addEventListener('click', closeMenus);
        window.addEventListener('scroll', closeMenus, true);
        window.addEventListener('resize', closeMenus);

        // SEARCH (client-side, dummy data)
        const form = document.getElementById('surveySearchForm');
        const input = document.getElementById('surveySearchInput');

        function applySearch() {
            const term = input.value.trim().toLowerCase();
            let visible = 0;
            document.querySelectorAll('.survey-row').forEach(function (row) {
                const show = row.dataset.search.includes(term);
                row.classList.toggle('hidden', ! show);
                if (show) visible++;
            });
            document.getElementById('surveyEmptyRow').classList.toggle('hidden', visible > 0);
        }

        form.addEventListener('submit', function (e) { e.preventDefault(); applySearch(); });
        input.addEventListener('input', applySearch);

        // DETAIL VIEW
        function scoreColor(score) {
            return score >= 4 ? '#115640' : (score >= 3 ? '#E8B931' : '#EC221F');
        }

        function showDetail(ticket) {
            const s = surveys.find(function (x) { return x.ticket === ticket; });
            if (! s) return showList();

            const machine = s.machine_ok ? 'Working properly' : 'Not working properly';

            set('dTicketCrumb', s.ticket);
            set('dAvg', Number(s.avg).toFixed(1));
            set('dCustomer', s.customer);
            set('dMachineTop', machine);
            set('dMachine', machine);
            document.getElementById('dMachine').style.color = s.machine_ok ? '#115640' : '#EC221F';
            set('dSource', s.source);
            set('dExplanation', s.explanation || '-');
            set('dComments', s.comments || '-');
            set('dSigner', s.signer);
            set('dOtp', s.otp);
            set('dLinkBy', s.link_by);
            set('dSharedVia', s.shared_via);
            set('dDevice', s.device);
            set('dDuration', s.duration);
            set('dReason', s.reason);

            document.getElementById('dBreakdown').innerHTML = s.breakdown.map(function (item) {
                const width = item.score / 5 * 100;
                return `<li class="flex items-center gap-4">
                    <span class="w-48 shrink-0">${item.label}</span>
                    <span class="flex-1 h-1.5 bg-[#EDEDED] rounded-full overflow-hidden">
                        <span class="block h-full rounded-full" style="width:${width}%; background-color:${scoreColor(item.score)}"></span>
                    </span>
                    <span class="w-8 text-right font-bold text-[#115640]">${Number(item.score).toFixed(1)}</span>
                </li>`;
            }).join('');

            currentTicket = s.ticket;
            renderReviewState(s);

            listView.classList.add('hidden');
            formView.classList.add('hidden');
            detailView.classList.remove('hidden');
            window.scrollTo(0, 0);
        }

        function showList() {
            detailView.classList.add('hidden');
            formView.classList.add('hidden');
            listView.classList.remove('hidden');
            if (location.hash) history.replaceState(null, '', location.pathname + location.search);
        }

        // MARK AS REVIEWED (client-side only, TODO: persist via backend)
        function renderReviewState(s) {
            const btn = document.getElementById('btnMarkReviewed');
            btn.textContent = s.reviewed ? 'Reviewed' : 'Mark as reviewed';
            btn.disabled = !! s.reviewed;
            btn.classList.toggle('opacity-60', !! s.reviewed);
            btn.classList.toggle('cursor-not-allowed', !! s.reviewed);

            document.querySelectorAll('.review-cell').forEach(function (cell) {
                if (cell.dataset.ticket !== s.ticket) return;
                cell.textContent = s.recoming ? 'Recoming' : (s.reviewed ? 'Reviewed' : 'Needs review');
                // !! → toggle() needs a real boolean, undefined would flip the class instead of forcing it
                cell.classList.toggle('text-[#115640]', !! (s.reviewed && ! s.recoming));
                cell.classList.toggle('text-[#D97706]', !! (! s.reviewed || s.recoming));
            });

            const rcBtn = document.getElementById('btnScheduleRecoming');
            rcBtn.textContent = s.recoming ? 'Recoming scheduled' : 'Schedule Recoming';
            rcBtn.disabled = !! s.recoming;
            rcBtn.classList.toggle('opacity-60', !! s.recoming);
            rcBtn.classList.toggle('cursor-not-allowed', !! s.recoming);
        }

        // SCHEDULE RECOMING (client-side only)
        // TODO: persist to backend (ticket progress -> Recoming, survey link disabled, status back to On Site on follow-up date)
        const recomingModal = document.getElementById('recomingModal');
        const rcDate = document.getElementById('rcDate');
        const rcDateError = document.getElementById('rcDateError');

        function setReason(btn) {
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
            set('rcTicket', currentTicket);
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
            if (! rcDate.value || rcDate.value < rcDate.min) return rcDateError.classList.remove('hidden');

            const s = surveys.find(function (x) { return x.ticket === currentTicket; });
            if (! s) return;
            s.recoming = true;
            renderReviewState(s);
            closeRecoming();
        });

        document.getElementById('btnMarkReviewed').addEventListener('click', function () {
            const s = surveys.find(function (x) { return x.ticket === currentTicket; });
            if (! s) return;
            s.reviewed = true;
            renderReviewState(s);
        });

        // VIEW FORM (read-only)
        function showForm() {
            const s = surveys.find(function (x) { return x.ticket === currentTicket; });
            if (! s) return;

            set('vfTicket', s.ticket);
            set('vfCustomer', s.customer);
            set('vfTechnician', s.technician);
            set('vfServiceDate', s.service_date);
            set('vfComments', s.comments || '-');
            set('vfMachineBadge', 'Machine: ' + (s.machine_ok ? 'Yes' : 'No'));
            set('vfMachine', s.machine_ok ? 'Yes, machine is working properly' : 'No, machine is not working properly');
            document.getElementById('vfMachine').style.color = s.machine_ok ? '#115640' : '#EC221F';

            document.getElementById('vfRatings').innerHTML = s.breakdown.map(function (item) {
                const boxes = [1, 2, 3, 4, 5].map(function (n) {
                    const on = n === Math.round(item.score);
                    return `<span class="flex items-center justify-center h-14 rounded-lg border font-semibold ${on ? 'bg-[#115640] border-[#115640] text-white' : 'bg-white border-[#D9D9D9] text-[#4B5563]'}">${n}</span>`;
                }).join('');
                return `<div class="py-4 border-b border-[#EDEDED] last:border-b-0">
                    <p class="font-medium mb-3">${item.label}</p>
                    <div class="grid grid-cols-5 gap-2">${boxes}</div>
                </div>`;
            }).join('');

            detailView.classList.add('hidden');
            formView.classList.remove('hidden');
            window.scrollTo(0, 0);
        }

        document.getElementById('btnViewForm').addEventListener('click', showForm);
        document.querySelectorAll('.back-to-response').forEach(function (btn) {
            btn.addEventListener('click', function () { showDetail(currentTicket); });
        });

        document.querySelectorAll('.view-detail').forEach(function (btn) {
            btn.addEventListener('click', function () {
                location.hash = this.dataset.ticket;
                showDetail(this.dataset.ticket);
            });
        });

        document.querySelectorAll('.back-to-list').forEach(function (btn) {
            btn.addEventListener('click', showList);
        });

        // browser back/forward: keep the visible view in sync with the URL hash
        window.addEventListener('hashchange', function () {
            if (location.hash) showDetail(decodeURIComponent(location.hash.slice(1)));
            else showList();
        });

        // ESC closes the recoming modal
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeRecoming();
        });

        if (location.hash) showDetail(decodeURIComponent(location.hash.slice(1)));
    })();
</script>
@endsection
