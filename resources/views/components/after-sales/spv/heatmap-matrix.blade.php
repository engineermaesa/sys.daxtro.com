{{-- Diisi via JS (renderHeatmap). Kolom = machine group, baris = problem category. --}}
<div class="grid md:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] gap-x-8 gap-y-6">
    {{-- Kiri: bar per kategori. Spacer h-6 menyamakan tinggi dengan header kolom heatmap supaya baris sejajar. --}}
    <div>
        <div class="h-6 mb-2 max-md:hidden"></div>
        <div id="spv-problem-bars" class="space-y-2"></div>
    </div>

    {{-- Kanan: heatmap --}}
    <div class="overflow-x-auto">
        <div class="min-w-[460px] md:min-w-0">
            <div id="spv-heatmap-head" class="grid gap-2 mb-2 h-6 items-center text-[11px] whitespace-nowrap text-[#9AA39E] text-center"></div>
            <div id="spv-heatmap-body" class="space-y-2"></div>
            <div class="flex items-center justify-end gap-1.5 mt-4 text-[11px] text-[#9AA39E]">
                <span>Few tickets</span>
                @foreach(['#E6F1EC', '#BFDCCD', '#8FBFA8', '#4F9374', '#14573F'] as $c)
                    <span class="w-4 h-3 rounded-sm" style="background:{{ $c }}"></span>
                @endforeach
                <span>Many tickets</span>
            </div>
        </div>
    </div>
</div>
