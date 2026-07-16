<section {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-[#D9D9D9]']) }} aria-labelledby="customer-overview-heading">
    <div class="flex items-center justify-between p-4 pb-0">
        <div>
            <h2 id="customer-overview-heading" class="text-[#1E1E1E] font-bold text-lg">Customer Overview</h2>
            <p class="text-[#757575] text-sm">Showing active industrial installations and specifications.</p>
        </div>

        <a href="{{ route('after-sales.customers.index') }}"
            class="text-[#115640] font-medium text-sm hover:opacity-70 flex items-center gap-1 whitespace-nowrap">
            View All Projects
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    <div class="mt-4 overflow-x-auto mb-4">
        <table class="w-full m-8 border border-[#D9D9D9] rounded-lg">
            <caption class="sr-only">Recent customer installations overview</caption>
            <thead class="text-[#1E1E1E]">
                <tr class="border-b border-b-[#D9D9D9]">
                    <th scope="col" class="p-3 text-center uppercase text-xs">Customer Name</th>
                    <th scope="col" class="p-3 text-center uppercase text-xs">Telephone</th>
                    <th scope="col" class="p-3 text-center uppercase text-xs">Machine Type</th>
                    <th scope="col" class="p-3 text-center uppercase text-xs">Power (W)</th>
                    <th scope="col" class="p-3 text-center uppercase text-xs">Room Area (M2)</th>
                    <th scope="col" class="p-3 text-center uppercase text-xs">Road Width (M)</th>
                    <th scope="col" class="p-3 text-center uppercase text-xs">Actions</th>
                </tr>
            </thead>
            <tbody id="customer-overview-table-body"></tbody>
        </table>
    </div>
</section>

{{-- CUSTOMER DETAIL MODAL --}}
<div class="modal fade" id="customerDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! items-center px-6! pt-6! pb-4!">
                <div class="flex items-center gap-3">
                    <i class="bi bi-person-circle text-[#115640] text-3xl"></i>
                    <h5 class="modal-title text-[#115640] text-2xl font-bold tracking-wide mb-0">CUSTOMER DETAIL</h5>
                </div>
                <button type="button" class="close cursor-pointer opacity-100! text-[#1E1E1E]!" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-3xl font-light leading-none">&times;</span>
                </button>
            </div>
            <div class="modal-body px-6! pb-4! pt-0!">
                <div id="customer-overview-detail-body" class="grid grid-cols-2 gap-x-10 gap-y-4 text-[#1E1E1E]">
                    <p class="col-span-2 text-center text-[#757575]">Loading...</p>
                </div>

                <div class="mt-6">
                    <h6 class="text-[#115640] font-bold uppercase text-sm tracking-wide mb-3">Upload File CAD Design</h6>

                    <div id="customer-overview-cad-dropzone"
                        class="border-2 border-dashed border-[#115640] rounded-lg py-10 px-10 text-center cursor-pointer hover:bg-[#F5FAF8] transition-colors">
                        <i class="bi bi-upload text-2xl text-[#1E1E1E] mt-2 inline-block"></i>
                        <p class="font-semibold text-[#1E1E1E] mt-3 mb-1">Select a file or drag and drop it here.</p>
                        <p class="text-[#757575] text-sm mb-4">DWG, DFX, and ZIP formats. Maximum 10 MB</p>
                        <button type="button" id="customer-overview-cad-browse-btn"
                            class="border border-[#D9D9D9] rounded-lg px-4 py-1.5 bg-white hover:bg-gray-50 cursor-pointer mb-2">Browse
                            Files</button>
                        <input type="file" id="customer-overview-cad-input" name="file_cad[]" multiple
                            accept=".dwg,.dxf,.zip" class="hidden">
                    </div>

                    <div id="customer-overview-cad-list" class="mt-4 flex flex-col gap-3"></div>
                </div>
            </div>
            <div class="modal-footer border-0! px-6 pb-6 pt-2">
                <button type="button" id="customer-overview-cad-save-btn" data-dismiss="modal"
                    class="bg-[#115640] text-white px-4 py-1.5 rounded-lg hover:bg-[#0d4633] transition-colors cursor-pointer">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
