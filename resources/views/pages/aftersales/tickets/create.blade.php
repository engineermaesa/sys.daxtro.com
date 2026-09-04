@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center justify-between mb-1">
            <div>
                <h1 class="text-[#115640] font-bold text-lg uppercase">Form Input Tiket <span class="text-[#757575] font-normal normal-case text-sm">(DAXTRO DATS v3.0)</span></h1>
                <p class="text-[#757575] text-sm mt-1">
                    <a href="{{ route('aftersales.pages.tickets.index') }}" class="hover:underline">All Ticket</a>
                    <span class="mx-1">&gt;</span>
                    <span class="font-semibold text-[#1E1E1E]">Form Input Tiket</span>
                </p>
            </div>
        </div>

        {{-- STEPPER --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-3">
            <div id="ticket-stepper" class="flex items-center">
                @php
                    $steps = [
                        1 => 'Data Customer & Mesin',
                        2 => 'Detail Keluhan',
                        3 => 'Assignment',
                        4 => 'Sparepart',
                        5 => 'Biaya',
                    ];
                @endphp
                @foreach ($steps as $number => $label)
                    <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                        <div class="flex items-center gap-2 step-indicator" data-step-indicator="{{ $number }}">
                            <span class="step-circle shrink-0 w-7 h-7 min-w-[1.75rem] leading-none aspect-square rounded-full inline-flex items-center justify-center text-xs font-semibold border-2 border-[#D9D9D9] text-[#757575]">{{ $number }}</span>
                            <span class="step-label text-sm font-medium text-[#757575] whitespace-nowrap">{{ $label }}</span>
                        </div>
                        @if (!$loop->last)
                            <div class="step-connector flex-1 h-[2px] bg-[#D9D9D9] mx-3"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- FORM --}}
        <form id="ticket-wizard-form" class="mt-4">
            {{-- STEP 1: DATA CUSTOMER & MESIN --}}
            <div class="wizard-step grid grid-cols-1 lg:grid-cols-2 gap-4" data-step="1">
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">1. Data Customer</h6>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Nama Customer<span class="text-red-600">*</span></label>
                        <select id="field-customer" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
                            <option value="">Pilih customer</option>
                            {{-- TODO: fetch GET /api/aftersales/customers lalu isi <option value="{id}" data-*="...">{name}</option> --}}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">PIC Contact<span class="text-red-600">*</span></label>
                        <input type="text" id="field-pic-name" readonly placeholder="Nama PIC"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Email PIC</label>
                        <input type="text" id="field-pic-email" readonly placeholder="pic@company.com"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Telepon / WA<span class="text-red-600">*</span></label>
                        <input type="text" id="field-phone" readonly placeholder="08xxxxxxxxxx"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Lokasi / Area<span class="text-red-600">*</span></label>
                        <input type="text" id="field-location" readonly placeholder="Kota, Provinsi"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">2. Data Mesin & Garansi</h6>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Nama Mesin<span class="text-red-600">*</span></label>
                        <select id="field-machine" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required disabled>
                            <option value="">Pilih customer dulu</option>
                            {{-- TODO: setelah customer dipilih, fetch GET /api/aftersales/customers/{id} lalu isi <option value="{product.id}">{machine_name}</option> --}}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Model / Tipe</label>
                        <input type="text" id="field-machine-model" readonly placeholder="Model atau tipe"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Serial Number<span class="text-red-600">*</span></label>
                        <input type="text" id="field-serial-number" readonly placeholder="SN-XXXXXX"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Tahun Instalasi</label>
                        <input type="text" id="field-install-year" placeholder="e.g. 2022"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                        <p class="text-xs text-[#B0B0B0] mt-1">Info saja — belum ada kolom penyimpanan di database, tidak ikut terkirim.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status Garansi</label>
                        <input type="text" id="field-warranty-status" readonly placeholder="Aktif / Kadaluarsa"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                    </div>
                </div>
            </div>

            {{-- STEP 2: DETAIL KELUHAN --}}
            <div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="2">
                <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">3. Detail Keluhan & Tiket</h6>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nomor Tiket (Auto)</label>
                        <input type="text" readonly value="Akan digenerate otomatis saat disimpan"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F0FAF5] text-[#115640] font-semibold focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Kategori Keluhan<span class="text-red-600">*</span></label>
                        <select id="field-category" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
                            <option value="">Pilih</option>
                            <option value="electrical">Electrical</option>
                            <option value="mechanical">Mechanical</option>
                            <option value="refrigeration_system">Refrigeration System</option>
                            <option value="production">Production</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Level Prioritas</label>
                        <select id="field-priority" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1">Deskripsi Problem<span class="text-red-600">*</span></label>
                    <textarea id="field-description" rows="4" placeholder="Jelaskan keluhan atau problem yang dialami..."
                        class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required></textarea>
                </div>
            </div>

            {{-- STEP 3: ASSIGNMENT & APPROVAL --}}
            <div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="3">
                <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">4. Assignment & Approval</h6>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Teknisi Assigned<span class="text-red-600">*</span></label>
                        <select id="field-technician" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
                            <option value="">Pilih Teknisi</option>
                            {{-- TODO: fetch GET /api/aftersales/technicians lalu isi <option value="{technician.user.id}">{technician.user.name}</option> --}}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Jadwal Visit<span class="text-red-600">*</span></label>
                        <input type="datetime-local" id="field-visit-date"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Target SLA</label>
                        <div class="flex gap-2">
                            <input type="number" id="field-sla-value" min="1" value="3"
                                class="w-1/2 border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <select id="field-sla-unit" class="w-1/2 border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                                <option value="day" selected>Hari</option>
                                <option value="hour">Jam</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Supervisor Approval</label>
                        <select id="field-supervisor" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Pilih Supervisor</option>
                            {{-- TODO: fetch GET /api/aftersales/tickets/supervisors (endpoint baru, role after_sales) lalu isi <option value="{id}">{name}</option> --}}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status Tiket</label>
                        <select disabled class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                            <option selected>Open</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- STEP 4: SPAREPART --}}
            <div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="4">
                <div class="flex items-center justify-between border-b border-[#D9D9D9] pb-3 mb-4">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide mb-0">Sparepart</h6>
                    <button type="button" id="btn-add-sparepart"
                        class="border border-[#115640] text-[#115640] rounded-lg px-3 py-1.5 text-sm hover:bg-[#F5FAF8] cursor-pointer">
                        <i class="bi bi-plus-lg"></i> Tambah Sparepart
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                                <th class="p-2 w-10">No</th>
                                <th class="p-2">Nama Item</th>
                                <th class="p-2">Part Code</th>
                                <th class="p-2 w-24">Qty</th>
                                <th class="p-2 w-32">Satuan</th>
                                <th class="p-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="sparepart-rows"></tbody>
                    </table>
                </div>
            </div>

            {{-- STEP 5: BIAYA --}}
            <div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="5">
                <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Biaya</h6>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                                <th class="p-2">Category</th>
                                <th class="p-2">Amount</th>
                                <th class="p-2 w-28">Unit of Material</th>
                                <th class="p-2">Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="cost-rows">
                            @foreach (['labor' => 'Jasa', 'accommodation' => 'Akomodasi', 'transportation' => 'Transportasi', 'other' => 'Lain-lain'] as $key => $label)
                                <tr class="border-t border-[#D9D9D9]" data-cost-category="{{ $key }}">
                                    <td class="p-2 font-semibold">{{ $label }}</td>
                                    <td class="p-2">
                                        <input type="number" min="0" value="0" class="cost-amount w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                                    </td>
                                    <td class="p-2">
                                        <select class="cost-currency w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                                            <option value="idr" selected>IDR</option>
                                            <option value="usd">USD</option>
                                        </select>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" placeholder="Keterangan..." class="cost-remarks w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="border-t border-[#D9D9D9] bg-[#F5FAF8]">
                                <td class="p-2 font-semibold text-[#115640]">Total</td>
                                <td class="p-2 font-semibold text-[#115640]" id="cost-total" colspan="3">Rp0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- NAVIGATION --}}
            <div class="flex items-center justify-between mt-4">
                <button type="button" id="btn-wizard-back"
                    class="border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 cursor-pointer">
                    <i class="bi bi-arrow-left"></i> Kembali
                </button>
                <button type="button" id="btn-wizard-next"
                    class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer">
                    Lanjut <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const TOTAL_STEPS = 5;
    let currentStep = 1;
    let sparepartRowCount = 0;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function addSparepartRow() {
        sparepartRowCount += 1;
        const rowNumber = sparepartRowCount;

        const html = `
            <tr class="border-t border-[#D9D9D9]" data-sparepart-row="${rowNumber}">
                <td class="p-2">Sparepart ${rowNumber}</td>
                <td class="p-2">
                    <select class="sparepart-item w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        <option value="">Pilih Item</option>
                        {{-- TODO: fetch GET /api/aftersales/spareparts lalu isi <option value="{id}" data-code="{part_number}">{name}</option> --}}
                    </select>
                </td>
                <td class="p-2">
                    <input type="text" readonly placeholder="PRT-XXX-000" class="sparepart-code w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 bg-[#F5F5F5] focus:outline-none!">
                </td>
                <td class="p-2">
                    <input type="number" min="0" value="0" class="sparepart-qty w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                </td>
                <td class="p-2">
                    <select class="sparepart-unit w-full border border-[#D9D9D9] rounded-lg px-3 py-1.5 focus:outline-none!">
                        <option value="">Pilih</option>
                        <option value="pieces">Pieces</option>
                        <option value="set">Set</option>
                        <option value="lot">Lot</option>
                        <option value="kilogram">Kilogram</option>
                    </select>
                </td>
                <td class="p-2 text-center">
                    <button type="button" class="btn-remove-sparepart text-[#900B09] cursor-pointer" title="Hapus baris">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#sparepart-rows').append(html);
    }

    function updateStepper() {
        $('.step-indicator').each(function () {
            const step = Number($(this).data('step-indicator'));
            const $circle = $(this).find('.step-circle');
            const $label = $(this).find('.step-label');

            $circle.removeClass('bg-[#115640] border-[#115640] text-white border-[#D9D9D9] text-[#757575]');
            $label.removeClass('text-[#115640] text-[#757575]');

            if (step < currentStep) {
                $circle.addClass('bg-[#115640] border-[#115640] text-white').html('<i class="bi bi-check-lg"></i>');
                $label.addClass('text-[#115640]');
            } else if (step === currentStep) {
                $circle.addClass('bg-[#115640] border-[#115640] text-white').text(step);
                $label.addClass('text-[#115640]');
            } else {
                $circle.addClass('border-[#D9D9D9] text-[#757575]').text(step);
                $label.addClass('text-[#757575]');
            }
        });

        $('.wizard-step').addClass('hidden');
        $(`.wizard-step[data-step="${currentStep}"]`).removeClass('hidden');

        $('#btn-wizard-back').toggle(currentStep > 1);
        $('#btn-wizard-next').text(currentStep === TOTAL_STEPS ? 'Simpan Ticket' : 'Lanjut');
        $('#btn-wizard-next').html(currentStep === TOTAL_STEPS
            ? 'Simpan Ticket <i class="bi bi-check-lg"></i>'
            : 'Lanjut <i class="bi bi-arrow-right"></i>');
    }

    function validateCurrentStep() {
        // TODO: perkuat validasi tiap step sebelum lanjut (khususnya field required)
        return true;
    }

    function collectPayload() {
        const parts = [];
        $('#sparepart-rows tr').each(function () {
            const itemId = $(this).find('.sparepart-item').val();
            const qty = $(this).find('.sparepart-qty').val();
            const unit = $(this).find('.sparepart-unit').val();
            if (itemId && qty && unit) {
                parts.push({ ref_sparepart_id: itemId, qty: qty, unit: unit });
            }
        });

        const costs = [];
        $('#cost-rows tr[data-cost-category]').each(function () {
            const amount = Number($(this).find('.cost-amount').val() || 0);
            if (amount > 0) {
                costs.push({
                    category: $(this).data('cost-category'),
                    amount: amount,
                    currency: $(this).find('.cost-currency').val(),
                    remarks: $(this).find('.cost-remarks').val() || null,
                });
            }
        });

        return {
            customer_id: $('#field-customer').val(),
            customer_product_id: $('#field-machine').val(),
            category: $('#field-category').val(),
            priority: $('#field-priority').val(),
            description: $('#field-description').val(),
            sla_value: $('#field-sla-value').val(),
            sla_unit: $('#field-sla-unit').val(),
            supervisor_id: $('#field-supervisor').val() || null,
            assigned_technician_id: $('#field-technician').val() || null,
            scheduled_at: $('#field-visit-date').val() || null,
            parts: parts,
            costs: costs,
        };
    }

    function submitTicket() {
        const payload = collectPayload();

         // TODO: sambungkan ke API asli, urutan request:
         // 1) POST /api/aftersales/tickets  -> body: customer_id, customer_product_id, category, priority,
         //    description, sla_value, sla_unit, supervisor_id, parts[], costs[]  => dapat {id}
         // 2) jika payload.assigned_technician_id ada:
         //    POST /api/aftersales/tickets/{id}/assign -> body: assigned_technician_id
         // 3) jika payload.scheduled_at ada:
         //    POST /api/aftersales/tickets/{id}/visits -> body: scheduled_at
         // 4) redirect ke route('aftersales.pages.tickets.index') + notif sukses
        console.log('submit payload (placeholder)', payload);
        window.location.href = '{{ route('aftersales.pages.tickets.index') }}';
    }

    function recalculateCostTotal() {
        let total = 0;
        $('#cost-rows tr[data-cost-category]').each(function () {
            total += Number($(this).find('.cost-amount').val() || 0);
        });
        $('#cost-total').text('Rp' + total.toLocaleString('id-ID'));
    }

    $(function () {
         // TODO: fetch GET /api/aftersales/customers untuk isi #field-customer
         // TODO: fetch GET /api/aftersales/technicians untuk isi #field-technician
         // TODO: fetch GET /api/aftersales/tickets/supervisors untuk isi #field-supervisor
         // TODO: fetch GET /api/aftersales/spareparts untuk isi opsi item di tiap baris sparepart

        for (let i = 0; i < 5; i++) addSparepartRow();
        updateStepper();

        $('#field-customer').on('change', function () {
            const selected = $(this).val();
            $('#field-machine').prop('disabled', !selected);

             // TODO: fetch GET /api/aftersales/customers/{selected} lalu isi:
             // - #field-pic-name, #field-pic-email, #field-phone, #field-location dari data customer
             // - opsi #field-machine dari data.products
        });

        $('#field-machine').on('change', function () {
             // TODO: dari opsi terpilih (data product), isi:
             // - #field-machine-model, #field-serial-number, #field-warranty-status
        });

        $(document).on('change', '.sparepart-item', function () {
            const code = $(this).find('option:selected').data('code') || '';
            $(this).closest('tr').find('.sparepart-code').val(code);
        });

        $('#btn-add-sparepart').on('click', addSparepartRow);

        $(document).on('click', '.btn-remove-sparepart', function () {
            $(this).closest('tr').remove();
        });

        $(document).on('input', '.cost-amount', recalculateCostTotal);

        $('#btn-wizard-back').on('click', function () {
            if (currentStep > 1) {
                currentStep -= 1;
                updateStepper();
            }
        });

        $('#btn-wizard-next').on('click', function () {
            if (!validateCurrentStep()) return;

            if (currentStep < TOTAL_STEPS) {
                currentStep += 1;
                updateStepper();
            } else {
                submitTicket();
            }
        });
    });
</script>
@endsection