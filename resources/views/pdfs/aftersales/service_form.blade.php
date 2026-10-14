<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8">@include('pdfs.aftersales._layout_head')</head>
<body>
    @php
        $product = $ticket->customerProduct;
        $visit = $ticket->visits->sortByDesc('scheduled_at')->first();
    @endphp

    <h1>Machine Service Form</h1>
    <p class="sub">No. {{ $docNumber }}</p>

    <h2>1. Customer Information</h2>
    <table class="nob">
        <tr><td width="20%"><strong>Customer</strong></td><td>: {{ $ticket->customer?->name }}</td><td width="20%"><strong>PIC</strong></td><td>: {{ $ticket->customer?->pic_name }}</td></tr>
        <tr><td><strong>Address</strong></td><td>: {{ $ticket->customer?->address }}</td><td><strong>Phone</strong></td><td>: {{ $ticket->customer?->phone }}</td></tr>
        <tr><td><strong>Technician</strong></td><td>: {{ $ticket->technician?->name }}</td><td><strong>Service date</strong></td><td>: {{ ($visit?->actual_start ?? $visit?->scheduled_at)?->timezone('Asia/Jakarta')->format('d M Y') }}</td></tr>
    </table>

    <h2>2. Machine Information</h2>
    <table class="nob">
        <tr><td width="20%"><strong>Machine type</strong></td><td>: {{ $product?->productType?->name }}</td><td width="20%"><strong>Service type</strong></td><td>: {{ ucfirst((string) $ticket->service_type) }}</td></tr>
        <tr><td><strong>Serial number</strong></td><td>: {{ $product?->serial_number }}</td><td><strong>Capacity</strong></td><td>: {{ $product?->capacity }}</td></tr>
        <tr><td><strong>Refrigerant</strong></td><td>: {{ $product?->refrigerant }}</td><td><strong>Condenser</strong></td><td>: {{ $product?->condenser_type }}</td></tr>
        <tr><td><strong>Voltage / Phase / Hz</strong></td><td colspan="3">: {{ $product?->voltage }} / {{ $product?->phase }} / {{ $product?->hz }}</td></tr>
    </table>

    <h2>3. Inspection</h2>
    @foreach ($groups as $group)
        <p style="margin: 8px 0 2px;"><strong>{{ $group['code'] }}. {{ $group['title'] }}</strong></p>
        <table>
            <tr><th width="5%">No</th><th width="28%">Item</th><th width="22%">Standard</th><th width="38%">Result</th><th width="7%">Status</th></tr>
            @foreach ($group['items'] as $item)
                <tr>
                    <td>{{ $item['number'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="muted">{{ $item['standard'] }}</td>
                    <td>
                        @foreach ($item['fields'] as $field)
                            @if (filled($field['value']))
                                {{ $field['label'] }}: <strong>{{ $field['value'] }}</strong> {{ $field['unit'] }}@if (! $loop->last); @endif
                            @endif
                        @endforeach
                    </td>
                    <td class="badge">{{ strtoupper($item['status'] === 'na' ? 'N/A' : ($item['status'] ?? '-')) }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    @include('pdfs.aftersales._signatures')

    @if (count($photos))
        <div style="page-break-before: always;"></div>
        <h2>Evidence Photos</h2>
        <table>
            @foreach (array_chunk($photos, 3) as $row)
                <tr>
                    @foreach ($row as $photo)
                        <td class="photo">
                            @if ($photo['image'])<img src="{{ $photo['image'] }}" alt="photo">@endif
                            <div>{{ $photo['caption'] }}</div>
                        </td>
                    @endforeach
                    @for ($i = count($row); $i < 3; $i++)<td class="photo"></td>@endfor
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
