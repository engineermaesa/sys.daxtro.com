<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8">@include('pdfs.aftersales._layout_head')</head>
<body>
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
