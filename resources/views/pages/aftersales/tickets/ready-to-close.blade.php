@extends('layouts.app')

{{-- READY TO CLOSE - Head Aftersales (PRD v3.0 W5, AS-CLS-01 / AS-HC-02).
     Daftar tiket Documentation Published beserta status Happy Call; Close manual, nonaktif sampai Happy Call selesai.
     Hanya untuk permission aftersales.tickets.close (Head Aftersales).
     [API] GET /api/aftersales/tickets?ready_to_close=1 -> baris: id, ticket_code, customer.name, average_score,
           happy_call { due_at, done_at, overdue }; POST /api/aftersales/tickets/{id}/close. --}}

@section('content')
@php abort_unless(hasPermission(auth()->user(), 'aftersales.tickets.close'), 403); @endphp
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <h1 class="text-[#115640] font-semibold text-2xl">Ready to Close</h1>
        <p class="text-[#757575] mt-1">Tickets with published documentation. Closing is manual and requires a completed Happy Call.</p>

        {{-- ALERTS: Happy Call lewat H+3 (AS-HC-02) --}}
        <div id="rtc-alerts" class="hidden bg-white rounded-lg border border-[#F0C36D] mt-4 p-4">
            <p class="font-bold text-[#8A6D00]"><i class="bi bi-bell"></i> Happy Call overdue</p>
            <div id="rtc-alert-list" class="mt-2"></div>
        </div>

        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex items-stretch gap-3">
                <div class="flex-1 relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="rtc-search" placeholder="Search ticket code or customer"
                        class="w-full pl-10 pr-3 py-2 border border-[#D9D9D9] rounded-lg! text-left text-[#1E1E1E] focus:outline-none!">
                </div>
                <button type="button" id="rtc-search-btn" class="bg-[#115640] text-white px-6 rounded-lg hover:bg-[#0d4633] transition-colors">Search</button>
            </div>
        </div>

        <div class="table-cards-wrap bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="table-cards w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-left uppercase text-xs font-bold">Ticket ID</th>
                        <th class="p-3 text-left uppercase text-xs font-bold">Customer</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Score</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Happy Call</th>
                        <th class="p-3 text-center uppercase text-xs font-bold">Action</th>
                    </tr>
                </thead>
                <tbody id="rtc-body"></tbody>
            </table>
            <div class="flex justify-between items-center px-3 py-2">
                <div id="rtc-showing" class="font-semibold">Showing 0-0 of 0</div>
                <div>
                    <button id="rtc-prev" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"><i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i></button>
                    <button id="rtc-next" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"><i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i></button>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- TEMPLATES: baris tabel dan item alert; elemen ber-data-ref diisi lewat fill() --}}
<template id="tpl-rtc-row">
    <tr class="border-t border-t-[#D9D9D9]">
        <td class="p-3 text-left font-semibold text-[#115640]" data-label="Ticket ID" data-primary data-ref="code"></td>
        <td class="p-3 text-left text-black" data-label="Customer" data-ref="customer"></td>
        <td class="p-3 text-center text-black" data-label="Score" data-ref="score"></td>
        <td class="p-3 text-center" data-label="Happy Call">
            <span class="font-semibold" data-ref="hcStatus"></span>
            <div class="text-[10px] text-[#757575]" data-ref="hcDue"></div>
        </td>
        <td class="p-3 text-center" data-label="Action" data-actions>
            <a class="rtc-open border border-[#D9D9D9] rounded-md px-3 py-1 text-[#115640] font-semibold mr-1" data-ref="open">Open</a>
            <button type="button" class="rtc-close bg-[#115640] text-white font-semibold rounded-md px-3 py-1 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">Close</button>
        </td>
    </tr>
</template>

<template id="tpl-rtc-alert">
    <div class="flex items-center justify-between text-xs" style="padding: 4px 0; gap: 8px;">
        <span data-ref="text"></span>
        <a class="text-[#115640] underline font-semibold" data-ref="link">Open ticket</a>
    </div>
</template>
@endsection

@section('scripts')
<script>
    const rtc = { page: 1, perPage: 10, search: '', lastPage: 1 };
    const API_TICKETS = '/api/aftersales/tickets';
    const TICKET_PAGE = '/aftersales/tickets/';

    function clone(id) { return $(document.getElementById(id).content.firstElementChild.cloneNode(true)); }
    function fill($el, values) {
        Object.keys(values).forEach(function (key) {
            const v = values[key];
            $el.find('[data-ref="' + key + '"]').addBack('[data-ref="' + key + '"]').text(v === null || v === undefined ? '' : v);
        });
        return $el;
    }
    function fmtDate(v) {
        return v ? new Date(v).toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }) : '-';
    }
    function showMessage(text, color) {
        $('#rtc-body').empty().append($('<tr>').append($('<td colspan="5" class="text-center p-4">').css('color', color || '#757575').text(text)));
    }

    function renderRows(rows) {
        const $body = $('#rtc-body').empty();
        if (!rows || !rows.length) return showMessage('No tickets are ready to close.');

        rows.forEach(function (row) {
            const hc = row.happy_call || {};
            const done = !!hc.done_at;
            const $row = fill(clone('tpl-rtc-row'), {
                code: row.ticket_code,
                customer: (row.customer || {}).name || '-',
                score: row.average_score === null || row.average_score === undefined ? '-' : Number(row.average_score).toFixed(1) + ' / 3',
                hcStatus: done ? 'Done' : 'Not done',
                hcDue: done ? fmtDate(hc.done_at) : (hc.due_at ? 'Due ' + fmtDate(hc.due_at) + ' (H+3)' : ''),
            });
            $row.find('[data-ref="hcStatus"]').css('color', done ? '#115640' : (hc.overdue ? '#900B09' : '#3B4FA6'));
            $row.find('[data-ref="open"]').attr('href', TICKET_PAGE + row.id);
            // AC-CLS-01: Close nonaktif sampai Happy Call selesai
            $row.find('.rtc-close').attr('data-id', row.id).attr('data-code', row.ticket_code).prop('disabled', !done)
                .attr('title', done ? '' : 'Complete the Happy Call first');
            $body.append($row);
        });
    }

    // Alert Happy Call overdue (AS-HC-02). Sementara diturunkan dari daftar yang sama.
    // [API] ganti ke endpoint notifikasi in-app bila BE sudah menyediakan.
    function renderAlerts(rows) {
        const overdue = (rows || []).filter(function (r) { return r.happy_call && !r.happy_call.done_at && r.happy_call.overdue; });
        $('#rtc-alerts').toggleClass('hidden', !overdue.length);
        const $list = $('#rtc-alert-list').empty();
        overdue.forEach(function (r) {
            const $item = fill(clone('tpl-rtc-alert'), {
                text: r.ticket_code + ' · ' + ((r.customer || {}).name || '-') + ' · Happy Call was due ' + fmtDate(r.happy_call.due_at),
            });
            $item.find('[data-ref="link"]').attr('href', TICKET_PAGE + r.id);
            $list.append($item);
        });
    }

    function renderPagination(res) {
        const total = res.total || 0;
        const current = res.current_page || 1;
        rtc.lastPage = Math.max(1, res.last_page || 1);
        const from = total === 0 ? 0 : (current - 1) * rtc.perPage + 1;
        $('#rtc-showing').text('Showing ' + from + '-' + Math.min(total, current * rtc.perPage) + ' of ' + total);
        $('#rtc-prev').prop('disabled', current <= 1);
        $('#rtc-next').prop('disabled', current >= rtc.lastPage);
    }

    function load() {
        showMessage('Loading...');
        $.ajax({
            url: API_TICKETS,
            method: 'GET',
            data: { ready_to_close: 1, page: rtc.page, per_page: rtc.perPage, search: rtc.search },
            headers: { Accept: 'application/json' },
            success: function (res) {
                renderRows(res.data);
                renderAlerts(res.data);
                renderPagination(res);
            },
            error: function (xhr) {
                showMessage((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load tickets.', '#900B09');
            }
        });
    }

    $(document).on('click', '.rtc-close', function () {
        const id = $(this).data('id');
        const code = $(this).data('code');
        Swal.fire({
            title: 'Close ' + code + '?',
            text: 'Closing is manual and cannot be undone from here.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Close ticket',
            confirmButtonColor: '#115640'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({ url: API_TICKETS + '/' + id + '/close', method: 'POST', headers: { Accept: 'application/json' } })
                .done(function () {
                    Swal.fire({ title: 'Ticket closed', icon: 'success', timer: 1400, showConfirmButton: false });
                    load();
                })
                .fail(function (xhr) {
                    const j = xhr.responseJSON || {};
                    Swal.fire({ title: 'Cannot close', text: j.message || 'Failed to close ticket.', icon: 'error', confirmButtonColor: '#115640' });
                });
        });
    });

    function search() { rtc.search = $('#rtc-search').val(); rtc.page = 1; load(); }
    $('#rtc-search-btn').on('click', search);
    $('#rtc-search').on('keydown', function (e) { if (e.key === 'Enter') search(); });
    $('#rtc-prev').on('click', function () { if (rtc.page > 1) { rtc.page -= 1; load(); } });
    $('#rtc-next').on('click', function () { if (rtc.page < rtc.lastPage) { rtc.page += 1; load(); } });

    $(load);
</script>
@endsection
