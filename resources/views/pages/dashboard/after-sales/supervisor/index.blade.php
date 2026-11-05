@include('pages.templates.main-headers', [
    'title' => 'Supervisor Dashboard',
    'subtitle' => 'Financial performance, service quality, and operational trends.',
    'action' => '<a href="#" class="px-3 py-1.5 rounded-lg border border-[#D9D9D9] bg-white text-xs font-medium text-[#1E1E1E]">Admin view</a>'
        . '<a href="#" class="px-3 py-1.5 rounded-lg border border-[#EBD79A] bg-[#FBF3D9] text-xs font-semibold text-[#8A6B12]">Unclassified <span id="spv-unclassified">0</span> <i class="bi bi-chevron-right text-[10px]"></i></a>',
])

<div id="spv-dashboard" class="space-y-5 bg-[#F6F8F7] p-4 sm:p-6 rounded-xl mt-4">

    <x-after-sales.spv.filter-bar />

    {{-- KPI --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-after-sales.spv.kpi-card key="billed" label="Billed Revenue" />
        <x-after-sales.spv.kpi-card key="warranty" label="Warranty Cost" info="Biaya klaim garansi pada periode terpilih." />
        <x-after-sales.spv.kpi-card key="net" label="Net Contribution" />
        <x-after-sales.spv.kpi-card key="pm" label="PM Cost" />
    </div>

    {{-- Revenue + cost split --}}
    <div class="grid lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-4">
        <x-after-sales.ui.panel title="Revenue and cost by month" subtitle="Billed revenue, warranty, and preventive maintenance.">
            <div id="spv-chart-revenue"></div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="Cost split" subtitle="Distribution of billed work, warranty, PM, and unclassified cost.">
            <div id="spv-chart-split"></div>
        </x-after-sales.ui.panel>
    </div>

    {{-- Warranty by model + service health --}}
    <div class="grid lg:grid-cols-2 gap-4">
        <x-after-sales.ui.panel title="Warranty Cost by model" subtitle="Highest warranty exposure across the selected breakdown.">
            <x-slot:actions>
                <x-after-sales.spv.segmented-toggle name="warranty" :options="['model' => 'Model', 'region' => 'Region', 'technician' => 'Technician']" />
            </x-slot:actions>
            <div id="spv-warranty-list" class="space-y-5 pt-2"></div>
        </x-after-sales.ui.panel>

        <x-after-sales.ui.panel title="Service health" subtitle="SLA delivery, first-visit success, rating, and follow-up health.">
            <div class="grid grid-cols-3 gap-3">
                <x-after-sales.spv.ring-stat key="sla" label="SLA" />
                <x-after-sales.spv.ring-stat key="firstVisit" label="First-Visit" />
                <x-after-sales.spv.ring-stat key="rating" label="Rating" />
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
                <x-after-sales.spv.progress-row key="recoming" label="Recoming" />
                <x-after-sales.spv.progress-row key="survey" label="Survey response" />
            </div>
        </x-after-sales.ui.panel>
    </div>

    {{-- Recoming reasons --}}
    <x-after-sales.ui.panel title="Recoming reasons" subtitle="Primary causes of repeat visits, split by counted and excluded cases.">
        <div id="spv-chart-recoming"></div>
    </x-after-sales.ui.panel>

    {{-- Reported problem --}}
    <x-after-sales.ui.panel title="Reported problem" subtitle="Problem concentration by category and machine group over time.">
        <x-slot:actions>
            <x-after-sales.spv.segmented-toggle name="problem" :options="['machine' => 'Machine', 'region' => 'Region', 'month' => 'Month']" />
        </x-slot:actions>
        <x-after-sales.spv.heatmap-matrix />
    </x-after-sales.ui.panel>
</div>

@push('scripts')
<script>
(function () {
    const C = { green: '#14573F', sage: '#7FAF9A', gold: '#D9B65B', grey: '#C9CFCB' };
    const HEAT = ['#E6F1EC', '#BFDCCD', '#8FBFA8', '#4F9374', '#14573F'];
    const root = document.getElementById('spv-dashboard');
    if (!root) return;

    // [API] Ganti objek dummy ini dengan fetch() ke endpoint ringkasan SPV (belum tersedia di BE).
    const spvData = {
        unclassified: 7,
        kpi: {
            billed:   { value: 684200000, delta: 12.4 },
            warranty: { value: 94800000,  delta: -4.8, tone: 'warn' },
            net:      { value: 521700000, delta: 15.1 },
            pm:       { value: 67700000,  delta: 2.1 },
        },
        months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        revenue: {
            Billed:   [60, 66, 55, 72, 68, 76],
            Warranty: [88, 70, 78, 84, 74, 50],
            PM:       [64, 82, 62, 78, 90, 96],
        },
        split: { labels: ['Billed', 'Warranty', 'PM', 'Unclassified'], values: [72, 14, 10, 4], ratio: '13.9%' },
        warranty: {
            model: [['RD 1445', 28.4], ['CD 10', 22.8], ['TD 150', 17.6], ['FM 40', 11.7]],
            region: [['Java', 40.2], ['Sumatera', 26.1], ['Kalimantan', 18.3], ['Sulawesi', 10.4]],
            technician: [['Budi', 21.5], ['Andi', 19.9], ['Rudi', 14.2], ['Dimas', 9.8]],
        },
        health: { sla: 91, firstVisit: 84, rating: 2.7, ratingMax: 3, recoming: 8.4, survey: 76 },
        recoming: {
            labels: ['Part delay', 'Repeat issue', 'Customer', 'Diagnosis', 'Other'],
            counted: [42, 30, 36, 22, 44], excluded: [28, 22, 33, 44, 36],
        },
        problem: {
            cols: ['Tube', 'Cube', 'Block', 'Flake', 'Slurry', 'Cold Room', 'RO'],
            rows: ['Refrigeration', 'Mechanical', 'Production', 'Electrical'],
            // 0..4 intensitas per [row][col]
            matrix: [
                [0, 1, 2, 3, 4, 0, 1],
                [2, 3, 4, 0, 1, 1, 3],
                [4, 0, 1, 2, 3, 4, 0],
                [1, 2, 3, 4, 0, 1, 2],
            ],
            totals: [100, 80, 60, 35],
        },
    };

    const fmtRp = n => 'Rp ' + (n / 1e6).toFixed(1) + 'M';
    const charts = {};
    const mount = (id, opts) => {
        charts[id]?.destroy();
        charts[id] = new ApexCharts(document.getElementById(id), opts);
        charts[id].render();
    };
    const baseChart = { toolbar: { show: false }, fontFamily: 'inherit', animations: { speed: 400 } };

    function renderKpis() {
        Object.entries(spvData.kpi).forEach(([key, k]) => {
            const el = root.querySelector(`[data-kpi="${key}"]`);
            el.querySelector('[data-kpi-value]').textContent = fmtRp(k.value);
            const d = el.querySelector('[data-kpi-delta]');
            d.textContent = (k.delta > 0 ? '▴ ' : '▾ ') + Math.abs(k.delta) + '%';
            const tone = k.tone === 'warn' ? 'text-[#B7791F]' : k.delta >= 0 ? 'text-[#1F8A5B]' : 'text-[#C0392B]';
            d.className = 'font-semibold ' + tone;
        });
        document.getElementById('spv-unclassified').textContent = spvData.unclassified;
    }

    function renderRevenue() {
        mount('spv-chart-revenue', {
            chart: { ...baseChart, type: 'bar', height: 280 },
            series: Object.entries(spvData.revenue).map(([name, data]) => ({ name, data })),
            colors: [C.green, C.sage, C.gold],
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: { categories: spvData.months, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { show: false },
            grid: { show: false },
            legend: { position: 'top', horizontalAlign: 'left', markers: { radius: 2, size: 5 } },
            tooltip: { y: { formatter: v => 'Rp ' + v + 'M' } },
        });
    }

    function renderSplit() {
        const s = spvData.split;
        mount('spv-chart-split', {
            chart: { ...baseChart, type: 'donut', height: 260 },
            series: s.values, labels: s.labels,
            colors: [C.green, C.sage, C.gold, C.grey],
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ['#fff'] },
            legend: { position: 'bottom', markers: { size: 5 } },
            plotOptions: { pie: { donut: { size: '68%', labels: {
                show: true,
                name: { show: true, fontSize: '11px', color: '#8A948F', offsetY: 18 },
                value: { show: true, fontSize: '24px', fontWeight: 700, color: C.green, offsetY: -14, formatter: () => s.ratio },
                total: { show: true, label: 'warranty ratio', color: '#8A948F', formatter: () => s.ratio },
            } } } },
        });
    }

    function renderWarranty(mode = 'model') {
        const rows = spvData.warranty[mode];
        const max = Math.max(...rows.map(r => r[1]));
        document.getElementById('spv-warranty-list').innerHTML = rows.map(([name, v]) => `
            <div class="flex items-center gap-4 text-sm">
                <span class="w-20 shrink-0 text-[#2B3441] truncate">${name}</span>
                <div class="flex-1 h-2 rounded-full bg-[#E6EBE8] overflow-hidden">
                    <div class="h-full rounded-full bg-[#14573F]" style="width:${(v / max) * 100}%"></div>
                </div>
                <span class="w-20 text-right font-semibold text-[#2B3441] tabular-nums">Rp ${v.toFixed(1)}M</span>
            </div>`).join('');
    }

    function renderHealth() {
        const h = spvData.health;
        const rings = { sla: [h.sla, h.sla + '%'], firstVisit: [h.firstVisit, h.firstVisit + '%'], rating: [(h.rating / h.ratingMax) * 100, h.rating + '/' + h.ratingMax] };
        Object.entries(rings).forEach(([key, [pct, text]]) => {
            const host = root.querySelector(`[data-ring="${key}"] [data-ring-chart]`);
            charts['ring-' + key]?.destroy();
            charts['ring-' + key] = new ApexCharts(host, {
                chart: { ...baseChart, type: 'radialBar', height: 120, sparkline: { enabled: true } },
                series: [pct], colors: [C.green],
                fill: { type: 'solid', opacity: 1 },
                plotOptions: { radialBar: { hollow: { size: '52%' }, track: { background: '#E6EBE8', strokeWidth: '100%', margin: 0 },
                    dataLabels: { name: { show: false }, value: { offsetY: 6, fontSize: '17px', fontWeight: 700, color: '#2B3441', formatter: () => text } } } },
                stroke: { lineCap: 'round' },
            });
            charts['ring-' + key].render();
        });
        const setBar = (key, pct) => {
            const el = root.querySelector(`[data-progress="${key}"]`);
            el.querySelector('[data-progress-value]').textContent = pct + '%';
            el.querySelector('[data-progress-bar]').style.width = pct + '%';
        };
        setBar('recoming', h.recoming);
        setBar('survey', h.survey);
    }

    function renderRecoming() {
        const r = spvData.recoming;
        mount('spv-chart-recoming', {
            chart: { ...baseChart, type: 'bar', height: 280 },
            series: [{ name: 'Counted', data: r.counted }, { name: 'Not counted', data: r.excluded }],
            colors: [C.green, C.grey],
            plotOptions: { bar: { columnWidth: '40%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: { categories: r.labels, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { show: false }, grid: { show: false },
            legend: { position: 'top', horizontalAlign: 'left', markers: { radius: 2, size: 5 } },
        });
    }

    function renderHeatmap() {
        const p = spvData.problem;
        const maxTotal = Math.max(...p.totals);
        const tone = i => HEAT[Math.min(i + 1, 4)];
        const barTone = [HEAT[4], '#3F9272', '#5FA68A', '#86B5A2'];
        document.getElementById('spv-problem-bars').innerHTML = p.rows.map((name, i) => `
            <div class="flex items-center gap-4 h-10">
                <span class="w-28 shrink-0 text-sm font-medium text-[#2B3441]">${name}</span>
                <div class="flex-1 min-w-0">
                    <div class="h-6 rounded-full" style="width:${(p.totals[i] / maxTotal) * 100}%;background:${barTone[i] ?? HEAT[2]}" title="${p.totals[i]} tickets"></div>
                </div>
            </div>`).join('');

        const cols = `grid-template-columns:repeat(${p.cols.length},minmax(0,1fr))`;
        document.getElementById('spv-heatmap-head').setAttribute('style', cols);
        document.getElementById('spv-heatmap-head').innerHTML = p.cols.map(c => `<span>${c}</span>`).join('');
        document.getElementById('spv-heatmap-body').innerHTML = p.matrix.map(row => `
            <div class="grid gap-2" style="${cols}">
                ${row.map(v => `<div class="h-10 rounded-lg" style="background:${HEAT[v]}" title="Intensity ${v + 1}/5"></div>`).join('')}
            </div>`).join('');
    }

    // Toggle & filter
    root.querySelectorAll('[data-toggle]').forEach(group => {
        group.addEventListener('click', e => {
            const btn = e.target.closest('button[data-value]');
            if (!btn) return;
            group.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
            // [API] problem toggle (machine/region/month) perlu data dari BE; sementara hanya warranty yang punya dataset.
            if (group.dataset.toggle === 'warranty') renderWarranty(btn.dataset.value);
        });
    });
    root.querySelectorAll('[data-filter]').forEach(sel => {
        // [API] panggil ulang endpoint dengan period/region/category lalu render ulang semua panel.
        sel.addEventListener('change', () => init());
    });

    function init() {
        renderKpis(); renderRevenue(); renderSplit();
        renderWarranty(root.querySelector('[data-toggle="warranty"] [aria-pressed="true"]')?.dataset.value);
        renderHealth(); renderRecoming(); renderHeatmap();
    }
    init();
})();
</script>
@endpush
