@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="mb-1">
            <h1 class="text-[#115640] font-bold text-2xl">{{ $technician->user->name }} — Work Order</h1>
            <p class="text-[#757575] text-sm mt-1">
                <a href="{{ route('aftersales.pages.technicians.index') }}" class="hover:underline">Technician</a>
                <span class="mx-1">&gt;</span>
                <span class="font-semibold text-[#1E1E1E] underline">{{ $technician->user->name }} — Work Order</span>
            </p>
        </div>

        {{--TABLE--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-left uppercase text-xs">Ticket ID</th>
                        <th class="p-3 text-left uppercase text-xs">Customer</th>
                        <th class="p-3 text-left uppercase text-xs">Machine</th>
                        <th class="p-3 text-left uppercase text-xs">Problem</th>
                        <th class="p-3 text-left uppercase text-xs">Open Date</th>
                        <th class="p-3 text-left uppercase text-xs">Visit Date</th>
                        <th class="p-3 text-left uppercase text-xs">Close Date</th>
                        <th class="p-3 text-left uppercase text-xs">SLA</th>
                        <th class="p-3 text-left uppercase text-xs">Priority</th>
                        <th class="p-3 text-left uppercase text-xs">Progress</th>
                    </tr>
                </thead>
                <tbody id="work-order-table-body" class="text-left"></tbody>
            </table>
        </div>

        {{--PAGINATION--}}
        <div class="flex items-center justify-between mt-4">
            <div id="work-order-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
            <div id="work-order-pagination" class="flex items-center gap-2"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const workOrderState = {
        page: 1,
        lastPage: 1,
        perPage: 10,
        technicianUserId: {{ $technician->user_id }},
    };

    const PROGRESS_PERCENT = {
        published: 10,
        assigned: 25,
        on_site: 40,
        repair: 60,
        waiting_sparepart: 70,
        documentation_published: 85,
        satisfaction_submitted: 95,
        closed: 100,
    };

    const PRIORITY_BADGE = {
        low: 'bg-[#F0F0F0] text-[#757575]',
        medium: 'bg-[#E7ECFB] text-[#3B4FA6]',
        high: 'bg-[#FFF3CD] text-[#8A6D00]',
        critical: 'bg-[#FBEAEA] text-[#900B09]',
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) return '—';
        return new Date(value).toISOString().slice(0, 10);
    }

    function priorityBadge(priority) {
        const cls = PRIORITY_BADGE[priority] || PRIORITY_BADGE.low;
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium ${cls}">${escapeHtml(priority ? priority.charAt(0).toUpperCase() + priority.slice(1) : '-')}</span>`;
    }

    function slaBadge(slaStatus) {
        if (!slaStatus) return '<span class="text-[#B0B0B0]">-</span>';
        const isOnTime = slaStatus === 'On Time';
        const cls = isOnTime ? 'text-[#115640]' : 'text-[#900B09]';
        return `<span class="font-semibold ${cls}">${escapeHtml(slaStatus)}</span>`;
    }

    function progressBadge(step) {
        const percent = PROGRESS_PERCENT[step] ?? 0;
        let cls = 'bg-[#FBEAEA] text-[#900B09]';
        if (percent >= 100) cls = 'bg-[#E6F4EF] text-[#115640]';
        else if (percent >= 50) cls = 'bg-[#FFF3CD] text-[#8A6D00]';
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold ${cls}">${percent}%</span>`;
    }

    function renderWorkOrderRows(rows) {
        const $body = $('#work-order-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="10" class="text-center p-4 text-[#757575]">No work orders found</td></tr>');
            return;
        }

        const html = rows.map(row => {
            const customer = row.customer || {};
            const machine = row.customer_product || {};

            return `
            <tr class="border-t border-t-[#D9D9D9] align-top">
                <td class="p-3 font-semibold text-[#115640]">${escapeHtml(row.ticket_code)}</td>
                <td class="p-3">${escapeHtml(customer.name)}</td>
                <td class="p-3">${escapeHtml(machine.product?.name)}</td>
                <td class="p-3 max-w-[220px]"><span class="line-clamp-2">${escapeHtml(row.description)}</span></td>
                <td class="p-3">${formatDate(row.created_at)}</td>
                <td class="p-3">${formatDate(row.visit_date)}</td>
                <td class="p-3">${formatDate(row.closed_at)}</td>
                <td class="p-3">${slaBadge(row.sla_status)}</td>
                <td class="p-3">${priorityBadge(row.priority)}</td>
                <td class="p-3">${progressBadge(row.progress)}</td>
            </tr>
        `;
        }).join('');

        $body.html(html);
    }

    function renderWorkOrderPagination(pagination) {
        pagination = pagination || {};
        workOrderState.lastPage = pagination.last_page || 1;

        const from = pagination.total ? ((pagination.current_page - 1) * workOrderState.perPage) + 1 : 0;
        const to = Math.min(pagination.current_page * workOrderState.perPage, pagination.total || 0);
        $('#work-order-showing').text(`Showing ${from} to ${to} of ${pagination.total || 0} entries`);

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

        $('#work-order-pagination').html(buttons);
    }

    function loadWorkOrders() {
        $('#work-order-table-body').html('<tr><td colspan="10" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: '/api/aftersales/tickets',
            method: 'GET',
            data: {
                page: workOrderState.page,
                per_page: workOrderState.perPage,
                assigned_technician_id: workOrderState.technicianUserId,
            },
            success: function (response) {
                renderWorkOrderRows(response.data);
                renderWorkOrderPagination(response);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load work orders';
                $('#work-order-table-body').html(`<tr><td colspan="10" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
            }
        });
    }

    $(function () {
        loadWorkOrders();

        $(document).on('click', '#work-order-pagination button', function () {
            const page = Number($(this).data('page'));
            if (!page || page < 1 || page > workOrderState.lastPage) return;
            workOrderState.page = page;
            loadWorkOrders();
        });
    });
</script>
@endsection
