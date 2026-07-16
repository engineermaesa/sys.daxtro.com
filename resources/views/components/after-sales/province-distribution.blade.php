<section {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-[#D9D9D9] p-4']) }} aria-labelledby="province-distribution-heading">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div>
            <h2 id="province-distribution-heading" class="text-[#1E1E1E] font-bold text-lg">Province Distribution</h2>
            <p class="text-[#757575] text-sm">Machine deployment by province.</p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1">
                <label for="province-distribution-region" class="text-[10px] uppercase text-[#757575] font-medium">Region</label>
                <select id="province-distribution-region"
                    class="min-w-[160px] bg-white border border-[#D9D9D9] rounded-lg px-3 py-2 text-sm focus:outline-none">
                    <option value="">All Provinces</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-[10px] uppercase text-[#757575] font-medium">Timeframe</span>
                <div class="inline-flex items-center bg-[#F0F0F0] rounded-lg p-1" role="group" aria-label="Select timeframe">
                    <button type="button" data-timeframe="monthly"
                        class="btn-timeframe px-3 py-1.5 rounded-md text-sm font-medium transition-colors" aria-pressed="true">
                        Monthly
                    </button>
                    <button type="button" data-timeframe="yearly"
                        class="btn-timeframe px-3 py-1.5 rounded-md text-sm font-medium transition-colors" aria-pressed="false">
                        Yearly
                    </button>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-[10px] uppercase text-[#757575] font-medium">Reporting Period</span>
                <span id="province-distribution-period"
                    class="inline-flex items-center gap-2 border border-[#D9D9D9] rounded-lg px-3 py-2 text-sm text-[#1E1E1E]">
                    <i class="bi bi-calendar3"></i>
                    <span id="province-distribution-period-text">Last 1 Month</span>
                </span>
            </div>
        </div>
    </div>

    <div id="province-distribution-chart" class="mt-4" role="img" aria-label="Bar chart of machine deployment by province"></div>
</section>
