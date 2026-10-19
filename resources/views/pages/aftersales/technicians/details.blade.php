@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <p class="text-[#757575] text-sm mb-3">
            <a href="{{ route('aftersales.pages.technicians.index') }}" class="hover:underline"><i class="bi bi-chevron-left"></i> Technician</a>
            <span class="mx-1">/</span>
            <span class="font-semibold text-[#1E1E1E]">See Detail</span>
        </p>

        @include('pages.aftersales.technicians.partials.header')
        @include('pages.aftersales.technicians.partials.summary-cards')
        @include('pages.aftersales.technicians.partials.rating-per-aspect')
        @include('pages.aftersales.technicians.partials.recoming-reasons')
        @include('pages.aftersales.technicians.partials.survey-responses')
        @include('pages.aftersales.technicians.partials.tickets')
    </div>
</section>
@endsection

@section('scripts')
<script>
    // helper bersama untuk script di partials/ (script partial dimuat setelah blok ini)
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endsection
