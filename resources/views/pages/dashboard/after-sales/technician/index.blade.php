@php
    $periodFilter = \Illuminate\Support\Facades\Blade::render('<x-after-sales.ui.filter-bar :only="[\'period\']" id="tech-filters" />');
@endphp

@include('pages.templates.main-headers', [
    'title' => 'My Dashboard',
    'subtitle' => 'Your assigned work, service quality, and follow-up queue.',
    'action' => $periodFilter,
])

@include('pages.dashboard.after-sales._chart-helpers')

<div id="tech-dashboard" data-ticket-url="{{ route('aftersales.pages.tickets.show', ['ticket' => '__ID__']) }}" class="space-y-5 bg-[#F6F8F7] p-4 sm:p-6 rounded-xl mt-4">
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
        <p id="tech-tasks-total" class="text-xs text-[#5F6B65]" aria-live="polite"></p>
        <button id="tech-tasks-more" type="button" class="hidden mt-3 rounded-lg border px-3 py-2 text-xs">Load more</button>
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

    <div class="grid md:grid-cols-2 gap-4">
        <x-after-sales.ui.panel title="Documents to Complete" subtitle="Draft documents for your assigned tickets.">
            <p id="tech-documents-status" class="text-xs text-[#5F6B65]" role="status"></p>
            <div id="tech-documents" class="divide-y divide-[#EEF1EF]"></div>
            <button id="tech-documents-more" type="button" class="hidden mt-3 rounded-lg border px-3 py-2 text-xs">Load more</button>
        </x-after-sales.ui.panel>
        <x-after-sales.ui.panel title="Upcoming Recoming" subtitle="Your follow-up schedules in the next 7 days (Asia/Jakarta).">
            <p id="tech-recomings-status" class="text-xs text-[#5F6B65]" role="status"></p>
            <div id="tech-recomings" class="divide-y divide-[#EEF1EF]"></div>
            <button id="tech-recomings-more" type="button" class="hidden mt-3 rounded-lg border px-3 py-2 text-xs">Load more</button>
        </x-after-sales.ui.panel>
    </div>

    <div id="tech-survey-links-panel" class="hidden">
        <x-after-sales.ui.panel title="Share Survey Link" subtitle="Active survey links for your tickets that have not been shared.">
            <p id="tech-survey-links-status" class="text-xs text-[#5F6B65]" role="status"></p>
            <div id="tech-survey-links" class="divide-y divide-[#EEF1EF]"></div>
            <button id="tech-survey-links-more" type="button" class="hidden mt-3 rounded-lg border px-3 py-2 text-xs">Load more</button>
        </x-after-sales.ui.panel>
    </div>

    {{-- Closed per week --}}
    <x-after-sales.ui.panel title="Closed per week" subtitle="Weekly completion trend for your own service tickets.">
        <div id="tech-chart-weekly"></div>
        <p class="mt-1 text-[10px] text-[#9AA39E]">Own tickets only. No scores or customer names.</p>
    </x-after-sales.ui.panel>
</div>

<dialog id="tech-ticket-list" aria-labelledby="tech-ticket-list-title" class="w-full max-w-2xl rounded-xl p-0 backdrop:bg-black/40">
    <div class="flex items-center justify-between border-b p-4">
        <h2 id="tech-ticket-list-title" class="font-semibold">My tickets</h2>
        <button type="button" data-list-close class="rounded-lg border px-3 py-1 text-xs">Close</button>
    </div>
    <p data-list-total class="px-4 py-2 text-xs" role="status"></p>
    <div data-list-body class="max-h-[60vh] overflow-y-auto divide-y"></div>
    <div class="p-4"><button type="button" data-list-more class="hidden rounded-lg border px-3 py-2 text-xs">Load more</button></div>
</dialog>

<dialog id="tech-reschedule" aria-labelledby="tech-reschedule-title" class="w-full max-w-md rounded-xl p-5 backdrop:bg-black/40">
    <form id="tech-reschedule-form" class="space-y-4">
        <h2 id="tech-reschedule-title" class="font-semibold">Reschedule Recoming</h2>
        <p id="tech-reschedule-ticket" class="text-sm"></p>
        <label class="block text-xs">New schedule (Asia/Jakarta)
            <input id="tech-reschedule-at" type="datetime-local" required class="mt-1 block w-full rounded-lg border p-2">
        </label>
        <label class="block text-xs">Note (optional)
            <textarea id="tech-reschedule-note" maxlength="1000" class="mt-1 block w-full rounded-lg border p-2"></textarea>
        </label>
        <p id="tech-reschedule-error" class="text-xs text-red-700" role="alert"></p>
        <div class="flex justify-end gap-2">
            <button id="tech-reschedule-cancel" type="button" class="rounded-lg border px-3 py-2 text-xs">Cancel</button>
            <button id="tech-reschedule-save" type="submit" class="rounded-lg bg-[#14573F] px-3 py-2 text-xs text-white">Reschedule</button>
        </div>
    </form>
</dialog>

<dialog id="tech-survey-share" aria-labelledby="tech-survey-share-title" class="w-full max-w-lg rounded-xl p-5 backdrop:bg-black/40">
    <h2 id="tech-survey-share-title" class="font-semibold">Share Survey Link</h2>
    <p id="tech-survey-share-ticket" class="mt-2 text-sm"></p>
    <label for="tech-survey-share-url" class="mt-4 block text-xs">Survey link</label>
    <input id="tech-survey-share-url" type="url" readonly class="mt-1 w-full rounded-lg border p-2 text-sm">
    <p id="tech-survey-share-status" class="mt-3 text-xs" role="status"></p>
    <div class="mt-4 flex flex-wrap justify-end gap-2">
        <button id="tech-survey-share-close" type="button" class="rounded-lg border px-3 py-2 text-xs">Close</button>
        <button id="tech-survey-share-manual" type="button" class="hidden rounded-lg border px-3 py-2 text-xs">I have shared this link</button>
        <button id="tech-survey-share-copy" type="button" class="rounded-lg bg-[#14573F] px-3 py-2 text-xs text-white">Copy link &amp; mark shared</button>
    </div>
</dialog>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('tech-dashboard');
    if (!root || !window.AS) return;
    const { colors: C, baseChart, mount } = window.AS;

    const API = '/api/dashboard/aftersales/technician';
    const TICKET_URL = root.dataset.ticketUrl;
    const techData = {
        rating: { score: null, max: 3 },
        tickets: [], totalActive: 0, canShareSurvey: false,
        sla: { value: null, target: null, months: [], series: [] },
        tasks: [], taskTotal: 0, taskPage: 1, taskHasMore: false,
        weeks: [], closed: 0, closedTotals: [], onTime: [], overtime: [], todo: [],
    };

    function mapTask(task) {
        return { ...task, code: task.ticket_code,
            meta: [task.customer_name, task.machine_name, task.machine_category,
                task.type === 'phone_call' ? 'Phone call' : null].filter(Boolean).join(' · '),
            tone: task.status, status: ({ done: 'Done', active: 'Active', next: 'Next', waiting: 'Waiting' })[task.status] || task.status };
    }

    const TONE = {
        done:    { dot: 'text-[#14573F]', pill: 'bg-[#E3F1EA] text-[#14573F]' },
        active:  { dot: 'text-[#14573F]', pill: 'bg-[#E3F1EA] text-[#14573F]' },
        next:    { dot: 'text-[#D9B65B]', pill: 'bg-[#FBF3D9] text-[#8A6B12]' },
        waiting: { dot: 'text-[#A9B8B0]', pill: 'bg-[#EEF1EF] text-[#5F6B65]' },
    };

    function donut(id, series, labels, colorList, centerValue, centerLabel, onSelect) {
        mount(id, {
            chart: { ...baseChart, type: 'donut', height: 150, sparkline: { enabled: true },
                events: { dataPointSelection: (_event, _chart, config) => onSelect?.(config.dataPointIndex) } },
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

    let controller, generation = 0;
    async function getJson(url, signal) {
        const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal });
        if (!res.ok) throw Object.assign(new Error(`Dashboard request failed: ${res.status}`), { status: res.status });
        return res.json();
    }

    async function loadDashboard(period) {
        controller?.abort(); // abaikan response lama bila Period berubah cepat
        controller = new AbortController();
        const signal = controller.signal;
        const { data } = await getJson(`${API}?${new URLSearchParams({ period })}`, signal);
        if (signal.aborted) return;
        techData.rating = data.rating;
        techData.canShareSurvey = data.capabilities?.can_share_survey === true;
        techData.tickets = data.tickets.statuses;
        techData.totalActive = data.tickets.total_active;
        techData.sla = { ...data.sla, months: data.sla.months.map(m => m.label), series: data.sla.months.map(m => m.value) };
        techData.tasks = data.today.items.map(mapTask);
        techData.taskTotal = data.today.total;
        techData.taskHasMore = data.today.has_more;
        techData.taskPage = 1;
        techData.closed = data.closed.total;
        techData.weeks = data.closed.weeks.map(w => w.label);
        techData.closedTotals = data.closed.weeks.map(w => w.total);
        techData.onTime = data.closed.weeks.map(w => w.on_time);
        techData.overtime = data.closed.weeks.map(w => w.overtime);
        techData.todo = data.todo.items.map((item, i) => [item.label, item.count, [C.green, C.sage, C.sageLight][i]]);
    }

    function renderTickets() {
        const entries = techData.tickets;
        const total = techData.totalActive;
        donut('tech-chart-tickets', total ? entries.map(e => e.count) : [1], total ? entries.map(e => e.label) : ['No tickets'],
            total ? [C.sageLight, C.green, C.sage, C.gold] : [C.track], String(total), 'active',
            i => { if (total && entries[i]) openTickets(entries[i]); });
        root.querySelectorAll('#tech-tickets-legend [data-legend]').forEach((el, i) => {
            const entry = entries[i];
            el.querySelector('[data-legend-count]').textContent = entry?.count ?? 0;
            el.querySelector('[data-legend-percent]').textContent = total ? Math.round(((entry?.count ?? 0) / total) * 100) + '%' : '0%';
            el.setAttribute('role', 'button');
            el.setAttribute('tabindex', '0');
            el.classList.add('cursor-pointer');
            el.onclick = () => { if (entry) openTickets(entry); };
            el.onkeydown = event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); el.onclick(); } };
        });
    }

    function renderSla() {
        const s = techData.sla;
        document.getElementById('tech-sla-value').textContent = s.value === null ? '—' : s.value + '%';
        document.getElementById('tech-sla-target').textContent = s.target === null ? 'Target —' : 'Target ' + s.target + '%';
        mount('tech-chart-sla', {
            chart: { ...baseChart, type: 'area', height: 130 },
            series: [{ name: 'On time', data: s.series }], colors: [C.green],
            stroke: { width: 2, curve: 'straight' },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.22, opacityTo: 0.02, stops: [0, 100] } },
            markers: { size: 3, colors: ['#fff'], strokeColors: C.green, strokeWidth: 2 },
            dataLabels: { enabled: false },
            xaxis: { categories: s.months, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '10px', colors: '#9AA39E' } } },
            yaxis: { show: false, min: 0, max: 100 },
            grid: { show: false, padding: { left: 6, right: 6 } },
            annotations: { yaxis: [{ y: s.target, borderColor: '#B9C4BE', strokeDashArray: 4 }] },
            tooltip: { y: { formatter: v => v + '%' } },
        });
    }

    function renderTasks() {
        const tpl = document.getElementById('tech-task-template');
        const list = document.getElementById('tech-tasks');
        list.replaceChildren(...techData.tasks.map(t => {
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
        if (!techData.tasks.length) list.append(textNode('No assigned tasks today.', 'p'));
        document.getElementById('tech-tasks-total').textContent = `${techData.tasks.length} of ${techData.taskTotal} tasks today (Asia/Jakarta)`;
        document.getElementById('tech-tasks-more').classList.toggle('hidden', !techData.taskHasMore);
    }

    function renderClosed() {
        const totals = techData.closedTotals;
        document.getElementById('tech-closed-total').textContent = techData.closed;
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

    function textNode(text, tag = 'span') {
        const node = document.createElement(tag);
        node.textContent = text ?? '—';
        node.className = 'block px-4 py-3 text-xs';
        return node;
    }

    const taskMore = document.getElementById('tech-tasks-more');
    taskMore.addEventListener('click', async () => {
        const version = generation;
        taskMore.disabled = true;
        try {
            const page = await getJson(`${API}/tasks?${new URLSearchParams({ page: techData.taskPage + 1, per_page: 10 })}`);
            if (version !== generation) return;
            techData.tasks.push(...page.data.map(mapTask));
            techData.taskPage = page.current_page;
            techData.taskTotal = page.total;
            techData.taskHasMore = page.current_page < page.last_page;
            renderTasks();
        } catch (e) {
            if (version === generation) showError(e);
        } finally {
            if (version === generation) taskMore.disabled = false;
        }
    });

    const ticketList = document.getElementById('tech-ticket-list');
    const listBody = ticketList.querySelector('[data-list-body]');
    const listTotal = ticketList.querySelector('[data-list-total]');
    const listMore = ticketList.querySelector('[data-list-more]');
    let listController, selectedStatus, listPage = 0;

    async function loadTickets(page) {
        listController?.abort();
        listController = new AbortController();
        const signal = listController.signal;
        listMore.disabled = true;
        try {
            const result = await getJson(`${API}/tickets?${new URLSearchParams({ metric: 'status', status: selectedStatus, page, per_page: 20 })}`, signal);
            if (signal.aborted) return;
            if (page === 1) listBody.replaceChildren();
            result.data.forEach(ticket => {
                const link = document.createElement('a');
                link.className = 'block hover:bg-[#F6F8F7]';
                link.href = TICKET_URL.replace('__ID__', encodeURIComponent(ticket.id));
                link.append(textNode(ticket.ticket_code), textNode([ticket.customer_name, ticket.machine_name].filter(Boolean).join(' · ')));
                listBody.append(link);
            });
            if (!result.total) listBody.append(textNode('No tickets with this status.', 'p'));
            listPage = result.current_page;
            listTotal.textContent = `${result.total} tickets`;
            listMore.textContent = 'Load more';
            listMore.classList.toggle('hidden', result.current_page >= result.last_page);
        } catch (e) {
            if (signal.aborted) return;
            listTotal.textContent = errorText(e);
            listMore.textContent = 'Retry';
            listMore.classList.remove('hidden');
        } finally {
            if (!signal.aborted) listMore.disabled = false;
        }
    }

    function openTickets(status) {
        selectedStatus = status.key;
        listPage = 0;
        document.getElementById('tech-ticket-list-title').textContent = `My tickets: ${status.label}`;
        listBody.replaceChildren();
        listTotal.textContent = 'Loading…';
        listMore.classList.add('hidden');
        if (!ticketList.open) ticketList.showModal();
        return loadTickets(1);
    }
    ticketList.querySelector('[data-list-close]').addEventListener('click', () => ticketList.close());
    ticketList.addEventListener('close', () => listController?.abort());
    listMore.addEventListener('click', () => loadTickets(listPage + 1));

    const queues = Object.fromEntries(['documents', 'recomings', 'survey-links'].map(key => [key, { page: 0, controller: null }]));

    async function loadQueue(key, page = 1) {
        const state = queues[key];
        state.controller?.abort();
        state.controller = new AbortController();
        const signal = state.controller.signal;
        const body = document.getElementById(`tech-${key}`);
        const status = document.getElementById(`tech-${key}-status`);
        const more = document.getElementById(`tech-${key}-more`);
        if (key === 'survey-links') {
            document.getElementById('tech-survey-links-panel').classList.toggle('hidden', !techData.canShareSurvey);
            if (!techData.canShareSurvey) { body.replaceChildren(); return; }
        }
        more.disabled = true;
        if (page === 1) { state.page = 0; body.replaceChildren(); more.classList.add('hidden'); }
        status.textContent = 'Loading…';
        try {
            const result = await getJson(`${API}/${key}?${new URLSearchParams({ page, per_page: 10 })}`, signal);
            if (signal.aborted) return;
            result.data.forEach(row => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between gap-3 py-3';
                const details = document.createElement('div');
                details.append(textNode(row.ticket_code), textNode([row.customer_name, row.machine_name].filter(Boolean).join(' · ')));
                const action = document.createElement(key === 'documents' ? 'a' : 'button');
                action.className = 'shrink-0 rounded-lg border border-[#14573F] px-3 py-2 text-xs text-[#14573F]';
                if (key === 'documents') {
                    details.append(textNode(`${row.document_type === 'agreement' ? 'Service Agreement' : 'Service Form'} · Draft`));
                    action.href = `${TICKET_URL.replace('__ID__', encodeURIComponent(row.id))}?${new URLSearchParams({ document: row.document_type })}`;
                    action.textContent = 'Open document';
                } else if (key === 'survey-links') {
                    action.type = 'button';
                    action.textContent = 'Share';
                    action.onclick = () => openSurveyShare(row);
                } else {
                    details.append(textNode(`${row.type === 'phone_call' ? 'Phone call' : 'Site visit'} · ${new Date(row.scheduled_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' })}`));
                    action.type = 'button';
                    action.textContent = row.can_reschedule ? 'Reschedule' : 'Scheduled today';
                    action.disabled = !row.can_reschedule;
                    action.onclick = () => openReschedule(row);
                }
                item.append(details, action);
                body.append(item);
            });
            state.page = result.current_page;
            const labels = { documents: ['draft documents', 'No draft documents to complete.'],
                recomings: ['upcoming schedules', 'No Recoming scheduled in the next 7 days.'],
                'survey-links': ['survey links to share', 'No survey links waiting to be shared.'] };
            status.textContent = result.total ? `${result.total} ${labels[key][0]}` : labels[key][1];
            more.textContent = 'Load more';
            more.classList.toggle('hidden', result.current_page >= result.last_page);
        } catch (e) {
            if (signal.aborted) return;
            status.textContent = errorText(e);
            more.textContent = 'Retry';
            more.classList.remove('hidden');
        } finally {
            if (!signal.aborted) more.disabled = false;
        }
    }
    Object.keys(queues).forEach(key => document.getElementById(`tech-${key}-more`)
        .addEventListener('click', () => loadQueue(key, queues[key].page + 1)));

    const shareDialog = document.getElementById('tech-survey-share');
    const shareCopy = document.getElementById('tech-survey-share-copy');
    const shareManual = document.getElementById('tech-survey-share-manual');
    const shareClose = document.getElementById('tech-survey-share-close');
    const shareUrl = document.getElementById('tech-survey-share-url');
    const shareStatus = document.getElementById('tech-survey-share-status');
    let shareController, shareRow, shareLink, shareBusy = false;

    async function openSurveyShare(row) {
        shareController?.abort();
        shareController = new AbortController();
        const signal = shareController.signal;
        shareRow = row;
        shareLink = null;
        shareUrl.value = '';
        shareCopy.disabled = true;
        shareManual.classList.add('hidden');
        document.getElementById('tech-survey-share-ticket').textContent = row.ticket_code;
        shareStatus.textContent = 'Loading survey link…';
        shareDialog.showModal();
        try {
            const result = await getJson(`/api/aftersales/tickets/${encodeURIComponent(row.id)}/survey-link`, signal);
            if (signal.aborted) return;
            if (!result.data.can_share || !result.data.url) throw new Error('This survey link is no longer active. Refresh the dashboard.');
            shareLink = result.data;
            shareUrl.value = shareLink.url;
            shareCopy.disabled = false;
            shareStatus.textContent = 'Copy this link to share it with the customer.';
        } catch (e) {
            if (!signal.aborted) shareStatus.textContent = e.status ? errorText(e) : e.message;
        }
    }
    shareClose.addEventListener('click', () => shareDialog.close());
    shareDialog.addEventListener('close', () => shareController?.abort());
    shareDialog.addEventListener('cancel', event => { if (shareBusy) event.preventDefault(); });

    async function shareSurvey(manual = false) {
        if (shareBusy || !shareLink) return;
        shareBusy = true;
        shareCopy.disabled = shareManual.disabled = shareClose.disabled = true;
        let recorded = false;
        try {
            if (!manual) {
                try {
                    if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
                    await navigator.clipboard.writeText(shareLink.url);
                } catch (_) {
                    shareUrl.select();
                    shareManual.classList.remove('hidden');
                    shareStatus.textContent = 'Copy and share the link manually, then click “I have shared this link”. Shared status has not been recorded.';
                    return;
                }
            }
            const res = await fetch(`/api/aftersales/tickets/${encodeURIComponent(shareRow.id)}/survey-link/share`, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ via: 'copy', link_id: shareLink.link_id }),
            });
            const result = await res.json();
            if (!res.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Failed to record survey sharing.');
            recorded = true;
            shareLink = null;
            shareManual.classList.add('hidden');
            shareStatus.textContent = manual ? 'Survey link marked as shared.' : 'Survey link copied. Shared status recorded.';
            await init();
        } catch (e) {
            shareStatus.textContent = e.message || 'Failed to record survey sharing. Please retry.';
        } finally {
            shareBusy = false;
            shareCopy.disabled = recorded;
            shareManual.disabled = false;
            shareClose.disabled = false;
        }
    }
    shareCopy.addEventListener('click', () => shareSurvey());
    shareManual.addEventListener('click', () => shareSurvey(true));

    const rescheduleDialog = document.getElementById('tech-reschedule');
    const rescheduleSave = document.getElementById('tech-reschedule-save');
    let rescheduleRow;
    function openReschedule(row) {
        rescheduleRow = row;
        document.getElementById('tech-reschedule-ticket').textContent = row.ticket_code;
        document.getElementById('tech-reschedule-at').value = row.scheduled_at.slice(0, 16);
        document.getElementById('tech-reschedule-note').value = '';
        document.getElementById('tech-reschedule-error').textContent = '';
        rescheduleDialog.showModal();
    }
    document.getElementById('tech-reschedule-cancel').addEventListener('click', () => rescheduleDialog.close());
    document.getElementById('tech-reschedule-form').addEventListener('submit', async event => {
        event.preventDefault();
        if (rescheduleSave.disabled) return;
        rescheduleSave.disabled = true;
        const row = rescheduleRow;
        try {
            const field = row.type === 'phone_call' ? 'planned_call_at' : 'planned_visit_at';
            const res = await fetch(`/api/aftersales/tickets/${encodeURIComponent(row.id)}/recoming/reschedule`, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ [field]: document.getElementById('tech-reschedule-at').value,
                    note: document.getElementById('tech-reschedule-note').value.trim() || null }),
            });
            const result = await res.json();
            if (!res.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Failed to reschedule.');
            rescheduleDialog.close();
            await init();
        } catch (e) {
            document.getElementById('tech-reschedule-error').textContent = e.message || 'Failed to reschedule.';
        } finally { rescheduleSave.disabled = false; }
    });

    const errorBox = document.getElementById('tech-error');
    function errorText(e) {
        if (e.status === 401) return 'Session expired. Please log in again.';
        if (e.status === 403) return 'Access denied for your role.';
        if (e.status === 422) return 'Invalid request.';
        return 'Failed to load dashboard. Check your connection and retry.';
    }
    function showError(e) {
        errorBox.querySelector('[data-error-text]').textContent = errorText(e);
        errorBox.classList.remove('hidden'); errorBox.classList.add('flex');
    }
    errorBox.querySelector('[data-error-retry]').addEventListener('click', () => init());

    async function init() {
        const version = ++generation;
        root.setAttribute('aria-busy', 'true');
        taskMore.disabled = true;
        errorBox.classList.add('hidden'); errorBox.classList.remove('flex');
        const period = root.ownerDocument.querySelector('#tech-filters [data-filter="period"]')?.value || 'this_month';
        try {
            await loadDashboard(period);
            if (version !== generation) return;
        } catch (e) {
            if (e.name === 'AbortError' || version !== generation) return;
            showError(e);
            return;
        } finally {
            if (version === generation) {
                root.setAttribute('aria-busy', 'false');
                taskMore.disabled = false;
            }
        }
        renderRating(); renderTickets(); renderSla(); renderTasks();
        renderClosed(); renderTodo(); renderWeekly();
        await Promise.all(Object.keys(queues).map(key => loadQueue(key)));
    }
    // Filter Period: panggil ulang endpoint lalu render ulang.
    root.ownerDocument.querySelectorAll('#tech-filters [data-filter]').forEach(sel => sel.addEventListener('change', init));
    init();
})();
</script>
@endpush
