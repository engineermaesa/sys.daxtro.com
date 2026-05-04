@extends('layouts.app')

@section('content')
<section class="min-h-screen text-xs! lg:text-sm!">
    <div class="pt-4">
        <div class="flex items-center gap-3 text-[#115640]">
            <x-icon.account/>
            <h1 class="font-semibold text-lg lg:text-2xl">Accounts</h1>
        </div>
        <p class="mt-1 text-[#115640] text-sm lg:text-lg">All Accounts</p>
    </div>

    <div class="mt-4 rounded-lg border border-[#D9D9D9]">
        <div class="bg-white border-b border-[#D9D9D9] p-3 rounded-tr-lg rounded-tl-lg">
            <div class="flex justify-between items-center gap-3">
                <div class="border border-gray-300 rounded-lg flex items-center p-2 h-full w-1/4">
                    <i class="fas fa-search text-[#6B7786] px-2"></i>
                    <input id="accountsSearchInput" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640]" />
                </div>
                <a href="{{ route('masters.accounts.form') }}" class="bg-[#115640] rounded-lg w-1/6 flex justify-center items-center gap-3 px-5 py-3">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6 8H1C0.716667 8 0.479167 7.90417 0.2875 7.7125C0.0958333 7.52083 0 7.28333 0 7C0 6.71667 0.0958333 6.47917 0.2875 6.2875C0.479167 6.09583 0.716667 6 1 6H6V1C6 0.716667 6.09583 0.479167 6.2875 0.2875C6.47917 0.0958333 6.71667 0 7 0C7.28333 0 7.52083 0.0958333 7.7125 0.2875C7.90417 0.479167 8 0.716667 8 1V6H13C13.2833 6 13.5208 6.09583 13.7125 6.2875C13.9042 6.47917 14 6.71667 14 7C14 7.28333 13.9042 7.52083 13.7125 7.7125C13.5208 7.90417 13.2833 8 13 8H8V13C8 13.2833 7.90417 13.5208 7.7125 13.7125C7.52083 13.9042 7.28333 14 7 14C6.71667 14 6.47917 13.9042 6.2875 13.7125C6.09583 13.5208 6 13.2833 6 13V8Z"
                            fill="#FFFFFF" />
                    </svg>
                    <p class="text-white font-medium">New Account</p>
                </a>
            </div>
        </div>

        <div class="accounts-table-container">
            <div class="max-xl:overflow-x-scroll">
                <table id="accountsTableNew" class="w-full bg-white">
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#D9D9D9]">
                            <th class="p-1 lg:p-3">#</th>
                            <th class="p-1 lg:p-3">Company</th>
                            <th class="p-1 lg:p-3">Bank</th>
                            <th class="p-1 lg:p-3">Account Number</th>
                            <th class="p-1 lg:p-3">Holder Name</th>
                            <th class="p-1 lg:p-3">Created At</th>
                            <th class="p-1 lg:p-3">Updated At</th>
                            <th class="p-1 lg:p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="accountsBodyTable"></tbody>
                </table>
            </div>

            <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! rounded-b-lg bg-white border-t border-t-[#D9D9D9]">
                <div class="flex items-center gap-3">
                    <p class="font-semibold">Show Rows</p>
                    <select id="accountsPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md" onchange="changeAccountPageSize(this.value)">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div id="accountsShowing" class="font-semibold">Showing 0-0 of 0</div>
                    <div>
                        <button id="accountsPrevBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goAccountPrev()">
                            <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                        </button>
                        <button id="accountsNextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goAccountNext()">
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
    const ACCOUNTS_COLSPAN = 8;
    const DEFAULT_ACCOUNT_PAGE_SIZE = 10;
    const accountsListUrl = '{{ route('masters.accounts.list') }}';

    let accountsRows = [];
    let accountPage = 1;
    let accountPageSize = DEFAULT_ACCOUNT_PAGE_SIZE;
    let accountSearchTerm = '';

    function escapeAccountHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function formatAccountDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function renderAccountStateRow(message, className = 'text-[#1E1E1E]') {
        $('#accountsBodyTable').html(
            '<tr><td colspan="' + ACCOUNTS_COLSPAN + '" class="text-center p-3 ' + className + '">' + message + '</td></tr>'
        );
    }

    function renderAccountPagination(total) {
        const start = total === 0 ? 0 : ((accountPage - 1) * accountPageSize) + 1;
        const end = Math.min(accountPage * accountPageSize, total);

        $('#accountsShowing').text('Showing ' + start + '-' + end + ' of ' + total);
        $('#accountsPrevBtn').prop('disabled', accountPage <= 1);
        $('#accountsNextBtn').prop('disabled', end >= total);
    }

    function getFilteredAccountRows() {
        const search = accountSearchTerm.trim().toLowerCase();

        if (!search) {
            return accountsRows;
        }

        return accountsRows.filter(function (account) {
            return [
                account.company_name,
                account.bank_name,
                account.account_number,
                account.holder_name
            ].some(function (value) {
                return String(value || '').toLowerCase().includes(search);
            });
        });
    }

    function renderAccountRows() {
        const tbody = $('#accountsBodyTable');
        const filteredRows = getFilteredAccountRows();
        const total = filteredRows.length;
        const startIndex = (accountPage - 1) * accountPageSize;
        const visibleRows = filteredRows.slice(startIndex, startIndex + accountPageSize);
        let index = startIndex + 1;

        tbody.empty();

        if (visibleRows.length === 0) {
            renderAccountStateRow('No accounts available');
            renderAccountPagination(total);
            return;
        }

        visibleRows.forEach(function (account) {
            tbody.append(`
                <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-1 md:p-2 lg:p-3">${index++}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeAccountHtml(account.company_name || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeAccountHtml(account.bank_name || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeAccountHtml(account.account_number || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeAccountHtml(account.holder_name || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatAccountDate(account.created_at)}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatAccountDate(account.updated_at)}</td>
                    <td class="text-center p-1 md:p-2 lg:p-3">${account.actions || '-'}</td>
                </tr>
            `);
        });

        renderAccountPagination(total);
    }

    function loadAccounts() {
        renderAccountStateRow('Loading data...');

        $.ajax({
            url: accountsListUrl,
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (result) {
                accountsRows = Array.isArray(result.data) ? result.data : [];
                accountPage = 1;
                renderAccountRows();
            },
            error: function () {
                accountsRows = [];
                accountPage = 1;
                renderAccountStateRow('Failed to load accounts', 'text-red-500');
                renderAccountPagination(0);
            }
        });
    }

    window.changeAccountPageSize = function (value) {
        accountPageSize = Number(value || DEFAULT_ACCOUNT_PAGE_SIZE);
        accountPage = 1;
        renderAccountRows();
    };

    window.goAccountPrev = function () {
        if (accountPage > 1) {
            accountPage -= 1;
            renderAccountRows();
        }
    };

    window.goAccountNext = function () {
        if (accountPage * accountPageSize < getFilteredAccountRows().length) {
            accountPage += 1;
            renderAccountRows();
        }
    };

    $(document).on('click', '.delete-account-data', function () {
        const deleteUrl = $(this).data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                headers: { 'Accept': 'application/json' },
                success: function () {
                    notif('Account deleted successfully!');
                    loadAccounts();
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete account';
                    notif(message, 'error');
                }
            });
        });
    });

    $(document).ready(function () {
        $('#accountsSearchInput').on('input', function () {
            accountSearchTerm = $(this).val() || '';
            accountPage = 1;
            renderAccountRows();
        });

        loadAccounts();
    });
</script>
@endsection

@section('styles')
<style>
    button:disabled {
        opacity: .45;
        cursor: not-allowed !important;
    }
</style>
@endsection
