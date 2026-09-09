@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"
                            fill="#115640" />
                    </svg>
                    <h1 class="text-[#115640] font-semibold text-2xl">Customer</h1>
                </div>
                <p class="text-[#757575] mt-1">Manage and monitor your customers' complete information here.</p>
            </div>
            <a href="{{ route('aftersales.pages.customers.create') }}"
                class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors">
                <i class="bi bi-plus-lg"></i> Add Customer
            </a>
        </div>

        {{-- SEARCH --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex items-stretch gap-3">
                <div class="flex-1 relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="customer-search" placeholder="Search customer name, phone, or region"
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
                        <th class="p-3 text-left uppercase text-xs font-bold">No.</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Customer Name</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">PIC</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Contact</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Region</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Province</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Action</th>
                    </tr>
                </thead>
                <tbody id="customer-table-body"></tbody>
            </table>

            {{-- NAVIGATION ROW --}}
            <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                <div class="flex items-center gap-3">
                    <p class="font-semibold">Show Rows</p>
                    <select id="customer-page-size" class="w-auto bg-white font-semibold p-2 rounded-md">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div id="customer-showing" class="font-semibold">Showing 0-0 of 0</div>
                    <div>
                        <button id="customer-prev-btn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                            <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                        </button>
                        <button id="customer-next-btn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                            <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const customerState = {
        page: 1,
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

    function waNumber(raw) {
        let digits = String(raw ?? '').replace(/\D/g, '');
        if (!digits) return null;
        if (digits.startsWith('0')) digits = '62' + digits.slice(1);
        else if (!digits.startsWith('62')) digits = '62' + digits;
        return digits;
    }

    function formatPhoneDisplay(waDigits) {
        const rest = waDigits.slice(2);
        const groups = rest.match(/.{1,4}/g) || [rest];
        return `+62 ${groups.join('-')}`;
    }

    function renderContact(rawPhone) {
        const wa = waNumber(rawPhone);
        if (!wa) return '-';

        const display = escapeHtml(formatPhoneDisplay(wa));
        return `<a href="https://wa.me/${wa}" target="_blank" rel="noopener" class="underline">${display}</a>`;
    }

    function renderCustomerRows(rows) {
        const $body = $('#customer-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="7" class="text-center p-4 text-[#757575]">No customers found</td></tr>');
            return;
        }

        const startNumber = (customerState.page - 1) * customerState.perPage;

        const html = rows.map((row, index) => `
            <tr class="border-t border-t-[#D9D9D9]">
                <td class="p-3 text-center text-black">${startNumber + index + 1}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.name)}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.pic_name) || '-'}</td>
                <td class="p-3 text-left text-black">${renderContact(row.phone)}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.region?.name) || '-'}</td>
                <td class="p-3 text-left text-black">${escapeHtml(row.province?.name) || '-'}</td>
                <td class="p-3 text-center text-black">
                    <div class="dropdown">
                        <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"
                            type="button" id="customerActionsDropdown${row.id}"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="customerActionsDropdown${row.id}">
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer" href="/aftersales/customers/${row.id}/machines">
                                <i class="bi bi-box-seam"></i> Machine
                            </a>
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer" href="/aftersales/customers/${row.id}/tickets">
                                <i class="bi bi-ticket-perforated"></i> Ticket
                            </a>
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer" href="/aftersales/customers/${row.id}/edit">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');

        $body.html(html);
    }

    function renderCustomerPagination(pagination) {
        pagination = pagination || {};

        const totalItems = pagination.total || 0;
        const current = pagination.current_page || 1;
        customerState.lastPage = Math.max(1, pagination.last_page || 1);

        const from = totalItems === 0 ? 0 : (current - 1) * customerState.perPage + 1;
        const to = Math.min(totalItems, current * customerState.perPage);

        $('#customer-showing').text(`Showing ${from}-${to} of ${totalItems}`);
        $('#customer-prev-btn').prop('disabled', current <= 1);
        $('#customer-next-btn').prop('disabled', current >= customerState.lastPage);
    }

    function loadCustomers() {
        $('#customer-table-body').html('<tr><td colspan="7" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: '/api/aftersales/customers',
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
                $('#customer-table-body').html(`<tr><td colspan="7" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
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

        $('#customer-page-size').on('change', function () {
            customerState.perPage = parseInt($(this).val(), 10) || 10;
            customerState.page = 1;
            loadCustomers();
        });

        $('#customer-prev-btn').on('click', function () {
            if (customerState.page > 1) {
                customerState.page -= 1;
                loadCustomers();
            }
        });

        $('#customer-next-btn').on('click', function () {
            if (customerState.page < customerState.lastPage) {
                customerState.page += 1;
                loadCustomers();
            }
        });
    });
</script>
@endsection
