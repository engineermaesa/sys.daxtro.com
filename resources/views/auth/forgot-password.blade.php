@extends('layouts.auth')
@section('content')
<div class="min-h-screen! flex! items-center! justify-center! bg-[#E7F3EE]!">
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
        <h1 class="text-2xl! font-bold text-[#1E1E1E] mt-10">Forgot Your Password?</h1>
        <p class="text-[#757575] text-base! mt-1">Please enter your email address below and we'll send you a link to
            reset your password!</p>

        @include('partials.flash')

        <form class="user" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group py-5">
                <label for="email" class="text-[#1E1E1E]">Email</label>
                <input type="email" name="email"
                    class="w-full rounded-lg px-3 py-2 border border-[#D9D9D9] focus:outline-[#115640]" id="email"
                    placeholder="Enter Email Address..." required>
            </div>
            <button type="submit" class="bg-[#115640] w-full rounded-lg text-white py-3 cursor-pointer">Reset
                Password</button>
        </form>

        <a class="small" href="{{ route('login') }}">
            <div class="w-full bg-white text-[#1E1E1E] text-center border border-[#D9D9D9] py-3 rounded-lg mt-3">
                Go Back to Login
            </div>
        </a>
    </div>
</div>
@endsection