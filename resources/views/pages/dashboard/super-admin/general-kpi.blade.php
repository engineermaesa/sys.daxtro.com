<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">General KPI</h1>

<div class="grid grid-cols-2 2xl:grid-cols-3 gap-3 mt-2">
    {{-- ACHIEVEMENT VS TARGET SALE AMOUNT SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">

        <div class="flex justify-between items-center">
            
            <h1 class="text-[#757575] font-semibold">Achievement vs Target Sale (MTD)</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.crosshair/>
            </div>
        </div>

        <div>
            <div class="mt-3 text-[#757575]">
                <p id="achievementSales">0/</p>
                <p id="targetSales">0</p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="percentageAchievement">0</p>
                <p class="text-[#1E1E1E] text-xs">Achievement</p>
            </div>

            <p id="compareAchievementSales" class="hidden"></p>
        </div>

    </div>
    
    {{-- ACHIEVEMENT VS TARGET LEADS SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.15s;">

        <div class="flex justify-between items-center">
            
            <h1 class="text-[#757575] font-semibold">Achievement vs Target Leads (MTD)</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.leads/>
            </div>
        </div>

        <div>
            <div class="mt-3 text-[#757575]">
                <p id="achievementLeads">0/</p>
                <p id="targetLeads">0</p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="percentageAchievementLeads">0</p>
                <p class="text-[#1E1E1E] text-xs">Achievement</p>
            </div>

            <p id="compareAchievementLeads" class="hidden"></p>
        </div>

    </div>
    
    {{-- ACHIEVEMENT VS TARGET VISITS SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.25s;">

        <div class="flex justify-between items-center">
            
            <h1 class="text-[#757575] font-semibold">Achievement vs Target Visits (MTD)</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.location/>
            </div>
        </div>

        <div>
            <div class="mt-3 text-[#757575]">
                <p id="achievementVisits">0/</p>
                <p id="targetVisits">0</p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="percentageAchievementVisits">0</p>
                <p class="text-[#1E1E1E] text-xs">Achievement</p>
            </div>

            <p id="compareAchievementVisits" class="hidden"></p>
        </div>

    </div>
    
    {{-- CLOSED DEAL SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.40s;">

        <div class="flex justify-between items-center">
            
            <h1 class="text-[#757575] font-semibold">CLOSED DEAL (MTD)</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.handshake/>
            </div>
        </div>

        <div>
            <div class="mt-3 text-[#757575]">
                <p id="totalDeals">
                    0
                /</p>
                <p id="totalAmount"></p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="conversionRate">0</p>
                <p id="conversionCaption" class="text-[#1E1E1E] text-xs">Conversion from Total Active Leads</p>
            </div>

            <p id="compareClosedDeal" class="hidden"></p>
        </div>

    </div>

    {{-- TOTAL ACTIVE LEADS SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.50s;">

        <div class="flex justify-between items-center">
            
            <h1 class="text-[#757575] font-semibold">Total Active Leads</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.users/>
            </div>
        </div>

        <div>
            <div class="mt-3 text-[#757575]">
                <p id="totalLeads">0/</p>
                <p id="totalTAvailable">Available Leads: 0</p>
            </div>

            <div class="flex items-center justify-start gap-3 mt-3">
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full block bg-[#3F80EA]"></span>
                    <p id="coldLeads">0 Cold</p>
                </div>

                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full block bg-[#E8B931]"></span>
                    <p id="warmLeads">0 Warm</p>
                </div>

                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full block bg-[#EC221F]"></span>
                    <p id="hotLeads">0 Hot</p>
                </div>
            </div>

            <p id="compareTotalActiveLeads" class="hidden"></p>
        </div>

    </div>

    {{-- POTENTIAL DEALING SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.65s;">

        <div class="flex justify-between items-center">
            
            <h1 class="text-[#757575] font-semibold">Potential Dealing <span class="block">(Warm + Hot)</span></h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.dollar/>
            </div>
        </div>

        <div>
            <div class="mt-3 text-[#757575] inline-block">
                <p id="potentialTotalAmount">0</p>
                <p id="potentialTotalOpportunity">0</p>
            </div>

            <p id="comparePotentialDealing" class="hidden"></p>
        </div>

    </div>
</div>

<script>
    // LOAD GRID (PERSONAL-KPI)
    async function loadDashboardGrid() {
        try {
            const generalFilter = typeof getSuperAdminGeneralFilter === 'function'
                ? getSuperAdminGeneralFilter()
                : { branch_id: null, sales_id: null };
            const params = new URLSearchParams();

            if (typeof applySuperAdminGeneralFilterToParams === 'function') {
                applySuperAdminGeneralFilterToParams(params, {
                    withBranch: true,
                    withSales: true,
                    withGridDate: true,
                    withCompareDate: true
                });
            } else {
                if (generalFilter.branch_id) {
                    params.append('branch_id', generalFilter.branch_id);
                }

                if (generalFilter.sales_id) {
                    params.append('sales_id', generalFilter.sales_id);
                }

                if (generalFilter.start_date_grid && generalFilter.end_date_grid) {
                    params.append('start_date_grid', generalFilter.start_date_grid);
                    params.append('end_date_grid', generalFilter.end_date_grid);
                }

                if (generalFilter.compare_start_date && generalFilter.compare_end_date) {
                    params.append('compare_start_date', generalFilter.compare_start_date);
                    params.append('compare_end_date', generalFilter.compare_end_date);
                }
            }

            const apiUrl = params.toString()
                ? `/api/dashboard/grid?${params.toString()}`
                : '/api/dashboard/grid';

            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }

            const result = await response.json();

            if (result.status !== "success") {
                throw new Error("API returned failed status");
            }

            const data = result.Data;
            
            // ACHIEVEMENT VS TARGET SALE FETCH DATA CARDS

            const STATUS_CLASSES = "status-finish status-waiting status-expired";
            const VALUE_COLOR_CLASSES = "text-[#009951] text-[#E8B931] text-[#900B09]";
            const BASE_TARGET_CLASSES = "font-semibold text-xl xl:text-2xl text-[#1E1E1E]";
            const BASE_VALUE_CLASSES = "font-semibold text-xl xl:text-2xl";

            function toNum(v) {
                const n = Number(v);
                return Number.isFinite(n) ? n : 0;
            }

            function safePercent(actual, target) {
                const a = toNum(actual);
                const t = toNum(target);
                if (t <= 0) return 0; // hindari Infinity / NaN
                return (a / t) * 100;
            }

            function formatNumber(value) {
                return Number(value || 0).toLocaleString('id-ID');
            }

            function formatCompareDateLabel(value) {
                if (!value) {
                    return '';
                }

                const date = new Date(value + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short'
                });
            }

            function formatCompareDateRangeLabel(compareData) {
                return '['
                    + formatCompareDateLabel(compareData.start_date)
                    + ' - '
                    + formatCompareDateLabel(compareData.end_date)
                    + ']';
            }

            function formatComparePart(metric, formatter = formatNumber, suffix = '') {
                if (!metric) {
                    return null;
                }

                const delta = toNum(metric.delta);
                const sign = delta > 0 ? '+' : (delta < 0 ? '-' : '');

                return `<span class="font-bold">${sign}${formatter(Math.abs(delta))}</span>${suffix}`;
            }

            function renderCompareParts(elementId, compareData, parts) {
                const element = document.getElementById(elementId);
                if (!element) {
                    return;
                }

                if (!compareData?.enabled || !compareData?.start_date || !compareData?.end_date) {
                    element.innerHTML = '';
                    element.className = 'hidden';
                    return;
                }

                const formattedParts = parts
                    .map(function (part) {
                        return formatComparePart(part.metric, part.formatter, part.suffix || '');
                    })
                    .filter(Boolean);

                if (!formattedParts.length) {
                    element.innerHTML = '';
                    element.className = 'hidden';
                    return;
                }

                const firstDelta = toNum(parts[0]?.metric?.delta);
                const toneClass = firstDelta > 0
                    ? 'text-[#009951]'
                    : (firstDelta < 0 ? 'text-[#900B09]' : 'text-[#757575]');

                element.innerHTML = formattedParts.join(' | ') + ' ' + formatCompareDateRangeLabel(compareData);
                element.className = 'text-xs mt-2 leading-5 break-words ' + toneClass;
            }

            function getStatusByPercent(rawPercent) {
                // pakai raw value untuk compare, bukan rounded
                if (rawPercent >= 70) return { statusClass: "status-finish", colorClass: "text-[#009951]" };
                if (rawPercent >= 35) return { statusClass: "status-waiting", colorClass: "text-[#E8B931]" };
                return { statusClass: "status-expired", colorClass: "text-[#900B09]" };
            }

            function resetKpiClasses($percentEl, $actualEl) {
                $percentEl.removeClass(STATUS_CLASSES).addClass("font-semibold!");
                $actualEl.removeClass(VALUE_COLOR_CLASSES).removeClass(BASE_VALUE_CLASSES).addClass(BASE_VALUE_CLASSES);
            }

            function renderKpiCard({
                target,
                actual,
                $targetEl,
                $actualEl,
                $percentEl,
                formatter = (x) => x,
                percentFromApi = null          // pakai ini kalau API sudah kirim percentage
            }) {
                const t = toNum(target);
                const a = toNum(actual);
                const rawPercent = percentFromApi === null ? safePercent(a, t) : toNum(percentFromApi);
                const roundedPercent = Math.round(rawPercent);
                const { statusClass, colorClass } = getStatusByPercent(rawPercent);

                $targetEl.text(formatter(t)).removeClass().addClass(BASE_TARGET_CLASSES);
                resetKpiClasses($percentEl, $actualEl);

                $percentEl.text(`${roundedPercent}%`).addClass(statusClass);
                $actualEl.text(`${formatter(a)}/`).addClass(colorClass);
            }

            // ===== Usage =====
            const at = data?.achievement_target ?? {};

            // Sales (target_amount vs achievement_amount)
            renderKpiCard({
                target: at.target_amount,
                actual: at.achievement_amount,
                percentFromApi: at.percentage,
                $targetEl: $("#targetSales"),
                $actualEl: $("#achievementSales"),
                $percentEl: $("#percentageAchievement"),
                formatter: formatRupiah
            });

            // Leads
            renderKpiCard({
                target: at.target_leads,
                actual: at.leads_actual,
                $targetEl: $("#targetLeads"),
                $actualEl: $("#achievementLeads"),
                $percentEl: $("#percentageAchievementLeads")
            });

            // Visits
            renderKpiCard({
                target: at.target_visits,
                actual: at.visits_actual,
                $targetEl: $("#targetVisits"),
                $actualEl: $("#achievementVisits"),
                $percentEl: $("#percentageAchievementVisits")
            });

            // CLOSED DEAL (MTD) FETCH DATA CARDS
            $("#totalDeals").text(data.closed_deal.total_deals + ' Deal Leads').addClass('font-semibold text-xl xl:text-2xl text-[#1E1E1E]');

            const totalAmountFormatted = formatRupiah(data.closed_deal.total_amount);
            $("#totalAmount").text("Amount: " + totalAmountFormatted).addClass('text-xs');

            if (data.closed_deal.conversion_rate > 75) {
                $("#conversionRate").text(data.closed_deal.conversion_rate + "%").addClass('font-semibold! status-finish text-xs xl:text-sm!');
            } else if (data.closed_deal.conversion_rate > 35) {
                $("#conversionRate").text(data.closed_deal.conversion_rate + "%").addClass('font-semibold! status-waiting text-xs xl:text-sm!');
            } else {
                $("#conversionRate").text(data.closed_deal.conversion_rate + "%").addClass('font-semibold! status-expired text-xs xl:text-sm!');
            }

            // TOTAL ACTIVE LEADS FETCH DATA CARDS
            $("#totalLeads").text(data.active_leads.total).addClass('font-semibold text-[#1E1E1E] text-xl xl:text-2xl');
            $("#totalTAvailable").text("Available Leads: " + data.active_leads.published).addClass('text-xs');

            $("#coldLeads").text(data.active_leads.cold + ' Cold').addClass('text-xs');
            $("#warmLeads").text(data.active_leads.warm + ' Warm').addClass('text-xs');
            $("#hotLeads").text((data.active_leads?.hot ?? 0) + " Hot").addClass('text-xs');

            // POTENTIAL DEALING FETCH DATA CARDS
            const potentialAmountFormatted = formatRupiah(data.potential_dealing.total_amount);
            $("#potentialTotalAmount").text(potentialAmountFormatted).addClass('font-semibold text-xl xl:text-2xl text-[#1E1E1E]');
            $("#potentialTotalOpportunity").text(data.potential_dealing.total_opportunity + ' Active Opportunity').addClass('text-right text-xs');

            const compareData = data?.compare || {};
            const compareKpi = compareData?.general_kpi || {};

            renderCompareParts('compareAchievementSales', compareData, [
                { metric: compareKpi.achievement_amount, formatter: formatRupiah }
            ]);
            renderCompareParts('compareAchievementLeads', compareData, [
                { metric: compareKpi.leads_actual, suffix: ' leads' }
            ]);
            renderCompareParts('compareAchievementVisits', compareData, [
                { metric: compareKpi.visits_actual, suffix: ' visits' }
            ]);
            renderCompareParts('compareClosedDeal', compareData, [
                { metric: compareKpi.closed_deal_total_deals, suffix: ' deals' },
                { metric: compareKpi.closed_deal_total_amount, formatter: formatRupiah }
            ]);
            renderCompareParts('compareTotalActiveLeads', compareData, [
                { metric: compareKpi.active_leads_total, suffix: ' total' },
                { metric: compareKpi.active_leads_published, suffix: ' available' },
                { metric: compareKpi.active_leads_cold, suffix: ' cold' },
                { metric: compareKpi.active_leads_warm, suffix: ' warm' },
                { metric: compareKpi.active_leads_hot, suffix: ' hot' }
            ]);
            renderCompareParts('comparePotentialDealing', compareData, [
                { metric: compareKpi.potential_dealing_total_amount, formatter: formatRupiah },
                { metric: compareKpi.potential_dealing_total_opportunity, suffix: ' opportunity' }
            ]);

        } catch (error) {
            console.error("Error loading dashboard grid:", error);
        }
    }

    window.addEventListener('super-admin-general-filter-change', function () {
        loadDashboardGrid();
    });

</script>
