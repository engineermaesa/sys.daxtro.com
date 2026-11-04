<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('pdfs.aftersales._layout_head', ['watermarkCopy' => true])
    <style>
        .agreement-watermark {
            position: fixed;
            top: 42%;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 40pt;
            font-weight: bold;
            color: #115640;
            opacity: 0.10;
            transform: rotate(-35deg);
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="agreement-watermark">{{ $copyLabel }}</div>
    <h1>{{ $doc->template->title }}</h1>
    <p class="sub">No. {{ $docNumber }} · Version {{ $doc->template_version }}</p>

    <table class="nob">
        <tr><td width="18%"><strong>Customer</strong></td><td>: {{ $ticket->customer?->name }}</td><td width="18%"><strong>Technician</strong></td><td>: {{ $ticket->technician?->name }}</td></tr>
        <tr><td><strong>Ticket</strong></td><td>: {{ $ticket->ticket_code }}</td><td><strong>Signed by (customer)</strong></td><td>: {{ $doc->customer_name }}</td></tr>
    </table>

    @foreach ($sections as $section)
        <h2>{{ $section['code'] }}. {{ $section['title'] }}</h2>
        <table>
            @foreach ($section['clauses'] as $clause)
                <tr>
                    <td width="4%" style="text-align:center;">{{ in_array($clause['key'], $accepted, true) ? '☑' : '☐' }}</td>
                    <td>{{ $clause['text'] }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    @if ($closing)
        <p style="margin-top: 10px;"><em>{{ $closing }}</em></p>
    @endif

    @include('pdfs.aftersales._signatures')
</body>
</html>
