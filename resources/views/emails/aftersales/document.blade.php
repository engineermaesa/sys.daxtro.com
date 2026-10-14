<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #1E1E1E; font-size: 14px;">
    <p>Hello,</p>
    <p>
        The <strong>{{ $title }}</strong> for ticket <strong>{{ $ticket->ticket_code }}</strong>
        has been completed and signed. Please find the document attached
        (<em>{{ $copyLabel }}</em>).
    </p>
    <p>Customer: {{ $ticket->customer?->name }}</p>
    <p>Thank you,<br>Daxtro Aftersales</p>
</body>
</html>
