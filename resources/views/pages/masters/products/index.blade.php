@extends('layouts.app')

@section('content')
<section class="min-h-screen text-xs! lg:text-sm!">
    <div class="pt-4">
        <div class="flex items-center gap-3 text-[#115640]">
            <x-icon.package/>
            <h1 class="font-semibold text-lg lg:text-2xl">Products</h1>
        </div>
        <p class="mt-1 text-[#115640] text-sm lg:text-lg">All Products</p>
    </div>

    <div class="mt-4 rounded-lg border border-[#D9D9D9]">
        <div class="bg-white border-b border-[#D9D9D9] p-3 rounded-tr-lg rounded-tl-lg">
            <div class="flex flex-col gap-3 lg:flex-row lg:justify-between lg:items-center">
                <div class="border border-gray-300 rounded-lg flex items-center p-2 h-full w-full lg:w-1/4">
                    <i class="fas fa-search text-[#6B7786] px-2"></i>
                    <input id="productsSearchInput" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640]" />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('masters.products.import') }}" class="bg-white border border-[#115640] rounded-lg flex justify-center items-center gap-3 px-5 py-3 text-[#115640]">
                        <x-icon.upload/>
                        <p class="font-bold">Import Product</p>
                    </a>
                    <a href="{{ route('masters.products.form') }}" class="bg-[#115640] rounded-lg flex justify-center items-center gap-3 px-5 py-3">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6 8H1C0.716667 8 0.479167 7.90417 0.2875 7.7125C0.0958333 7.52083 0 7.28333 0 7C0 6.71667 0.0958333 6.47917 0.2875 6.2875C0.479167 6.09583 0.716667 6 1 6H6V1C6 0.716667 6.09583 0.479167 6.2875 0.2875C6.47917 0.0958333 6.71667 0 7 0C7.28333 0 7.52083 0.0958333 7.7125 0.2875C7.90417 0.479167 8 0.716667 8 1V6H13C13.2833 6 13.5208 6.09583 13.7125 6.2875C13.9042 6.47917 14 6.71667 14 7C14 7.28333 13.9042 7.52083 13.7125 7.7125C13.5208 7.90417 13.2833 8 13 8H8V13C8 13.2833 7.90417 13.5208 7.7125 13.7125C7.52083 13.9042 7.28333 14 7 14C6.71667 14 6.47917 13.9042 6.2875 13.7125C6.09583 13.5208 6 13.2833 6 13V8Z"
                                fill="#FFFFFF" />
                        </svg>
                        <p class="text-white font-medium">New Product</p>
                    </a>
                </div>
            </div>
        </div>

        <div class="products-table-container">
            <div class="max-xl:overflow-x-scroll">
                <table id="productsTableNew" class="w-full bg-white">
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#D9D9D9]">
                            <th class="p-1 lg:p-3 text-center">#</th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="product_type_name" data-sort-type="string">Type <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="sku" data-sort-type="string">SKU <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="name" data-sort-type="string">Name <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="corporate_price" data-sort-type="number">Corporate Price <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="government_price" data-sort-type="number">Government Price <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="personal_price" data-sort-type="number">Personal Price <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="fob_price" data-sort-type="number">FOB Price <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 sortable-product-th" data-sort-key="bdi_price" data-sort-type="number">BDI Price <span class="product-sort-indicator"></span></th>
                            <th class="p-1 lg:p-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsBodyTable"></tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center px-3 py-2 text-[#1E1E1E]! rounded-b-lg bg-white border-t border-t-[#D9D9D9]">
                <div class="flex items-center gap-3">
                    <p class="font-semibold">Show Rows</p>
                    <select id="productsPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md" onchange="changeProductPageSize(this.value)">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div id="productsShowing" class="font-semibold">Showing 0-0 of 0</div>
                    <div>
                        <button id="productsPrevBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goProductPrev()">
                            <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                        </button>
                        <button id="productsNextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goProductNext()">
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
    const PRODUCTS_COLSPAN = 10;
    const DEFAULT_PRODUCT_PAGE_SIZE = 10;
    const productsListUrl = '{{ route('masters.products.list') }}';

    let productsRows = [];
    let productPage = 1;
    let productPageSize = DEFAULT_PRODUCT_PAGE_SIZE;
    let productSearchTerm = '';
    let productSortKey = '';
    let productSortDirection = 'asc';
    let productSortType = 'string';

    function escapeProductHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function formatProductCurrency(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        const amount = Number(value);

        if (Number.isNaN(amount)) {
            return '-';
        }

        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(amount);
    }

    function renderProductStateRow(message, className = 'text-[#1E1E1E]') {
        $('#productsBodyTable').html(
            '<tr><td colspan="' + PRODUCTS_COLSPAN + '" class="text-center p-3 ' + className + '">' + message + '</td></tr>'
        );
    }

    function renderProductPagination(total) {
        const start = total === 0 ? 0 : ((productPage - 1) * productPageSize) + 1;
        const end = Math.min(productPage * productPageSize, total);

        $('#productsShowing').text('Showing ' + start + '-' + end + ' of ' + total);
        $('#productsPrevBtn').prop('disabled', productPage <= 1);
        $('#productsNextBtn').prop('disabled', end >= total);
    }

    function compareProductValues(firstValue, secondValue, type) {
        if (type === 'number') {
            const firstNumber = Number(firstValue);
            const secondNumber = Number(secondValue);
            const normalizedFirst = Number.isNaN(firstNumber) ? null : firstNumber;
            const normalizedSecond = Number.isNaN(secondNumber) ? null : secondNumber;

            if (normalizedFirst === null && normalizedSecond === null) {
                return 0;
            }

            if (normalizedFirst === null) {
                return 1;
            }

            if (normalizedSecond === null) {
                return -1;
            }

            return normalizedFirst - normalizedSecond;
        }

        return String(firstValue || '').localeCompare(String(secondValue || ''), undefined, {
            numeric: true,
            sensitivity: 'base'
        });
    }

    function sortProductRows(rows) {
        if (!productSortKey) {
            return rows;
        }

        return rows.slice().sort(function (firstProduct, secondProduct) {
            const comparison = compareProductValues(
                firstProduct[productSortKey],
                secondProduct[productSortKey],
                productSortType
            );

            return productSortDirection === 'asc' ? comparison : comparison * -1;
        });
    }

    function updateProductSortIndicators() {
        $('.sortable-product-th .product-sort-indicator')
            .removeClass('is-active')
            .html('');

        if (!productSortKey) {
            return;
        }

        const activeHeader = $('.sortable-product-th[data-sort-key="' + productSortKey + '"]');
        const iconClass = productSortDirection === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down';
        activeHeader.find('.product-sort-indicator')
            .addClass('is-active')
            .html('<i class="fas ' + iconClass + '"></i>');
    }

    function getFilteredProductRows() {
        const search = productSearchTerm.trim().toLowerCase();
        let filteredRows = productsRows;

        if (search) {
            filteredRows = productsRows.filter(function (product) {
                return [
                    product.product_type_name,
                    product.sku,
                    product.name,
                    product.corporate_price,
                    product.government_price,
                    product.personal_price,
                    product.fob_price,
                    product.bdi_price,
                    formatProductCurrency(product.corporate_price),
                    formatProductCurrency(product.government_price),
                    formatProductCurrency(product.personal_price),
                    formatProductCurrency(product.fob_price),
                    formatProductCurrency(product.bdi_price)
                ].some(function (value) {
                    return String(value || '').toLowerCase().includes(search);
                });
            });
        }

        return sortProductRows(filteredRows);
    }

    function renderProductRows() {
        const tbody = $('#productsBodyTable');
        const filteredRows = getFilteredProductRows();
        const total = filteredRows.length;
        const startIndex = (productPage - 1) * productPageSize;
        const visibleRows = filteredRows.slice(startIndex, startIndex + productPageSize);
        let index = startIndex + 1;

        tbody.empty();

        if (visibleRows.length === 0) {
            renderProductStateRow('No products available');
            renderProductPagination(total);
            return;
        }

        visibleRows.forEach(function (product) {
            tbody.append(`
                <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-1 md:p-2 lg:p-3 text-center">${index++}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeProductHtml(product.product_type_name || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeProductHtml(product.sku || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${escapeProductHtml(product.name || '-')}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatProductCurrency(product.corporate_price)}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatProductCurrency(product.government_price)}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatProductCurrency(product.personal_price)}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatProductCurrency(product.fob_price)}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatProductCurrency(product.bdi_price)}</td>
                    <td class="text-center p-1 md:p-2 lg:p-3">${product.actions || '-'}</td>
                </tr>
            `);
        });

        renderProductPagination(total);
    }

    function loadProducts() {
        renderProductStateRow('Loading data...');

        $.ajax({
            url: productsListUrl,
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (result) {
                productsRows = Array.isArray(result.data) ? result.data : [];
                productPage = 1;
                renderProductRows();
            },
            error: function () {
                productsRows = [];
                productPage = 1;
                renderProductStateRow('Failed to load products', 'text-red-500');
                renderProductPagination(0);
            }
        });
    }

    window.changeProductPageSize = function (value) {
        productPageSize = Number(value || DEFAULT_PRODUCT_PAGE_SIZE);
        productPage = 1;
        renderProductRows();
    };

    window.goProductPrev = function () {
        if (productPage > 1) {
            productPage -= 1;
            renderProductRows();
        }
    };

    window.goProductNext = function () {
        if (productPage * productPageSize < getFilteredProductRows().length) {
            productPage += 1;
            renderProductRows();
        }
    };

    $(document).on('click', '.sortable-product-th', function () {
        const sortKey = $(this).data('sort-key');

        if (productSortKey === sortKey) {
            productSortDirection = productSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            productSortKey = sortKey;
            productSortDirection = 'asc';
            productSortType = $(this).data('sort-type') || 'string';
        }

        productPage = 1;
        updateProductSortIndicators();
        renderProductRows();
    });

    $(document).on('click', '.delete-product-data', function () {
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
                    notif('Product deleted successfully!');
                    loadProducts();
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete product';
                    notif(message, 'error');
                }
            });
        });
    });

    $(document).ready(function () {
        $('#productsSearchInput').on('input', function () {
            productSearchTerm = $(this).val() || '';
            productPage = 1;
            renderProductRows();
        });

        updateProductSortIndicators();
        loadProducts();

        @if(session('success'))
            notif({!! json_encode(session('success')) !!});
        @endif

        @if(session('error'))
            notif({!! json_encode(session('error')) !!}, 'error');
        @endif
    });
</script>
@endsection

@section('styles')
<style>
    button:disabled {
        opacity: .45;
        cursor: not-allowed !important;
    }

    .sortable-product-th {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }

    .product-sort-indicator {
        display: inline-block;
        min-width: 12px;
        margin-left: 4px;
        font-size: 11px;
        opacity: 0;
        transform: translateY(2px);
        vertical-align: middle;
    }

    .product-sort-indicator.is-active {
        opacity: 1;
        transform: translateY(0);
    }
</style>
@endsection
