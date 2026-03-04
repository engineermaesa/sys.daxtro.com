@extends('layouts.auth')

@section('content')
<section class="min-h-screen! flex! items-center! justify-center! bg-[#E7F3EE]!">
    <div class="p-10! bg-white! rounded-xl shadow-sm max-w-[425px]">

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
                <input type="email" name="email" class="w-full rounded-lg px-3 py-2 border border-[#D9D9D9] focus:outline-[#115640]" id="email" placeholder="Enter your email address" required autofocus>
            </div>
            
            {{-- PASSWORD --}}
            <div class="mt-4">
                <label for="password" class="text-[#1E1E1E]">Password</label>
                <input type="password" name="password" class="w-full rounded-lg px-3 py-2 border border-[#D9D9D9] focus:outline-[#115640]" id="password" placeholder="Password" required>
            </div>

            <div class="mt-10">
                <button type="submit" class="bg-[#115640] w-full rounded-lg text-white py-3 cursor-pointer">Login</button>

                <a class="small" href="{{ route('password.request') }}">
                    <div class="w-full bg-white text-[#1E1E1E] text-center border border-[#D9D9D9] py-3 rounded-lg mt-3">
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
