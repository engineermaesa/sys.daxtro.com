<section class="min-h-screen sm:text-xs lg:text-sm" aria-labelledby="after-sales-heading">
    <header class="pt-4">
        <h1 id="after-sales-heading" class="text-[#115640] font-semibold text-2xl">After-Sales Performance</h1>
        <p class="text-[#757575] mt-1">Monitoring industrial machinery deployment and customer onboarding status.</p>
    </header>

    {{-- STAT CARDS --}}
    <section class="grid! grid-cols-2! gap-4 mt-4" aria-label="After-sales summary">
        <x-after-sales.stat-card
            icon="bi-clipboard-check"
            icon-bg="bg-yellow-50"
            label="Pending Profiles"
            value="-"
            unit="units"
            id="stat-pending-profiles"
        />
        <x-after-sales.stat-card
            icon="bi-patch-check-fill"
            icon-bg="bg-green-50"
            label="Completed Profiles"
            value="-"
            unit="units"
            id="stat-completed-profiles"
        />
    </section>

    {{-- PROVINCE DISTRIBUTION --}}
    <x-after-sales.province-distribution class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4" />

    {{-- CUSTOMER OVERVIEW --}}
    <x-after-sales.customer-overview class="bg-white rounded-lg border border-[#D9D9D9] mt-4" />
</section>

<script>
    (function () {
        const afterSalesState = {
            provinceId: '',
            timeframe: 'monthly',
        };

        let provinceDistributionChart = null;
        let customerOverviewDetailUrl = null;
        let customerOverviewUploadUrl = null;

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

        function setStat(prefix, { value, percentage, trend }) {
            const card = document.getElementById(prefix);
            if (!card) return;

            const valueEl = card.querySelector('[data-stat="value"]');
            if (valueEl) valueEl.textContent = Number(value ?? 0).toLocaleString('id-ID');

            if (percentage === undefined) return;

            const badge = card.querySelector('.rounded-full');
            if (!badge) return;

            const icon = badge.querySelector('i');
            const trendClass = trend === 'up'
                ? 'bg-green-50 text-green-700'
                : trend === 'down'
                    ? 'bg-red-50 text-red-700'
                    : 'bg-gray-100 text-gray-600';

            badge.className = `flex items-center gap-1 text-xs font-medium ${trendClass} px-2 py-1 rounded-full`;
            if (icon) {
                icon.className = `bi ${trend === 'up' ? 'bi-arrow-up-short' : trend === 'down' ? 'bi-arrow-down-short' : 'bi-dash'}`;
            }
            badge.lastChild.textContent = ` ${percentage}%`;
        }

        async function loadGrid() {
            try {
                const response = await fetch('/api/dashboard/after-sales/grid', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                const data = result?.data || {};

                setStat('stat-pending-profiles', { value: data.pending_profiles?.total ?? 0 });
                setStat('stat-completed-profiles', {
                    value: data.completed_profiles?.total ?? 0,
                    percentage: data.completed_profiles?.percentage_change ?? 0,
                    trend: data.completed_profiles?.trend ?? 'flat',
                });
            } catch (error) {
                console.error('Error loadGrid:', error);
            }
        }

        async function loadProvinceOptions() {
            const select = document.getElementById('province-distribution-region');
            if (!select) return;

            try {
                const response = await fetch('/api/dashboard/after-sales/provinces', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                const provinces = Array.isArray(result?.data) ? result.data : [];

                provinces.forEach((province) => {
                    const option = document.createElement('option');
                    option.value = province.id;
                    option.textContent = province.name;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error loadProvinceOptions:', error);
            }
        }

        function renderProvinceDistributionChart(rows) {
            const chartElement = document.querySelector('#province-distribution-chart');
            if (!chartElement) return;

            const labels = rows.map((row) => row.region || '-');
            const series = rows.map((row) => Number(row.total || 0));

            if (provinceDistributionChart) {
                provinceDistributionChart.destroy();
                provinceDistributionChart = null;
            }

            provinceDistributionChart = new ApexCharts(chartElement, {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                },
                series: [{ name: 'Units', data: series }],
                xaxis: {
                    categories: labels,
                    labels: { style: { colors: '#757575' } },
                },
                yaxis: {
                    labels: {
                        style: { colors: '#757575' },
                        formatter: (value) => Math.round(value).toLocaleString('id-ID'),
                    },
                    forceNiceScale: true,
                    decimalsInFloat: 0,
                },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '45%',
                        distributed: false,
                    },
                },
                colors: ['#115640'],
                dataLabels: { enabled: false },
                grid: { borderColor: '#F0F0F0' },
                tooltip: {
                    y: {
                        formatter: (value) => `${Number(value || 0).toLocaleString('id-ID')} units`,
                    },
                },
                noData: { text: 'No data available' },
            });

            provinceDistributionChart.render();
        }

        function updatePeriodLabel() {
            const label = document.getElementById('province-distribution-period-text');
            if (!label) return;
            label.textContent = afterSalesState.timeframe === 'yearly' ? 'Last 12 Months' : 'Last 1 Month';
        }

        async function loadProvinceDistribution() {
            const params = new URLSearchParams();
            if (afterSalesState.provinceId) params.set('province_id', afterSalesState.provinceId);
            params.set('timeframe', afterSalesState.timeframe);

            try {
                const response = await fetch(`/api/dashboard/after-sales/province-distribution?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                renderProvinceDistributionChart(Array.isArray(result?.data) ? result.data : []);
            } catch (error) {
                console.error('Error loadProvinceDistribution:', error);
                renderProvinceDistributionChart([]);
            }
        }

        function renderCustomerOverviewRows(rows) {
            const body = document.getElementById('customer-overview-table-body');
            if (!body) return;

            if (!rows || rows.length === 0) {
                body.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-[#757575]">No customers found</td></tr>';
                return;
            }

            body.innerHTML = rows.map((row) => `
                <tr class="border-t border-t-[#D9D9D9]">
                    <td class="p-3 text-center font-medium text-[#1E1E1E]">${escapeHtml(row.customer_name)}</td>
                    <td class="p-3 text-center">${escapeHtml(row.telephone)}</td>
                    <td class="p-3 text-center">${escapeHtml(row.machine_type)}</td>
                    <td class="p-3 text-center">${row.power_watts ? `${escapeHtml(row.power_watts)}W` : '-'}</td>
                    <td class="p-3 text-center">${row.room_area_m2 ? `${escapeHtml(row.room_area_m2)} m2` : '-'}</td>
                    <td class="p-3 text-center">${row.road_width_m ? `${escapeHtml(row.road_width_m)} m` : '-'}</td>
                    <td class="p-3 text-center">${row.actions || ''}</td>
                </tr>
            `).join('');
        }

        async function loadCustomerOverview() {
            const body = document.getElementById('customer-overview-table-body');
            if (body) {
                body.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-[#757575]">Loading...</td></tr>';
            }

            try {
                const response = await fetch(`{{ route('after-sales.customers.list') }}?per_page=5`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                renderCustomerOverviewRows(result?.data || []);
            } catch (error) {
                console.error('Error loadCustomerOverview:', error);
                if (body) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-[#900B09]">Failed to load customers</td></tr>';
                }
            }
        }

        function loadCustomerOverviewDetail(url) {
            customerOverviewDetailUrl = url;

            $('#customer-overview-detail-body').html('<p class="col-span-2 text-center text-[#757575]">Loading...</p>');
            $('#customer-overview-cad-list').html('');
            $('#customerDetailModal').modal('show');

            $.get(url, function (response) {
                const c = response.customer;
                $('#customer-overview-detail-body').html(`
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
                renderCustomerOverviewCadList(c.file_cad);
            });
        }

        function renderCustomerOverviewCadList(files) {
            const $list = $('#customer-overview-cad-list');

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
                        <button type="button" class="btn-customer-overview-cad-delete border border-[#900B09] text-[#900B09]! rounded-lg px-3 py-1 cursor-pointer hover:bg-[#FBEAEA]"
                            data-path="${escapeHtml(file.path)}">Delete</button>
                        <a href="${file.url}" target="_blank"
                            class="bg-[#115640] text-white rounded-lg px-3 py-1 hover:bg-[#0d4633]">Preview</a>
                    </div>
                </div>
            `).join('');

            $list.html(html);
        }

        function uploadCustomerOverviewCadFiles(files) {
            if (!customerOverviewUploadUrl || !files || files.length === 0) return;

            const formData = new FormData();
            Array.from(files).forEach(file => formData.append('file_cad[]', file));

            $.ajax({
                url: customerOverviewUploadUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function () {
                    notif('CAD file(s) uploaded successfully!');
                    loadCustomerOverviewDetail(customerOverviewDetailUrl);
                },
                error: function (xhr) {
                    notif(xhr.responseJSON?.message || 'Failed to upload CAD file', 'error');
                }
            });
        }

        function deleteCustomerOverviewCadFile(path) {
            if (!customerOverviewUploadUrl) return;

            const formData = new FormData();
            formData.append('remove_file_cad[]', path);

            $.ajax({
                url: customerOverviewUploadUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function () {
                    notif('CAD file deleted successfully!');
                    loadCustomerOverviewDetail(customerOverviewDetailUrl);
                },
                error: function (xhr) {
                    notif(xhr.responseJSON?.message || 'Failed to delete CAD file', 'error');
                }
            });
        }

        function bindCustomerOverviewDetailModal() {
            $(document).on('click', '.btn-customer-detail', function (e) {
                e.preventDefault();

                customerOverviewUploadUrl = $(this).data('upload-url');
                loadCustomerOverviewDetail($(this).data('url'));
            });

            $('#customer-overview-cad-dropzone').on('click', function () {
                document.getElementById('customer-overview-cad-input').click();
            });

            $('#customer-overview-cad-browse-btn').on('click', function (e) {
                e.stopPropagation();
                document.getElementById('customer-overview-cad-input').click();
            });

            $('#customer-overview-cad-input').on('change', function () {
                uploadCustomerOverviewCadFiles(this.files);
                $(this).val('');
            });

            $('#customer-overview-cad-save-btn').on('click', function () {
                $('#customerDetailModal').modal('hide');
            });

            const $dropzone = $('#customer-overview-cad-dropzone');
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
                if (files && files.length) uploadCustomerOverviewCadFiles(files);
            });

            $(document).on('click', '.btn-customer-overview-cad-delete', function () {
                const path = $(this).data('path');
                if (path) deleteCustomerOverviewCadFile(path);
            });
        }

        function bindTimeframeToggle() {
            const buttons = document.querySelectorAll('.btn-timeframe');

            function paint() {
                buttons.forEach((button) => {
                    const active = button.dataset.timeframe === afterSalesState.timeframe;
                    button.setAttribute('aria-pressed', String(active));
                    button.className = `btn-timeframe px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                        active ? 'bg-[#115640] text-white' : 'text-[#757575]'
                    }`;
                });
            }

            buttons.forEach((button) => {
                button.addEventListener('click', function () {
                    afterSalesState.timeframe = this.dataset.timeframe;
                    paint();
                    updatePeriodLabel();
                    loadProvinceDistribution();
                });
            });

            paint();
        }

        function bindProvinceFilter() {
            const select = document.getElementById('province-distribution-region');
            if (!select) return;

            select.addEventListener('change', function () {
                afterSalesState.provinceId = this.value || '';
                loadProvinceDistribution();
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindTimeframeToggle();
            bindProvinceFilter();
            bindCustomerOverviewDetailModal();
            updatePeriodLabel();

            loadGrid();
            loadProvinceOptions();
            loadProvinceDistribution();
            loadCustomerOverview();
        });
    })();
</script>
