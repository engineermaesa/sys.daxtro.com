@extends('layouts.app')

@section('content')
<section class="min-h-screen text-xs! lg:text-sm! text-[#1E1E1E]">
    <div class="pt-4">
        <h1 class="font-bold text-xl lg:text-2xl text-[#1E1E1E]">Satisfaction Survey</h1>
        <p class="text-[#757575] mt-1">Customer satisfaction tracking for after-sales service quality.</p>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        {{-- WORK TYPE --}}
        <div class="p-5 bg-white border border-[#D9D9D9] rounded-lg">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full border border-[#417866] text-[#417866] [&_svg]:w-4 [&_svg]:h-4"><x-icon.task/></span>
                <span class="text-[#757575] font-semibold tracking-wide">WORK TYPE</span>
            </div>
            <p class="mt-4 text-2xl font-bold">{{ $totalWorkType }} <span class="text-sm font-normal text-[#757575]">total</span></p>
            <ul class="mt-3 divide-y divide-[#EDEDED]">
                @foreach ($jobTypes as $type)
                    <li class="flex items-center justify-between py-1.5">
                        <span>{{ $type }}</span>
                        <span class="font-semibold">{{ $jobTypeCounts[$type] ?? 0 }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- PENDING MACHINES --}}
        <div class="p-5 bg-white border border-[#D9D9D9] rounded-lg">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full border border-[#E8B931] text-[#E8B931] [&_svg]:w-4 [&_svg]:h-4"><x-icon.info/></span>
                <span class="text-[#757575] font-semibold tracking-wide">PENDING MACHINES</span>
            </div>
            <p class="mt-4 text-3xl font-bold text-[#E8B931]">{{ $pendingCount }}</p>
            <p class="text-[#757575] mt-1">Machines not yet resolved</p>
            @if ($pendingCompanies->isNotEmpty())
                <hr class="my-3 border-[#EDEDED]">
                <ul class="space-y-1.5">
                    @foreach ($pendingCompanies->take(3) as $company)
                        <li class="text-[#1E1E1E] truncate">{{ $company }}</li>
                    @endforeach
                </ul>
                @if ($pendingCompanies->count() > 3)
                    <button type="button" id="openPendingModal" class="mt-2 text-[#115640] font-semibold hover:underline cursor-pointer">
                        View more ({{ $pendingCompanies->count() - 3 }})
                    </button>
                @endif
            @endif
        </div>

        {{-- RESOLVED MACHINES --}}
        <div class="p-5 bg-white border border-[#D9D9D9] rounded-lg">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full border border-[#115640] text-[#115640] [&_svg]:w-4 [&_svg]:h-4"><x-icon.circle-check/></span>
                <span class="text-[#757575] font-semibold tracking-wide">RESOLVED MACHINES</span>
            </div>
            <p class="mt-4 text-3xl font-bold text-[#115640]">{{ $resolvedCount }}</p>
            <p class="text-[#757575] mt-1">Machines working properly</p>
            <div class="mt-4 flex items-center gap-3">
                <div class="flex-1 h-2 bg-[#EDEDED] rounded-full overflow-hidden">
                    <div class="h-full bg-[#115640] rounded-full" style="width: {{ $resolvedPct }}%"></div>
                </div>
                <span class="font-semibold text-[#757575]">{{ $resolvedPct }}%</span>
            </div>
        </div>
    </div>

    {{-- PENDING MACHINES MODAL --}}
    @if ($pendingCompanies->count() > 3)
        <div id="pendingModal" class="hidden fixed inset-0 bg-black/40 z-[9999] flex items-center justify-center p-4">
            <div class="bg-white rounded-lg w-full max-w-sm max-h-[70vh] overflow-y-auto p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Pending Machines ({{ $pendingCompanies->count() }})</h3>
                    <button type="button" id="closePendingModal" class="text-[#757575] cursor-pointer text-lg leading-none">&times;</button>
                </div>
                <ul class="divide-y divide-[#EDEDED]">
                    @foreach ($pendingCompanies as $company)
                        <li class="text-[#1E1E1E] py-2">{{ $company }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- SURVEY RESULTS --}}
    <div class="mt-4 bg-white border border-[#D9D9D9] rounded-lg">
        <div class="p-4 border-b border-[#D9D9D9]">
            <h2 class="font-semibold text-base">Survey Results</h2>
            <p class="text-[#757575]">{{ $responses->total() }} entries found</p>

            <form id="surveyFilterForm" method="GET" action="{{ route('satisfaction-survey.index') }}"
                class="mt-3 flex flex-col lg:flex-row lg:items-center gap-3">

                <div class="flex-1 border border-[#D9D9D9] rounded-lg flex items-center gap-2 px-3">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                        <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786" />
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer, company, technician..."
                        class="w-full py-2 border-none focus:outline-none" />
                </div>

                <select name="job_type" class="border border-[#D9D9D9] rounded-lg px-3 py-2 lg:w-48">
                    <option value="">All Job Types</option>
                    @foreach ($jobTypes as $type)
                        <option value="{{ $type }}" @selected(request('job_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>

                <select name="sort" class="border border-[#D9D9D9] rounded-lg px-3 py-2 lg:w-44">
                    <option value="">Sort by Score</option>
                    <option value="score_asc" @selected(request('sort') === 'score_asc')>Score: Low to High</option>
                    <option value="score_desc" @selected(request('sort') === 'score_desc')>Score: High to Low</option>
                </select>

                <div class="relative shrink-0" id="serviceDateFilter">
                    <button type="button" id="openServiceDate"
                        class="flex items-center gap-2 border border-[#D9D9D9] rounded-lg px-3 py-2 w-full lg:w-auto cursor-pointer">
                        <i class="fa-regular fa-calendar text-[#757575]"></i>
                        <span id="serviceDateLabel" class="text-[#1E1E1E] whitespace-nowrap">
                            @if (request('from_date') && request('to_date'))
                                {{ request('from_date') }} &rarr; {{ request('to_date') }}
                            @else
                                Service Date
                            @endif
                        </span>
                        <i id="serviceDateChevron" class="fas fa-chevron-down text-xs text-[#757575] transition-transform duration-200"></i>
                    </button>

                    <div id="serviceDateDropdown"
                        class="absolute top-full right-0 mt-2 bg-white rounded-lg shadow-xl w-[320px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top-right">
                        <h3 class="font-semibold mb-2">Select Service Date Range</h3>

                        <div class="flex justify-center">
                            <input type="text" id="service-date-picker" class="hidden">
                        </div>

                        <div class="flex justify-end gap-2 mt-3">
                            <button type="button" id="cancelServiceDate" class="px-3 py-1 text-[#303030] cursor-pointer">Cancel</button>
                            <button type="button" id="applyServiceDate" class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">Apply</button>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="from_date" id="fromDateInput" value="{{ request('from_date') }}">
                <input type="hidden" name="to_date" id="toDateInput" value="{{ request('to_date') }}">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead>
                    <tr class="border-b border-[#D9D9D9] text-left text-[#757575]">
                        <th class="p-3 font-semibold">Customer / Company</th>
                        <th class="p-3 font-semibold">WO #</th>
                        <th class="p-3 font-semibold">Date</th>
                        <th class="p-3 font-semibold">Technician</th>
                        <th class="p-3 font-semibold">Job Type</th>
                        <th class="p-3 font-semibold">Avg Score</th>
                        <th class="p-3 font-semibold">Machine Functioning</th>
                        <th class="p-3 font-semibold">Pending Issues</th>
                        <th class="p-3 font-semibold">Feedback</th>
                        <th class="p-3 font-semibold">Respondent / Role</th>
                        <th class="p-3 font-semibold">Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($responses as $row)
                        <tr class="border-b border-[#D9D9D9] align-top">
                            <td class="p-3">
                                <p class="font-semibold">{{ $row['customer'] }}</p>
                                <p class="text-[#757575]">{{ $row['company'] }}</p>
                            </td>
                            <td class="p-3">{{ $row['wo_number'] }}</td>
                            <td class="p-3">{{ $row['service_date']?->format('d M y') }}</td>
                            <td class="p-3">{{ $row['technician'] }}</td>
                            <td class="p-3">
                                @if ($row['job_type'] !== '')
                                    @php
                                        $jobTypeLabels = ['Preventive Maintenance' => 'PM', 'Installation' => 'Install'];
                                    @endphp
                                    <span class="px-2 py-1 rounded-md bg-[#EDEDED] text-[#1E1E1E] font-medium">
                                        {{ $jobTypeLabels[$row['job_type']] ?? $row['job_type'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if (! is_null($row['avg_score']))
                                    @php
                                        $scoreColor = $row['avg_score'] >= 4 ? '#115640' : ($row['avg_score'] >= 3 ? '#B4841F' : '#EC221F');
                                        $scoreModalData = [
                                            'avg' => $row['avg_score'],
                                            'customer' => $row['customer'],
                                            'wo' => $row['wo_number'],
                                            'date' => optional($row['service_date'])->format('d M Y'),
                                            'technician' => $row['technician'],
                                            'breakdown' => $row['score_breakdown'],
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="font-semibold" style="color: {{ $scoreColor }}">{{ number_format($row['avg_score'], 1) }}</span>
                                        <button type="button"
                                            class="score-trigger w-5 h-5 flex items-center justify-center rounded-full border border-[#115640] text-[#115640] cursor-pointer"
                                            data-score="{{ json_encode($scoreModalData) }}">
                                            <i class="fas fa-info text-[9px]"></i>
                                        </button>
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($row['machine_ok'] === true)
                                    <span class="px-2 py-1 rounded-md bg-[#E7F3EE] text-[#115640] font-medium">Yes</span>
                                @elseif ($row['machine_ok'] === false)
                                    <span class="px-2 py-1 rounded-md bg-[#FBEAEA] text-[#EC221F] font-medium">No</span>
                                @endif
                            </td>
                            @php
                                $issueModalMeta = [
                                    'customer' => $row['customer'],
                                    'wo' => $row['wo_number'],
                                    'date' => optional($row['service_date'])->format('d M Y'),
                                    'technician' => $row['technician'],
                                ];
                            @endphp
                            <td class="p-3 w-[160px]">
                                @if ($row['pending_issues'])
                                    <button type="button" class="issue-trigger flex items-center gap-2 w-full text-left cursor-pointer"
                                        data-issue="{{ json_encode($issueModalMeta + ['title' => 'Pending Issues', 'text' => $row['pending_issues']]) }}">
                                        <span class="truncate min-w-0">{{ \Illuminate\Support\Str::limit($row['pending_issues'], 25) }}</span>
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#757575]">
                                            <i class="fas fa-arrow-right text-[10px]"></i>
                                        </span>
                                    </button>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-3 w-[160px]">
                                @if ($row['feedback'])
                                    <button type="button" class="issue-trigger flex items-center gap-2 w-full text-left cursor-pointer"
                                        data-issue="{{ json_encode($issueModalMeta + ['title' => 'Feedback', 'text' => $row['feedback']]) }}">
                                        <span class="truncate min-w-0 text-[#115640]">{{ \Illuminate\Support\Str::limit($row['feedback'], 25) }}</span>
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#757575]">
                                            <i class="fas fa-arrow-right text-[10px]"></i>
                                        </span>
                                    </button>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-3">
                                <p class="font-semibold">{{ $row['respondent'] }}</p>
                                <p class="text-[#757575]">{{ $row['role'] }}</p>
                            </td>
                            <td class="p-3">{{ $row['submitted_at']?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-6 text-center text-[#757575]" colspan="11">No survey responses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="flex justify-between items-center px-4 py-3 text-[#1E1E1E]">
            <p class="text-[#757575]">
                Showing {{ $responses->firstItem() ?? 0 }} to {{ $responses->lastItem() ?? 0 }} of {{ $responses->total() }} entries
            </p>

            @if ($responses->lastPage() > 1)
                <div class="flex items-center gap-1">
                    <a href="{{ $responses->previousPageUrl() ?? '#' }}"
                        class="p-2 border border-[#D9D9D9] rounded-md {{ $responses->onFirstPage() ? 'pointer-events-none opacity-40' : '' }}">
                        <i class="fas fa-chevron-left" style="font-size: 12px;"></i>
                    </a>

                    @for ($p = 1; $p <= $responses->lastPage(); $p++)
                        <a href="{{ $responses->url($p) }}"
                            class="px-3 py-1.5 rounded-md border {{ $p === $responses->currentPage() ? 'bg-[#115640] text-white border-[#115640]' : 'border-[#D9D9D9]' }}">
                            {{ $p }}
                        </a>
                    @endfor

                    <a href="{{ $responses->nextPageUrl() ?? '#' }}"
                        class="p-2 border border-[#D9D9D9] rounded-md {{ ! $responses->hasMorePages() ? 'pointer-events-none opacity-40' : '' }}">
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- ISSUE / FEEDBACK DETAIL MODAL --}}
    <div id="issueModal" class="hidden fixed inset-0 bg-black/40 z-[9999] flex items-center justify-center p-4">
        <div class="bg-white rounded-lg w-full max-w-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 id="issueModalTitle" class="font-semibold text-base"></h3>
                <button type="button" id="closeIssueModal" class="text-[#757575] cursor-pointer text-lg leading-none">&times;</button>
            </div>
            <div class="flex items-center justify-between bg-[#F7F7F7] rounded-lg p-3 mb-4">
                <div>
                    <p id="issueModalCustomer" class="font-semibold"></p>
                    <p id="issueModalMeta" class="text-[#757575] text-xs mt-0.5"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase text-[#9CA3AF] font-semibold">Technician</p>
                    <p id="issueModalTechnician" class="font-semibold"></p>
                </div>
            </div>
            <p id="issueModalText" class="text-[#1E1E1E]"></p>
        </div>
    </div>

    {{-- SCORE BREAKDOWN MODAL --}}
    <div id="scoreModal" class="hidden fixed inset-0 bg-black/40 z-[9999] flex items-center justify-center p-4">
        <div class="bg-white rounded-lg w-full max-w-md p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-[#E7F3EE] rounded-lg px-3 py-1.5 text-center">
                        <p id="scoreModalAvg" class="text-[#115640] font-bold text-lg leading-none"></p>
                        <p class="text-[10px] text-[#417866]">/ 5.0</p>
                    </div>
                    <div>
                        <p class="font-semibold">Score Breakdown</p>
                        <p id="scoreModalCriteria" class="text-[#757575] text-xs"></p>
                    </div>
                </div>
                <button type="button" id="closeScoreModal" class="text-[#757575] cursor-pointer text-lg leading-none">&times;</button>
            </div>

            <div class="flex items-center justify-between bg-[#F7F7F7] rounded-lg p-3 mb-4">
                <div>
                    <p id="scoreModalCustomer" class="font-semibold"></p>
                    <p id="scoreModalMeta" class="text-[#757575] text-xs mt-0.5"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase text-[#9CA3AF] font-semibold">Technician</p>
                    <p id="scoreModalTechnician" class="font-semibold"></p>
                </div>
            </div>

            <ul id="scoreModalList" class="space-y-2.5 max-h-[45vh] overflow-y-auto"></ul>

            <div class="mt-4 pt-3 border-t border-[#EDEDED] flex items-center justify-between gap-3">
                <p class="text-[#757575] shrink-0"><span class="text-[#115640] font-semibold">Overall</span> Average</p>
                <div class="flex items-center gap-2 flex-1">
                    <div class="flex-1 h-1.5 bg-[#EDEDED] rounded-full overflow-hidden">
                        <div id="scoreModalOverallBar" class="h-full bg-[#115640] rounded-full"></div>
                    </div>
                    <span id="scoreModalOverallValue" class="font-bold text-[#115640] shrink-0"></span>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    (function () {
        const form = document.getElementById('surveyFilterForm');
        if (! form) return;

        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () { form.requestSubmit(); });
        });

        let searchTimer = null;
        form.querySelector('input[name="search"]').addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { form.requestSubmit(); }, 500);
        });

        // SERVICE DATE RANGE PICKER
        const openBtn = document.getElementById('openServiceDate');
        const dropdown = document.getElementById('serviceDateDropdown');
        const chevron = document.getElementById('serviceDateChevron');
        const fromInput = document.getElementById('fromDateInput');
        const toInput = document.getElementById('toDateInput');
        const label = document.getElementById('serviceDateLabel');

        const fp = flatpickr('#service-date-picker', {
            mode: 'range',
            inline: true,
            dateFormat: 'Y-m-d',
            defaultDate: (fromInput.value && toInput.value) ? [fromInput.value, toInput.value] : null,
        });

        function closeDropdown() {
            dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            chevron.classList.remove('rotate-180');
        }

        openBtn.addEventListener('click', function () {
            dropdown.classList.toggle('opacity-0');
            dropdown.classList.toggle('scale-95');
            dropdown.classList.toggle('pointer-events-none');
            chevron.classList.toggle('rotate-180');
        });

        document.addEventListener('click', function (e) {
            if (! document.getElementById('serviceDateFilter').contains(e.target)) closeDropdown();
        });

        document.getElementById('cancelServiceDate').addEventListener('click', closeDropdown);

        document.getElementById('applyServiceDate').addEventListener('click', function () {
            const dates = fp.selectedDates;
            if (dates.length !== 2) return closeDropdown();

            const startDate = fp.formatDate(dates[0], 'Y-m-d');
            const endDate = fp.formatDate(dates[1], 'Y-m-d');

            fromInput.value = startDate;
            toInput.value = endDate;
            label.textContent = `${startDate} → ${endDate}`;

            closeDropdown();
            form.requestSubmit();
        });

        // PENDING MACHINES MODAL
        const pendingModal = document.getElementById('pendingModal');
        const openPendingModal = document.getElementById('openPendingModal');
        const closePendingModal = document.getElementById('closePendingModal');

        if (pendingModal && openPendingModal) {
            openPendingModal.addEventListener('click', function () { pendingModal.classList.remove('hidden'); });
            closePendingModal.addEventListener('click', function () { pendingModal.classList.add('hidden'); });
            pendingModal.addEventListener('click', function (e) {
                if (e.target === pendingModal) pendingModal.classList.add('hidden');
            });
        }

        // ISSUE / FEEDBACK DETAIL MODAL
        const issueModal = document.getElementById('issueModal');

        document.querySelectorAll('.issue-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const data = JSON.parse(this.dataset.issue);
                document.getElementById('issueModalTitle').textContent = data.title;
                document.getElementById('issueModalCustomer').textContent = data.customer;
                document.getElementById('issueModalMeta').textContent = [data.wo, data.date].filter(Boolean).join(' · ');
                document.getElementById('issueModalTechnician').textContent = data.technician;
                document.getElementById('issueModalText').textContent = data.text;
                issueModal.classList.remove('hidden');
            });
        });

        document.getElementById('closeIssueModal').addEventListener('click', function () { issueModal.classList.add('hidden'); });
        issueModal.addEventListener('click', function (e) {
            if (e.target === issueModal) issueModal.classList.add('hidden');
        });

        // SCORE BREAKDOWN MODAL
        const scoreModal = document.getElementById('scoreModal');

        function scoreBarColor(score) {
            if (score === null) return '#D9D9D9';
            if (score >= 4.5) return '#115640';
            if (score >= 3.5) return '#3FA772';
            if (score >= 2.5) return '#E8B931';
            return '#EC221F';
        }

        document.querySelectorAll('.score-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const data = JSON.parse(this.dataset.score);

                document.getElementById('scoreModalAvg').textContent = data.avg !== null ? Number(data.avg).toFixed(1) : '-';
                document.getElementById('scoreModalCriteria').textContent = data.breakdown.length + ' criteria evaluated';
                document.getElementById('scoreModalCustomer').textContent = data.customer;
                document.getElementById('scoreModalMeta').textContent = [data.wo, data.date].filter(Boolean).join(' · ');
                document.getElementById('scoreModalTechnician').textContent = data.technician;

                document.getElementById('scoreModalList').innerHTML = data.breakdown.map(function (item, index) {
                    const num = String(index + 1).padStart(2, '0');
                    const width = item.score !== null ? (item.score / 5 * 100) : 0;

                    return `<li class="flex items-center gap-3">
                        <span class="text-[#B0B0B0] font-semibold w-5 shrink-0">${num}</span>
                        <span class="flex-1 text-[#1E1E1E] truncate">${item.label}</span>
                        <span class="w-24 h-1.5 bg-[#EDEDED] rounded-full overflow-hidden shrink-0">
                            <span class="block h-full rounded-full" style="width:${width}%; background-color:${scoreBarColor(item.score)}"></span>
                        </span>
                        <span class="w-7 text-center font-semibold text-[#115640] bg-[#E7F3EE] rounded-md py-0.5 shrink-0">${item.score ?? '-'}</span>
                    </li>`;
                }).join('');

                const overallWidth = data.avg !== null ? (data.avg / 5 * 100) : 0;
                document.getElementById('scoreModalOverallBar').style.width = overallWidth + '%';
                document.getElementById('scoreModalOverallValue').textContent = data.avg !== null ? Number(data.avg).toFixed(1) : '-';

                scoreModal.classList.remove('hidden');
            });
        });

        document.getElementById('closeScoreModal').addEventListener('click', function () { scoreModal.classList.add('hidden'); });
        scoreModal.addEventListener('click', function (e) {
            if (e.target === scoreModal) scoreModal.classList.add('hidden');
        });
    })();
</script>
@endsection
