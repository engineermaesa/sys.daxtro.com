<ul
    id="sidebarInner"
    class="flex flex-col bg-white sticky top-0 h-screen w-full border-r border-[#D9D9D9] transition-all duration-300 ease-in-out overflow-hidden">
    
    {{-- HEADER PANEL --}}
    <div
        id="headerSidebar"
        class="flex items-center justify-between border-b border-b-[#D9D9D9] p-4 min-h-[76px]">
        
        <a
            href="{{ route('dashboard') }}"
            id="sidebarLogoLink"
            class="transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap">
            <img
                id="sidebarLogo"
                src="{{ asset('assets/images/favicon-newer.png') }}"
                alt="DAXTRO Logo"
                class="max-lg:hidden h-10 transition-all duration-300 ease-in-out">
        </a>

        <button
            id="toggleSidepanel"
            type="button"
            class="cursor-pointer duration-300 hover:bg-[#CFE7DE] p-3 rounded-lg flex items-center justify-center shrink-0">
            <span id="iconClosed">
                <x-icon.right-closed/>
            </span>
            <span id="iconOpened" class="hidden">
                <x-icon.right-opened/>
            </span>
        </button>
    </div>

    {{-- ITEM PANEL --}}
    <div id="sidebarMenuContainer" class="flex-1 overflow-y-auto px-3 mt-5 transition-all duration-300">
        
        @if (auth()->check() && auth()->user()->hasPermission('dashboard'))
        <li
            class="{{ request()->routeIs('dashboard') ? 'bg-[#CFE7DE]' : 'bg-white' }} rounded-lg p-3">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start"
                href="{{ route('dashboard') }}">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.76989 4.38C8.77187 4.95405 8.65616 5.52242 8.42989 6.05C8.10525 6.84542 7.55185 7.52659 6.83978 8.00724C6.1277 8.48789 5.28899 8.74639 4.42989 8.75C3.5656 8.74998 2.72073 8.49367 2.00211 8.01349C1.28348 7.5333 0.723388 6.85081 0.392642 6.05231C0.0618964 5.25381 -0.024644 4.37517 0.143964 3.52749C0.312572 2.6798 0.728756 1.90116 1.33989 1.29C1.74586 0.882725 2.22823 0.559581 2.75933 0.339092C3.29044 0.118603 3.85983 0.0051055 4.43489 0.0051055C5.00994 0.0051055 5.57934 0.118603 6.11045 0.339092C6.64155 0.559581 7.12392 0.882725 7.52989 1.29C8.34034 2.11555 8.79916 3.22317 8.80989 4.38H8.76989ZM19.5199 4.38C19.5223 4.9531 19.4101 5.52089 19.1899 6.05C18.9497 6.58254 18.6104 7.06446 18.1899 7.47C17.3687 8.29011 16.2555 8.75075 15.0949 8.75075C13.9343 8.75075 12.8211 8.29011 11.9999 7.47C11.1804 6.65047 10.72 5.53897 10.72 4.38C10.72 3.22103 11.1804 2.10953 11.9999 1.29C12.4042 0.88151 12.8854 0.557186 13.4158 0.335769C13.9462 0.114352 14.5152 0.000230875 15.0899 0C16.2531 0.00331972 17.3677 0.46713 18.1899 1.29C18.6056 1.69096 18.9373 2.1707 19.1657 2.70123C19.394 3.23175 19.5144 3.80244 19.5199 4.38ZM8.76989 15.13C8.76694 15.7088 8.64437 16.2808 8.40989 16.81C8.19681 17.3426 7.8769 17.8258 7.46989 18.23C7.06358 18.6379 6.58035 18.9611 6.04821 19.1808C5.51607 19.4005 4.9456 19.5124 4.36989 19.51C3.5056 19.51 2.66073 19.2537 1.94211 18.7735C1.22348 18.2933 0.663388 17.6108 0.332642 16.8123C0.00189644 16.0138 -0.0846439 15.1352 0.0839641 14.2875C0.252572 13.4398 0.668756 12.6612 1.27989 12.05C1.68586 11.6427 2.16823 11.3196 2.69933 11.0991C3.23044 10.8786 3.79983 10.7651 4.37489 10.7651C4.94994 10.7651 5.51934 10.8786 6.05045 11.0991C6.58155 11.3196 7.06392 11.6427 7.46989 12.05C8.28034 12.8756 8.73916 13.9832 8.74989 15.14L8.76989 15.13ZM19.5199 15.13C19.5179 15.9935 19.2602 16.837 18.7792 17.5541C18.2983 18.2712 17.6157 18.8297 16.8176 19.1593C16.0195 19.4888 15.1417 19.5745 14.2949 19.4055C13.4481 19.2366 12.6704 18.8206 12.0598 18.21C11.4493 17.5995 11.0333 16.8218 10.8644 15.975C10.6954 15.1282 10.7811 14.2504 11.1106 13.4523C11.4401 12.6542 11.9987 11.9716 12.7158 11.4906C13.4329 11.0097 14.2764 10.752 15.1399 10.75C16.3027 10.7555 17.4165 11.219 18.2399 12.04C18.6466 12.4451 18.969 12.9267 19.1887 13.4571C19.4084 13.9874 19.521 14.556 19.5199 15.13Z"
                        fill="{{ request()->routeIs('dashboard') ? '#115640' : '#0D0F11' }}" />
                </svg>

                <span
                    class="sidebar-label {{ request()->routeIs('dashboard') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Dashboard
                </span>
            </a>
        </li>
        @endif

        {{-- LEADS MENU --}}
        @php
        $showLeads = auth()->check() && (
            auth()->user()->hasPermission('leads.manage') ||
            auth()->user()->hasPermission('leads.available') ||
            auth()->user()->hasPermission('leads.my') ||
            auth()->user()->hasPermission('leads.trash')
            );
        @endphp
        @if ($showLeads)
            @include('partials.sidebar.leads-menu')
        @endif

        {{-- ORDERS SECTION --}}
        @if(auth()->check() && auth()->user()->hasPermission('orders'))
        <li
            class="{{ request()->routeIs('orders*') ? 'bg-[#CFE7DE]' : 'bg-white' }} rounded-lg p-3 mt-2">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start"
                href="{{ route('orders.index') }}">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M6 20C5.45 20 4.97917 19.8042 4.5875 19.4125C4.19583 19.0208 4 18.55 4 18C4 17.45 4.19583 16.9792 4.5875 16.5875C4.97917 16.1958 5.45 16 6 16C6.55 16 7.02083 16.1958 7.4125 16.5875C7.80417 16.9792 8 17.45 8 18C8 18.55 7.80417 19.0208 7.4125 19.4125C7.02083 19.8042 6.55 20 6 20ZM16 20C15.45 20 14.9792 19.8042 14.5875 19.4125C14.1958 19.0208 14 18.55 14 18C14 17.45 14.1958 16.9792 14.5875 16.5875C14.9792 16.1958 15.45 16 16 16C16.55 16 17.0208 16.1958 17.4125 16.5875C17.8042 16.9792 18 17.45 18 18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20ZM4.2 2H18.95C19.3333 2 19.625 2.17083 19.825 2.5125C20.025 2.85417 20.0333 3.2 19.85 3.55L16.3 9.95C16.1167 10.2833 15.8708 10.5417 15.5625 10.725C15.2542 10.9083 14.9167 11 14.55 11H7.1L6 13H17C17.2833 13 17.5208 13.0958 17.7125 13.2875C17.9042 13.4792 18 13.7167 18 14C18 14.2833 17.9042 14.5208 17.7125 14.7125C17.5208 14.9042 17.2833 15 17 15H6C5.25 15 4.68333 14.6708 4.3 14.0125C3.91667 13.3542 3.9 12.7 4.25 12.05L5.6 9.6L2 2H1C0.716667 2 0.479167 1.90417 0.2875 1.7125C0.0958333 1.52083 0 1.28333 0 1C0 0.716667 0.0958333 0.479167 0.2875 0.2875C0.479167 0.0958333 0.716667 0 1 0H2.625C2.80833 0 2.98333 0.05 3.15 0.15C3.31667 0.25 3.44167 0.391667 3.525 0.575L4.2 2Z"
                        fill="{{ request()->routeIs('orders*') ? '#115640' : '#0D0F11' }}" />
                </svg>
                <span
                    class="sidebar-label {{ request()->routeIs('orders*') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Orders</span>
            </a>
        </li>
        @endif

        {{-- PURCHASING LOG --}}
        @if(auth()->check() && auth()->user()->hasPermission('purchasing.log'))
        <li
            class="{{ request()->routeIs('purchasing.*') ? 'bg-[#CFE7DE]' : 'bg-white' }} rounded-lg p-3">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start"
                href="{{ route('purchasing.index') }}">                
                <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.99984 0.666687V2.66669M9.99984 0.666687V2.66669M5.99984 13.3334V15.3334M9.99984 13.3334V15.3334M13.3332 6.00002H15.3332M13.3332 9.33335H15.3332M0.666504 6.00002H2.6665M0.666504 9.33335H2.6665M3.99984 2.66669H11.9998C12.7362 2.66669 13.3332 3.26364 13.3332 4.00002V12C13.3332 12.7364 12.7362 13.3334 11.9998 13.3334H3.99984C3.26346 13.3334 2.6665 12.7364 2.6665 12V4.00002C2.6665 3.26364 3.26346 2.66669 3.99984 2.66669ZM5.99984 6.00002H9.99984V10H5.99984V6.00002Z" stroke="{{ request()->routeIs('purchasing*') ? '#115640' : '#0D0F11' }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                <span
                    class="sidebar-label {{ request()->routeIs('purchasing.*') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Purchasing
                    Log</span>
            </a>
        </li>

       
        @endif

        {{-- Finance Requests --}}
        @if(auth()->check() && auth()->user()->hasPermission('finance.requests'))
        <li
            class="{{ request()->routeIs('finance-requests.*') ? 'bg-[#CFE7DE]' : 'bg-white' }} rounded-lg p-3">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start"
                href="{{ route('finance-requests.index') }}">
                <svg width="20" height="20" viewBox="0 0 22 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.8" d="M2.66667 26.6667C1.93333 26.6667 1.30556 26.4056 0.783333 25.8833C0.261111 25.3611 0 24.7333 0 24V2.66667C0 1.93333 0.261111 1.30556 0.783333 0.783333C1.30556 0.261111 1.93333 0 2.66667 0H13.3333L21.3333 8V24C21.3333 24.7333 21.0722 25.3611 20.55 25.8833C20.0278 26.4056 19.4 26.6667 18.6667 26.6667H2.66667ZM9.33333 22.6667H12V21.3333H13.3333C13.7111 21.3333 14.0278 21.2056 14.2833 20.95C14.5389 20.6944 14.6667 20.3778 14.6667 20V16C14.6667 15.6222 14.5389 15.3056 14.2833 15.05C14.0278 14.7944 13.7111 14.6667 13.3333 14.6667H9.33333V13.3333H14.6667V10.6667H12V9.33333H9.33333V10.6667H8C7.62222 10.6667 7.30556 10.7944 7.05 11.05C6.79444 11.3056 6.66667 11.6222 6.66667 12V16C6.66667 16.3778 6.79444 16.6944 7.05 16.95C7.30556 17.2056 7.62222 17.3333 8 17.3333H12V18.6667H6.66667V21.3333H9.33333V22.6667ZM12.2333 8H17.5667L12.2333 2.66667V8Z" fill="{{ request()->routeIs('finance-requests.*') ? '#115640' : '#0D0F11' }}"/>
                </svg>

                <span
                    class="sidebar-label {{ request()->routeIs('finance-requests.*') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Finance
                    Requests</span>
            </a>
        </li>
        @endif

        {{-- INCENTIVES MENU --}}
        @if(auth()->check() && auth()->user()->hasPermission('incentives.view'))
        <li
            class="{{ request()->routeIs('incentives.dashboard') ? 'bg-[#CFE7DE]' : 'bg-white' }} rounded-lg p-3">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start"
                href="{{ route('incentives.dashboard') }}">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M9 12.0006V20.0006H5C4.20435 20.0006 3.44129 19.6845 2.87868 19.1219C2.31607 18.5593 2 17.7962 2 17.0006V13.0006C2 12.7354 2.10536 12.481 2.29289 12.2935C2.48043 12.1059 2.73478 12.0006 3 12.0006H9ZM17 12.0006C17.2652 12.0006 17.5196 12.1059 17.7071 12.2935C17.8946 12.481 18 12.7354 18 13.0006V17.0006C18 17.7962 17.6839 18.5593 17.1213 19.1219C16.5587 19.6845 15.7957 20.0006 15 20.0006H11V12.0006H17ZM14.5 0.000586895C15.0905 0.000465017 15.6715 0.149753 16.1888 0.434555C16.7061 0.719356 17.1429 1.13041 17.4586 1.62945C17.7743 2.1285 17.9586 2.6993 17.9943 3.28873C18.0301 3.87816 17.9161 4.46705 17.663 5.00059H18C18.5304 5.00059 19.0391 5.2113 19.4142 5.58637C19.7893 5.96145 20 6.47015 20 7.00059V8.00059C20 8.53102 19.7893 9.03973 19.4142 9.4148C19.0391 9.78987 18.5304 10.0006 18 10.0006H11V5.00059H9V10.0006H2C1.46957 10.0006 0.960859 9.78987 0.585786 9.4148C0.210714 9.03973 0 8.53102 0 8.00059V7.00059C0 6.47015 0.210714 5.96145 0.585786 5.58637C0.960859 5.2113 1.46957 5.00059 2 5.00059H2.337C2.11488 4.53174 1.99977 4.01938 2 3.50059C2 1.56759 3.567 0.000586895 5.483 0.000586895C7.238 -0.0294131 8.795 1.09259 9.864 2.93459L10 3.17759C11.033 1.26359 12.56 0.063587 14.291 0.00258699L14.5 0.000586895ZM5.5 2.00059C5.10218 2.00059 4.72064 2.15862 4.43934 2.43993C4.15804 2.72123 4 3.10276 4 3.50059C4 3.89841 4.15804 4.27994 4.43934 4.56125C4.72064 4.84255 5.10218 5.00059 5.5 5.00059H8.643C7.902 3.09559 6.694 1.98059 5.5 2.00059ZM14.483 2.00059C13.303 1.98059 12.098 3.09659 11.357 5.00059H14.5C14.8978 4.99833 15.2785 4.83814 15.5582 4.55524C15.8379 4.27234 15.9938 3.88991 15.9915 3.49209C15.9892 3.09426 15.8291 2.71363 15.5462 2.43392C15.2633 2.15421 14.8808 1.99833 14.483 2.00059Z"
                        fill="{{ request()->routeIs('incentives.dashboard') ? '#115640' : '#0D0F11' }}" />
                </svg>
                <span
                    class="sidebar-label {{ request()->routeIs('incentives.dashboard') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Incentives</span>
            </a>
        </li>
        @endif

        {{-- MASTERS MENU --}}
        @php
        $masterPermissions = [
            'masters.companies',
            'masters.provinces',
            'masters.branches',
            'masters.regions',
            'masters.banks',
            'masters.accounts',
            'masters.product-categories',
            'masters.products',
            'masters.parts',
            'masters.expense-types',
            'masters.customer-types',
        ];

        $showMasters = collect($masterPermissions)->contains(fn($p) => auth()->check() &&
        auth()->user()->hasPermission($p));
        @endphp

        @if ($showMasters)
            @include('partials.sidebar.masters-menu')
        @endif


        {{-- USERS MENU --}}
        @php
        $userMenu = [
            'users.manage',
            'users.roles',
        ];

        $showUsers = collect($userMenu)->contains(fn($p) => auth()->check() && auth()->user()->hasPermission($p));
        @endphp

        @if ($showUsers)
            @include('partials.sidebar.users-menu')
        @endif

        {{-- SETTINGS MENU --}}
        @php
        $settingsMenu = [
            'settings.permissions-settings',
        ];

        $showSettings = collect($settingsMenu)->contains(fn($p) => auth()->check() &&
        auth()->user()->hasPermission($p));
        @endphp

        @if ($showSettings)
            @include('partials.sidebar.settings-menu')
        @endif
    </div>

    {{-- USER INFO --}}
    <div class="mt-auto py-4 border-t border-[#CFD5DC] relative">
        {{-- TOGGLE USER INFO --}}
        <div id="toggleUserInfo"
            class="lg:flex lg:justify-between lg:items-center cursor-pointer px-3 xl:px-5 grid grid-cols-1 place-items-center">
            <div class="lg:flex lg:items-center lg:gap-3">
                {{-- USER ICON --}}
                <div
                    id="iconUser"
                    class="rounded-circle bg-[#115641] flex items-center justify-center sm:w-[35px]! sm:h-[35px]! xl:w-[45px] xl:h-[45px] shrink-0">
                    <i class="fas fa-user text-white text-xs xl:text-sm"></i>
                </div>

                <div id="userMeta" class="sidebar-user-meta lg:flex! lg:flex-col! sm:hidden!">
                    <span class="text-sm xl:text-lg font-semibold text-black">
                        {{ auth()->user()?->name ?? 'User' }}
                    </span>
                    <small class="text-xs xl:text-sm text-black mt-1">
                        {{ auth()->user()?->role?->name ?? 'User' }}
                    </small>
                </div>
            </div>

            <i id="chevronUserInfo"
                class="sidebar-chevron sm:hidden! lg:inline-block! fas fa-chevron-up transition-transform duration-300 text-black"
                style="font-size: 16px;"></i>
        </div>

        {{-- DETAIL USER INFO --}}
        <div id="userDropdown"
            class="absolute bottom-[78px] left-3 right-3 bg-white rounded-xl shadow-xl p-4 opacity-0 scale-95 pointer-events-none transition-all duration-200 z-50">

            <div class="text-center mb-3">
                <div class="mx-auto w-12 h-12 rounded-full bg-[#115641] flex items-center justify-center">
                    <i class="fas fa-user text-white"></i>
                </div>

                <span class="block mt-2 font-semibold">
                    {{ auth()->user()?->name ?? 'User' }}
                </span>

                <span class="hidden xl:block! mt-2 text-[#B3B3B3] text-wrap!">
                    {{ auth()->user()?->email ?? '' }}
                </span>
            </div>

            <hr class="my-3">

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    class="cursor-pointer w-full px-3 py-2 rounded hover:bg-gray-100 flex justify-center items-center gap-2">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="sm:hidden! lg:block!">Logout</span>
                </button>
            </form>
        </div>
        {{-- SCRIPTS FOR TOGGLE USER INFO --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {

                const userInfoToggle = document.getElementById('toggleUserInfo');
                const dropdownUserInfo = document.getElementById('userDropdown');
                const chevronUserInfo = document.getElementById('chevronUserInfo');

                userInfoToggle.addEventListener('click', () => {
                    const sidebarWrapper = document.getElementById('sidebarWrapper');
                    if (sidebarWrapper && sidebarWrapper.classList.contains('sidebar-collapsed')) return;

                    if (dropdownUserInfo.classList.contains('opacity-0')) {
                        dropdownUserInfo.classList.remove('opacity-0','scale-95','pointer-events-none');
                        dropdownUserInfo.classList.add('opacity-100','scale-100');
                        chevronUserInfo.classList.add('rotate-90');
                    } else {
                        dropdownUserInfo.classList.add('opacity-0','scale-95','pointer-events-none');
                        dropdownUserInfo.classList.remove('opacity-100','scale-100');
                        chevronUserInfo.classList.remove('rotate-90');
                    }
                });
            });
        </script>

    </div>
</ul>

<style>
    @media (min-width: 1024px) {

        #sidebarWrapper {
            width: 280px;
        }

    }

    #sidebarWrapper.sidebar-collapsed {
        width: 88px;
    }

    #sidebarWrapper.sidebar-collapsed #sidebarInner {
        width: 88px;
    }

    #sidebarWrapper.sidebar-collapsed #sidebarLogoLink {
        width: 0;
        opacity: 0;
        margin: 0;
        display: none;
    }

    #sidebarWrapper.sidebar-collapsed #sidebarLogo {
        opacity: 0;
        transform: scale(0.8);
    }

    #sidebarWrapper.sidebar-collapsed #sidebarMenuContainer {
        padding-left: 10px;
        padding-right: 10px;
    }

    #sidebarWrapper.sidebar-collapsed .sidebar-label,
    #sidebarWrapper.sidebar-collapsed .sidebar-sub-label,
    #sidebarWrapper.sidebar-collapsed .sidebar-user-meta,
    #sidebarWrapper.sidebar-collapsed .sidebar-chevron {
        display: none !important;
    }

    #sidebarWrapper.sidebar-collapsed .sidebar-submenu {
        display: none !important;
        max-height: 0 !important;
        margin-top: 0 !important;
    }

    #sidebarWrapper.sidebar-collapsed li > a,
    #sidebarWrapper.sidebar-collapsed li > button,
    #sidebarWrapper.sidebar-collapsed #toggleUserInfo {
        justify-content: center !important;
        text-align: center !important;
    }

    #sidebarWrapper.sidebar-collapsed li > a > svg,
    #sidebarWrapper.sidebar-collapsed li > button svg,
    #sidebarWrapper.sidebar-collapsed li > a > i,
    #sidebarWrapper.sidebar-collapsed li > button > i {
        margin-right: 0 !important;
    }

    #sidebarWrapper.sidebar-collapsed #userDropdown {
        left: 90px;
        right: auto;
        bottom: 16px;
        width: 220px;
    }

    #sidebarWrapper.sidebar-collapsed .sidebar-label,
    #sidebarWrapper.sidebar-collapsed .sidebar-sub-label,
    #sidebarWrapper.sidebar-collapsed .sidebar-user-meta {
        display: none !important;
    }

    #sidebarWrapper.sidebar-collapsed .sidebar-chevron {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        width: 0 !important;
        min-width: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
    }

    
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebarWrapper = document.getElementById('sidebarWrapper');
        const headerSidebar = document.getElementById('headerSidebar');
        const toggleSidepanel = document.getElementById('toggleSidepanel');
        const iconClosed = document.getElementById('iconClosed');
        const iconOpened = document.getElementById('iconOpened');
        const userDropdown = document.getElementById('userDropdown');
        const userMeta = document.getElementById('userMeta');
        const chevronUserInfo = document.getElementById('chevronUserInfo');

        if (!sidebarWrapper || !toggleSidepanel || !headerSidebar) return;

        const SIDEBAR_KEY = 'sidebar-collapsed';

        function applySidebarState(collapsed) {
            sidebarWrapper.classList.toggle('sidebar-collapsed', collapsed);
            document.documentElement.classList.toggle('sidebar-precollapsed', collapsed);

            if (collapsed) {
                headerSidebar.classList.remove('justify-between');
                headerSidebar.classList.add('justify-center');

                iconClosed.classList.add('hidden');
                iconOpened.classList.remove('hidden');

                userMeta.classList.add('hidden');
                userMeta.classList.remove('lg:flex!');

                chevronUserInfo.classList.add('hidden');
                chevronUserInfo.classList.remove('lg:inline-block!');


                if (userDropdown) {
                    userDropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                    userDropdown.classList.remove('opacity-100', 'scale-100');
                }
            } else {
                headerSidebar.classList.remove('justify-center');
                headerSidebar.classList.add('justify-between');

                iconClosed.classList.remove('hidden');
                iconOpened.classList.add('hidden');

                
                userMeta.classList.remove('hidden');
                userMeta.classList.add('lg:flex!');

                chevronUserInfo.classList.remove('hidden');
                chevronUserInfo.classList.add('lg:inline-block!');
            }
        }

        const savedState = localStorage.getItem(SIDEBAR_KEY) === 'true';
        applySidebarState(savedState);

        toggleSidepanel.addEventListener('click', () => {
            const isCollapsed = !sidebarWrapper.classList.contains('sidebar-collapsed');
            localStorage.setItem(SIDEBAR_KEY, isCollapsed);
            applySidebarState(isCollapsed);
        });
    });
</script>