<div id="spv-dashboard" data-api="{{ route('dashboard.aftersales.supervisor.summary') }}" data-tickets-api="{{ route('dashboard.aftersales.supervisor.tickets') }}" class="space-y-5 bg-[#F6F8F7] p-4 sm:p-6 rounded-xl">
    {{-- Header --}}
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#14573F] uppercase">Supervisor Dashboard</h1>
            <p class="text-xs text-[#8A948F] mt-1">Financial performance, service quality, and operational trends.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard', ['view' => 'admin']) }}" class="px-3 py-1.5 rounded-lg border border-[#D9D9D9] bg-white text-xs font-medium text-[#1E1E1E]">Admin view</a>
            <a href="#" data-unclassified class="px-3 py-1.5 rounded-lg border border-[#EBD79A] bg-[#FBF3D9] text-xs font-semibold text-[#8A6B12]">
                Unclassified <span id="spv-unclassified">0</span> <i class="bi bi-chevron-right text-[10px]"></i>
            </a>
        </div>
    </header>

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
        <x-after-sales.spv.panel title="Revenue and cost by month" subtitle="Billed revenue, warranty, and preventive maintenance.">
            <div id="spv-chart-revenue"></div>
        </x-after-sales.spv.panel>

        <x-after-sales.spv.panel title="Cost split" subtitle="Distribution of billed work, warranty, PM, and unclassified cost.">
            <div id="spv-chart-split"></div>
        </x-after-sales.spv.panel>
    </div>

    {{-- Warranty by model + service health --}}
    <div class="grid lg:grid-cols-2 gap-4">
        <x-after-sales.spv.panel title="Warranty Cost by model" subtitle="Highest warranty exposure across the selected breakdown.">
            <x-slot:actions>
                <x-after-sales.spv.segmented-toggle name="warranty" :options="['model' => 'Model', 'region' => 'Region', 'technician' => 'Technician']" />
            </x-slot:actions>
            <div id="spv-warranty-list" class="space-y-5 pt-2"></div>
        </x-after-sales.spv.panel>

        <x-after-sales.spv.panel title="Service health" subtitle="SLA delivery, first-visit success, rating, and follow-up health.">
            <div class="grid grid-cols-3 gap-3">
                <x-after-sales.spv.ring-stat key="sla" label="SLA" />
                <x-after-sales.spv.ring-stat key="firstVisit" label="First-Visit" />
                <x-after-sales.spv.ring-stat key="rating" label="Rating" />
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
                <x-after-sales.spv.progress-row key="recoming" label="Recoming" />
                <x-after-sales.spv.progress-row key="survey" label="Survey response" />
            </div>
        </x-after-sales.spv.panel>
    </div>

    {{-- Recoming reasons --}}
    <x-after-sales.spv.panel title="Recoming reasons" subtitle="Primary causes of repeat visits, split by counted and excluded cases.">
        <div id="spv-chart-recoming"></div>
    </x-after-sales.spv.panel>

    {{-- Reported problem --}}
    <x-after-sales.spv.panel title="Reported problem" subtitle="Problem concentration by category and machine group over time.">
        <x-slot:actions>
            <x-after-sales.spv.segmented-toggle name="problem" :options="['machine' => 'Machine', 'region' => 'Region', 'month' => 'Month']" />
        </x-slot:actions>
        <x-after-sales.spv.heatmap-matrix />
    </x-after-sales.spv.panel>
</div>

@push('scripts')
<script src="{{ asset('js/aftersales/supervisor-dashboard.js') }}?v={{ filemtime(public_path('js/aftersales/supervisor-dashboard.js')) }}" defer></script>
@endpush
