{{-- MAP PICKER MODAL --}}
<div id="map-picker-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg w-full max-w-2xl">
        <div class="flex items-center justify-between p-4 border-b border-b-[#D9D9D9]">
            <h3 class="font-semibold text-[#1E1E1E]">Pilih Lokasi</h3>
            <button type="button" id="btn-close-map-modal" class="text-[#757575] hover:text-[#1E1E1E]">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div id="map-picker" class="w-full" style="height: 400px;"></div>
        <div class="flex items-center justify-end gap-3 p-4 border-t border-t-[#D9D9D9]">
            <button type="button" id="btn-cancel-map"
                class="bg-white text-[#1E1E1E] px-5 py-2 rounded-lg border border-[#D9D9D9] hover:bg-gray-50 transition-colors">
                Batal
            </button>
            <button type="button" id="btn-confirm-map"
                class="bg-[#115640] text-white! px-5 py-2 rounded-lg hover:bg-[#0d4633] transition-colors">
                OK
            </button>
        </div>
    </div>
</div>
