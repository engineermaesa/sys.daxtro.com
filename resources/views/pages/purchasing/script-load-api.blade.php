async function loadAll(){
    const page = pageState.all || 1;
    const perPage = pageSizeState.all || DEFAULT_PAGE_SIZE;
    const tbody = document.getElementById('allBodyTable');

    tbody.innerHTML = `
        <tr>
            <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                Loading data...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `/api/purchasing/list?page=${page}&per_page=${perPage}`,
            { credentials: 'same-origin' }
        );

        const result = await response.json();

        updatePagerUI('all', result.total);
        tbody.innerHTML = '';
        totals.all = result.total || 0;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" class="p-4 text-center text-gray-500">
                        Data tidak ditemukan
                    </td>
                </tr>
            `;
            return;
        }

        result.data.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-1 lg:p-2">${row.created_at ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.name ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.company ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.phone ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.customer_type ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.needs ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.tonase ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.stage ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.status ?? '-'}</td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error('Gagal load purchasing:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="17" class="p-4 text-center text-red-500">
                    Gagal memuat data
                </td>
            </tr>
        `;
    }
}

async function loadInvoiceReceived(){
    const page = pageState.all || 1;
    const perPage = pageSizeState.invoiceReceived || DEFAULT_PAGE_SIZE;
    const tbody = document.getElementById('invoiceReceivedBodyTable');

    tbody.innerHTML = `
        <tr>
            <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                Loading data...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `/api/purchasing/list?page=${page}&per_page=${perPage}&stage=invoiceReceived`,
            { credentials: 'same-origin' }
        );

        const result = await response.json();

        updatePagerUI('all', result.total);
        tbody.innerHTML = '';
        totals.all = result.total || 0;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                        Data tidak ditemukan
                    </td>
                </tr>
            `;
            return;
        }

        result.data.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-1 lg:p-2">${row.created_at ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.name ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.company ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.phone ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.customer_type ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.needs ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.tonase ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.status ?? '-'}</td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error('Gagal load purchasing:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="17" class="p-4 text-center text-red-500">
                    Gagal memuat data
                </td>
            </tr>
        `;
    }
}

async function loadVendorProcessing(){
    const page = pageState.all || 1;
    const perPage = pageSizeState.vendorProcessing || DEFAULT_PAGE_SIZE;
    const tbody = document.getElementById('vendorProcessingBodyTable');

    tbody.innerHTML = `
        <tr>
            <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                Loading data...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `/api/purchasing/list?page=${page}&per_page=${perPage}&stage=vendorProcessing`,
            { credentials: 'same-origin' }
        );

        const result = await response.json();

        updatePagerUI('all', result.total);
        tbody.innerHTML = '';
        totals.all = result.total || 0;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                        Data tidak ditemukan
                    </td>
                </tr>
            `;
            return;
        }

        result.data.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-1 lg:p-2">${row.created_at ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.name ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.company ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.phone ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.customer_type ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.needs ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.tonase ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.status ?? '-'}</td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error('Gagal load purchasing:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                    Gagal memuat data
                </td>
            </tr>
        `;
    }
}

async function loadHandover(){
    const page = pageState.all || 1;
    const perPage = pageSizeState.readyForHandover || DEFAULT_PAGE_SIZE;
    const tbody = document.getElementById('readyForHandoverBodyTable');

    tbody.innerHTML = `
        <tr>
            <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                Loading data...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `/api/purchasing/list?page=${page}&per_page=${perPage}&stage=readyForHandover`,
            { credentials: 'same-origin' }
        );

        const result = await response.json();

        updatePagerUI('all', result.total);
        tbody.innerHTML = '';
        totals.all = result.total || 0;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                        Data tidak ditemukan
                    </td>
                </tr>
            `;
            return;
        }

        result.data.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-1 lg:p-2">${row.created_at ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.name ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.company ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.phone ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.customer_type ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.needs ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.tonase ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.status ?? '-'}</td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error('Gagal load purchasing:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                    Gagal memuat data
                </td>
            </tr>
        `;
    }
}

async function loadCompleted(){
    const page = pageState.all || 1;
    const perPage = pageSizeState.completed || DEFAULT_PAGE_SIZE;
    const tbody = document.getElementById('completedBodyTable');

    tbody.innerHTML = `
        <tr>
            <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                Loading data...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `/api/purchasing/list?page=${page}&per_page=${perPage}&stage=completed`,
            { credentials: 'same-origin' }
        );

        const result = await response.json();

        updatePagerUI('all', result.total);
        tbody.innerHTML = '';
        totals.all = result.total || 0;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                        Data tidak ditemukan
                    </td>
                </tr>
            `;
            return;
        }

        result.data.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-1 lg:p-2">${row.created_at ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.name ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.company ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.phone ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.customer_type ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.needs ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.tonase ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.status ?? '-'}</td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error('Gagal load purchasing:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                    Gagal memuat data
                </td>
            </tr>
        `;
    }
}

async function loadPending(){
    const page = pageState.all || 1;
    const perPage = pageSizeState.pending || DEFAULT_PAGE_SIZE;
    const tbody = document.getElementById('pendingBodyTable');

    tbody.innerHTML = `
        <tr>
            <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                Loading data...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `/api/purchasing/list?page=${page}&per_page=${perPage}&stage=pending`,
            { credentials: 'same-origin' }
        );

        const result = await response.json();

        updatePagerUI('all', result.total);
        tbody.innerHTML = '';
        totals.all = result.total || 0;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                        Data tidak ditemukan
                    </td>
                </tr>
            `;
            return;
        }

        result.data.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-1 lg:p-2">${row.created_at ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.name ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.company ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.phone ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.customer_type ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.needs ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.tonase ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.status ?? '-'}</td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error('Gagal load purchasing:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                    Gagal memuat data
                </td>
            </tr>
        `;
    }
}

async function loadCanceled(){
    const page = pageState.all || 1;
    const perPage = pageSizeState.canceled || DEFAULT_PAGE_SIZE;
    const tbody = document.getElementById('canceledBodyTable');

    tbody.innerHTML = `
        <tr>
            <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                Loading data...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `/api/purchasing/list?page=${page}&per_page=${perPage}&stage=canceled`,
            { credentials: 'same-origin' }
        );

        const result = await response.json();

        updatePagerUI('all', result.total);
        tbody.innerHTML = '';
        totals.all = result.total || 0;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                        Data tidak ditemukan
                    </td>
                </tr>
            `;
            return;
        }

        result.data.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-1 lg:p-2">${row.created_at ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.name ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.company ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.phone ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.customer_type ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.needs ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.lead?.tonase ?? '-'}</td>
                    <td class="p-1 lg:p-2">${row.status ?? '-'}</td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error('Gagal load purchasing:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="17" class="p-4 text-center text-[#1E1E1E] opacity-50">
                    Gagal memuat data
                </td>
            </tr>
        `;
    }
}