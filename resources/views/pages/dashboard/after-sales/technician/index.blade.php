@php
    $periodFilter = \Illuminate\Support\Facades\Blade::render('<x-after-sales.ui.filter-bar :only="[\'period\']" id="tech-filters" />');
@endphp

@include('pages.templates.main-headers', [
    'title' => 'My Dashboard',
    'subtitle' => 'Your assigned work, service quality, and follow-up queue.',
    'action' => $periodFilter,
])

@include('pages.dashboard.after-sales._chart-helpers')

<div id="tech-dashboard" class="space-y-5 bg-[#F6F8F7] p-4 sm:p-6 rounded-xl mt-4">
    <div id="tech-error" class="hidden items-center justify-between gap-3 rounded-xl border border-[#F0C9C9] bg-[#FDF3F3] px-4 py-3 text-sm text-[#8A2B2B]" role="alert">
        <span data-error-text>Failed to load dashboard.</span>
        <button type="button" data-error-retry class="shrink-0 rounded-lg border border-[#8A2B2B] px-3 py-1 text-xs font-medium">Retry</button>
    </div>
    {{-- Rating + My tickets + SLA --}}
    <div class="grid md:grid-cols-3 gap-4">
        <x-after-sales.ui.panel title="Overall rating" subtitle="Approved customer survey average.">
            <div class="flex items-center gap-4">
                <div id="tech-chart-rating" class="w-[150px] shrink-0"></div>
                <div class="min-w-0 space-y-3">
                    <x-after-sales.ui.stat-side label="Survey result" id="tech-rating-side" />
                </div>
            </div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="My tickets" subtitle="Current workload grouped by progress.">
            <div class="flex items-center gap-3">
                <div id="tech-chart-tickets" class="w-[150px] shrink-0"></div>
                <div class="flex-1 min-w-0 divide-y divide-[#EEF1EF]" id="tech-tickets-legend">
                    <x-after-sales.ui.legend-row color="#9CC4B2" label="Assigned" />
                    <x-after-sales.ui.legend-row color="#14573F" label="On Site" />
                    <x-after-sales.ui.legend-row color="#7FAF9A" label="Repair" />
                    <x-after-sales.ui.legend-row color="#D9B65B" label="Waiting" />
                </div>
            </div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="SLA on time" subtitle="Monthly completion rate.">
            <div class="flex items-start justify-between">
                <p class="text-3xl font-extrabold text-[#14573F] tabular-nums" id="tech-sla-value">-</p>
                <p class="text-[11px] text-[#9AA39E] mt-2" id="tech-sla-target">Target -</p>
            </div>
            <div id="tech-chart-sla"></div>
        </x-after-sales.ui.panel>
    </div>

    {{-- Today task --}}
    <x-after-sales.ui.panel title="Today Task" subtitle="Your assigned visits and service tasks for today.">
        <div id="tech-tasks" class="divide-y divide-[#EEF1EF]"></div>
        <template id="tech-task-template"><x-after-sales.ui.task-row /></template>
    </x-after-sales.ui.panel>

    {{-- Closed tickets + To-do --}}
    <div class="grid md:grid-cols-2 gap-4">
        <x-after-sales.ui.panel title="Closed tickets" subtitle="Completed in this period.">
            <x-slot:actions>
                <span class="text-3xl font-extrabold text-[#14573F] tabular-nums" id="tech-closed-total">-</span>
            </x-slot:actions>
            <div id="tech-chart-closed"></div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="To-do" subtitle="Open workload by action.">
            <x-slot:actions>
                <span class="text-3xl font-extrabold text-[#14573F] tabular-nums" id="tech-todo-total">-</span>
            </x-slot:actions>
            <div id="tech-todo" class="divide-y divide-[#F1F4F2] pt-1"></div>
            <div class="grid grid-cols-[88px_minmax(0,1fr)_24px] gap-3 mt-1">
                <span></span>
                <div id="tech-todo-axis" class="relative h-4 text-[10px] text-[#9AA39E]"></div>
                <span></span>
            </div>
            <template id="tech-todo-template"><x-after-sales.ui.bar-row /></template>
        </x-after-sales.ui.panel>
    </div>

    {{-- Closed per week --}}
    <x-after-sales.ui.panel title="Closed per week" subtitle="Weekly completion trend for your own service tickets.">
        <div id="tech-chart-weekly"></div>
        <p class="mt-1 text-[10px] text-[#9AA39E]">Own tickets only. No scores or customer names.</p>
    </x-after-sales.ui.panel>
</div>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('tech-dashboard');
    if (!root || !window.AS) return;
    const { colors: C, baseChart, mount } = window.AS;

    // Rating sudah dari API (GET /api/dashboard/aftersales/technician). Panel lain masih dummy.
    // [API] Sambungkan tickets/sla/tasks/closed/todo ke respons yang sama (lihat docs/aftersales-dashboards/integrate-contract.md).
    const techData = {
        rating: { score: null, max: 3 },
        tickets: { Assigned: 3, 'On Site': 4, Repair: 3, Waiting: 2 },
        sla: { value: 92, target: 96, months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], series: [86, 88, 87, 91, 90, 92] },
        tasks: [
            { time: '08:30', code: 'DAX-AF-20261016-4', meta: 'Global Dynamic · RD 1445 · Cold Room', tone: 'done', status: 'Done' },
            { time: '10:15', code: 'DAX-AF-20261016-3', meta: 'PT Sejuk Sentosa · CD 10 · Cube Ice', tone: 'active', status: 'Active' },
            { time: '13:00', code: 'DAX-AF-20261015-2', meta: 'CV Tirta Abadi · FM 40 · Flake Ice', tone: 'next', status: 'Next' },
            { time: '15:30', code: 'DAX-AF-20261015-1', meta: 'PT Sumber Es · TD 150 · Tube Ice', tone: 'waiting', status: 'Waiting' },
            { time: '17:00', code: 'DAX-AF-20261014-3', meta: 'PT Dingin Jaya · CR 20 · Cold Room', tone: 'next', status: 'Next' },
        ],
        weeks: ['W1', 'W2', 'W3', 'W4'],
        onTime: [6, 8, 6, 9],
        overtime: [1, 1, 2, 1],
        todo: [['Share survey', 5, C.green], ['Documents', 3, C.sage], ['Recoming', 2, C.sageLight]],
    };

    const TONE = {
        done:    { dot: 'text-[#14573F]', pill: 'bg-[#E3F1EA] text-[#14573F]' },
        active:  { dot: 'text-[#14573F]', pill: 'bg-[#E3F1EA] text-[#14573F]' },
        next:    { dot: 'text-[#D9B65B]', pill: 'bg-[#FBF3D9] text-[#8A6B12]' },
        waiting: { dot: 'text-[#A9B8B0]', pill: 'bg-[#EEF1EF] text-[#5F6B65]' },
    };

    function donut(id, series, labels, colorList, centerValue, centerLabel) {
        mount(id, {
            chart: { ...baseChart, type: 'donut', height: 150, sparkline: { enabled: true } },
            series, labels, colors: colorList, dataLabels: { enabled: false }, legend: { show: false },
            stroke: { width: 2, colors: ['#fff'] }, tooltip: { enabled: false },
            plotOptions: { pie: { donut: { size: '70%', labels: {
                show: true,
                name: { show: true, fontSize: '11px', color: '#8A948F', offsetY: 28 },
                value: { show: true, fontSize: '24px', fontWeight: 700, color: C.green, offsetY: -12, formatter: () => centerValue },
                total: { show: true, label: centerLabel, color: '#8A948F', formatter: () => centerValue },
            } } } },
        });
    }

    function renderRating() {
        const { score, max } = techData.rating;
        const has = score !== null && score !== undefined;
        donut('tech-chart-rating', has ? [score, max - score] : [0, 1], ['Score', ''], [C.green, C.track],
            has ? Number(score).toFixed(1) : '—', '/ ' + max);
        const side = document.getElementById('tech-rating-side');
        side.querySelector('[data-side-value]').textContent = has ? `${Number(score).toFixed(1)} / ${max}` : '—';
        side.querySelector('[data-side-note]').textContent = 'Average of approved surveys';
    }

    let controller;
    async function loadDashboard(period) {
        controller?.abort(); // abaikan response lama bila Period berubah cepat
        controller = new AbortController();
        const res = await fetch(`/api/dashboard/aftersales/technician?${new URLSearchParams({ period })}`, {
            credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal,
        });
        if (!res.ok) throw Object.assign(new Error(`Dashboard request failed: ${res.status}`), { status: res.status });
        const { data } = await res.json();
        techData.rating = data.rating; // { score: number|null, max: 3 }
    }

    function renderTickets() {
        const entries = Object.entries(techData.tickets);
        const total = entries.reduce((s, [, v]) => s + v, 0);
        donut('tech-chart-tickets', entries.map(e => e[1]), entries.map(e => e[0]),
            [C.sageLight, C.green, C.sage, C.gold], String(total), 'active');
        root.querySelectorAll('#tech-tickets-legend [data-legend]').forEach((el, i) => {
            el.querySelector('[data-legend-count]').textContent = entries[i][1];
            el.querySelector('[data-legend-percent]').textContent = total ? Math.round((entries[i][1] / total) * 100) + '%' : '0%';
        });
    }

    function renderSla() {
        const s = techData.sla;
        document.getElementById('tech-sla-value').textContent = s.value + '%';
        document.getElementById('tech-sla-target').textContent = 'Target ' + s.target + '%';
        mount('tech-chart-sla', {
            chart: { ...baseChart, type: 'area', height: 130 },
            series: [{ name: 'On time', data: s.series }], colors: [C.green],
            stroke: { width: 2, curve: 'straight' },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.22, opacityTo: 0.02, stops: [0, 100] } },
            markers: { size: 3, colors: ['#fff'], strokeColors: C.green, strokeWidth: 2 },
            dataLabels: { enabled: false },
            xaxis: { categories: s.months, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '10px', colors: '#9AA39E' } } },
            yaxis: { show: false, min: 70, max: 100 },
            grid: { show: false, padding: { left: 6, right: 6 } },
            annotations: { yaxis: [{ y: s.target, borderColor: '#B9C4BE', strokeDashArray: 4 }] },
            tooltip: { y: { formatter: v => v + '%' } },
        });
    }

    function renderTasks() {
        const tpl = document.getElementById('tech-task-template');
        document.getElementById('tech-tasks').replaceChildren(...techData.tasks.map(t => {
            const node = tpl.content.firstElementChild.cloneNode(true);
            node.querySelector('[data-time]').textContent = t.time;
            node.querySelector('[data-dot]').className = 'size-4 rounded-full border-2 border-current grid place-items-center ' + TONE[t.tone].dot;
            node.querySelector('[data-code]').textContent = t.code;
            node.querySelector('[data-meta]').textContent = t.meta;
            const pill = node.querySelector('[data-pill]');
            pill.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold ' + TONE[t.tone].pill;
            pill.textContent = t.status;
            return node;
        }));
    }

    function renderClosed() {
        const totals = techData.weeks.map((_, i) => techData.onTime[i] + techData.overtime[i]);
        document.getElementById('tech-closed-total').textContent = totals.reduce((a, b) => a + b, 0);
        mount('tech-chart-closed', {
            chart: { ...baseChart, type: 'bar', height: 170 },
            series: [{ name: 'Closed', data: totals }],
            plotOptions: { bar: { distributed: true, columnWidth: '45%', borderRadius: 2 } },
            colors: totals.map((_, i) => (i === totals.length - 1 ? C.green : C.sageLight)),
            legend: { show: false }, dataLabels: { enabled: false },
            xaxis: { categories: techData.weeks, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '10px', colors: '#9AA39E' } } },
            yaxis: { show: false, min: 0 }, grid: { show: false },
        });
    }

    function renderTodo() {
        const list = techData.todo;
        const max = Math.max(...list.map(r => r[1]), 1);
        document.getElementById('tech-todo-total').textContent = list.reduce((s, r) => s + r[1], 0);
        const tpl = document.getElementById('tech-todo-template');
        document.getElementById('tech-todo').replaceChildren(...list.map(([label, value, color]) => {
            const node = tpl.content.firstElementChild.cloneNode(true);
            node.querySelector('[data-label]').textContent = label;
            node.querySelector('[data-value]').textContent = value;
            const bar = node.querySelector('[data-bar]');
            bar.style.width = (value / max) * 100 + '%';
            bar.style.background = color;
            return node;
        }));
        const ticks = [0, Math.round(max * 0.4), Math.round(max * 0.8), max];
        document.getElementById('tech-todo-axis').innerHTML = [...new Set(ticks)]
            .map(n => `<span class="absolute -translate-x-1/2" style="left:${(n / max) * 100}%">${n}</span>`).join('');
    }

    function renderWeekly() {
        mount('tech-chart-weekly', {
            chart: { ...baseChart, type: 'bar', height: 260 },
            series: [{ name: 'On time', data: techData.onTime }, { name: 'Overtime', data: techData.overtime }],
            colors: [C.green, C.grey],
            plotOptions: { bar: { columnWidth: '22%', borderRadius: 2 } },
            dataLabels: { enabled: false }, stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: { categories: techData.weeks, axisTicks: { show: false }, labels: { style: { fontSize: '10px', colors: '#9AA39E' } } },
            yaxis: { show: false, min: 0 }, grid: { show: false },
            legend: { position: 'bottom', horizontalAlign: 'right', fontSize: '10px', markers: { size: 4, shape: 'square' } },
        });
    }

    const errorBox = document.getElementById('tech-error');
    function errorText(e) {
        if (e.status === 401) return 'Session expired. Please log in again.';
        if (e.status === 403) return 'Access denied for your role.';
        if (e.status === 422) return 'Invalid request.';
        return 'Failed to load dashboard. Check your connection and retry.';
    }
    errorBox.querySelector('[data-error-retry]').addEventListener('click', () => init());

    async function init() {
        errorBox.classList.add('hidden'); errorBox.classList.remove('flex');
        const period = root.ownerDocument.querySelector('#tech-filters [data-filter="period"]')?.value || 'this_month';
        try {
            await loadDashboard(period);
        } catch (e) {
            if (e.name === 'AbortError') return;
            console.error(e); // jangan render dummy sebagai data live
            errorBox.querySelector('[data-error-text]').textContent = errorText(e);
            errorBox.classList.remove('hidden'); errorBox.classList.add('flex');
            return;
        }
        renderRating(); renderTickets(); renderSla(); renderTasks();
        renderClosed(); renderTodo(); renderWeekly();
    }
    // Filter Period: panggil ulang endpoint lalu render ulang.
    root.ownerDocument.querySelectorAll('#tech-filters [data-filter]').forEach(sel => sel.addEventListener('change', init));
    init();
})();
</script>
@endpush
