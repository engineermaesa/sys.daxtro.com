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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @vite('resources/css/app.css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Fixed Header Styling -->
    <style>
        /* Compensate for fixed header */
        #content-wrapper {
            padding-top: 90px; /* Height of fixed header + margin */
        }
        
        /* Sidebar adjustments - ensure it stays full height */
        .sidebar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            height: 100vh !important;
            z-index: 1020;
        }
        
        /* Content wrapper positioning */
        #content-wrapper {
            margin-left: 224px; /* Sidebar width (same as header left) */
            min-height: 100vh;
        }
        
        /* Additional padding for content */
        .container-fluid {
            padding-top: 20px;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .topbar {
                left: 0 !important; /* Full width on mobile */
                padding: 0 1rem !important;
            }
            
            #content-wrapper {
                margin-left: 0; /* No margin on mobile */
                padding-top: 80px; /* Smaller header on mobile + margin */
            }
            
            .sidebar {
                transform: translateX(-100%); /* Hide sidebar on mobile */
                transition: transform 0.3s ease;
            }
            
            .sidebar.toggled {
                transform: translateX(0); /* Show sidebar when toggled */
            }
        }
        
        /* Sidebar toggled state adjustments */
        .sidebar.toggled ~ #content-wrapper {
            margin-left: 90px; /* Collapsed sidebar width */
        }
        
        .sidebar.toggled ~ #content-wrapper .topbar {
            left: 90px; /* Adjust header position */
        }
        
        /* Ensure header transitions smoothly */
        .topbar {
            transition: left 0.3s ease;
        }
        
        /* Enhanced header user profile styling */
        .topbar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        /* Header branding enhancement */
        .header-brand {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        /* Make dropdown menu more attractive */
        .dropdown-menu {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: none;
        }
    </style>
    
    @yield('styles')
</head>
<body id="page-top">
	<div id="loader" class="loader hidden"></div>
    <div class="flex">
        <div class="w-1/6">
            @include('partials.sidebar')
        </div>
        <div class="flex flex-col w-5/6">
            <div id="content">
                <div class="bg-[#E8EFEC] px-6">
                    @yield('content')
                </div>
            </div>
            {{-- @include('partials.footer') --}}
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
            
            // Handle sidebar toggle for header positioning
            $('#sidebarToggle, #sidebarToggleTop').on('click', function() {
                setTimeout(function() {
                    const sidebar = $('.sidebar');
                    const header = $('.topbar');
                    
                    if (sidebar.hasClass('toggled')) {
                        // Sidebar collapsed
                        header.css('left', '90px');
                    } else {
                        // Sidebar expanded
                        header.css('left', '224px');
                    }
                }, 300); // Wait for sidebar animation
            });
            
            // Handle window resize
            $(window).on('resize', function() {
                const header = $('.topbar');
                if ($(window).width() <= 768) {
                    header.css('left', '0');
                } else {
                    const sidebar = $('.sidebar');
                    if (sidebar.hasClass('toggled')) {
                        header.css('left', '90px');
                    } else {
                        header.css('left', '224px');
                    }
                }
            });
        });
    </script>
    @yield('scripts')
</body>
</html>
