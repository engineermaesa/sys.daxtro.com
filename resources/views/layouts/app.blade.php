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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

    @if(auth()->check() && (hasRole(auth()->user(), 'branch_manager') || hasRole(auth()->user(), 'sales')))
    <script>
    (function () {
        var userId = {{ auth()->id() }};
        @if(hasRole(auth()->user(), 'branch_manager') && auth()->user()->branch_id)
        var branchId = {{ auth()->user()->branch_id }};
        @endif
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        var _leadFormBase  = '{{ url("leads/manage/form") }}';
        var _trashFormBase = '{{ url("trash-leads/form") }}';
        var _availableLeadsUrl = '{{ url("leads/available") }}';

        function _notifBadge() {
            fetch('/api/notifications/unread-count', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    var badge = document.getElementById('notif-badge');
                    var sidebarCount = document.getElementById('sidebar-notif-count');
                    var label = res.count > 99 ? '99+' : res.count;

                    if (res.count > 0) {
                        if (badge) { badge.textContent = label; badge.classList.remove('hidden'); }
                        if (sidebarCount) { sidebarCount.textContent = label; sidebarCount.classList.remove('hidden'); }

                        // Branch Manager Baru Login
                        if (!sessionStorage.getItem('notif_welcomed') && typeof notyf !== 'undefined') {
                            notyf.success('You have ' + res.count + ' notifications unreaded.');
                            sessionStorage.setItem('notif_welcomed', '1');
                        }
                    } else {
                        if (badge) badge.classList.add('hidden');
                        if (sidebarCount) sidebarCount.classList.add('hidden');
                    }
                });
        }

        function _playNotifSound() {
            var audio = new Audio('/sounds/ping.wav');
            audio.volume = 0.5;
            audio.play().catch(function() {});
        }

        function _notifLoad() {
            fetch('/api/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    var list = document.getElementById('notif-list');
                    if (!list) return;
                    var data = res.data || [];
                    if (!data.length) {
                        list.innerHTML = '<li class="px-3 py-3 text-muted text-center" style="font-size:13px;">Belum ada notifikasi</li>';
                        return;
                    }
                    list.innerHTML = data.map(function(n) {
                        var titles = { lead_created: 'New Leads', lead_activity: 'Update Activity Leads', lead_trashed: 'Lead Trashed', lead_available: 'Available Lead', lead_expiring: 'Warning: Lead Will Be Trashed In 3 Days', quotation_submitted: 'Quotation Perlu Review', quotation_reviewed: 'Update Quotation' };
                        var title = titles[n.data.type] || 'Notification';
                        var detail = n.data.lead_name || '';
                        var isUnread = !n.read_at;
                        var date = new Date(n.created_at);
                        var formatted = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                            + ' ' + date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                        var href = n.data.type === 'lead_available'
                            ? _availableLeadsUrl
                            : (n.data.type === 'quotation_submitted' || n.data.type === 'quotation_reviewed') && n.data.url
                                ? n.data.url
                                : (n.data.type === 'lead_created' || n.data.type === 'lead_activity' || n.data.type === 'lead_expiring')
                                    ? _leadFormBase + '/' + n.data.lead_id
                                    : _trashFormBase + '/' + n.data.lead_id;

                        return '<li style="padding:0; font-size:13px; border-bottom:1px solid #f1f1f1;'
                            + (isUnread ? 'background:#eff6ff;' : '')
                            + '" class="' + (isUnread ? 'notif-unread' : '') + '">'
                            + '<a href="' + href + '" onclick="window._notifMarkRead(\'' + n.id + '\', this.closest(\'li\'))" '
                            + 'style="display:block; padding:10px 14px; color:inherit; text-decoration:none;">'
                            + '<div style="font-weight:600;">' + title + '</div>'
                            + '<div style="color:#555;">Lead name: ' + detail + '</div>'
                            + (n.data.sales_name ? '<div style="color:#888;font-size:11px;">By: ' + n.data.sales_name + '</div>' : '')
                            + '<div style="color:#aaa;font-size:11px;margin-top:2px;">' + formatted + '</div>'
                            + '</a>'
                            + '</li>';
                    }).join('');
                });
        }

        function _notifPrepend(data, title) {
            var list = document.getElementById('notif-list');
            if (!list) return;
            var li = document.createElement('li');
            li.style.cssText = 'padding:10px 14px;font-size:13px;background:#eff6ff;border-bottom:1px solid #f1f1f1;';
            li.className = 'notif-unread';
            li.innerHTML = '<div style="font-weight:600;">' + title + '</div>'
                + '<div style="color:#555;">' + (data.company || data.lead_name || '') + '</div>'
                + (data.region_name ? '<div style="color:#888;font-size:11px;">Region: ' + data.region_name + '</div>' : '')
                + (data.sales_name ? '<div style="color:#888;font-size:11px;">Oleh: ' + data.sales_name + '</div>' : '')
                + '<div style="color:#aaa;font-size:11px;margin-top:2px;">Baru saja</div>';
            var loadingLi = list.querySelector('li:first-child');
            if (loadingLi && loadingLi.classList.contains('text-muted') && loadingLi.textContent.includes('Memuat')) {
                list.innerHTML = '';
            }
            list.prepend(li);
        }

        window._notifMarkRead = function(id, el) {
            fetch('/api/notifications/' + id + '/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function() {
                el.style.background = '';
                el.classList.remove('notif-unread');
                _notifBadge();
            });
        };

        // Toggle dropdown — posisi fixed dihitung dari bounding rect bell button
        var bellBtn = document.getElementById('notif-bell');
        var dropdown = document.getElementById('notif-dropdown');
        // Pindahkan dropdown ke body agar keluar dari stacking context transform sidebar
        if (dropdown) document.body.appendChild(dropdown);
        if (bellBtn && dropdown) {
            function _positionDropdown() {
                var rect = bellBtn.getBoundingClientRect();
                var dropW = 340;
                var leftPos = rect.right + 8;
                // Pastikan tidak keluar layar kanan
                if (leftPos + dropW > window.innerWidth) {
                    leftPos = window.innerWidth - dropW - 8;
                }
                dropdown.style.top  = rect.top + 'px';
                dropdown.style.left = leftPos + 'px';
            }

            bellBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var isHidden = dropdown.classList.contains('hidden');
                if (isHidden) {
                    _positionDropdown();
                    dropdown.classList.remove('hidden');
                    _notifLoad();
                } else {
                    dropdown.classList.add('hidden');
                }
            });

            document.addEventListener('click', function(e) {
                var container = document.getElementById('notif-container');
                if (container && !container.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });

            // Tutup dropdown kalau window di-scroll / resize (tapi bukan scroll di dalam dropdown sendiri)
            window.addEventListener('scroll', function(e) {
                if (!dropdown.contains(e.target)) { dropdown.classList.add('hidden'); }
            }, true);
            window.addEventListener('resize', function() { dropdown.classList.add('hidden'); });
        }

        // Mark all read
        var readAllBtn = document.getElementById('notif-read-all');
        if (readAllBtn) {
            readAllBtn.addEventListener('click', function() {
                fetch('/api/notifications/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function() {
                    document.querySelectorAll('#notif-list li.notif-unread').forEach(function(el) {
                        el.style.background = '';
                        el.classList.remove('notif-unread');
                    });
                    var badge = document.getElementById('notif-badge');
                    if (badge) badge.style.display = 'none';
                });
            });
        }

        // Load badge on page load
        _notifBadge();

        // Subscribe to Echo channels after modules have loaded
        window.addEventListener('load', function() {
            if (!window.Echo) return;
            @if(hasRole(auth()->user(), 'branch_manager') && auth()->user()->branch_id)
            window.Echo.private('branch.' + branchId)
                .listen('.lead.created', function(data) {
                    _playNotifSound();
                    if (typeof notyf !== 'undefined') notyf.success('New leads: ' + (data.lead_name || '') + ' by ' + (data.sales_name || ''));
                    _notifBadge();
                    _notifPrepend(data, 'New Lead Just Added!');
                })
                .listen('.lead.activity', function(data) {
                    _playNotifSound();
                    if (typeof notyf !== 'undefined') notyf.success('Activity lead: ' + (data.lead_name || '') + ' — ' + (data.activity_name || ''));
                    _notifBadge();
                    _notifPrepend(data, 'Update Activity Lead');
                })
                .listen('.lead.trashed', function(data) {
                    _playNotifSound();
                    var label = data.is_auto_trash ? 'Auto-Trash Lead' : 'Lead Moved To Trash';
                    if (typeof notyf !== 'undefined') notyf.error(label + ': ' + (data.lead_name || ''));
                    _notifBadge();
                    _notifPrepend(data, label);
                });
            @endif
            window.Echo.private('App.Models.User.' + userId)
                .listen('.lead.available', function(data) {
                    _playNotifSound();
                    if (typeof notyf !== 'undefined') notyf.success('Lead baru tersedia: ' + (data.lead_name || ''));
                    _notifBadge();
                    _notifPrepend(data, 'Available Lead');
                })
                .listen('.lead.expiring', function(data) {
                    _playNotifSound();
                    var msg = 'Lead will be trashed in ' + (data.days_remaining || 3) + ' days: ' + (data.lead_name || '');
                    if (typeof notyf !== 'undefined') notyf.error(msg);
                    _notifBadge();
                    _notifPrepend(data, 'Warning: Lead will be trashed');
                })
                .listen('.quotation.submitted', function(data) {
                    _playNotifSound();
                    var msg = 'Quotation baru dari ' + (data.sales_name || '') + ' menunggu review';
                    if (typeof notyf !== 'undefined') notyf.success(msg);
                    _notifBadge();
                    _notifPrepend(data, 'Quotation Perlu Review');
                })
                .listen('.quotation.reviewed', function(data) {
                    _playNotifSound();
                    var label = data.decision === 'approve' ? 'disetujui' : 'ditolak';
                    var msg = 'Quotation ' + (data.quotation_no || '') + ' ' + label + ' oleh ' + (data.reviewer_role || '');
                    if (typeof notyf !== 'undefined') {
                        data.decision === 'approve' ? notyf.success(msg) : notyf.error(msg);
                    }
                    _notifBadge();
                    _notifPrepend(data, 'Update Quotation');
                });
        });
    })();
    </script>
    @endif
</body>
</html>
