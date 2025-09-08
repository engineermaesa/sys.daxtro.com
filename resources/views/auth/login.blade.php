@extends('layouts.auth')

@section('content')
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="row justify-content-center w-100">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg">
                <div class="card-body p-0">
                    <div class="row">
                        <!-- Logo -->
                        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-white">
                            <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid p-4" style="max-height: 200px;" alt="Logo Daxtro">
                        </div>

                        <!-- Login Form -->
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center mb-4">
                                    <h1 class="h4 text-gray-900">DAXTRO</h1>
                                </div>

                                <form class="user" method="POST" action="{{ route('login') }}">
                                    @csrf
                                    <div class="form-group">
                                        <input type="email" name="email" class="form-control form-control-user" id="email" placeholder="Enter Email Address..." required autofocus>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" name="password" class="form-control form-control-user" id="password" placeholder="Password" required>
                                    </div>
                                    <button type="submit" style="background:#115641;" class="btn btn-primary btn-user btn-block">Login</button>
                                </form>

                                <hr>
                                <div class="text-center">
                                    <a class="small" href="{{ route('password.request') }}">Forgot Password?</a>
                                </div>
                            </div>
                        </div>
                        <!-- End Login Form -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
