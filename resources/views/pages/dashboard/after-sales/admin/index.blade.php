@include('pages.templates.main-headers', [
    'title' => 'Admin Dashboard',
    'subtitle' => 'Operational queues, visits, follow-up, and data quality.',
    'action' => '<a href="#" class="px-3 py-1.5 rounded-lg border border-[#D9D9D9] bg-white text-xs font-medium text-[#1E1E1E]">Supervisor view</a>',
])

<div id="admin-dashboard" class="space-y-5 bg-[#F6F8F7] p-4 sm:p-6 rounded-xl mt-4">

    {{-- Progress + SLA --}}
    <div class="grid md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-4">
        <x-after-sales.ui.panel title="Open tickets by progress" subtitle="Tap a bar to open. All Ticket.">
            <div id="admin-progress-counts" class="grid gap-2 text-center text-sm font-semibold text-[#2B3441] tabular-nums"></div>
            <div id="admin-chart-progress"></div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="SLA of open tickets" subtitle="Current SLA exposure across every open service ticket.">
            <div id="admin-chart-sla"></div>
            <div class="mt-2 divide-y divide-[#EEF1EF]" id="admin-sla-legend">
                <x-after-sales.ui.legend-row color="#14573F" label="On track" />
                <x-after-sales.ui.legend-row color="#D9B65B" label="At risk <24h" />
                <x-after-sales.ui.legend-row color="#A9B8B0" label="Overdue" />
            </div>
        </x-after-sales.ui.panel>
    </div>

    {{-- Visits --}}
    <x-after-sales.ui.panel title="Visits, next 3 days" subtitle="Scheduled visits and recomings from today through the next three days.">
        <div id="admin-chart-visits"></div>
        <div class="grid grid-cols-2 md:grid-cols-4 mt-4 border border-[#E3E8E5] rounded-xl overflow-hidden" id="admin-days">
            @for($i = 0; $i < 4; $i++)
                <x-after-sales.ui.day-stat />
            @endfor
        </div>
    </x-after-sales.ui.panel>

    {{-- Follow-up + hygiene --}}
    <div class="grid md:grid-cols-2 gap-4">
        <x-after-sales.ui.panel title="Follow-up" subtitle="Queues that need admin action before tickets can move forward.">
            <div id="admin-followup-counts" class="grid gap-2 text-center text-sm font-semibold text-[#2B3441] tabular-nums"></div>
            <div id="admin-chart-followup"></div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="Setup and data hygiene" subtitle="Records that need setup corrections or missing data.">
            <div id="admin-hygiene-counts" class="grid gap-2 text-center text-sm font-semibold text-[#2B3441] tabular-nums"></div>
            <div id="admin-chart-hygiene"></div>
        </x-after-sales.ui.panel>
    </div>

    {{-- Technician workload --}}
    <x-after-sales.ui.panel title="Technician workload" subtitle="Active ticket workload across all technicians. Scroll to view more.">
        <div class="grid grid-cols-[150px_minmax(0,1fr)_48px] gap-3 text-[10px] text-[#9AA39E] pb-2 border-b border-[#EEF1EF]">
            <span>Technician</span>
            <div class="flex justify-between" id="admin-workload-axis"></div>
            <span class="text-right">Active tickets</span>
        </div>
        <div id="admin-workload" class="max-h-[420px] overflow-y-auto divide-y divide-[#F1F4F2]"></div>
        <template id="admin-rank-template"><x-after-sales.ui.rank-bar-row /></template>
    </x-after-sales.ui.panel>
</div>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('admin-dashboard');
    if (!root) return;

    const C = { green: '#14573F', sage: '#7FAF9A', sageLight: '#9CC4B2', gold: '#D9B65B', grey: '#A9B8B0' };

    // Data dari GET /api/dashboard/aftersales/admin (snapshot saat ini, tanpa filter Period).
    const API = '/api/dashboard/aftersales/admin';
    const STATUS_LABEL = { on_duty: 'On duty', standby: 'Standby' };
    const adminData = {
        progress: [], sla: { onTrack: 0, atRisk: 0, overdue: 0 },
        visits: { labels: [], visits: [], recomings: [] },
        followUp: [], hygiene: [],
        technicians: { items: [], total: 0, hasMore: false, maxActive: 0, page: 1 },
    };

    const getJson = async (url) => {
        const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(`Dashboard request failed: ${res.status}`);
        return res.json();
    };
    const mapTech = t => ({ rank: t.rank, name: t.name, status: STATUS_LABEL[t.status] ?? t.status, count: t.active_tickets });

    async function loadDashboard() {
        const { data } = await getJson(API);
        adminData.progress = data.progress.items.map(i => [i.label, i.count, i.key === 'published' ? 'warn' : undefined]);
        adminData.sla = { onTrack: data.sla.on_track, atRisk: data.sla.at_risk, overdue: data.sla.overdue };
        adminData.visits = {
            labels: data.visits.days.map(d => d.label),
            visits: data.visits.days.map(d => d.visits),
            recomings: data.visits.days.map(d => d.recomings),
        };
        adminData.followUp = data.follow_up.map(i => [i.label, i.count]);
        adminData.hygiene = data.hygiene.map(i => [i.label, i.count]);
        adminData.technicians = {
            items: data.technicians.items.map(mapTech), total: data.technicians.total,
            hasMore: data.technicians.has_more, maxActive: data.technicians.max_active, page: 1,
        };
    }

    // Load more workload saat scroll mentok bawah (max_active dari summary dipakai agar skala bar tetap).
    let loadingMore = false;
    async function loadMoreTechnicians() {
        const t = adminData.technicians;
        if (loadingMore || !t.hasMore) return;
        loadingMore = true;
        try {
            const next = t.page + 1;
            const res = await getJson(`${API}/technicians?${new URLSearchParams({ page: next, per_page: 20 })}`);
            t.items.push(...res.data.map(mapTech));
            t.page = next;
            t.hasMore = res.current_page < res.last_page;
            renderWorkload();
        } catch (e) { console.error(e); } finally { loadingMore = false; }
    }

    const charts = {};
    const mount = (id, opts) => {
        charts[id]?.destroy();
        charts[id] = new ApexCharts(document.getElementById(id), opts);
        charts[id].render();
    };
    const baseChart = { toolbar: { show: false }, fontFamily: 'inherit', animations: { speed: 400 }, parentHeightOffset: 0 };

    // Chart kolom dengan angka di baris atas (sejajar dengan tiap kolom). Dipakai: progress, follow-up, hygiene.
    function renderCountBars(chartId, countsId, items, { colors, height = 220 } = {}) {
        const counts = document.getElementById(countsId);
        counts.style.gridTemplateColumns = `repeat(${items.length}, minmax(0, 1fr))`;
        counts.innerHTML = items.map(i => `<span>${i[1]}</span>`).join('');
        mount(chartId, {
            chart: { ...baseChart, type: 'bar', height },
            series: [{ name: 'Tickets', data: items.map(i => i[1]) }],
            plotOptions: { bar: { distributed: true, columnWidth: '38%', borderRadius: 3 } },
            colors: colors ?? items.map(() => C.green),
            legend: { show: false }, dataLabels: { enabled: false },
            xaxis: { categories: items.map(i => i[0]), axisBorder: { show: false }, axisTicks: { show: false },
                labels: { style: { fontSize: '10px', colors: '#5F6B65' }, rotate: 0, trim: true, hideOverlappingLabels: false } },
            yaxis: { show: false, min: 0 },
            grid: { show: false, padding: { left: 0, right: 0 } },
            tooltip: { y: { formatter: v => v + ' tickets' } },
        });
    }

    function renderProgress() {
        renderCountBars('admin-chart-progress', 'admin-progress-counts', adminData.progress, {
            colors: adminData.progress.map(i => i[2] === 'warn' ? C.gold : C.green), height: 240,
        });
    }

    function renderSla() {
        const s = adminData.sla, total = s.onTrack + s.atRisk + s.overdue;
        mount('admin-chart-sla', {
            chart: { ...baseChart, type: 'donut', height: 220 },
            series: [s.onTrack, s.atRisk, s.overdue], labels: ['On track', 'At risk <24h', 'Overdue'],
            colors: [C.green, C.gold, C.grey], dataLabels: { enabled: false }, legend: { show: false },
            stroke: { width: 2, colors: ['#fff'] },
            plotOptions: { pie: { donut: { size: '66%', labels: {
                show: true,
                name: { show: true, fontSize: '11px', color: '#8A948F', offsetY: 18 },
                value: { show: true, fontSize: '30px', fontWeight: 700, color: C.green, offsetY: -14, formatter: () => total },
                total: { show: true, label: 'open', color: '#8A948F', formatter: () => total },
            } } } },
        });
        const rows = [s.onTrack, s.atRisk, s.overdue];
        root.querySelectorAll('#admin-sla-legend [data-legend]').forEach((el, i) => {
            el.querySelector('[data-legend-count]').textContent = rows[i];
            el.querySelector('[data-legend-percent]').textContent = total ? Math.round((rows[i] / total) * 100) + '%' : '0%';
        });
    }

    function renderVisits() {
        const v = adminData.visits;
        mount('admin-chart-visits', {
            chart: { ...baseChart, type: 'line', height: 260 },
            series: [{ name: 'Visits', data: v.visits }, { name: 'Recoming', data: v.recomings }],
            colors: [C.green, C.gold], stroke: { width: 3, curve: 'straight' },
            markers: { size: 0 }, dataLabels: { enabled: false },
            xaxis: { categories: v.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { show: false } },
            yaxis: { show: false, min: 0 },
            grid: { borderColor: '#EEF1EF', yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
            legend: { position: 'top', horizontalAlign: 'right', markers: { size: 5, shape: 'square' } },
        });
        const cells = root.querySelectorAll('#admin-days [data-day-label]');
        v.labels.forEach((label, i) => {
            const cell = cells[i].parentElement;
            cell.querySelector('[data-day-label]').textContent = label;
            cell.querySelector('[data-day-value]').textContent = v.visits[i] + v.recomings[i];
            cell.querySelector('[data-day-note]').textContent = `${v.visits[i]} visits · ${v.recomings[i]} recomings`;
        });
    }

    function renderWorkload() {
        const list = adminData.technicians.items;
        const max = Math.max(adminData.technicians.maxActive, 1);
        const step = Math.ceil(max / 3);
        document.getElementById('admin-workload-axis').innerHTML = [0, step, step * 2, step * 3].map(n => `<span>${n}</span>`).join('');
        const tpl = document.getElementById('admin-rank-template');
        const wrap = document.getElementById('admin-workload');
        wrap.replaceChildren(...list.map(({ rank, name, status, count }) => {
            const node = tpl.content.firstElementChild.cloneNode(true);
            node.querySelector('[data-rank]').textContent = rank;
            node.querySelector('[data-name]').textContent = name;
            node.querySelector('[data-status]').textContent = status;
            node.querySelector('[data-value]').textContent = count;
            node.querySelector('[data-bar]').style.width = (count / (step * 3)) * 100 + '%';
            return node;
        }));
    }

    async function init() {
        try {
            await loadDashboard();
        } catch (e) {
            console.error(e); // 401/403/5xx: jangan tampilkan dummy sebagai data live
            return;
        }
        renderProgress();
        renderSla();
        renderVisits();
        renderCountBars('admin-chart-followup', 'admin-followup-counts', adminData.followUp, {
            colors: adminData.followUp.map((_, i) => (i === 3 ? C.sageLight : i === 5 || i === 0 ? C.green : C.sage)), height: 200,
        });
        renderCountBars('admin-chart-hygiene', 'admin-hygiene-counts', adminData.hygiene, {
            colors: [C.green, '#4F9374', '#6FA58C', C.sage], height: 200,
        });
        renderWorkload();
    }
    document.getElementById('admin-workload').addEventListener('scroll', e => {
        const el = e.currentTarget;
        if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40) loadMoreTechnicians();
    });
    init();
})();
</script>
@endpush
