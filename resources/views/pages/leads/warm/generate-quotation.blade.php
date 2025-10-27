@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <strong>{{ $quotation ? 'View Quotation' : 'Generate Quotation' }}</strong>
                    </div>
                    <div class="card-body pt-3">
                        @php
                            $disabled = $isEditable ? '' : 'disabled';
                            $defaultSegment = strtolower($defaultSegment ?? '');
                            $segmentOptions = $segments ?? collect();
                        @endphp

                        @if (!$isEditable && $quotation)
                            <div class="alert alert-warning">
                                This quotation is already <strong>{{ ucfirst($quotation->status) }}</strong> and cannot be
                                edited.
                            </div>
                        @endif
                        @if ($quotation && $quotation->status === 'rejected' && isset($rejection))
                            <div class="alert alert-danger">
                                Quotation rejected by <b>{{ $rejection->reviewer->name ?? $rejection->role }}</b> on
                                {{ $rejection->decided_at ? \Carbon\Carbon::parse($rejection->decided_at)->format('d M Y') : '' }}
                                <strong>Notes:</strong> {{ $rejection->notes }}
                            </div>
                        @elseif($quotation && isset($approval))
                            <div class="alert alert-success">
                                Quotation approved by <b>{{ $approval->reviewer->name ?? $approval->role }}</b> on
                                {{ $approval->decided_at ? \Carbon\Carbon::parse($approval->decided_at)->format('d M Y') : '' }}
                                <strong>Notes:</strong> {{ $approval->notes }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('leads.my.warm.quotation.store', $claim->id) }}" id="form"
                            require-confirmation="true">
                            @csrf

                            {{-- Items Table --}}
                            {{--  --}}
                            <div class="mb-3">
                                <label class="form-label">Items <i class="required">*</i></label>
                                <table class="table table-bordered" id="items-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Description</th>
                                            <th style="width:100px;">Qty</th>
                                            <th style="width:190px;">Unit Price</th>
                                            <th style="width:100px;">Disc %</th>
                                            <th style="width:150px;">Line Total</th>
                                            @if ($isEditable)
                                                <th style="width:80px;">PDF Visibility</th>
                                                <th style="width:20px;">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($quotation)
                                            @foreach ($quotation->items as $item)
                                                <tr data-item-id="{{ $item->id }}">
                                                    <td style="max-width: 100px;">
                                                        <select name="product_id[]" class="form-select item-product select2" {{ $disabled }} required>
                                                            <option value="">Select Product</option>
                                                            <option value="add_on" {{ is_null($item->product_id) ? 'selected' : '' }}>Add On Product</option>
                                                            @foreach ($products as $p)
                                                                <option value="{{ $p->id }}"
                                                                    {{ $item->product_id == $p->id ? 'selected' : '' }}
                                                                    data-name="{{ $p->name }}"
                                                                    data-sku="{{ $p->sku }}"
                                                                    data-price="{{ $p->price }}"
                                                                    data-gov="{{ $p->government_price }}"
                                                                    data-corp="{{ $p->corporate_price }}"
                                                                    data-pers="{{ $p->personal_price }}"
                                                                    data-fob="{{ $p->fob_price }}">
                                                                    {{ $p->name }} ({{ $p->sku }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td style="max-width: 50px;">
                                                        <input type="text" name="description[]"
                                                            class="form-control item-desc text-start" value="{{ $item->description }}"
                                                            {{ $item->product_id ? 'readonly' : '' }} {{ $disabled }}
                                                            required>
                                                    </td>
                                                    <td><input type="number" name="qty[]" class="form-control item-qty"
                                                            value="{{ $item->qty }}" {{ $disabled }}></td>
                                                    <td>
                                                        <select class="form-select form-select-sm item-segment mb-1 {{ $item->product ? '' : 'd-none' }}" {{ $disabled }}>
                                                            @foreach($segmentOptions as $seg)
                                                                <option value="{{ strtolower($seg->name) }}" {{ strtolower($seg->name) == $defaultSegment ? 'selected' : '' }}>{{ $seg->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="text" name="unit_price[]"
                                                            class="form-control item-price number-input"
                                                            value="{{ number_format($item->unit_price, 0, ',', '.') }}"
                                                            {{ $disabled }} required>
                                                        @if ($item->product)
                                                        <div class="form-text segment-price-info small text-muted">
                                                            <ul class="list-inline mb-0">
                                                            <li class="list-inline-item me-3">
                                                                <span class="fw-semibold">Gov:</span>
                                                                Rp{{ number_format($item->product->government_price, 0, ',', '.') }}
                                                            </li>
                                                            <li class="list-inline-item me-3">
                                                                <span class="fw-semibold">Corp:</span>
                                                                Rp{{ number_format($item->product->corporate_price, 0, ',', '.') }}
                                                            </li>
                                                            <li class="list-inline-item me-3">
                                                                <span class="fw-semibold">Personal:</span>
                                                                Rp{{ number_format($item->product->personal_price, 0, ',', '.') }}
                                                            </li>
                                                            <li class="list-inline-item me-3">
                                                                <span class="fw-semibold">FOB:</span>
                                                                Rp{{ number_format($item->product->fob_price, 0, ',', '.') }}
                                                            </li>
                                                            </ul>
                                                        </div>
                                                        @endif
                                                    </td>
                                                    <td><input type="number" 
                                                        name="discount_pct[]"
                                                            class="form-control item-disc"
                                                            step='0.01'
                                                            min="0"
                                                            max="100"
                                                            value="{{ $item->discount_pct }}" {{ $disabled }}></td>
                                                    <td><input type="text" class="form-control item-total number-input"
                                                            value="{{ number_format($item->line_total, 0, ',', '.') }}"
                                                            readonly></td>
                                                    @if ($isEditable)
                                                        <td class="text-center">
                                                            {{-- Visibility Toggle Button --}}
                                                            <button type="button" class="btn btn-sm visibility-toggle"
                                                                    data-visible="{{ $item->is_visible_pdf ? 'true' : 'false' }}">
                                                                <i class="bi {{ $item->is_visible_pdf ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                                                            </button>
                                                            <input type="hidden" name="is_visible_pdf[]" 
                                                                value="{{ $item->is_visible_pdf ? '1' : '0' }}"
                                                                class="visibility-input">

                                                            {{-- Merge Into Dropdown --}}
                                                            <select name="merge_into_item_id[]"
                                                                class="form-select form-select-sm mt-1 merge-dropdown {{ $item->is_visible_pdf ? 'd-none' : '' }}"
                                                                {{ $disabled }}>
                                                                <option value="">Select visible item...</option>
                                                                @foreach ($quotation->items->where('is_visible_pdf', true) as $visibleItem)
                                                                    <option value="{{ $loop->index }}"
                                                                        {{ $item->merge_into_item_id == $visibleItem->id ? 'selected' : '' }}>
                                                                        {{ $visibleItem->description }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-danger remove-item">&times;</button>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @else
                                            {{-- New quotation - single empty row --}}
                                            <tr data-item-id="new">
                                                <td style="max-width: 100px;">
                                                    <select name="product_id[]" class="form-select item-product select2"
                                                        {{ $disabled }} required>
                                                        <option value="">Select Product</option>
                                                        <option value="add_on">Add On Product</option>
                                                        @foreach ($products as $p)
                                                            <option value="{{ $p->id }}"
                                                                data-name="{{ $p->name }}"
                                                                data-sku="{{ $p->sku }}"
                                                                data-price="{{ $p->price }}"
                                                                data-gov="{{ $p->government_price }}"
                                                                data-corp="{{ $p->corporate_price }}"
                                                                data-pers="{{ $p->personal_price }}"
                                                                data-fob="{{ $p->fob_price }}">
                                                                {{ $p->name }} ({{ $p->sku }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td style="max-width: 50px;">
                                                    <input type="text" name="description[]"
                                                        class="form-control item-desc text-start" readonly {{ $disabled }}>
                                                </td>
                                                <td><input type="number" name="qty[]" class="form-control item-qty"
                                                        value="1" {{ $disabled }} required></td>
                                                <td>
                                                    <select class="form-select form-select-sm item-segment mb-1 d-none" {{ $disabled }}>
                                                        @foreach($segmentOptions as $seg)
                                                            <option value="{{ strtolower($seg->name) }}" {{ strtolower($seg->name) == $defaultSegment ? 'selected' : '' }}>{{ $seg->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="unit_price[]"
                                                        class="form-control item-price number-input" {{ $disabled }}
                                                        required>
                                                    <div class="form-text segment-price-info"></div>
                                                </td>
                                                <td><input type="number" 
                                                        name="discount_pct[]"
                                                        class="form-control item-disc"
                                                        step='0.01'
                                                        min="0"
                                                        max="100" {{ $disabled }}></td>
                                                <td><input type="text" class="form-control item-total number-input"
                                                        readonly></td>
                                                @if ($isEditable)
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm visibility-toggle" data-visible="true">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <input type="hidden" name="is_visible_pdf[]" value="1" class="visibility-input">
                                                        <select name="merge_into_item_id[]" class="form-select form-select-sm mt-1 merge-dropdown d-none">
                                                            <option value="">Select visible item...</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-danger remove-item">&times;</button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                @if ($isEditable)
                                    <button type="button" id="add-item" class="btn btn-sm btn-outline-primary">Add Item</button>
                                @endif
                            </div>

                            {{-- Totals --}}
                            <div class="mb-3">
                                <label class="form-label">Tax (%)</label>
                                <input type="number" step="0.01" name="tax_pct" id="tax_pct" class="form-control"
                                    value="{{ old('tax_pct', $quotation->tax_pct ?? 11) }}" {{ $disabled }} required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subtotal</label>
                                <input type="text" id="subtotal_display" class="form-control"
                                    value="{{ 'Rp' . number_format($quotation->subtotal ?? 0, 0, ',', '.') }}" readonly>
                                <input type="hidden" name="subtotal" id="subtotal"
                                    value="{{ $quotation->subtotal ?? 0 }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tax Amount</label>
                             
                                <input type="text" id="tax_total_display" class="form-control"
                                    value="{{ 'Rp' . number_format($quotation->tax_total ?? 0, 0, ',', '.') }}" readonly>
                                <input type="hidden" name="tax_total" id="tax_total"
                                    value="{{ $quotation->tax_total ?? 0 }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Grand Total</label>
                                <input type="text" id="grand_total_display" class="form-control"
                                    value="{{ 'Rp' . number_format($quotation->grand_total ?? 0, 0, ',', '.') }}"
                                    readonly>
                                <input type="hidden" name="grand_total" id="grand_total"
                                    value="{{ $quotation->grand_total ?? 0 }}">
                            </div>

                            {{-- Payment Type --}}
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                @php
                                    $paymentType = old(
                                        'payment_type',
                                        $quotation?->booking_fee ? 'booking_fee' : 'down_payment',
                                    );
                                @endphp
                                <select name="payment_type" id="payment_type" class="form-select w-100"
                                    {{ $disabled }}>
                                    <option value="booking_fee" {{ $paymentType === 'booking_fee' ? 'selected' : '' }}>
                                        Booking Fee First</option>
                                    <option value="down_payment" {{ $paymentType === 'down_payment' ? 'selected' : '' }}>
                                        Direct Down Payment</option>
                                </select>
                            </div>

                            @if ($isEditable)
                            <div class="mb-3">
                                <label class="form-label">Quick Fill Payment Terms</label>
                                <div>
                                    <button type="button" id="autofill-downpayment" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-magic"></i> Autofill Down Payment Terms
                                    </button>
                                    <small class="text-muted ms-2">Will populate standard down payment terms</small>
                                </div>
                            </div>
                            @endif

                            <div class="mb-3" id="booking_fee_field" style="display:none;">
                                <label class="form-label">Booking Fee Value</label>
                                <input type="text" name="booking_fee" id="booking_fee"
                                    class="form-control number-input" value="{{ number_format(old('booking_fee', $quotation->booking_fee ?? 0), 0, ',', '.') }}"
                                    {{ $disabled }}>
                            </div>

                            {{-- Payment Terms --}}
                            <div class="mb-3">
                                <label class="form-label">Term of Payment (%) <i class="required">*</i></label>
                                <div id="terms-container">
                                    @php
                                        $terms = $quotation
                                            ? $quotation->paymentTerms->pluck('percentage')
                                            : collect([null]);
                                    @endphp
                                    @foreach ($terms as $i => $term)
                                        <div class="input-group mb-2 term-row">
                                            <span class="input-group-text">Term {{ $i + 1 }}</span>
                                           {{-- % --}}
                                            <input
                                                type="number" step="0.01"
                                                name="term_percentage[]"
                                                class="form-control"
                                                value="{{ old("term_percentage.$i", $term) }}"
                                                {{ $disabled }}
                                                required
                                            >

                                            {{-- description --}}
                                            <input
                                                type="text"
                                                name="term_description[]"
                                                class="form-control ms-2"
                                                value="{{ old("term_description.$i", $quotation->paymentTerms[$i]->description ?? '') }}"
                                                placeholder="Description (optional)"
                                                {{ $disabled }}
                                            >

                                            @if ($isEditable)
                                                <button type="button"
                                                    class="btn btn-outline-danger remove-term">&times;</button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @if ($isEditable)
                                    <button type="button" id="add-term" class="btn btn-sm btn-outline-primary">Add
                                        Term</button>
                                @endif
                            </div>

                            {{-- Buttons --}}
                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('leads.my') }}" class="btn btn-light">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                                @if ($quotation)
                                    <a href="{{ route('quotations.download', $quotation->id) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-download"></i> Download Quotation
                                    </a>
                                @endif
                                @if ($isEditable)
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    @if ($isEditable)
        <script>
            //— Helpers to parse/format numbers
            function parseNumber(val) {
                if (!val) return 0;
                return parseFloat(val.toString().replace(/\./g, '').replace(',', '.')) || 0;
            }

            function formatNumber(val) {
                return new Intl.NumberFormat('id-ID').format(val);
            }

            function formatCurrency(val) {
                return 'Rp' + formatNumber(val);
            }

            const defaultSegment = '{{ $defaultSegment }}';

            //— Render the four price tiers as a neat inline list
            function renderPriceTiers(gov, corp, pers, fob) {
                return `
                    <ul class="list-inline mb-0 small text-muted">
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">Gov:</span> Rp${formatNumber(gov)}
                      </li>
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">Corp:</span> Rp${formatNumber(corp)}
                      </li>
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">Personal:</span> Rp${formatNumber(pers)}
                      </li>
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">FOB:</span> Rp${formatNumber(fob)}
                      </li>
                    </ul>
                `;
            }

            function updatePriceForRow(row) {
                let seg = row.find('.item-segment').val() || defaultSegment;
                let opt = row.find('.item-product option:selected');
                let price = 0;

                if (!opt.length) return;

                switch (seg) {
                    case 'government':
                        price = opt.data('gov');
                        break;
                    case 'corporate':
                        price = opt.data('corp');
                        break;
                    case 'fob':
                        price = opt.data('fob');
                        break;
                    default:
                        price = opt.data('pers');
                }

                row.find('.item-price').val(formatNumber(price || 0));
            }

            //— Calculate a single row’s line total
            function calcRow(row) {
                let price = parseNumber(row.find('.item-price').val());
                let qty   = parseFloat(row.find('.item-qty').val()) || 0;
                let disc  = parseFloat(row.find('.item-disc').val()) || 0;
                let line  = (price - (price * disc / 100)) * qty;
                row.find('.item-total').val(formatNumber(line));
                return line;
            }

            //— Sum up all rows, compute tax & grand total
            function calcTotal() {
                let subtotal = 0;
                $('#items-table tbody tr').each(function() {
                    subtotal += calcRow($(this));
                });

                let pct   = parseFloat($('#tax_pct').val()) || 0;
                let tax   = subtotal * pct / 100;
                let grand = subtotal + tax;

                $('#subtotal_display').val(formatCurrency(subtotal));
                $('#tax_total_display').val(formatCurrency(tax));
                $('#grand_total_display').val(formatCurrency(grand));

                $('#subtotal').val(subtotal.toFixed(2));
                $('#tax_total').val(tax.toFixed(2));
                $('#grand_total').val(grand.toFixed(2));
            }

            // Add this after the calcTotal() function in your existing JavaScript

            // Visibility toggle functionality
            $(document).on('click', '.visibility-toggle', function() {
                const $btn = $(this);
                const $row = $btn.closest('tr');
                const $icon = $btn.find('i');
                const $input = $row.find('.visibility-input');
                const $dropdown = $row.find('.merge-dropdown');
                const isVisible = $btn.data('visible') === true || $btn.data('visible') === 'true';
                
                if (isVisible) {
                    // Make invisible - show crossed eye
                    $icon.removeClass('bi-eye').addClass('bi-eye-slash');
                    $input.val('0');
                    $btn.data('visible', false);
                    $dropdown.removeClass('d-none');
                    updateMergeDropdowns();
                } else {
                    // Make visible - show normal eye
                    $icon.removeClass('bi-eye-slash').addClass('bi-eye');
                    $input.val('1');
                    $btn.data('visible', true);
                    $dropdown.addClass('d-none').val('');
                    updateMergeDropdowns();
                }
            });

            // Update merge dropdowns when visibility changes
            function updateMergeDropdowns() {
                const visibleItems = [];
                
                // Collect all visible items
                $('#items-table tbody tr').each(function(index) {
                    const $row = $(this);
                    const $toggle = $row.find('.visibility-toggle');
                    const isVisible = $toggle.data('visible') === true || $toggle.data('visible') === 'true';
                    
                    if (isVisible) {
                        const description = $row.find('.item-desc').val();
                        if (description) {
                            visibleItems.push({ id: index, description: description }); // Use index as ID
                        }
                    }
                });
                
                // Update all merge dropdowns
                $('.merge-dropdown').each(function() {
                    const $dropdown = $(this);
                    const currentValue = $dropdown.val();
                    
                    $dropdown.empty().append('<option value="">Select visible item...</option>');
                    
                    visibleItems.forEach(item => {
                        $dropdown.append(`<option value="${item.id}">${item.description}</option>`);
                    });
                    
                    // Restore selected value if it still exists
                    if (currentValue && visibleItems.find(item => item.id == currentValue)) {
                        $dropdown.val(currentValue);
                    }
                });
            }

            // Update merge dropdowns when item descriptions change
            $(document).on('input', '.item-desc', function() {
                updateMergeDropdowns();
            });

            $(function() {
                // — Initialize existing rows
                $('#items-table tbody tr').each(function() {
                    let row = $(this);
                    let sel = row.find('.item-product');
                    if (sel.val() && sel.val() !== 'add_on') {
                        let opt = sel.find('option:selected');
                        row.find('.item-segment').removeClass('d-none').val(defaultSegment);
                        updatePriceForRow(row);
                        row.find('.segment-price-info').html(
                            renderPriceTiers(
                                opt.data('gov')  || 0,
                                opt.data('corp') || 0,
                                opt.data('pers') || 0,
                                opt.data('fob')  || 0
                            )
                        );
                    } else {
                        row.find('.item-segment').addClass('d-none');
                    }
                    row.find('.item-total').val(formatNumber(parseNumber(row.find('.item-total').val())));
                });

                    $('#autofill-downpayment').on('click', function() {
                    $('#terms-container').empty();
                    
                    const terms = [
                        { percentage: 50, description: 'Down Payment' },
                        { percentage: 50, description: 'Before Shipment' },
                        // { percentage: 50, description: 'AFTER RUNNING TEST OR BEFORE DELIVERY TO INDONESIA' }
                    ];
                    
                    terms.forEach((term, index) => {
                        let html = `
                            <div class="input-group mb-2 term-row">
                                <span class="input-group-text">Term ${index + 1}</span>
                                <input type="number" step="0.01" name="term_percentage[]" class="form-control" 
                                    value="${term.percentage}" required>
                                <input type="text" name="term_description[]" class="form-control ms-2" 
                                    value="${term.description}" placeholder="Description (optional)">
                                <button type="button" class="btn btn-outline-danger remove-term">&times;</button>
                            </div>
                        `;
                        $('#terms-container').append(html);
                    });
                });

                $('#payment_type').on('change', function() {
                    if ($(this).val() === 'down_payment' && $('#terms-container .term-row').length <= 1) {
                        // Only autofill if terms are empty or only have one term
                        $('#autofill-downpayment').trigger('click');
                    }
                });

                $(function() {
                    // Check if this is a new quotation (not editing existing) and payment type is down_payment
                    const isNewQuotation = {{ $quotation ? 'false' : 'true' }};
                    const paymentType = $('#payment_type').val();
                    
                    if (isNewQuotation && paymentType === 'down_payment' && $('#terms-container .term-row').length <= 1) {
                        $('#autofill-downpayment').trigger('click');
                    }
                });

                $(document).on('change', '.item-product', function() {
                    let row       = $(this).closest('tr');
                    let descInput = row.find('.item-desc');
                    let sel       = $(this);

                    if (sel.val() === 'add_on') {
                        descInput
                            .prop('readonly', false)
                            .val('')
                            .attr('required', true);
                        row.find('.item-price').val(formatNumber(0));
                        row.find('.segment-price-info').html('');
                        row.find('.item-segment').addClass('d-none');
                    } else {
                        let opt  = sel.find('option:selected');
                        let name = opt.data('name');
                        let sku  = opt.data('sku');

                        descInput
                            .prop('readonly', true)
                            .val(`${name} (${sku})`)     // otomatis terisi Name (SKU)
                            .removeAttr('required');

                        row.find('.item-segment').removeClass('d-none').val(defaultSegment);
                        updatePriceForRow(row);
                        row.find('.segment-price-info').html(
                            renderPriceTiers(
                                opt.data('gov')  || 0,
                                opt.data('corp') || 0,
                                opt.data('pers') || 0,
                                opt.data('fob')  || 0
                            )
                        );
                    }

                    calcTotal();
                });

                $(document).on('change', '.item-segment', function() {
                    let row = $(this).closest('tr');
                    updatePriceForRow(row);
                    calcTotal();
                });


                // — Recalc when qty, price, discount, or tax pct change
                $(document).on('input', '.item-qty, .item-price, .item-disc, #tax_pct', calcTotal);

                // — Format number inputs on keyup
                $(document).on('keyup', '.number-input', function() {
                    $(this).val(formatNumber(parseNumber($(this).val())));
                });

                // — Add new item row
                $('#add-item').on('click', function() {
                    $('select.select2').select2('destroy');
                    let newRow = $('#items-table tbody tr:first').clone();

                    // Clear all inputs
                    newRow.find('input').val('');
                    newRow.find('select').val('').trigger('change');
                    newRow.find('.item-desc').prop('readonly', true);
                    newRow.find('.item-qty').val(1);
                    newRow.find('.item-price').val(formatNumber(0));
                    newRow.find('.segment-price-info').html('');
                    newRow.find('.item-segment').val(defaultSegment).addClass('d-none');
                    newRow.find('.item-total').val('');

                    // Reset visibility controls
                    newRow.find('.visibility-toggle').data('visible', true).find('i')
                        .removeClass('bi-eye-slash').addClass('bi-eye');
                    newRow.find('.visibility-input').val('1');
                    newRow.find('.merge-dropdown').addClass('d-none').val('');
                    
                    // Update data-item-id for new row
                    newRow.attr('data-item-id', 'new-' + Date.now());
                    
                    $('#items-table tbody').append(newRow);
                    $('.select2').select2({ width: '100%' });
                    updateMergeDropdowns();
                });

                // — Remove item row
                $(document).on('click', '.remove-item', function() {
                    if ($('#items-table tbody tr').length > 1) {
                        $(this).closest('tr').remove();
                        calcTotal();
                    }
                });

                // — Payment terms: add / remove
                $('#add-term').on('click', function() {
                    let idx = $('#terms-container .term-row').length + 1;
                    let html = `
                        <div class="input-group mb-2 term-row">
                        <span class="input-group-text">Term ${idx}</span>
                        <input type="number" step="0.01" name="term_percentage[]" class="form-control" required>
                        <input type="text" name="term_description[]" class="form-control ms-2" placeholder="Description (optional)">
                        <button type="button" class="btn btn-outline-danger remove-term">&times;</button>
                        </div>
                    `;
                    $('#terms-container').append(html);
                });

                $(document).on('click', '.remove-term', function() {
                    $(this).closest('.term-row').remove();
                    $('#terms-container .term-row').each(function(i) {
                        $(this).find('.input-group-text').text('Term ' + (i + 1));
                    });
                });

                // — Show/hide booking fee field
                function toggleBookingFee() {
                    if ($('#payment_type').val() === 'booking_fee') {
                        $('#booking_fee_field').show()
                            .find('input, select, textarea').attr('required', true);
                    } else {
                        $('#booking_fee_field').hide()
                            .find('input, select, textarea').removeAttr('required');
                    }
                }
                $('#payment_type').on('change', toggleBookingFee);
                toggleBookingFee();

                // — Initial total calculation
                calcTotal();
            });
        </script>
    @endif
@endsection
