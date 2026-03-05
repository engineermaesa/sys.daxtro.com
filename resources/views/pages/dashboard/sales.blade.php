<section>
    
    {{-- PERSONAL KPI GRID --}}
    <h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Personal KPI</h1>
    <div class="grid grid-cols-4 gap-3 mt-2">
        
        {{-- ACHIEVEMENT VS TARGET SECTION--}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Achievement vs Target (MTD)</h1>
                
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
                    <p class="text-[#1E1E1E]">Achievement</p>
                </div>
            </div>

        </div>
        
        {{-- CLOSED DEAL SECTION--}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">CLOSED DEAL (MTD)</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.handshake/>
                </div>
            </div>

            <div>
                <div class="mt-3 text-[#757575]">
                    <p id="totalDeals">0/</p>
                    <p id="totalAmount"></p>
                </div>

                <div class="flex items-center justify-start gap-1 mt-3">
                    <p id="conversionRate">0</p>
                    <p id="percentageAchievement" class="text-[#1E1E1E]">Conversion from Total Active Leads</p>
                </div>
            </div>

        </div>

        {{-- TOTAL ACTIVE LEADS SECTION--}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Total Active Leads</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.users/>
                </div>
            </div>

            <div>
                <div class="mt-3 text-[#757575]">
                    <p id="totalLeads">0/</p>
                    <p id="totalTrash">Trash Leads: 0</p>
                </div>

                <div class="flex items-center justify-start gap-3 mt-3">
                    <div class="flex items-center gap-1">
                        <span class="w-[8px] h-[8px] rounded-full block bg-[#3F80EA]"></span>
                        <p id="coldLeads">0 Cold</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-[8px] h-[8px] rounded-full block bg-[#E8B931]"></span>
                        <p id="warmLeads">0 Warm</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-[8px] h-[8px] rounded-full block bg-[#EC221F]"></span>
                        <p id="hotLeads">0 Hot</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- POTENTIAL DEALING SECTION--}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

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
            </div>

        </div>
    </div>

    {{-- ACTIVITY OPPORTUNITIES --}}
    <h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">My Active Opportunities</h1>
    <div class="mt-4 bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
        {{-- NAVIGATION TABLES --}}
        <div class="bg-white lg:flex justify-between items-center border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
            {{-- SEARCH TABLES --}}
            <div class="lg:w-1/6 w-full border border-gray-300 rounded-lg flex items-center p-2">
                <div class="px-2">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                    </svg>
                </div>
                <input id="searchInputActivitySales" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
            </div>

            <div class="lg:w-5/6 w-full border border-gray-300 rounded-lg flex items-center p-2">
                
            </div>
        </div>

        {{-- CONTENTS TABLES --}}
        <div class="max-md:overflow-x-scroll">
            <table class="w-full bg-white rounded-br-lg rounded-bl-lg">

                {{-- HEADER TABLE --}}
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#CFD5DC]">
                        <th class="hidden">ID (hidden)</th>
                        <th class="p-1 md:p-2 lg:p-3">
                            Customer Name
                        </th>
                        <th class="p-1 md:p-2 lg:p-3">  
                            Stage
                        </th>
                        <th class="p-1 md:p-2 lg:p-3">
                            Amount
                        </th>
                        <th class="p-1 md:p-2 lg:p-3">
                            Product
                        </th>
                        <th class="p-1 md:p-2 lg:p-3">
                            Segment
                        </th>
                        <th class="p-1 md:p-2 lg:p-3">
                            Last Activity
                        </th>
                        <th class="text-center p-1 md:p-2 lg:p-3">
                            Data Validation
                        </th>
                    </tr>
                </thead>
                <tbody id="bodyTable" class="text-[#1E1E1E]"></tbody>
            </table>
            {{-- NAVIGATION ROWS --}}
            <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent border-t border-t-[#D9D9D9]">
                <div class="flex items-center gap-3">
                    <p class="font-semibold">Show Rows</p>
                    <select id="tabPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                        onchange="loadActivity('size', this.value)">
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div id="tabShowing" class="font-semibold">Showing 0-0 of 0</div>
                    <div>
                        <button id="tabPrevBtn"
                            class="btn btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                            onclick="loadActivity('prev')">
                            <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                        </button>
                        <button id="tabNextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                            onclick="loadActivity('next')">
                            <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        loadDashboardGrid();
        loadActivity();
    });

    async function loadDashboardGrid() {
        try {
            const response = await fetch("/api/leads/grid");
            
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }

            const result = await response.json();

            if (result.status !== "success") {
                throw new Error("API returned failed status");
            }

            const data = result.Data;

            
            const achievementSalesFormatted = formatRupiah(data.achievement_target.achievement);
            $("#achievementSales").text(achievementSalesFormatted + "/").addClass('font-semibold text-2xl text-[#1E1E1E]');

            const targetSalesFormatted = formatRupiah(data.achievement_target.target);
            $("#targetSales").text(targetSalesFormatted).addClass('font-semibold text-2xl text-[#1E1E1E]');
            
            if ( data.achievement_target.percentage > 70 ){
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-finish');
            }
            else if ( data.achievement_target.percentage > 35 ){
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-waiting');
            } else {
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-expired');
            }

            $("#totalDeals").text(data.closed_deal.total_deals).addClass('font-semibold text-3xl text-[#1E1E1E]');;

            const totalAmountFormatted = formatRupiah(data.closed_deal.total_amount);
            $("#totalAmount").text("Amount: " + totalAmountFormatted);

            $("#conversionRate").text(data.closed_deal.conversion_rate + "%");

            $("#totalLeads").text(data.active_leads.total).addClass('font-semibold text-3xl text-[#1E1E1E]');
            $("#totalTrash").text("Trash Leads: " + data.active_leads.trash);

            $("#coldLeads").text(data.active_leads.cold + ' Cold');
            $("#warmLeads").text(data.active_leads.warm + ' Warm');
            $("#hotLeads").text((data.active_leads?.hot ?? 0) + " Hot");

            const potentialAmountFormatted = formatRupiah(data.potential_dealing.total_amount);
            $("#potentialTotalAmount").text(potentialAmountFormatted).addClass('font-semibold text-3xl text-[#1E1E1E]');
            $("#potentialTotalOpportunity").text(data.potential_dealing.total_opportunity + ' Active Opportunity').addClass('text-right');

        } catch (error) {
            console.error("Error loading dashboard grid:", error);
        }
    }

    const DEFAULT_PAGE_SIZE = 5;

    let activityPage = 1;
    let activityPageSize = DEFAULT_PAGE_SIZE;
    let activityTotal = 0;

    async function loadActivity(action = 'init', value = null) {

        const API_URL = '/api/leads/active-opportunities';

        if (action === 'prev' && activityPage > 1) {
            activityPage--;
        }

        if (action === 'next') {
            activityPage++;
        }

        if (action === 'size' && value) {
            activityPageSize = parseInt(value, 10) || DEFAULT_PAGE_SIZE;
            activityPage = 1;
        }

        const params = new URLSearchParams({
            page: activityPage,
            per_page: activityPageSize,
            stage: document.getElementById('filter_stage')?.value || '',
            start_date: document.getElementById('filter_start')?.value || '',
            end_date: document.getElementById('filter_end')?.value || '',
            search: getSearchQuery()
        });

        try {

            const response = await fetch(`${API_URL}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!result || result.status !== 'success') return;

            const paginatedData = result.data || [];
            activityTotal = result.total || 0;

            // 🔥 SYNC PAGE DARI SERVER
            activityPage = result.current_page || 1;

            const totalPages = result.last_page || 1;

            const tbody = document.getElementById('bodyTable');
            tbody.innerHTML = '';

            if (paginatedData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-3 text-[#1E1E1E]">
                            No data found
                        </td>
                    </tr>
                `;
            } else {
                paginatedData.forEach(item => {
                    tbody.innerHTML += `
                        <tr class="border-t border-t-[#D9D9D9]">
                            <td class="hidden">${item.id ?? '-'}</td>
                            <td class="p-1 lg:p-3">${item.customer_name ?? '-'}</td>
                            <td class="p-1 lg:p-3">
                                <span class="inline-block lg:px-2 lg:py-1 rounded-sm
                                ${
                                    item.stage === 'Cold' ? 'status-cold' :
                                    item.stage === 'Warm' ? 'status-warm' :
                                    item.stage === 'Hot' ? 'status-hot' :
                                    ''
                                }
                                ">
                                    ${item.stage ?? '-'}
                                
                                </span>
                            </td>
                            <td class="p-1 lg:p-3">${formatRupiah(item.amount) ?? '-'}</td>
                            <td class="p-1 lg:p-3">${item.product ?? '-'}</td>
                            <td class="p-1 lg:p-3">${item.segment ?? '-'}</td>
                            <td class="p-1 lg:p-3">${item.last_activity ?? '-'}</td>
                            <td class="p-1 lg:p-3">
                                <span class="inline-block lg:px-2 lg:py-1 rounded-sm
                                    ${
                                        item.data_validation === 'Complete' ? 'status-deal' :
                                        item.data_validation === 'Moderate' ? 'status-warm' :
                                        'status-trash'
                                    }
                                "
                                >
                                    ${item.data_validation ?? '-'}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            }

            // =========================
            // UPDATE PAGINATION UI
            // =========================
            const prevBtn = document.getElementById('tabPrevBtn');
            const nextBtn = document.getElementById('tabNextBtn');
            const showing = document.getElementById('tabShowing');

            if (prevBtn) prevBtn.disabled = activityPage <= 1;
            if (nextBtn) nextBtn.disabled = activityPage >= totalPages;

            const startIdx = activityTotal === 0
                ? 0
                : (activityPage - 1) * activityPageSize + 1;

            const endIdx = Math.min(activityTotal, activityPage * activityPageSize);

            if (showing) {
                showing.innerText = `Showing ${startIdx}-${endIdx} of ${activityTotal}`;
            }

        } catch (error) {
            console.error('Load Activity Error:', error);
        }
    }

    function getSearchQuery() {
        return document.getElementById('searchInputActivitySales')?.value.trim() || '';
    }

    let searchTimeout = null;

    document.getElementById('searchInputActivitySales')
        ?.addEventListener('input', function () {

            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {
                activityPage = 1; // reset ke page 1 saat search
                loadActivity();
            }, 500);
        });

    function formatRupiah(number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0
        }).format(number);
    }


</script>