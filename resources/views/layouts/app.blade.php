<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DAXTRO</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('sb-admin-2/vendor/fontawesome-free/css/all.min.css') }}?ver=1.0.4" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('sb-admin-2/css/sb-admin-2.min.css') }}?ver=1.0.4" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/summernote-bs5.min.css') }}?ver=1.0.4" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatables.min.css') }}?ver=1.0.4" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/select2.min.css') }}?ver=1.0.4">
	<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/notfy.min.css') }}?ver=1.0.4">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert2.min.css') }}?ver=1.0.4">
	<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/main.css') }}?ver=1.0.4">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('styles')
</head>
<body id="page-top">
	<div id="loader" class="loader hidden"></div>
    <div id="wrapper">
        @include('partials.sidebar')
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                @include('partials.header')
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
            @include('partials.footer')
        </div>
    </div>
    <script src="{{ asset('sb-admin-2/vendor/jquery/jquery.min.js') }}?ver=1.0.4"></script>
    <script src="{{ asset('sb-admin-2/vendor/bootstrap/js/bootstrap.bundle.min.js') }}?ver=1.0.4"></script>
    <script src="{{ asset('sb-admin-2/vendor/jquery-easing/jquery.easing.min.js') }}?ver=1.0.4"></script>
    <script src="{{ asset('sb-admin-2/js/sb-admin-2.min.js') }}?ver=1.0.4"></script>
	<script src="{{ asset('assets/js/datatables.min.js') }}?ver=1.0.4"></script>
	<script src="{{ asset('assets/js/select2.min.js') }}?ver=1.0.4"></script>
	<script src="{{ asset('assets/js/notyf.min.js') }}?ver=1.0.4"></script>
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}?ver=1.0.4"></script>
	<script src="{{ asset('assets/js/main.js') }}?ver=1.0.4"></script>
    <script>
        var notyf = new Notyf({
            position: {
            x: 'right',
            y: 'top',
            }
        });
        
        $(document).ready(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        });
    </script>
    @yield('scripts')
</body>
</html>
