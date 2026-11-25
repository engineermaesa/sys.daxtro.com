<style>
    #spv-dashboard .spv-heatmap-scroll { overflow-x: auto; }
    #spv-dashboard .spv-heatmap-layout { display: grid; grid-template-columns: minmax(230px, 1fr) minmax(0, 1.85fr); gap: 48px; min-width: 680px; }
    #spv-dashboard .spv-heatmap-heading { display: grid; gap: 6px; height: 24px; margin-bottom: 8px; align-items: center; text-align: center; font-size: 11px; color: #9AA39E; }
    #spv-dashboard .spv-heatmap-heading span { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    #spv-dashboard .spv-heatmap-rows { display: grid; gap: 6px; }
    #spv-dashboard .spv-heatmap-row { display: grid; gap: 6px; height: 36px; }
    #spv-dashboard .spv-problem-row { display: flex; gap: 16px; height: 36px; align-items: center; }
    #spv-dashboard .spv-problem-label { width: 144px; flex-shrink: 0; color: #2B3441; font-size: 14px; font-weight: 500; }
    #spv-dashboard .spv-problem-track { flex: 1; min-width: 0; }
    #spv-dashboard .spv-problem-bar { height: 20px; border-radius: 999px; }
    #spv-dashboard .spv-heatmap-cell { width: 100%; height: 36px; border: 0; border-radius: 5px; padding: 0; cursor: pointer; }
    #spv-dashboard .spv-heatmap-cell:hover { filter: brightness(.94); }
    #spv-dashboard .spv-heatmap-cell:focus-visible { outline: 2px solid #14573F; outline-offset: 2px; }
    #spv-dashboard .spv-heatmap-legend { display: flex; align-items: center; justify-content: flex-end; gap: 5px; margin-top: 24px; color: #9AA39E; font-size: 11px; }
    #spv-dashboard .spv-heatmap-swatch { width: 16px; height: 10px; border-radius: 3px; }
    .spv-drill-sheet { position: fixed; inset: 0; z-index: 2000; display: flex; flex-direction: column; background: #F7F8F7; overflow: hidden; }
    .spv-drill-head { padding: 12px 16px; background: #fff; border-bottom: 1px solid #D9D9D9; }
    .spv-drill-close { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #D9D9D9; border-radius: 8px; background: #fff; font-weight: 600; color: #115640; cursor: pointer; }
    .spv-drill-title { margin-top: 10px; font-size: 18px; font-weight: 700; color: #115640; overflow-wrap: anywhere; }
    .spv-drill-sub { font-size: 12px; color: #757575; }
    .spv-drill-body { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 12px 16px; display: grid; gap: 10px; align-content: start; }
    .spv-drill-card { display: block; padding: 12px 14px; background: #fff; border: 1px solid #D9D9D9; border-radius: 10px; color: inherit; text-decoration: none; }
    .spv-drill-card:active { background: #F0F5F2; }
    .spv-drill-code { font-weight: 700; color: #115640; margin-bottom: 6px; overflow-wrap: anywhere; }
    .spv-drill-card dl { display: grid; grid-template-columns: 88px minmax(0, 1fr); gap: 4px 10px; margin: 0; font-size: 13px; }
    .spv-drill-card dt { color: #757575; font-weight: 400; }
    .spv-drill-card dd { margin: 0; color: #1E1E1E; overflow-wrap: anywhere; }
    .spv-drill-empty { text-align: center; color: #757575; padding: 24px 0; }
    .spv-drill-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 16px; background: #fff; border-top: 1px solid #D9D9D9; font-size: 12px; color: #757575; }
    .spv-drill-foot button { padding: 6px 14px; border: 1px solid #D9D9D9; border-radius: 8px; background: #fff; color: #115640; font-weight: 600; cursor: pointer; }
    .spv-drill-foot button:disabled { opacity: .45; cursor: not-allowed; }
    @media (max-width: 767px) {
        #spv-dashboard .spv-heatmap-layout { gap: 24px; }
        #spv-dashboard .spv-problem-label { width: 112px; }
    }
</style>
<div class="spv-heatmap-scroll" role="region" aria-label="Reported problems heatmap" tabindex="0">
    <div class="spv-heatmap-layout">
        <div>
            <div class="spv-heatmap-heading" aria-hidden="true"></div>
            <div id="spv-problem-bars" class="spv-heatmap-rows"></div>
        </div>
        <div>
            <div id="spv-heatmap-head" class="spv-heatmap-heading"></div>
            <div id="spv-heatmap-body" class="spv-heatmap-rows"></div>
            <div class="spv-heatmap-legend" aria-label="Ticket count from few to many">
                <span>Few tickets</span>
                @foreach(['#E6F1EC', '#BFDCCD', '#8FBFA8', '#4F9374', '#14573F'] as $c)
                    <span class="spv-heatmap-swatch" style="background:{{ $c }}" aria-hidden="true"></span>
                @endforeach
                <span>Many tickets</span>
            </div>
        </div>
    </div>
</div>
