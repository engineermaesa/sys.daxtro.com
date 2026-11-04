<table class="sig" style="margin-top: 14px;">
    <tr>
        @foreach (['technician' => 'Serviced by / Technician', 'supervisor' => 'Checked by / Supervisor', 'customer' => 'Approved by / Customer'] as $role => $label)
            <td>
                <div class="muted" style="font-size: 8pt;">{{ $label }}</div>
                @if (! empty($signatures[$role]['image']))
                    <img src="{{ $signatures[$role]['image'] }}" alt="signature">
                @endif
                <div><strong>{{ $signatures[$role]['signer_name'] ?? '-' }}</strong></div>
                <div class="muted" style="font-size: 7pt;">{{ isset($signatures[$role]['signed_at']) ? $signatures[$role]['signed_at']->timezone('Asia/Jakarta')->format('d M Y H:i') : '' }}</div>
            </td>
        @endforeach
    </tr>
</table>
