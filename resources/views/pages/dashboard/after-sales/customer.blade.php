@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center gap-3">
            <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V2C0 1.45 0.195833 0.979167 0.5875 0.5875C0.979167 0.195833 1.45 0 2 0H11.175C11.4417 0 11.6958 0.0500001 11.9375 0.15C12.1792 0.25 12.3917 0.391667 12.575 0.575L17.425 5.425C17.6083 5.60833 17.75 5.82083 17.85 6.0625C17.95 6.30417 18 6.55833 18 6.825V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2ZM11 6V2H2V18H16V7H12C11.7167 7 11.4792 6.90417 11.2875 6.7125C11.0958 6.52083 11 6.28333 11 6ZM5 15H13C13.2833 15 13.5208 14.9042 13.7125 14.7125C13.9042 14.5208 14 14.2833 14 14C14 13.7167 13.9042 13.4792 13.7125 13.2875C13.5208 13.0958 13.2833 13 13 13H5C4.71667 13 4.47917 13.0958 4.2875 13.2875C4.09583 13.4792 4 13.7167 4 14C4 14.2833 4.09583 14.5208 4.2875 14.7125C4.47917 14.9042 4.71667 15 5 15ZM5 11H13C13.2833 11 13.5208 10.9042 13.7125 10.7125C13.9042 10.5208 14 10.2833 14 10C14 9.71667 13.9042 9.47917 13.7125 9.2875C13.5208 9.09583 13.2833 9 13 9H5C4.71667 9 4.47917 9.09583 4.2875 9.2875C4.09583 9.47917 4 9.71667 4 10C4 10.2833 4.09583 10.5208 4.2875 10.7125C4.47917 10.9042 4.71667 11 5 11Z"
                    fill="#115640" />
            </svg>
            <h1 class="text-[#115640] font-semibold text-2xl">Customer</h1>
        </div>
        <p class="text-[#757575] mt-1">Manage and monitor your customers' complete information here.</p>

        {{-- SEARCH --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex items-stretch gap-3">
                <div class="flex-1 relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="customer-search" placeholder="Search customer name, phone, or machine type"
                        class="w-full pl-10 pr-3 py-2 border border-[#D9D9D9] rounded-lg! text-left text-[#1E1E1E] focus:outline-none!">
                </div>
                <button type="button" id="btn-search-customer"
                    class="bg-[#115640] text-white px-6 rounded-lg hover:bg-[#0d4633] transition-colors">
                    Search
                </button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-center uppercase text-xs">No.</th>
                        <th class="p-3 text-center uppercase text-xs">Customer Name</th>
                        <th class="p-3 text-center uppercase text-xs">Telephone</th>
                        <th class="p-3 text-center uppercase text-xs">Machine Type</th>
                        <th class="p-3 text-center uppercase text-xs">Power (W)</th>
                        <th class="p-3 text-center uppercase text-xs">Room Area (m2)</th>
                        <th class="p-3 text-center uppercase text-xs">Road Width (M)</th>
                        <th class="p-3 text-center uppercase text-xs">Actions</th>
                    </tr>
                </thead>
                <tbody id="customer-table-body" class="text-center"></tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="flex items-center justify-between mt-4">
            <div id="customer-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
            <div id="customer-pagination" class="flex items-center gap-2"></div>
        </div>
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
                <div id="customer-detail-body" class="grid grid-cols-2 gap-x-10 gap-y-4 text-[#1E1E1E]">
                    <p class="col-span-2 text-center text-[#757575]">Loading...</p>
                </div>

                <div class="mt-6">
                    <h6 class="text-[#115640] font-bold uppercase text-sm tracking-wide mb-3">Upload File CAD Design</h6>

                    <div id="customer-cad-dropzone"
                        class="border-2 border-dashed border-[#115640] rounded-lg py-10 px-10 text-center cursor-pointer hover:bg-[#F5FAF8] transition-colors">
                        <i class="bi bi-upload text-2xl text-[#1E1E1E] mt-2 inline-block"></i>
                        <p class="font-semibold text-[#1E1E1E] mt-3 mb-1">Select a file or drag and drop it here.</p>
                        <p class="text-[#757575] text-sm mb-4">DWG, DFX, and ZIP formats. Maximum 10 MB</p>
                        <button type="button" id="customer-cad-browse-btn"
                            class="border border-[#D9D9D9] rounded-lg px-4 py-1.5 bg-white hover:bg-gray-50 cursor-pointer mb-2">Browse
                            Files</button>
                        <input type="file" id="customer-cad-input" name="file_cad[]" multiple
                            accept=".dwg,.dxf,.zip" class="hidden">
                    </div>

                    <div id="customer-cad-list" class="mt-4 flex flex-col gap-3"></div>
                </div>
            </div>
            <div class="modal-footer border-0! px-6 pb-6 pt-2">
                <button type="button" id="customer-cad-save-btn" data-dismiss="modal"
                    class="bg-[#115640] text-white px-4 py-1.5 rounded-lg hover:bg-[#0d4633] transition-colors cursor-pointer">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const customerState = {
        page: 1,
        lastPage: 1,
        perPage: 10,
        search: '',
    };

    let currentDetailUrl = null;
    let currentUploadUrl = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatFileSize(bytes) {
        if (!bytes && bytes !== 0) return '';
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    function fileIconClass(name) {
        const ext = (name.split('.').pop() || '').toLowerCase();
        if (ext === 'zip') return 'bi-file-earmark-zip';
        return 'bi-file-earmark-text';
    }

    function renderCustomerRows(rows) {
        const $body = $('#customer-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="8" class="text-center p-4 text-[#757575]">No customers found</td></tr>');
            return;
        }

        const startNumber = (customerState.page - 1) * customerState.perPage;

        const html = rows.map((row, index) => `
            <tr class="border-t border-t-[#D9D9D9]">
                <td class="p-3">${startNumber + index + 1}</td>
                <td class="p-3">${escapeHtml(row.customer_name)}</td>
                <td class="p-3">${escapeHtml(row.telephone)}</td>
                <td class="p-3">${escapeHtml(row.machine_type)}</td>
                <td class="p-3">${row.power_watts ? escapeHtml(row.power_watts) + 'W' : '-'}</td>
                <td class="p-3">${row.room_area_m2 ? escapeHtml(row.room_area_m2) + ' m2' : '-'}</td>
                <td class="p-3">${row.road_width_m ? escapeHtml(row.road_width_m) + ' m' : '-'}</td>
                <td class="p-3 text-center">${row.actions || ''}</td>
            </tr>
        `).join('');

        $body.html(html);
    }

    function renderCustomerPagination(pagination) {
        pagination = pagination || {};
        customerState.lastPage = pagination.last_page || 1;

        const from = pagination.total ? ((pagination.current_page - 1) * customerState.perPage) + 1 : 0;
        const to = Math.min(pagination.current_page * customerState.perPage, pagination.total || 0);
        $('#customer-showing').text(`Showing ${from} to ${to} of ${pagination.total || 0} entries`);

        const current = pagination.current_page || 1;
        const last = pagination.last_page || 1;
        let buttons = `<button class="w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white" ${current <= 1 ? 'disabled' : ''} data-page="${current - 1}">
            <i class="bi bi-chevron-left"></i>
        </button>`;

        const windowSize = 10;
        const windowStart = Math.floor((current - 1) / windowSize) * windowSize + 1;
        const windowEnd = Math.min(windowStart + windowSize - 1, last);

        for (let i = windowStart; i <= windowEnd; i++) {
            buttons += `<button class="w-8 h-8 p-2 rounded-lg border border-[#D9D9D9] ${i === current ? 'bg-grey-300 text-primary' : 'bg-white'}" data-page="${i}">${i}</button>`;
        }

        buttons += `<button class="w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white" ${current >= last ? 'disabled' : ''} data-page="${current + 1}">
            <i class="bi bi-chevron-right"></i>
        </button>`;

        $('#customer-pagination').html(buttons);
    }

    function loadCustomers() {
        $('#customer-table-body').html('<tr><td colspan="8" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: '{{ route('after-sales.customers.list') }}',
            method: 'GET',
            data: {
                page: customerState.page,
                per_page: customerState.perPage,
                search: customerState.search,
            },
            success: function (response) {
                renderCustomerRows(response.data);
                renderCustomerPagination(response);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load customers';
                $('#customer-table-body').html(`<tr><td colspan="8" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
            }
        });
    }

    function loadCustomerDetail(url) {
        currentDetailUrl = url;

        $('#customer-detail-body').html('<p class="col-span-2 text-center text-[#757575]">Loading...</p>');
        $('#customer-cad-list').html('');
        $('#customerDetailModal').modal('show');

        $.get(url, function (response) {
            const c = response.customer;
            $('#customer-detail-body').html(`
                <div class="border-b border-[#D9D9D9] mb-2 pb-2">
                    <label class="uppercase text-[#115640] text-base font-bold tracking-wide mb-0">Customer Information</label>
                </div>
                <div class="border-b border-[#D9D9D9] mb-2 pb-2">
                    <label class="uppercase text-[#115640] text-base font-bold tracking-wide mb-0">Technical Specifications</label>
                </div>

                <div>
                    <label class="uppercase text-[11px] text-[#757575] font-medium">Contact Person</label>
                    <p class="font-semibold pb-2">${escapeHtml(c.contact_person)}</p>
                </div>
                <div>
                    <label class="uppercase text-[11px] text-[#757575]">Machine Type</label>
                    <p class="font-semibold">${escapeHtml(c.machine_type)}</p>
                </div>

                <div>
                    <label class="uppercase text-[11px] text-[#757575]">Phone Number</label>
                    <p class="font-semibold">${escapeHtml(c.phone_number)}</p>
                </div>
                <div>
                    <label class="uppercase text-[11px] text-[#757575] font-medium">Power Requirements</label>
                    <p class="font-medium">${c.power_watts ? escapeHtml(c.power_watts) + ' W' : '-'}</p>
                </div>

                <div>
                    <label class="uppercase text-[11px] text-[#757575] font-medium">Site Address</label>
                    <p class="font-semibold">${escapeHtml(c.site_address)}</p>
                    ${c.location_link ? `<a href="${c.location_link}" target="_blank" class="text-[#115640] text-xs text-sm hover:opacity-50">View Detail</a>` : ''}
                </div>
                <div>
                    <label class="uppercase text-[11px] text-[#757575] font-medium">Site Dimensions</label>
                    <p class="font-semibold">${c.building_area_m2 ? escapeHtml(c.building_area_m2) + ' m2' : '-'}${c.road_width_m ? ' (Road: ' + escapeHtml(c.road_width_m) + ' m)' : ''}</p>
                </div>
            `);
            renderCadList(c.file_cad);
        });
    }

    function renderCadList(files) {
        const $list = $('#customer-cad-list');

        if (!files || files.length === 0) {
            $list.html('');
            return;
        }

        const html = files.map(file => `
            <div class="flex items-center justify-between border border-[#D9D9D9] rounded-lg p-3">
                <div class="flex items-center gap-3">
                    <i class="bi ${fileIconClass(file.name)} text-2xl text-[#900B09]"></i>
                    <div>
                        <p class="font-medium mb-0">${escapeHtml(file.name)}</p>
                        <p class="text-xs text-[#757575] mb-0">
                            ${formatFileSize(file.size)}
                            <span class="text-[#115640]"><i class="bi bi-check-circle-fill"></i> Complete</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-cad-delete border border-[#900B09] text-[#900B09]! rounded-lg px-3 py-1 cursor-pointer hover:bg-[#FBEAEA]"
                        data-path="${escapeHtml(file.path)}">Delete</button>
                    <a href="${file.url}" target="_blank"
                        class="btn-cad-preview bg-[#115640] text-white rounded-lg px-3 py-1 hover:bg-[#0d4633]">Preview</a>
                </div>
            </div>
        `).join('');

        $list.html(html);
    }

    function uploadCadFiles(files) {
        if (!currentUploadUrl || !files || files.length === 0) return;

        const formData = new FormData();
        Array.from(files).forEach(file => formData.append('file_cad[]', file));

        $.ajax({
            url: currentUploadUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function () {
                notif('CAD file(s) uploaded successfully!');
                loadCustomerDetail(currentDetailUrl);
            },
            error: function (xhr) {
                notif(xhr.responseJSON?.message || 'Failed to upload CAD file', 'error');
            }
        });
    }

    function deleteCadFile(path) {
        if (!currentUploadUrl) return;

        const formData = new FormData();
        formData.append('remove_file_cad[]', path);

        $.ajax({
            url: currentUploadUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function () {
                notif('CAD file deleted successfully!');
                loadCustomerDetail(currentDetailUrl);
            },
            error: function (xhr) {
                notif(xhr.responseJSON?.message || 'Failed to delete CAD file', 'error');
            }
        });
    }

    $(function () {
        loadCustomers();

        let searchTimer = null;
        $('#customer-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                customerState.search = $('#customer-search').val();
                customerState.page = 1;
                loadCustomers();
            }, 400);
        });

        $('#btn-search-customer').on('click', function () {
            customerState.search = $('#customer-search').val();
            customerState.page = 1;
            loadCustomers();
        });

        $(document).on('click', '#customer-pagination button', function () {
            const page = Number($(this).data('page'));
            if (!page || page < 1 || page > customerState.lastPage) return;
            customerState.page = page;
            loadCustomers();
        });

        $(document).on('click', '.btn-customer-detail', function (e) {
            e.preventDefault();

            currentUploadUrl = $(this).data('upload-url');
            loadCustomerDetail($(this).data('url'));
        });

        $('#customer-cad-dropzone').on('click', function () {
            document.getElementById('customer-cad-input').click();
        });

        $('#customer-cad-browse-btn').on('click', function (e) {
            e.stopPropagation();
            document.getElementById('customer-cad-input').click();
        });

        $('#customer-cad-input').on('change', function () {
            uploadCadFiles(this.files);
            $(this).val('');
        });

        $('#customer-cad-save-btn').on('click', function () {
            $('#customerDetailModal').modal('hide');
        });

        const $dropzone = $('#customer-cad-dropzone');
        $dropzone.on('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dropzone.addClass('bg-[#F5FAF8]');
        });
        $dropzone.on('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dropzone.removeClass('bg-[#F5FAF8]');
        });
        $dropzone.on('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dropzone.removeClass('bg-[#F5FAF8]');
            const files = e.originalEvent.dataTransfer?.files;
            if (files && files.length) uploadCadFiles(files);
        });

        $(document).on('click', '.btn-cad-delete', function () {
            const path = $(this).data('path');
            if (path) deleteCadFile(path);
        });
    });
</script>
@endsection
