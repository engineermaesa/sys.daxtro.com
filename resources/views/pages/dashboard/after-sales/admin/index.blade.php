{{-- Tombol "Supervisor view" dihapus: role After Sales tidak punya akses KPI Supervisor (kontrak admin §4). --}}
@include('pages.templates.main-headers', [
    'title' => 'Admin Dashboard',
    'subtitle' => 'Operational queues, visits, follow-up, and data quality.',
])

<div id="admin-dashboard" class="space-y-5 bg-[#F6F8F7] p-4 sm:p-6 rounded-xl mt-4">

    <div id="admin-error" class="hidden items-center justify-between gap-3 rounded-xl border border-[#F0C9C9] bg-[#FDF3F3] px-4 py-3 text-sm text-[#8A2B2B]" role="alert">
        <span data-error-text>Failed to load dashboard.</span>
        <button type="button" data-error-retry class="shrink-0 rounded-lg border border-[#8A2B2B] px-3 py-1 text-xs font-medium">Retry</button>
    </div>

    {{-- Progress + SLA --}}
    <div class="grid md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-4">
        <x-after-sales.ui.panel title="Open tickets by progress" subtitle="Tap a bar to open. All Ticket.">
            <div id="admin-progress-counts" class="grid gap-2 text-center text-sm font-semibold text-[#2B3441] tabular-nums"></div>
            <div id="admin-chart-progress"></div>
            <div class="mt-2 flex items-center justify-between text-xs text-[#5F6B65]">
                <button type="button" id="admin-recoming-note" class="hover:underline">Recoming: 0</button>
                <button type="button" id="admin-all-open" class="font-medium text-[#14573F] hover:underline">All open tickets: 0</button>
            </div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="SLA of open tickets" subtitle="Current SLA exposure across every open service ticket.">
            <div id="admin-chart-sla"></div>
            <div class="mt-2 divide-y divide-[#EEF1EF]" id="admin-sla-legend">
                <x-after-sales.ui.legend-row color="#14573F" label="On track" />
                <x-after-sales.ui.legend-row color="#D9B65B" label="At risk <24h" />
                <x-after-sales.ui.legend-row color="#A9B8B0" label="Overdue" />
            </div>
            <button type="button" id="admin-sla-note" class="hidden mt-2 text-xs text-[#8A6B12] hover:underline"></button>
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

    {{-- Drill-down: daftar tiket / record hasil klik bar (read-only; detail lewat layar existing) --}}
    <div id="admin-drill" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
        <div class="flex max-h-[80vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-[#EEF1EF] px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-[#1E1E1E]" data-drill-title>Tickets</h3>
                    <p class="text-xs text-[#8A948F]" data-drill-total></p>
                </div>
                <button type="button" data-drill-close class="rounded-lg border border-[#D9D9D9] px-3 py-1 text-xs">Close</button>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-[#F1F4F2]" data-drill-body></div>
            <div class="border-t border-[#EEF1EF] px-5 py-3 text-center">
                <button type="button" data-drill-more class="hidden rounded-lg border border-[#14573F] px-4 py-1.5 text-xs font-medium text-[#14573F]">Load more</button>
            </div>
        </div>
    </div>
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
        extra: { recoming: 0, totalOpen: 0, notConfigured: 0 },
        technicians: { items: [], total: 0, hasMore: false, maxActive: 0, page: 1 },
    };
    // Item bar = [label, count, tone, key]; key dipakai untuk drill-down saat bar diklik.
    const SLA_KEYS = ['on_track', 'at_risk', 'overdue'];
    const TICKET_URL = "{{ route('aftersales.pages.tickets.show', ['ticket' => '__ID__']) }}";

    const getJson = async (url) => {
        const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!res.ok) throw Object.assign(new Error(`Dashboard request failed: ${res.status}`), { status: res.status });
        return res.json();
    };
    const mapTech = t => ({ id: t.id, rank: t.rank, name: t.name, status: STATUS_LABEL[t.status] ?? t.status, count: t.active_tickets });

    async function loadDashboard() {
        const { data } = await getJson(API);
        adminData.progress = data.progress.items.map(i => [i.label, i.count, i.key === 'published' ? 'warn' : undefined, i.key]);
        adminData.extra = { recoming: data.progress.recoming, totalOpen: data.progress.total_open, notConfigured: data.sla.not_configured };
        adminData.sla = { onTrack: data.sla.on_track, atRisk: data.sla.at_risk, overdue: data.sla.overdue };
        adminData.visits = {
            labels: data.visits.days.map(d => d.label),
            visits: data.visits.days.map(d => d.visits),
            recomings: data.visits.days.map(d => d.recomings),
            phoneCalls: data.visits.days.map(d => d.phone_calls),
            dates: data.visits.days.map(d => d.date),
        };
        adminData.followUp = data.follow_up.map(i => [i.label, i.count, undefined, i.key]);
        adminData.hygiene = data.hygiene.map(i => [i.label, i.count, undefined, i.key]);
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
    function renderCountBars(chartId, countsId, items, { colors, height = 220, onSelect } = {}) {
        const counts = document.getElementById(countsId);
        counts.style.gridTemplateColumns = `repeat(${items.length}, minmax(0, 1fr))`;
        counts.innerHTML = items.map(i => `<span>${i[1]}</span>`).join('');
        mount(chartId, {
            chart: { ...baseChart, type: 'bar', height, events: onSelect ? {
                dataPointSelection: (_e, _c, cfg) => onSelect(items[cfg.dataPointIndex]),
            } : {} },
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
            onSelect: i => openDrill(`${i[0]} tickets`, 'tickets', { queue: 'progress', progress: i[3] }),
        });
        const x = adminData.extra;
        const rec = document.getElementById('admin-recoming-note');
        rec.textContent = `Recoming: ${x.recoming}`;
        rec.onclick = () => openDrill('Recoming tickets', 'tickets', { queue: 'progress', progress: 'recoming' });
        const all = document.getElementById('admin-all-open');
        all.textContent = `All open tickets: ${x.totalOpen}`;
        all.onclick = () => openDrill('All open tickets', 'tickets', { queue: 'all' });
    }

    function renderSla() {
        const s = adminData.sla, total = s.onTrack + s.atRisk + s.overdue;
        mount('admin-chart-sla', {
            chart: { ...baseChart, type: 'donut', height: 220, events: {
                dataPointSelection: (_e, _c, cfg) => {
                    const names = ['On track', 'At risk <24h', 'Overdue'];
                    openDrill(`SLA: ${names[cfg.dataPointIndex]}`, 'tickets', { queue: 'sla', sla: SLA_KEYS[cfg.dataPointIndex] });
                },
            } },
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
        // Tiket tanpa konfigurasi SLA tidak dihitung sebagai on track/overdue; tampilkan terpisah bila ada.
        const note = document.getElementById('admin-sla-note');
        const nc = adminData.extra.notConfigured;
        note.classList.toggle('hidden', nc === 0);
        note.textContent = `${nc} ticket${nc === 1 ? '' : 's'} without SLA configured`;
        note.onclick = () => openDrill('SLA: not configured', 'tickets', { queue: 'sla', sla: 'not_configured' });
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
            const phone = v.phoneCalls[i] ? ` · ${v.phoneCalls[i]} phone call${v.phoneCalls[i] > 1 ? 's' : ''}` : '';
            cell.querySelector('[data-day-note]').textContent = `${v.visits[i]} visits · ${v.recomings[i]} recomings${phone}`;
            cell.classList.add('cursor-pointer', 'hover:bg-[#F6F8F7]');
            cell.onclick = () => openDrill(`Schedule: ${label} (${v.dates[i]})`, 'tickets', { queue: 'visits', date: v.dates[i] });
        });
    }

    function renderWorkload() {
        const list = adminData.technicians.items;
        const max = Math.max(adminData.technicians.maxActive, 1);
        const step = Math.ceil(max / 3);
        document.getElementById('admin-workload-axis').innerHTML = [0, step, step * 2, step * 3].map(n => `<span>${n}</span>`).join('');
        const tpl = document.getElementById('admin-rank-template');
        const wrap = document.getElementById('admin-workload');
        wrap.replaceChildren(...list.map(({ id, rank, name, status, count }) => {
            const node = tpl.content.firstElementChild.cloneNode(true);
            node.classList.add('cursor-pointer', 'hover:bg-[#F6F8F7]');
            node.onclick = () => openDrill(`Tickets: ${name}`, 'tickets', { queue: 'all', technician_id: id });
            node.querySelector('[data-rank]').textContent = rank;
            node.querySelector('[data-name]').textContent = name;
            node.querySelector('[data-status]').textContent = status;
            node.querySelector('[data-value]').textContent = count;
            node.querySelector('[data-bar]').style.width = (count / (step * 3)) * 100 + '%';
            return node;
        }));
    }

    // ---- Drill-down modal (GET /tickets atau /hygiene; read-only, detail lewat layar tiket existing) ----
    const drill = document.getElementById('admin-drill');
    const drillBody = drill.querySelector('[data-drill-body]');
    const drillMore = drill.querySelector('[data-drill-more]');
    const drillState = { path: '', params: {}, page: 1, lastPage: 1 };

    function cell(text, cls = '') {
        const s = document.createElement('span');
        s.className = cls;
        s.textContent = text ?? '—';
        return s;
    }

    // Satu baris hasil drill-down; bentuk row mengikuti entity (ticket/customer/machine/sparepart).
    function drillRow(entity, r) {
        const row = document.createElement(entity === 'ticket' ? 'a' : 'div');
        row.className = 'flex items-center justify-between gap-3 px-5 py-3 text-xs hover:bg-[#F6F8F7]';
        const main = document.createElement('div');
        main.className = 'min-w-0';
        const side = document.createElement('div');
        side.className = 'shrink-0 text-right text-[#5F6B65]';
        if (entity === 'ticket') {
            row.href = TICKET_URL.replace('__ID__', r.id);
            main.append(cell(r.ticket_code, 'block font-semibold text-[#14573F]'),
                cell([r.customer?.name, r.machine?.name].map(v => v ?? '—').join(' · '), 'block truncate text-[#5F6B65]'));
            const sla = r.sla?.bucket ? r.sla.bucket.replace('_', ' ') : '—';
            side.append(cell(`${r.technician?.name ?? '—'}`, 'block'), cell(`${r.progress ?? '—'} · SLA ${sla}`, 'block text-[#9AA39E]'));
            if (r.scheduled_at) side.append(cell(new Date(r.scheduled_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }), 'block text-[#9AA39E]'));
        } else if (entity === 'customer') {
            main.append(cell(r.name, 'block font-semibold text-[#2B3441]'));
            side.append(cell([r.phone_missing && 'No phone', r.email_missing && 'No email'].filter(Boolean).join(' · ')));
        } else if (entity === 'machine') {
            main.append(cell(r.machine_name, 'block font-semibold text-[#2B3441]'));
            side.append(cell('No warranty date'));
        } else {
            main.append(cell(r.name, 'block font-semibold text-[#2B3441]'), cell(r.part_number, 'block text-[#9AA39E]'));
            side.append(cell(`Stock ${r.stock} / min ${r.min_stock}`));
        }
        row.append(main, side);
        return row;
    }

    async function fetchDrillPage(page) {
        const q = new URLSearchParams({ ...drillState.params, page, per_page: 20 });
        const res = await getJson(`${API}/${drillState.path}?${q}`);
        const entity = res.entity ?? 'ticket';
        if (!res.data.length && page === 1) {
            const empty = cell('No records found.', 'block px-5 py-8 text-center text-xs text-[#9AA39E]');
            drillBody.append(empty);
        }
        res.data.forEach(r => drillBody.append(drillRow(entity, r)));
        drillState.page = res.current_page;
        drillState.lastPage = res.last_page;
        drill.querySelector('[data-drill-total]').textContent = `${res.total} record${res.total === 1 ? '' : 's'}`;
        drillMore.classList.toggle('hidden', res.current_page >= res.last_page);
    }

    async function openDrill(title, path, params) {
        Object.assign(drillState, { path, params });
        drill.querySelector('[data-drill-title]').textContent = title;
        drill.querySelector('[data-drill-total]').textContent = 'Loading…';
        drillBody.replaceChildren();
        drillMore.classList.add('hidden');
        drill.classList.remove('hidden'); drill.classList.add('flex');
        try {
            await fetchDrillPage(1);
        } catch (e) {
            console.error(e);
            drill.querySelector('[data-drill-total]').textContent = errorText(e);
        }
    }
    const closeDrill = () => { drill.classList.add('hidden'); drill.classList.remove('flex'); };
    drill.querySelector('[data-drill-close]').addEventListener('click', closeDrill);
    drill.addEventListener('click', e => { if (e.target === drill) closeDrill(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrill(); });
    drillMore.addEventListener('click', () => fetchDrillPage(drillState.page + 1).catch(e => console.error(e)));

    // ---- Error banner ----
    const errorBox = document.getElementById('admin-error');
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
        errorBox.classList.add('hidden'); errorBox.classList.remove('flex');
        try {
            await loadDashboard();
        } catch (e) {
            console.error(e); // jangan tampilkan dummy sebagai data live
            showError(e);
            return;
        }
        renderProgress();
        renderSla();
        renderVisits();
        // Hygiene memakai /hygiene (bukan /tickets); entity menentukan bentuk row.
        renderCountBars('admin-chart-followup', 'admin-followup-counts', adminData.followUp, {
            colors: adminData.followUp.map((_, i) => (i === 3 ? C.sageLight : i === 5 || i === 0 ? C.green : C.sage)), height: 200,
            onSelect: i => openDrill(i[0], 'tickets', { queue: i[3] }),
        });
        renderCountBars('admin-chart-hygiene', 'admin-hygiene-counts', adminData.hygiene, {
            colors: [C.green, '#4F9374', '#6FA58C', C.sage], height: 200,
            onSelect: i => openDrill(i[0], 'hygiene', { queue: i[3] }),
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
