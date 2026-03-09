<div class="bg-[#F1F6FD] text-[#183057] p-3 rounded-lg border border-[#183057] flex items-center justify-start gap-3 mt-5 mb-5">
    <div class="w-5 h-5 flex items-center justify-center 
        border-2 border-[#183057] text-[#183057] text-xs rounded-full cursor-pointer font-semibold">
        i
    </div>
    <div class="text-[#183057]">
        <h1 class="font-semibold uppercase text-lg">Summary</h1>
        <div class="flex items-center gap-10 mt-3">
            <p>Telpon Pertama : <span id="firstCall">0</span></p>
            <p>Quotation Sent : <span id="quotationSent">0</span></p>
        </div>
        <p class="mt-1">Visit Scheduled : <span id="visitScheduled">0</span></p>
        <span class="w-full bg-[#183057] h-[1px] block my-3"></span>
        <p>Status: Slightly Behind Target (Need +30M more to achieve target)</p>
    </div>
</div>

<script>
    // LOAD SUMMARY
    async function loadSummary(){
        try {
            const response = await fetch("/api/leads/summary");
            
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }

            const result = await response.json();

            if (result.status !== "success") {
                throw new Error("API returned failed status");
            }

            const data = result.Data;
            
            $("#firstCall").text(data.telpon_pertama);
            $("#quotationSent").text(data.quotation_sent);
            $("#visitScheduled").text(data.visi_scheduled);

        } catch (error) {
            console.error("Error loading summary:", error);
        }
    }
</script>