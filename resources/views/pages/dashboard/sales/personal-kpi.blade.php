<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Personal KPI</h1>

<div class="grid grid-cols-2 2xl:grid-cols-4 gap-3 mt-2">
    
    {{-- ACHIEVEMENT VS TARGET SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">

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
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.30s;">

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

            <div class="flex items-center justify-start gap-1 mt-3">
                <p id="conversionRate">0</p>
                <p id="conversionCaption" class="text-[#1E1E1E]">Conversion from Total Active Leads</p>
            </div>
        </div>

    </div>

    {{-- TOTAL ACTIVE LEADS SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.45s;">

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
        </div>

    </div>

    {{-- POTENTIAL DEALING SECTION--}}
    <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.60s;">

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

<script>
    // LOAD GRID (PERSONAL-KPI)
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
            
            // ACHIEVEMENT VS TARGET FETCH DATA CARDS
            const achievementSalesFormatted = formatRupiah(data.achievement_target.achievement);
            
            const targetSalesFormatted = formatRupiah(data.achievement_target.target_amount);
            $("#targetSales").text(targetSalesFormatted).addClass('font-semibold text-lg lg:text-2xl text-[#1E1E1E]');
            
            if ( data.achievement_target.percentage > 70 ){
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-finish');

                $("#achievementSales").text(achievementSalesFormatted + "/").addClass('text-lg lg:text-2xl font-semibold! text-[#009951]');
            }
            else if ( data.achievement_target.percentage > 35 ){
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-waiting');
                
                $("#achievementSales").text(achievementSalesFormatted + "/").addClass('text-lg lg:text-2xl font-semibold! text-[#E8B931]');
            } else {
                $("#percentageAchievement").text(data.achievement_target.percentage + "%").addClass('font-semibold! status-expired');

                $("#achievementSales").text(achievementSalesFormatted + "/").addClass('text-lg lg:text-2xl font-semibold! text-[#900B09]');
            }

            // CLOSED DEAL (MTD) FETCH DATA CARDS
            $("#totalDeals").text(data.closed_deal.total_deals + ' Deal Leads').addClass('font-semibold text-lg lg:text-3xl text-[#1E1E1E]');

            const totalAmountFormatted = formatRupiah(data.closed_deal.total_amount);
            $("#totalAmount").text("Amount: " + totalAmountFormatted).addClass('text-xs lg:text-sm');

            if (data.closed_deal.conversion_rate > 75) {
                $("#conversionRate").text(data.closed_deal.conversion_rate + "%").addClass('font-semibold! status-finish text-xs lg:text-sm');
            } else if (data.closed_deal.conversion_rate > 35) {
                $("#conversionRate").text(data.closed_deal.conversion_rate + "%").addClass('font-semibold! status-waiting text-xs lg:text-sm');
            } else {
                $("#conversionRate").text(data.closed_deal.conversion_rate + "%").addClass('font-semibold! status-expired text-xs lg:text-sm');
            }

            // TOTAL ACTIVE LEADS FETCH DATA CARDS
            $("#totalLeads").text(data.active_leads.total).addClass('font-semibold text-[#1E1E1E] text-lg lg:text-3xl');
            $("#totalTrash").text("Trash Leads: " + data.active_leads.trash).addClass('text-xs lg:text-sm');

            $("#coldLeads").text(data.active_leads.cold + ' Cold').addClass('text-xs lg:text-sm');
            $("#warmLeads").text(data.active_leads.warm + ' Warm').addClass('text-xs lg:text-sm');
            $("#hotLeads").text((data.active_leads?.hot ?? 0) + " Hot").addClass('text-xs lg:text-sm');

            // POTENTIAL DEALING FETCH DATA CARDS
            const potentialAmountFormatted = formatRupiah(data.potential_dealing.total_amount);
            $("#potentialTotalAmount").text(potentialAmountFormatted).addClass('font-semibold text-lg lg:text-3xl text-[#1E1E1E]');
            $("#potentialTotalOpportunity").text(data.potential_dealing.total_opportunity + ' Active Opportunity').addClass('text-right text-xs lg:text-sm');

        } catch (error) {
            console.error("Error loading dashboard grid:", error);
        }
    }

</script>
