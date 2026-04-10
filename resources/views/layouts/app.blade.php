<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DAXTRO</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/icon-daxtro.png') }}">
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
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Lora:ital,wght@0,400..700;1,400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (() => {
            try {
                const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                if (collapsed) {
                    document.documentElement.classList.add('sidebar-precollapsed');
                }
            } catch (e) {}
        })();
    </script>
    
    <!-- Fixed Header Styling -->
    <style>
        .topbar {
            transition: left 0.3s ease;
        }

        .dropdown-menu {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: none;
        }

        @media (max-width: 640px) {
            #sidebarWrapper {
                position: fixed;
                left: 0;
                top: 0;
                z-index: 1050;
                height: 100vh;
                transform: translateX(-100%);
            }

            #sidebarWrapper.mobile-open {
                transform: translateX(0);
            }
        }

        html.sidebar-precollapsed #sidebarWrapper {
            width: 88px !important;
        }

        html.sidebar-precollapsed #sidebarInner {
            width: 88px !important;
        }

        html.sidebar-precollapsed #sidebarLogoLink {
            width: 0 !important;
            opacity: 0 !important;
            margin: 0 !important;
            display: none !important;
        }

        html.sidebar-precollapsed .sidebar-label,
        html.sidebar-precollapsed .sidebar-sub-label,
        html.sidebar-precollapsed .sidebar-user-meta,
        html.sidebar-precollapsed .sidebar-chevron,
        html.sidebar-precollapsed .sidebar-submenu {
            display: none !important;
        }

        html.sidebar-precollapsed #headerSidebar {
            justify-content: center !important;
        }
    </style>

    {{-- bg-[#E8EFEC] --}}
    @yield('styles')
</head>
<body id="page-top">
	<div id="loader" class="loader hidden"></div>

    <div id="appLayout" class="flex min-h-screen bg-[#F5F5F5]">
        <aside
            id="sidebarWrapper"
            class="shrink-0 transition-all duration-300 ease-in-out">
            @include('partials.sidebar')
        </aside>

        <main
            id="mainWrapper"
            class="flex-1 min-w-0 transition-all duration-300 ease-in-out">
            <div id="content" class="flex-1 min-h-screen">
                <div class="bg-[#F5F5F5] px-6 min-h-full">
                    @yield('content')
                </div>
            </div>
        </main>
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
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
