{{-- Helper ApexCharts bersama dashboard aftersales. Di-include sekali per halaman (@once). --}}
@once
@push('scripts')
<script>
window.AS = window.AS || (function () {
    const colors = { green: '#14573F', sage: '#7FAF9A', sageLight: '#9CC4B2', gold: '#D9B65B', grey: '#A9B8B0', track: '#E6EBE8' };
    const baseChart = { toolbar: { show: false }, fontFamily: 'inherit', animations: { speed: 400 }, parentHeightOffset: 0 };
    const charts = {};
    // Destroy chart lama sebelum render supaya tidak dobel saat data/filter berubah.
    function mount(id, opts) {
        charts[id]?.destroy();
        charts[id] = new ApexCharts(document.getElementById(id), opts);
        charts[id].render();
    }
    return { colors, baseChart, mount };
})();
</script>
@endpush
@endonce
