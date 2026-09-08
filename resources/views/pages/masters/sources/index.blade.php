@extends('layouts.app')

@section('content')
<section class="min-h-screen text-xs! lg:text-sm!">
    <div class="pt-4">
        <div class="flex items-center gap-3 text-[#115640]">
            <x-icon.globe/>
            <h1 class="font-semibold text-lg lg:text-2xl">Source</h1>
        </div>
        <p class="mt-1 text-[#115640] text-sm lg:text-lg">All Sources</p>
    </div>

    <div class="mt-4 rounded-lg border border-[#D9D9D9]">
        <div class="bg-white border-b border-[#D9D9D9] p-3 rounded-tr-lg rounded-tl-lg">
            <div class="flex justify-between items-center gap-3">
                <div class="border border-gray-300 rounded-lg flex items-center p-2 h-full w-1/4">
                    <i class="fas fa-search text-[#6B7786] px-2"></i>
                    <input id="sourcesSearchInput" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640]" />
                </div>
                <a href="{{ route('masters.sources.form') }}" class="bg-[#115640] rounded-lg w-1/6 flex justify-center items-center gap-3 px-5 py-3">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6 8H1C0.716667 8 0.479167 7.90417 0.2875 7.7125C0.0958333 7.52083 0 7.28333 0 7C0 6.71667 0.0958333 6.47917 0.2875 6.2875C0.479167 6.09583 0.716667 6 1 6H6V1C6 0.716667 6.09583 0.479167 6.2875 0.2875C6.47917 0.0958333 6.71667 0 7 0C7.28333 0 7.52083 0.0958333 7.7125 0.2875C7.90417 0.479167 8 0.716667 8 1V6H13C13.2833 6 13.5208 6.09583 13.7125 6.2875C13.9042 6.47917 14 6.71667 14 7C14 7.28333 13.9042 7.52083 13.7125 7.7125C13.5208 7.90417 13.2833 8 13 8H8V13C8 13.2833 7.90417 13.5208 7.7125 13.7125C7.52083 13.9042 7.28333 14 7 14C6.71667 14 6.47917 13.9042 6.2875 13.7125C6.09583 13.5208 6 13.2833 6 13V8Z"
                            fill="#FFFFFF" />
                    </svg>
                    <p class="text-white font-medium">New Source</p>
                </a>
            </div>
        </div>

        <div class="sources-table-container">
            <div class="max-xl:overflow-x-scroll">
                <table id="sourcesTableNew" class="w-full bg-white">
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#D9D9D9]">
                            <th class="p-1 lg:p-3">#</th>
                            <th class="p-1 lg:p-3">Name</th>
                            <th class="p-1 lg:p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="sourcesBodyTable"></tbody>
                </table>
            </div>

            <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! rounded-b-lg bg-white border-t border-t-[#D9D9D9]">
                <div class="flex items-center gap-3">
                    <p class="font-semibold">Show Rows</p>
                    <select id="sourcesPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md" onchange="changeSourcePageSize(this.value)">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div id="sourcesShowing" class="font-semibold">Showing 0-0 of 0</div>
                    <div>
                        <button id="sourcesPrevBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goSourcePrev()">
                            <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                        </button>
                        <button id="sourcesNextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goSourceNext()">
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
    const SOURCES_COLSPAN = 3;
    const DEFAULT_SOURCE_PAGE_SIZE = 10;
    const sourcesListUrl = '{{ route('masters.sources.list') }}';

    let sourcesRows = [];
    let sourcePage = 1;
    let sourcePageSize = DEFAULT_SOURCE_PAGE_SIZE;
    let sourceSearchTerm = '';

    function escapeSourceHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function renderSourceStateRow(message, className = 'text-[#1E1E1E]') {
        $('#sourcesBodyTable').html(
            '<tr><td colspan="' + SOURCES_COLSPAN + '" class="text-center p-3 ' + className + '">' + message + '</td></tr>'
        );
    }

    function renderSourcePagination(total) {
        const start = total === 0 ? 0 : ((sourcePage - 1) * sourcePageSize) + 1;
        const end = Math.min(sourcePage * sourcePageSize, total);

        $('#sourcesShowing').text('Showing ' + start + '-' + end + ' of ' + total);
        $('#sourcesPrevBtn').prop('disabled', sourcePage <= 1);
        $('#sourcesNextBtn').prop('disabled', end >= total);
    }

    function getFilteredSourceRows() {
        const search = sourceSearchTerm.trim().toLowerCase();

        if (!search) {
            return sourcesRows;
        }

        return sourcesRows.filter(function (source) {
            return String(source.name || '').toLowerCase().includes(search);
        });
    }

    function renderSourceRows() {
        const tbody = $('#sourcesBodyTable');
        const filteredRows = getFilteredSourceRows();
        const total = filteredRows.length;
        const startIndex = (sourcePage - 1) * sourcePageSize;
        const visibleRows = filteredRows.slice(startIndex, startIndex + sourcePageSize);
        let index = startIndex + 1;

        tbody.empty();

        if (visibleRows.length === 0) {
            renderSourceStateRow('No sources available');
            renderSourcePagination(total);
            return;
        }

        visibleRows.forEach(function (source) {
            tbody.append(`
                <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-1 md:p-2 lg:p-3">${index++}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeSourceHtml(source.name || '-')}</td>
                    <td class="text-center p-1 md:p-2 lg:p-3">${source.actions || '-'}</td>
                </tr>
            `);
        });

        renderSourcePagination(total);
    }

    function loadSources() {
        renderSourceStateRow('Loading data...');

        $.ajax({
            url: sourcesListUrl,
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (result) {
                sourcesRows = Array.isArray(result.data) ? result.data : [];
                sourcePage = 1;
                renderSourceRows();
            },
            error: function () {
                sourcesRows = [];
                sourcePage = 1;
                renderSourceStateRow('Failed to load sources', 'text-red-500');
                renderSourcePagination(0);
            }
        });
    }

    window.changeSourcePageSize = function (value) {
        sourcePageSize = Number(value || DEFAULT_SOURCE_PAGE_SIZE);
        sourcePage = 1;
        renderSourceRows();
    };

    window.goSourcePrev = function () {
        if (sourcePage > 1) {
            sourcePage -= 1;
            renderSourceRows();
        }
    };

    window.goSourceNext = function () {
        if (sourcePage * sourcePageSize < getFilteredSourceRows().length) {
            sourcePage += 1;
            renderSourceRows();
        }
    };

    $(document).on('click', '.delete-source-data', function () {
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
                    notif('Source deleted successfully!');
                    loadSources();
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete source';
                    notif(message, 'error');
                }
            });
        });
    });

    $(document).ready(function () {
        $('#sourcesSearchInput').on('input', function () {
            sourceSearchTerm = $(this).val() || '';
            sourcePage = 1;
            renderSourceRows();
        });

        loadSources();
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
