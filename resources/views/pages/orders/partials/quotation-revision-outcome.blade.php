@foreach (['BM' => 'Branch Manager', 'FIN' => 'Finance'] as $reviewRole => $reviewLabel)
    <div class="mb-3 last:mb-0">
        <p class="font-semibold">{{ $reviewLabel }}</p>
        @forelse ($reviews->where('role', $reviewRole) as $review)
            <div class="mt-1">
                <span class="{{ $review->decision === 'approve' ? 'text-[#02542D]' : 'text-[#900B09]' }} font-semibold">
                    {{ $review->decision === 'approve' ? 'Approved' : 'Rejected' }}
                </span>
                <p class="text-[#757575]">{{ $review->reviewer?->name ?? 'Unknown reviewer' }}
                    @if ($review->decided_at)
                        &middot; {{ $review->decided_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                    @endif
                </p>
                @if (filled($review->notes))
                    <p class="whitespace-pre-line break-words"><strong>Notes:</strong> {{ $review->notes }}</p>
                @endif
            </div>
        @empty
            <p class="text-[#757575]">No decision recorded</p>
        @endforelse
    </div>
@endforeach
