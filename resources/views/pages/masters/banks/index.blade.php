@extends('layouts.app')

@section('content')
<section class="min-h-screen text-xs! lg:text-sm!">
    <div class="pt-4">
        <div class="flex items-center gap-3 text-[#115640]">
            <x-icon.bank/>
            <h1 class="font-semibold text-lg lg:text-2xl">Banks</h1>
        </div>
        <p class="mt-1 text-[#115640] text-sm lg:text-lg">All Banks</p>
    </div>

    <div class="mt-4 rounded-lg border border-[#D9D9D9]">
        <div class="bg-white border-b border-[#D9D9D9] p-3 rounded-tr-lg rounded-tl-lg">
            <div class="flex justify-between items-center gap-3">
                <div class="border border-gray-300 rounded-lg flex items-center p-2 h-full w-1/4">
                    <i class="fas fa-search text-[#6B7786] px-2"></i>
                    <input id="banksSearchInput" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640]" />
                </div>
                <a href="{{ route('masters.banks.form') }}" class="bg-[#115640] rounded-lg w-1/6 flex justify-center items-center gap-3 px-5 py-3">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6 8H1C0.716667 8 0.479167 7.90417 0.2875 7.7125C0.0958333 7.52083 0 7.28333 0 7C0 6.71667 0.0958333 6.47917 0.2875 6.2875C0.479167 6.09583 0.716667 6 1 6H6V1C6 0.716667 6.09583 0.479167 6.2875 0.2875C6.47917 0.0958333 6.71667 0 7 0C7.28333 0 7.52083 0.0958333 7.7125 0.2875C7.90417 0.479167 8 0.716667 8 1V6H13C13.2833 6 13.5208 6.09583 13.7125 6.2875C13.9042 6.47917 14 6.71667 14 7C14 7.28333 13.9042 7.52083 13.7125 7.7125C13.5208 7.90417 13.2833 8 13 8H8V13C8 13.2833 7.90417 13.5208 7.7125 13.7125C7.52083 13.9042 7.28333 14 7 14C6.71667 14 6.47917 13.9042 6.2875 13.7125C6.09583 13.5208 6 13.2833 6 13V8Z"
                            fill="#FFFFFF" />
                    </svg>
                    <p class="text-white font-medium">New Bank</p>
                </a>
            </div>
        </div>

        <div class="banks-table-container">
            <div class="max-xl:overflow-x-scroll">
                <table id="banksTableNew" class="w-full bg-white">
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#D9D9D9]">
                            <th class="p-1 lg:p-3">#</th>
                            <th class="p-1 lg:p-3">Name</th>
                            <th class="p-1 lg:p-3">Created At</th>
                            <th class="p-1 lg:p-3">Updated At</th>
                            <th class="p-1 lg:p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="banksBodyTable"></tbody>
                </table>
            </div>

            <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! rounded-b-lg bg-white border-t border-t-[#D9D9D9]">
                <div class="flex items-center gap-3">
                    <p class="font-semibold">Show Rows</p>
                    <select id="banksPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md" onchange="changeBankPageSize(this.value)">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div id="banksShowing" class="font-semibold">Showing 0-0 of 0</div>
                    <div>
                        <button id="banksPrevBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goBankPrev()">
                            <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                        </button>
                        <button id="banksNextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goBankNext()">
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
    const BANKS_COLSPAN = 5;
    const DEFAULT_BANK_PAGE_SIZE = 10;
    const banksListUrl = '{{ route('masters.banks.list') }}';

    let banksRows = [];
    let bankPage = 1;
    let bankPageSize = DEFAULT_BANK_PAGE_SIZE;
    let bankSearchTerm = '';

    function escapeBankHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function formatBankDate(value) {
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

    function renderBankStateRow(message, className = 'text-[#1E1E1E]') {
        $('#banksBodyTable').html(
            '<tr><td colspan="' + BANKS_COLSPAN + '" class="text-center p-3 ' + className + '">' + message + '</td></tr>'
        );
    }

    function renderBankPagination(total) {
        const start = total === 0 ? 0 : ((bankPage - 1) * bankPageSize) + 1;
        const end = Math.min(bankPage * bankPageSize, total);

        $('#banksShowing').text('Showing ' + start + '-' + end + ' of ' + total);
        $('#banksPrevBtn').prop('disabled', bankPage <= 1);
        $('#banksNextBtn').prop('disabled', end >= total);
    }

    function getFilteredBankRows() {
        const search = bankSearchTerm.trim().toLowerCase();

        if (!search) {
            return banksRows;
        }

        return banksRows.filter(function (bank) {
            return String(bank.name || '').toLowerCase().includes(search);
        });
    }

    function renderBankRows() {
        const tbody = $('#banksBodyTable');
        const filteredRows = getFilteredBankRows();
        const total = filteredRows.length;
        const startIndex = (bankPage - 1) * bankPageSize;
        const visibleRows = filteredRows.slice(startIndex, startIndex + bankPageSize);
        let index = startIndex + 1;

        tbody.empty();

        if (visibleRows.length === 0) {
            renderBankStateRow('No banks available');
            renderBankPagination(total);
            return;
        }

        visibleRows.forEach(function (bank) {
            tbody.append(`
                <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-1 md:p-2 lg:p-3">${index++}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeBankHtml(bank.name || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatBankDate(bank.created_at)}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatBankDate(bank.updated_at)}</td>
                    <td class="text-center p-1 md:p-2 lg:p-3">${bank.actions || '-'}</td>
                </tr>
            `);
        });

        renderBankPagination(total);
    }

    function loadBanks() {
        renderBankStateRow('Loading data...');

        $.ajax({
            url: banksListUrl,
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (result) {
                banksRows = Array.isArray(result.data) ? result.data : [];
                bankPage = 1;
                renderBankRows();
            },
            error: function () {
                banksRows = [];
                bankPage = 1;
                renderBankStateRow('Failed to load banks', 'text-red-500');
                renderBankPagination(0);
            }
        });
    }

    window.changeBankPageSize = function (value) {
        bankPageSize = Number(value || DEFAULT_BANK_PAGE_SIZE);
        bankPage = 1;
        renderBankRows();
    };

    window.goBankPrev = function () {
        if (bankPage > 1) {
            bankPage -= 1;
            renderBankRows();
        }
    };

    window.goBankNext = function () {
        if (bankPage * bankPageSize < getFilteredBankRows().length) {
            bankPage += 1;
            renderBankRows();
        }
    };

    $(document).on('click', '.delete-bank-data', function () {
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
                    notif('Bank deleted successfully!');
                    loadBanks();
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete bank';
                    notif(message, 'error');
                }
            });
        });
    });

    $(document).ready(function () {
        $('#banksSearchInput').on('input', function () {
            bankSearchTerm = $(this).val() || '';
            bankPage = 1;
            renderBankRows();
        });

        loadBanks();
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
