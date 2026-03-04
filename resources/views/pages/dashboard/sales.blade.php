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
                    <p id="totalTrash">0</p>
                </div>

                <div class="flex items-center justify-start gap-3 mt-3">
                    <div class="flex items-center gap-1">
                        <span class="w-[8px] h-[8px] rounded-full block bg-[#3F80EA]"></span>
                        <p id="coldLeads">0</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-[8px] h-[8px] rounded-full block bg-[#E8B931]"></span>
                        <p id="warmLeads">0</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-[8px] h-[8px] rounded-full block bg-[#EC221F]"></span>
                        <p id="warmLeads">0</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- POTENTIAL DEALING SECTION--}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Potential Dealing <span>(Warm + Hot)</span></h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.dollar/>
                </div>
            </div>

            <div>
                <div class="mt-3 text-[#757575]">
                    <p id="potentialTotalAmount">0</p>
                    <p id="potentialTotalOpportunity">0</p>
                </div>
            </div>

        </div>
    </div>

    {{-- ACTIVITY OPPORTUNITIES --}}
    <h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">My Active Opportunities</h1>


</section>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        loadDashboardGrid();
    });

    async function loadDashboardGrid() {
        try {
            const response = await fetch("https://sys-daxtro-com-main.test/api/leads/grid");
            
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }

            const result = await response.json();

            if (result.status !== "success") {
                throw new Error("API returned failed status");
            }

            const data = result.Data;

            const 

            $("#achievementSales").text(data.achievement_target.achievement + "/");
            $("#targetSales").text(data.achievement_target.target);
            
            if ( data.achievement_target.percentage > 70 ){
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-finish');
            }
            else if ( data.achievement_target.percentage > 35 ){
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-waiting');
            } else {
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-expired');
            }

            $("#totalDeals").text(data.closed_deal.total_deals + "/");

            const totalAmountFormatted = formatRupiah(data.closed_deal.total_amount);
            $("#totalAmount").text(totalAmountFormatted);

            $("#conversionRate").text(data.closed_deal.conversion_rate + "%");

            $("#totalLeads").text(data.active_leads.total + "/");
            $("#totalTrash").text(data.active_leads.trash);

            $("#coldLeads").text(data.active_leads.cold);
            $("#warmLeads").text(data.active_leads.warm);
            $("#hotLeads").text(data.active_leads.hot);

            const potentialAmountFormatted = formatRupiah(data.potential_dealing.total_amount);
            $("#potentialTotalAmount").text(potentialAmountFormatted);
            $("#potentialTotalOpportunity").text(data.potential_dealing.total_opportunity);

        } catch (error) {
            console.error("Error loading dashboard grid:", error);
        }
    }

    function formatRupiah(number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0
        }).format(number);
    }

</script>