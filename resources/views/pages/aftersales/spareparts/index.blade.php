@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-[#115640] font-semibold text-2xl">Sparepart</h1>
                <p class="text-[#757575] mt-1">Manage sparepart inventory, suppliers, and stock levels.</p>
            </div>
            <a href="{{ route('aftersales.pages.spareparts.create') }}" class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Sparepart
            </a>
        </div>

        {{--SEARCH--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex items-stretch gap-3">
                <div class="flex-1 relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="sparepart-search" placeholder="Search sparepart name, part number, or brand"
                    class="w-full pl-10 pr-3 py-2 border border-[#D9D9D9] rounded-lg! text-left text-[#1E1E1E] focus:outline-none!">
                </div>
                <button type="button" id="btn-search-sparepart"
                class="bg-[#115640] text-white px-6 rounded-lg hover:bg-[#0d4633] transition-colors">
                    Search
                </button>
            </div>
        </div>

        {{--TABLE--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-left uppercase text-xs">Sparepart Name</th>
                        <th class="p-3 text-left uppercase text-xs">Brand</th>
                        <th class="p-3 text-left uppercase text-xs">Supplier</th>
                        <th class="p-3 text-left uppercase text-xs">Price (IDR)</th>
                        <th class="p-3 text-left uppercase text-xs">Stock</th>
                        <th class="p-3 text-left uppercase text-xs">Min Stock</th>
                        <th class="p-3 text-left uppercase text-xs">Rack Location</th>
                        <th class="p-3 text-left uppercase text-xs">Stock Status</th>
                        <th class="p-3 text-left uppercase text-xs">Action</th>
                    </tr>
                </thead>
                <tbody id="sparepart-table-body" class="text-left"></tbody>
            </table>
        </div>

        {{--PAGINATION--}}
        <div class="flex items-center justify-between mt-4">
            <div id="sparepart-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
            <div id="sparepart-pagination" class="flex items-center gap-2"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const sparepartState = {
        page: 1,
        lastPage: 1,
        perPage: 10,
        search: '',
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatCurrency(value) {
        const number = Number(value || 0);
        return 'Rp' + number.toLocaleString('id-ID');
    }

    function stockStatusBadge(status) {
        const isLow = status === 'REORDER';
        const cls = isLow ? 'bg-[#FFF3CD] text-[#8A6D00]' : 'bg-[#E6F4EF] text-[#115640]';
        const label = isLow ? 'Low Stock' : 'Available';
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium ${cls}">${label}</span>`;
    }

    function renderSparepartRows(rows) {
        const $body = $('#sparepart-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="9" class="text-center p-4 text-[#757575]">No spareparts found</td></tr>');
            return;
        }

        const html = rows.map(row => `
            <tr class="border-t border-t-[#D9D9D9] align-top" data-sparepart-id="${row.id}">
                <td class="p-3 font-semibold text-[#1E1E1E]">${escapeHtml(row.name)}</td>
                <td class="p-3">${escapeHtml(row.brand || '-')}</td>
                <td class="p-3">${escapeHtml(row.supplier || '-')}</td>
                <td class="p-3 font-semibold">${formatCurrency(row.price)}</td>
                <td class="p-3 font-semibold">${row.stock ?? 0}</td>
                <td class="p-3">${row.min_stock ?? 0}</td>
                <td class="p-3">${escapeHtml(row.rack_location || '-')}</td>
                <td class="p-3">${stockStatusBadge(row.stock_status)}</td>
                <td class="p-3">
                    <div class="dropdown">
                        <button class="btn-sparepart-action w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white cursor-pointer" type="button" data-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li><a class="dropdown-item btn-sparepart-edit" href="/aftersales/spareparts/create?id=${row.id}">Edit Details</a></li>
                            <li><a class="dropdown-item btn-sparepart-delete text-[#900B09]" href="javascript:void(0)" data-id="${row.id}">Delete</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');

        $body.html(html);
    }

    function renderSparepartPagination(pagination) {
        pagination = pagination || {};
        sparepartState.lastPage = pagination.last_page || 1;

        const from = pagination.total ? ((pagination.current_page - 1) * sparepartState.perPage) + 1 : 0;
        const to = Math.min(pagination.current_page * sparepartState.perPage, pagination.total || 0);
        $('#sparepart-showing').text(`Showing ${from} to ${to} of ${pagination.total || 0} entries`);

        const current = pagination.current_page || 1;
        const last = pagination.last_page || 1;
        let buttons = `<button class="w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white" ${current <= 1 ? 'disabled' : ''} data-page="${current - 1}">
            <i class="bi bi-chevron-left"></i>
        </button>`;

        for (let i = 1; i <= last; i++) {
            buttons += `<button class="w-8 h-8 p-2 rounded-lg border border-[#D9D9D9] ${i === current ? 'bg-grey-300 text-primary' : 'bg-white'}" data-page="${i}">${i}</button>`;
        }

        buttons += `<button class="w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white" ${current >= last ? 'disabled' : ''} data-page="${current + 1}">
            <i class="bi bi-chevron-right"></i>
        </button>`;

        $('#sparepart-pagination').html(buttons);
    }

    function loadSpareparts() {
        $('#sparepart-table-body').html('<tr><td colspan="9" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: '/api/aftersales/spareparts',
            method: 'GET',
            data: {
                page: sparepartState.page,
                per_page: sparepartState.perPage,
                search: sparepartState.search,
            },
            success: function (response) {
                renderSparepartRows(response.data);
                renderSparepartPagination(response);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load spareparts';
                $('#sparepart-table-body').html(`<tr><td colspan="9" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
            }
        });
    }

    function deleteSparepart(id) {
        $.ajax({
            url: `/api/aftersales/spareparts/${id}`,
            method: 'DELETE',
            success: function () {
                loadSpareparts();
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to delete sparepart';
                alert(message);
            }
        });
    }

    $(function () {
        loadSpareparts();

        let searchTimer = null;
        $('#sparepart-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                sparepartState.search = $('#sparepart-search').val();
                sparepartState.page = 1;
                loadSpareparts();
            }, 400);
        });

        $('#btn-search-sparepart').on('click', function () {
            sparepartState.search = $('#sparepart-search').val();
            sparepartState.page = 1;
            loadSpareparts();
        });

        $(document).on('click', '#sparepart-pagination button', function () {
            const page = Number($(this).data('page'));
            if (!page || page < 1 || page > sparepartState.lastPage) return;
            sparepartState.page = page;
            loadSpareparts();
        });

        $(document).on('click', '.btn-sparepart-delete', function () {
            const id = $(this).data('id');
            if (confirm('Delete this sparepart?')) {
                deleteSparepart(id);
            }
        });
    });
</script>
@endsection
