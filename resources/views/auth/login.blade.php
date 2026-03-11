@extends('layouts.auth')

@section('content')
<section class="min-h-screen! flex! items-center! justify-center! bg-[#E7F3EE]! relative overflow-hidden">
    {{-- Background decorative images --}}
    <img src="{{ asset('assets/images/left-img.svg') }}" alt="" aria-hidden="true"
        class="pointer-events-none select-none"
        style="position: absolute; bottom: 52px; left: 52px; width: clamp(180px, 26vw, 360px); height: auto; opacity: 0.7;">

    <img src="{{ asset('assets/images/right-img.svg') }}" alt="" aria-hidden="true"
        class="pointer-events-none select-none"
        style="position: absolute; top: 52px; right: 52px; width: clamp(160px, 22vw, 320px); height: auto; opacity: 0.7;">

    <div class="p-10! bg-white! rounded-xl shadow-sm max-w-[425px] relative z-10">

        {{-- LOGO DAXTRO --}}
        <div class="flex justify-center">
            <img src="{{ asset('assets/images/logo.png') }}" style="max-height: 60px;" alt="Logo Daxtro">
        </div>

        
        {{-- ACCENT TEXT --}}
        <h1 class="text-2xl! font-bold text-[#1E1E1E] mt-10">Login to Daxtro ERP</h1>
        <p class="text-[#757575] text-base! mt-1">Manage your business operations in one integrated system.</p>

        <form class="my-5" method="POST" action="{{ route('login') }}">
            @csrf

            {{-- EMAIL --}}
            <div>
                <label for="email" class="text-[#1E1E1E]">Email</label>
                <input type="email" name="email"
                    class="w-full rounded-lg px-3 py-2 border border-[#D9D9D9] focus:outline-[#115640]" id="email"
                    placeholder="Enter your email address" required autofocus>
            </div>

            {{-- PASSWORD --}}
            <div class="mt-4">
                <label for="password" class="text-[#1E1E1E]">Password</label>
                <input type="password" name="password"
                    class="w-full rounded-lg px-3 py-2 border border-[#D9D9D9] focus:outline-[#115640]" id="password"
                    placeholder="Enter your password" required>
            </div>

            <div class="mt-10">
                <button type="submit"
                    class="bg-[#115640] w-full rounded-lg text-white py-3 cursor-pointer">Login</button>

                <a class="small" href="{{ route('password.request') }}">
                    <div
                        class="w-full bg-white text-[#1E1E1E] text-center border border-[#D9D9D9] py-3 rounded-lg mt-3">
                        Forgot Password?
                    </div>
                </a>
            </div>

        </form>
    </div>
</section>
@endsection

@push('scripts')
@if(session('success'))
<script>
    $(document).ready(function () {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: @json(session('success'))
        });
    });
</script>
@endif
@if($errors->has('email'))
<script>
    $(document).ready(function () {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: @json($errors->first('email'))
        });
    });
</script>
@endif
@endpush