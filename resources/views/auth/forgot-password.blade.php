@extends('layouts.auth')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-password-image"></div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-2">Forgot Your Password?</h1>
                                    <p class="mb-4">We get it, stuff happens. Just enter your email address below and we'll send you a link to reset your password!</p>
                                </div>
                                @include('partials.flash')
                                <form class="user" method="POST" action="{{ route('password.email') }}">
                                    @csrf
                                    <div class="form-group">
                                        <input type="email" name="email" class="form-control form-control-user" id="email" placeholder="Enter Email Address..." required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">Reset Password</button>
                                </form>
                                <hr>
                                {{-- <div class="text-center">
                                    <a class="small" href="#">Create an Account!</a>
                                </div> --}}
                                <div class="text-center">
                                    <a class="small" href="{{ route('login') }}">Already have an account? Login!</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="min-h-screen! flex! items-center! justify-center! bg-[#E7F3EE]!">
    <div class="p-10! bg-white! rounded-xl shadow-sm max-w-[425px]">
        
        {{-- LOGO DAXTRO --}}
        <div class="flex justify-center">
            <img src="{{ asset('assets/images/logo.png') }}" style="max-height: 60px;" alt="Logo Daxtro">
        </div>

        {{-- ACCENT TEXT --}}
        <h1 class="text-2xl! font-bold text-[#1E1E1E] mt-10">Forgot Your Password?</h1>
        <p class="text-[#757575] text-base! mt-1">Please enter your email address below and we'll send you a link to reset your password!</p>

        @include('partials.flash')

        <form class="user" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group py-5">
                <label for="email" class="text-[#1E1E1E]">Email</label>    
                <input type="email" name="email" class="w-full rounded-lg px-3 py-2 border border-[#D9D9D9] focus:outline-[#115640]" id="email" placeholder="Enter Email Address..." required>
            </div>
            <button type="submit" class="bg-[#115640] w-full rounded-lg text-white py-3 cursor-pointer">Reset Password</button>
        </form>
                
        <a class="small" href="{{ route('login') }}">
            <div class="w-full bg-white text-[#1E1E1E] text-center border border-[#D9D9D9] py-3 rounded-lg mt-3">
                Go Back to Login
            </div>
        </a>
    </div>
</div>
@endsection
