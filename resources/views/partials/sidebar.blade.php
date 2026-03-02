<ul class="flex flex-col bg-white pt-10 sticky top-0 h-screen">
    <a class="px-2" href="{{ route('dashboard') }}">
        <div class="flex justify-center items-center">
            <img src="{{ asset('assets/images/favicon-newer.png') }}" alt="DAXTRO Logo" class="w-45">
        </div>
    </a>
    <div class="flex-1 overflow-y-auto px-2">
        @if (auth()->check() && auth()->user()->hasPermission('dashboard'))
        <li class="{{ request()->routeIs('dashboard') ? 'bg-[#E8EFEC]' : 'bg-white' }} lg:flex lg:items-center lg:justify-start rounded-lg mt-10 px-3 py-2 sm:grid sm:grid-cols-1 sm:place-items-center">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start" href="{{ route('dashboard') }}">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8.76989 4.38C8.77187 4.95405 8.65616 5.52242 8.42989 6.05C8.10525 6.84542 7.55185 7.52659 6.83978 8.00724C6.1277 8.48789 5.28899 8.74639 4.42989 8.75C3.5656 8.74998 2.72073 8.49367 2.00211 8.01349C1.28348 7.5333 0.723388 6.85081 0.392642 6.05231C0.0618964 5.25381 -0.024644 4.37517 0.143964 3.52749C0.312572 2.6798 0.728756 1.90116 1.33989 1.29C1.74586 0.882725 2.22823 0.559581 2.75933 0.339092C3.29044 0.118603 3.85983 0.0051055 4.43489 0.0051055C5.00994 0.0051055 5.57934 0.118603 6.11045 0.339092C6.64155 0.559581 7.12392 0.882725 7.52989 1.29C8.34034 2.11555 8.79916 3.22317 8.80989 4.38H8.76989ZM19.5199 4.38C19.5223 4.9531 19.4101 5.52089 19.1899 6.05C18.9497 6.58254 18.6104 7.06446 18.1899 7.47C17.3687 8.29011 16.2555 8.75075 15.0949 8.75075C13.9343 8.75075 12.8211 8.29011 11.9999 7.47C11.1804 6.65047 10.72 5.53897 10.72 4.38C10.72 3.22103 11.1804 2.10953 11.9999 1.29C12.4042 0.88151 12.8854 0.557186 13.4158 0.335769C13.9462 0.114352 14.5152 0.000230875 15.0899 0C16.2531 0.00331972 17.3677 0.46713 18.1899 1.29C18.6056 1.69096 18.9373 2.1707 19.1657 2.70123C19.394 3.23175 19.5144 3.80244 19.5199 4.38ZM8.76989 15.13C8.76694 15.7088 8.64437 16.2808 8.40989 16.81C8.19681 17.3426 7.8769 17.8258 7.46989 18.23C7.06358 18.6379 6.58035 18.9611 6.04821 19.1808C5.51607 19.4005 4.9456 19.5124 4.36989 19.51C3.5056 19.51 2.66073 19.2537 1.94211 18.7735C1.22348 18.2933 0.663388 17.6108 0.332642 16.8123C0.00189644 16.0138 -0.0846439 15.1352 0.0839641 14.2875C0.252572 13.4398 0.668756 12.6612 1.27989 12.05C1.68586 11.6427 2.16823 11.3196 2.69933 11.0991C3.23044 10.8786 3.79983 10.7651 4.37489 10.7651C4.94994 10.7651 5.51934 10.8786 6.05045 11.0991C6.58155 11.3196 7.06392 11.6427 7.46989 12.05C8.28034 12.8756 8.73916 13.9832 8.74989 15.14L8.76989 15.13ZM19.5199 15.13C19.5179 15.9935 19.2602 16.837 18.7792 17.5541C18.2983 18.2712 17.6157 18.8297 16.8176 19.1593C16.0195 19.4888 15.1417 19.5745 14.2949 19.4055C13.4481 19.2366 12.6704 18.8206 12.0598 18.21C11.4493 17.5995 11.0333 16.8218 10.8644 15.975C10.6954 15.1282 10.7811 14.2504 11.1106 13.4523C11.4401 12.6542 11.9987 11.9716 12.7158 11.4906C13.4329 11.0097 14.2764 10.752 15.1399 10.75C16.3027 10.7555 17.4165 11.219 18.2399 12.04C18.6466 12.4451 18.969 12.9267 19.1887 13.4571C19.4084 13.9874 19.521 14.556 19.5199 15.13Z" fill="{{ request()->routeIs('dashboard') ? '#115640' : '#0D0F11' }}"/>
                </svg>

                <span class="{{ request()->routeIs('dashboard') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Dashboard</span>
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
        <li id= "listLeads" class="{{ (request()->is('leads/*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*')) ? '' : '' }} rounded-lg mt-2">
            <button 
                class="cursor-pointer w-full text-left rounded-lg px-3 py-2 mt-2 grid place-items-center lg:flex lg:items-center lg:justify-between" href="#" data-toggle="collapse" data-target="#collapseLeads" aria-expanded="{{ request()->is('leads*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*') }}" id="leadsToggle">
                <div class="lg:flex lg:items-center lg:justify-start lg:gap-3">
                    <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg"> <path id="leadsIcon" d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z" fill="#0D0F11"/> </svg>
                    <span id="leadsSpan" class="text-[#010508] font-semibold sm:hidden lg:inline">Leads</span>
                </div>
                <i id="chevronLeadsMenu" class="sm:hidden! lg:inline-block! fas fa-chevron-right transition-transform duration-300 text-black" style="font-size: 16px;"></i>
            </button>
            <div    
                id="leadsMenu" 
                class="block mt-2 overflow-hidden transition-all duration-300 max-h-0 {{ request()->is('leads*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*') }} text-sm">
                <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">
                    {{-- AVAILABLE LEADS --}}
                    @if(auth()->check() && (auth()->user()->hasPermission('leads.available') || auth()->user()->hasPermission('leads.available')))
                    <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/available*') }}" href="{{ route('leads.available') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('leads/available*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('leads/available*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Available Leads
                        </span>
                    </a>
                    @endif

                    {{-- MY LEADS --}}
                    @if(auth()->check() && (auth()->user()->hasPermission('leads.my') || auth()->user()->hasPermission('leads.my')))
                    <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/my*') || request()->is('quotations*') || request()->is('payment-confirmation*') }}" href="{{ route('leads.my') }}">
                        <span class="block h-[20px] w-[3px] {{ request()->is('leads/my*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('leads/my*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            My Leads
                        </span>
                    </a>
                    @endif

                    {{-- LEADS MANAGE --}}
                    @if(auth()->check() && auth()->user()->hasPermission('leads.manage'))
                    <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/manage*') || request()->is('quotations*') || request()->is('payment-confirmation*') }}" href="{{ route('leads.manage') }}">
                        <span class="block h-[20px] w-[3px] {{ request()->is('leads/manage*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('leads/manage*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            All Leads
                        </span>
                    </a>
                    @endif

                    {{-- IMPORTS LEAD --}}
                    @if(auth()->check() && auth()->user()->role?->code === 'super_admin')
                    <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/import*') }}" href="{{ route('leads.import') }}">
                        <span class="block h-[20px] w-[3px] {{ request()->is('leads/import*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('leads/import*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Import Leads
                        </span>
                    </a>
                    @endif

                    {{-- TRASH LEADS --}}
                    @if(auth()->check() && auth()->user()->hasPermission('leads.trash'))
                    <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('trash-leads*') }}" href="{{ route('trash-leads.index') }}">
                        <span class="block h-[20px] w-[3px] {{ request()->is('trash-leads*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('trash-leads*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Trash Leads
                        </span>
                    </a>
                    @endif
                </div>
            </div>
        </li>

        {{-- Scripts for collapse leads --}}
        @php
        $isLeadsActive =
            request()->is('leads/available*') ||
            request()->is('leads/manage*') ||
            request()->is('leads/import*') ||
            request()->is('leads/my*') ||
            request()->is('trash-leads*');
            // request()->is('quotations*') ||
            // request()->is('payment-confirmation*') ||
            // request()->is('trash-leads*');
        @endphp

        <script>
        document.addEventListener('DOMContentLoaded', () => {

            const leadsToggle = document.getElementById('leadsToggle');
            const leadsMenu = document.getElementById('leadsMenu');
            const leadsIcon = document.getElementById('leadsIcon');
            const leadsSpan = document.getElementById('leadsSpan');
            const chevronLeadsMenu = document.getElementById('chevronLeadsMenu');

            const isActive = @json($isLeadsActive);

            function openLeads() {
                leadsMenu.classList.remove('max-h-0');
                leadsMenu.classList.add('max-h-[500px]');
                leadsToggle.classList.add('bg-[#E8EFEC]');
                leadsSpan.classList.add('text-[#115640]');
                leadsIcon.setAttribute('fill', '#115640');
                chevronLeadsMenu.classList.remove('text-black');
                chevronLeadsMenu.classList.add('rotate-90', 'text-[#115640]');
            }

            function closeLeads() {
                leadsMenu.classList.add('max-h-0');
                leadsMenu.classList.remove('max-h-[500px]');
                leadsToggle.classList.remove('bg-[#E8EFEC]');
                leadsSpan.classList.remove('text-[#115640]');
                leadsIcon.setAttribute('fill', '#0D0F11');
                chevronLeadsMenu.classList.remove('rotate-90', 'text-[#115640]');
                chevronLeadsMenu.classList.add('text-black');
            }

            if(isActive){
                openLeads();
            }

            leadsToggle.addEventListener('click', () => {
                if (leadsMenu.classList.contains('max-h-0')) {
                    openLeads();
                } else {
                    closeLeads();
                }
            });

        });
        </script>
        @endif

        {{-- ORDERS SECTION --}}
        @if(auth()->check() && auth()->user()->hasPermission('orders'))
        <li class="{{ request()->routeIs('orders*') ? 'bg-[#E8EFEC]' : 'bg-white' }} lg:flex lg:items-center lg:justify-start rounded-lg mt-2 px-3 py-2 sm:grid sm:grid-cols-1 sm:place-items-center">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start" href="{{ route('orders.index') }}">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 20C5.45 20 4.97917 19.8042 4.5875 19.4125C4.19583 19.0208 4 18.55 4 18C4 17.45 4.19583 16.9792 4.5875 16.5875C4.97917 16.1958 5.45 16 6 16C6.55 16 7.02083 16.1958 7.4125 16.5875C7.80417 16.9792 8 17.45 8 18C8 18.55 7.80417 19.0208 7.4125 19.4125C7.02083 19.8042 6.55 20 6 20ZM16 20C15.45 20 14.9792 19.8042 14.5875 19.4125C14.1958 19.0208 14 18.55 14 18C14 17.45 14.1958 16.9792 14.5875 16.5875C14.9792 16.1958 15.45 16 16 16C16.55 16 17.0208 16.1958 17.4125 16.5875C17.8042 16.9792 18 17.45 18 18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20ZM4.2 2H18.95C19.3333 2 19.625 2.17083 19.825 2.5125C20.025 2.85417 20.0333 3.2 19.85 3.55L16.3 9.95C16.1167 10.2833 15.8708 10.5417 15.5625 10.725C15.2542 10.9083 14.9167 11 14.55 11H7.1L6 13H17C17.2833 13 17.5208 13.0958 17.7125 13.2875C17.9042 13.4792 18 13.7167 18 14C18 14.2833 17.9042 14.5208 17.7125 14.7125C17.5208 14.9042 17.2833 15 17 15H6C5.25 15 4.68333 14.6708 4.3 14.0125C3.91667 13.3542 3.9 12.7 4.25 12.05L5.6 9.6L2 2H1C0.716667 2 0.479167 1.90417 0.2875 1.7125C0.0958333 1.52083 0 1.28333 0 1C0 0.716667 0.0958333 0.479167 0.2875 0.2875C0.479167 0.0958333 0.716667 0 1 0H2.625C2.80833 0 2.98333 0.05 3.15 0.15C3.31667 0.25 3.44167 0.391667 3.525 0.575L4.2 2Z" fill="{{ request()->routeIs('orders*') ? '#115640' : '#0D0F11' }}"/>
                </svg>
                <span class="{{ request()->routeIs('orders*') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Orders</span>
            </a>
        </li>
        @endif

        {{-- Finance Requests --}}
        @if(auth()->check() && auth()->user()->hasPermission('finance.requests'))
        <li class="{{ request()->routeIs('finance-requests.*') ? 'bg-[#E8EFEC]' : 'bg-white' }} lg:flex lg:items-center lg:justify-start rounded-lg mt-2 px-3 py-2 sm:grid sm:grid-cols-1 sm:place-items-center">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start" href="{{ route('finance-requests.index') }}">
                <i class="fas fa-fw fa-file-invoice-dollar"></i>
                <span class="{{ request()->routeIs('finance-requests.*') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Finance Requests</span>
            </a>
        </li>
        @endif

        {{-- INCENTIVES MENU --}}
        @if(auth()->check() && auth()->user()->hasPermission('incentives.view'))
        <li class="{{ request()->routeIs('incentives.dashboard') ? 'bg-[#E8EFEC]' : 'bg-white' }} lg:flex lg:items-center lg:justify-start rounded-lg mt-2 px-3 py-2 sm:grid sm:grid-cols-1 sm:place-items-center">
            <a class="lg:flex lg:items-center lg:gap-3 grid grid-cols-1 place-items-center lg:justify-start" href="{{ route('incentives.dashboard') }}">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12.0006V20.0006H5C4.20435 20.0006 3.44129 19.6845 2.87868 19.1219C2.31607 18.5593 2 17.7962 2 17.0006V13.0006C2 12.7354 2.10536 12.481 2.29289 12.2935C2.48043 12.1059 2.73478 12.0006 3 12.0006H9ZM17 12.0006C17.2652 12.0006 17.5196 12.1059 17.7071 12.2935C17.8946 12.481 18 12.7354 18 13.0006V17.0006C18 17.7962 17.6839 18.5593 17.1213 19.1219C16.5587 19.6845 15.7957 20.0006 15 20.0006H11V12.0006H17ZM14.5 0.000586895C15.0905 0.000465017 15.6715 0.149753 16.1888 0.434555C16.7061 0.719356 17.1429 1.13041 17.4586 1.62945C17.7743 2.1285 17.9586 2.6993 17.9943 3.28873C18.0301 3.87816 17.9161 4.46705 17.663 5.00059H18C18.5304 5.00059 19.0391 5.2113 19.4142 5.58637C19.7893 5.96145 20 6.47015 20 7.00059V8.00059C20 8.53102 19.7893 9.03973 19.4142 9.4148C19.0391 9.78987 18.5304 10.0006 18 10.0006H11V5.00059H9V10.0006H2C1.46957 10.0006 0.960859 9.78987 0.585786 9.4148C0.210714 9.03973 0 8.53102 0 8.00059V7.00059C0 6.47015 0.210714 5.96145 0.585786 5.58637C0.960859 5.2113 1.46957 5.00059 2 5.00059H2.337C2.11488 4.53174 1.99977 4.01938 2 3.50059C2 1.56759 3.567 0.000586895 5.483 0.000586895C7.238 -0.0294131 8.795 1.09259 9.864 2.93459L10 3.17759C11.033 1.26359 12.56 0.063587 14.291 0.00258699L14.5 0.000586895ZM5.5 2.00059C5.10218 2.00059 4.72064 2.15862 4.43934 2.43993C4.15804 2.72123 4 3.10276 4 3.50059C4 3.89841 4.15804 4.27994 4.43934 4.56125C4.72064 4.84255 5.10218 5.00059 5.5 5.00059H8.643C7.902 3.09559 6.694 1.98059 5.5 2.00059ZM14.483 2.00059C13.303 1.98059 12.098 3.09659 11.357 5.00059H14.5C14.8978 4.99833 15.2785 4.83814 15.5582 4.55524C15.8379 4.27234 15.9938 3.88991 15.9915 3.49209C15.9892 3.09426 15.8291 2.71363 15.5462 2.43392C15.2633 2.15421 14.8808 1.99833 14.483 2.00059Z" fill="{{ request()->routeIs('incentives.dashboard') ? '#115640' : '#0D0F11' }}"/>
                </svg>
                <span class="{{ request()->routeIs('incentives.dashboard') ? 'text-[#115640]' : 'text-[#0D0F11]' }} font-semibold sm:hidden lg:inline">Incentives</span>
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
            $showMasters = collect($masterPermissions)->contains(fn($p) => auth()->check() && auth()->user()->hasPermission($p));
        @endphp

        @if ($showMasters)
        <li id="listMasters" 
            class="{{ request()->is('masters/*')}} rounded-lg mt-2">
            <button
                id="mastersToggle" 
                class="cursor-pointer w-full text-left rounded-lg px-3 py-2 grid place-items-center lg:flex lg:items-center lg:justify-between" href="#" data-toggle="collapse" data-target="#collapseMasters" aria-expanded="{{ request()->is('masters/*') ? 'true' : 'false' }}" aria-controls="collapseMasters">
                {{-- ICON MASTERS --}}
                <div class="lg:flex! lg:items-center! lg:justify-start! lg:gap-3!">
                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_68_15973)">
                            <path d="M7.99984 10.0001C9.10441 10.0001 9.99984 9.10465 9.99984 8.00008C9.99984 6.89551 9.10441 6.00008 7.99984 6.00008C6.89527 6.00008 5.99984 6.89551 5.99984 8.00008C5.99984 9.10465 6.89527 10.0001 7.99984 10.0001Z" id="mastersIconInner" stroke="#1E1E1E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12.9332 10.0001C12.8444 10.2012 12.818 10.4242 12.8572 10.6405C12.8964 10.8567 12.9995 11.0563 13.1532 11.2134L13.1932 11.2534C13.3171 11.3772 13.4155 11.5243 13.4826 11.6862C13.5497 11.848 13.5842 12.0215 13.5842 12.1967C13.5842 12.372 13.5497 12.5455 13.4826 12.7073C13.4155 12.8692 13.3171 13.0162 13.1932 13.1401C13.0693 13.264 12.9223 13.3624 12.7604 13.4295C12.5986 13.4966 12.4251 13.5311 12.2498 13.5311C12.0746 13.5311 11.9011 13.4966 11.7392 13.4295C11.5774 13.3624 11.4303 13.264 11.3065 13.1401L11.2665 13.1001C11.1094 12.9464 10.9098 12.8433 10.6936 12.8041C10.4773 12.7649 10.2542 12.7913 10.0532 12.8801C9.85599 12.9646 9.68783 13.1049 9.56937 13.2838C9.45092 13.4626 9.38736 13.6722 9.3865 13.8867V14.0001C9.3865 14.3537 9.24603 14.6928 8.99598 14.9429C8.74593 15.1929 8.40679 15.3334 8.05317 15.3334C7.69955 15.3334 7.36041 15.1929 7.11036 14.9429C6.86031 14.6928 6.71984 14.3537 6.71984 14.0001V13.9401C6.71467 13.7194 6.64325 13.5054 6.51484 13.3259C6.38644 13.1464 6.20699 13.0096 5.99984 12.9334C5.79876 12.8447 5.57571 12.8182 5.35944 12.8574C5.14318 12.8966 4.94362 12.9997 4.7865 13.1534L4.7465 13.1934C4.62267 13.3174 4.47562 13.4157 4.31376 13.4828C4.15189 13.5499 3.97839 13.5845 3.80317 13.5845C3.62795 13.5845 3.45445 13.5499 3.29258 13.4828C3.13072 13.4157 2.98367 13.3174 2.85984 13.1934C2.73587 13.0696 2.63752 12.9225 2.57042 12.7607C2.50333 12.5988 2.46879 12.4253 2.46879 12.2501C2.46879 12.0749 2.50333 11.9014 2.57042 11.7395C2.63752 11.5776 2.73587 11.4306 2.85984 11.3067L2.89984 11.2667C3.05353 11.1096 3.15663 10.9101 3.19584 10.6938C3.23505 10.4775 3.20858 10.2545 3.11984 10.0534C3.03533 9.85623 2.89501 9.68807 2.71615 9.56962C2.53729 9.45117 2.32769 9.3876 2.11317 9.38675H1.99984C1.64622 9.38675 1.30708 9.24627 1.05703 8.99622C0.80698 8.74617 0.666504 8.40704 0.666504 8.05341C0.666504 7.69979 0.80698 7.36065 1.05703 7.11061C1.30708 6.86056 1.64622 6.72008 1.99984 6.72008H2.05984C2.2805 6.71492 2.49451 6.64349 2.67404 6.51509C2.85357 6.38668 2.99031 6.20724 3.0665 6.00008C3.15525 5.799 3.18172 5.57595 3.14251 5.35969C3.10329 5.14343 3.00019 4.94387 2.8465 4.78675L2.8065 4.74675C2.68254 4.62292 2.58419 4.47587 2.51709 4.314C2.44999 4.15214 2.41545 3.97864 2.41545 3.80341C2.41545 3.62819 2.44999 3.45469 2.51709 3.29283C2.58419 3.13096 2.68254 2.98391 2.8065 2.86008C2.93033 2.73611 3.07739 2.63777 3.23925 2.57067C3.40111 2.50357 3.57462 2.46903 3.74984 2.46903C3.92506 2.46903 4.09856 2.50357 4.26042 2.57067C4.42229 2.63777 4.56934 2.73611 4.69317 2.86008L4.73317 2.90008C4.89029 3.05377 5.08985 3.15687 5.30611 3.19608C5.52237 3.2353 5.74543 3.20882 5.9465 3.12008H5.99984C6.19702 3.03557 6.36518 2.89525 6.48363 2.71639C6.60208 2.53753 6.66565 2.32794 6.6665 2.11341V2.00008C6.6665 1.64646 6.80698 1.30732 7.05703 1.05727C7.30708 0.807224 7.64621 0.666748 7.99984 0.666748C8.35346 0.666748 8.6926 0.807224 8.94265 1.05727C9.19269 1.30732 9.33317 1.64646 9.33317 2.00008V2.06008C9.33403 2.27461 9.39759 2.4842 9.51604 2.66306C9.63449 2.84192 9.80266 2.98224 9.99984 3.06675C10.2009 3.15549 10.424 3.18196 10.6402 3.14275C10.8565 3.10354 11.056 3.00044 11.2132 2.84675L11.2532 2.80675C11.377 2.68278 11.5241 2.58443 11.6859 2.51733C11.8478 2.45024 12.0213 2.4157 12.1965 2.4157C12.3717 2.4157 12.5452 2.45024 12.7071 2.51733C12.869 2.58443 13.016 2.68278 13.1398 2.80675C13.2638 2.93058 13.3621 3.07763 13.4292 3.23949C13.4963 3.40136 13.5309 3.57486 13.5309 3.75008C13.5309 3.9253 13.4963 4.0988 13.4292 4.26067C13.3621 4.42253 13.2638 4.56958 13.1398 4.69341L13.0998 4.73341C12.9461 4.89053 12.843 5.09009 12.8038 5.30635C12.7646 5.52262 12.7911 5.74567 12.8798 5.94675V6.00008C12.9643 6.19726 13.1047 6.36543 13.2835 6.48388C13.4624 6.60233 13.672 6.66589 13.8865 6.66675H13.9998C14.3535 6.66675 14.6926 6.80722 14.9426 7.05727C15.1927 7.30732 15.3332 7.64646 15.3332 8.00008C15.3332 8.3537 15.1927 8.69284 14.9426 8.94289C14.6926 9.19294 14.3535 9.33341 13.9998 9.33341H13.9398C13.7253 9.33427 13.5157 9.39784 13.3369 9.51629C13.158 9.63474 13.0177 9.8029 12.9332 10.0001Z" id="mastersIconOuter" stroke="#1E1E1E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_68_15973">
                                <rect width="16" height="16" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>
                    <span id="mastersSpan" class="text-[#010508] font-semibold sm:hidden lg:inline">Masters</span>
                </div>
                <i id="chevronMastersMenu" class="sm:hidden! lg:inline-block! fas fa-chevron-right transition-transform duration-300 text-black" style="font-size: 16px;"></i>
            </button>
            <div 
                id="mastersMenu" 
                class="block mt-2 overflow-hidden transition-all duration-300 max-h-0{{ request()->is('masters/*') }}">
                <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">
                    {{-- MASTER COMPANIES --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.companies'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/companies*')}}" href="{{ route('masters.companies.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/companies*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">
                        </span>
                        <span class="{{ request()->is('masters/companies*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Companies
                        </span>
                    </a>
                    @endif

                    {{-- MASTERS PROVINCES --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.provinces'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/provinces*') }}" href="{{ route('masters.provinces.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/provinces*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('masters/provinces*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Provinces
                        </span>
                    </a>
                    @endif

                    {{-- MASTERS BRANCHES --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.branches'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/branches*')}}" href="{{ route('masters.branches.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/branches*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('masters/branches*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Branches
                        </span>
                    </a>
                    @endif

                    {{-- Masters Regions --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.regions'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/regions*') }}" href="{{ route('masters.regions.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/regions*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('masters/regions*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Regions
                        </span>
                    </a>
                    @endif

                    {{-- @if(auth()->check() && auth()->user()->hasPermission('masters.banks'))
                    <a class="collapse-item {{ request()->routeIs('masters.banks*') ? 'active' : '' }}" href="{{ route('masters.banks.index') }}">Banks</a>
                    @endif
                    @if(auth()->check() && auth()->user()->hasPermission('masters.accounts'))
                    <a class="collapse-item {{ request()->routeIs('masters.accounts*') ? 'active' : '' }}" href="{{ route('masters.accounts.index') }}">Accounts</a>
                    @endif --}}

                    {{-- Masters Expense Types --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.expense-types'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/expense-types*') }}" href="{{ route('masters.expense-types.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/expense-types*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('masters/expense-types*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Expenses Types
                        </span>
                    </a>
                    @endif

                    {{-- Masters Customer Types --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.customer-types'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/customer-types*')}}" href="{{ route('masters.customer-types.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/customer-types*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">
                        </span>
                        <span class="{{ request()->is('masters/customer-types*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Customer Types
                        </span>
                    </a>
                    @endif

                    {{-- Maters Product Categories --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.product-categories'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/product-categories*')}}" href="{{ route('masters.product-categories.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/product-categories*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('masters/product-categories*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Product Categories
                        </span>
                    </a>
                    @endif

                    {{-- Masters Products Types --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.parts'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/parts*') }}" href="{{ route('masters.parts.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/parts*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('masters/parts*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Product Types
                        </span>
                    </a>
                    @endif

                    {{-- Master Products --}}
                    @if(auth()->check() && auth()->user()->hasPermission('masters.products'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('masters/products*') }}" href="{{ route('masters.products.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('masters/products*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('masters/products*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Products
                        </span>
                    </a>
                    @endif                                
                </div>
            </div>
        </li>
        {{-- Scripts for collapse masters --}}
        @php
        $isMastersActive = 
            request()->is('masters/companies*') ||
            request()->is('masters/provinces*') ||
            request()->is('masters/branches*') ||
            request()->is('masters/regions*') ||
            request()->is('masters/expense-types*') ||
            request()->is('masters/customer-types*') ||
            request()->is('masters/product-categories*') ||
            request()->is('masters/product-types*') ||
            request()->is('masters/products*');
        @endphp

        <script>
        document.addEventListener('DOMContentLoaded', () => {

            const mastersToggle = document.getElementById('mastersToggle');
            const mastersMenu = document.getElementById('mastersMenu');
            const mastersIconInner = document.getElementById('mastersIconInner');
            const mastersIconOuter = document.getElementById('mastersIconOuter');
            const mastersSpan = document.getElementById('mastersSpan');
            const chevronMastersMenu = document.getElementById('chevronMastersMenu');

            const isActive = @json($isMastersActive);

            if (!mastersToggle || !mastersMenu) return;

            function openMasters() {
                mastersMenu.classList.remove('max-h-0');
                mastersMenu.classList.add('max-h-[500px]');
                mastersToggle.classList.add('bg-[#E8EFEC]');
                mastersSpan.classList.add('text-[#115640]');
                mastersIconInner.setAttribute('stroke', '#115640');
                mastersIconOuter.setAttribute('stroke', '#115640');
                chevronMastersMenu.classList.remove('text-black');
                chevronMastersMenu.classList.add('rotate-90', 'text-[#115640]');
            }

            function closeMasters() {
                mastersMenu.classList.add('max-h-0');
                mastersMenu.classList.remove('max-h-[500px]');
                mastersToggle.classList.remove('bg-[#E8EFEC]');
                mastersSpan.classList.remove('text-[#115640]');
                mastersIconInner.setAttribute('stroke', '#1E1E1E');
                mastersIconOuter.setAttribute('stroke', '#1E1E1E');
                chevronMastersMenu.classList.remove('rotate-90', 'text-[#115640]');
                chevronMastersMenu.classList.add('text-black');
            }

            if(isActive){
                openMasters();
            }

            mastersToggle.addEventListener('click', () => {
                if (mastersMenu.classList.contains('max-h-0')) {
                    openMasters();
                } else {
                    closeMasters();
                }
            });

        });
        </script>
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
        <li id="listUsers" class="{{ request()->is('users*') }} rounded-lg mt-2">
            <button 
                id="usersToggle"
                class="cursor-pointer w-full text-left rounded-lg px-3 py-2 grid place-items-center lg:flex lg:items-center lg:justify-between">
                <div class="lg:flex! lg:items-center! lg:justify-start! lg:gap-3!">
                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path id="usersIcon" d="M11.4665 12.8V11.4667C11.4665 10.7595 11.1855 10.0812 10.6854 9.5811C10.1853 9.081 9.50705 8.80005 8.7998 8.80005H3.46647C2.75923 8.80005 2.08095 9.081 1.58085 9.5811C1.08076 10.0812 0.799805 10.7595 0.799805 11.4667V12.8M8.7998 3.46672C8.7998 4.93947 7.6059 6.13338 6.13314 6.13338C4.66038 6.13338 3.46647 4.93947 3.46647 3.46672C3.46647 1.99396 4.66038 0.800049 6.13314 0.800049C7.6059 0.800049 8.7998 1.99396 8.7998 3.46672Z" stroke="#1E1E1E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
    
                    <span id="usersSpan" class="text-[#010508] font-semibold sm:hidden lg:inline">Users</span>
                </div>
                <i id="chevronUsersMenu" class="sm:hidden! lg:inline-block! fas fa-chevron-right transition-transform duration-300 text-black" style="font-size: 16px;"></i>
            </button>
            <div 
                id="usersMenu" 
                class="block mt-2 overflow-hidden transition-all duration-300 max-h-0 {{ request()->is('users*') }}">
                <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">
                    {{-- USERS MANAGE --}}
                    @if(auth()->check() && auth()->user()->hasPermission('users.manage'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('users*') || request()->routeIs('users/form')}}" href="{{ route('users.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('users') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">
                        </span>
                        <span class="{{ request()->is('users') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Manage Users
                        </span>
                    </a>
                    @endif

                    {{-- USER ROLES --}}
                    @if(auth()->check() && auth()->user()->hasPermission('users.roles'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('users/roles*') }}" href="{{ route('users.roles.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('users/roles*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                        </span>
                        <span class="{{ request()->is('users/roles*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Roles
                        </span>
                    </a>
                    @endif                
                </div>
            </div>
        </li>

        {{-- Scripts for collapse users --}}

        @php
            $isUsersActive = 
                request()->is('users') ||
                request()->is('users/roles*');
        @endphp

        <script>
        document.addEventListener('DOMContentLoaded', () => {

            const usersToggle = document.getElementById('usersToggle');
            const usersMenu = document.getElementById('usersMenu');
            const usersIcon = document.getElementById('usersIcon');
            const usersSpan = document.getElementById('usersSpan');
            const chevronUsersMenu = document.getElementById('chevronUsersMenu');

            const isActive = @json($isUsersActive);

            function openUsers() {
                usersMenu.classList.remove('max-h-0');
                usersMenu.classList.add('max-h-[500px]');
                usersToggle.classList.add('bg-[#E8EFEC]');
                usersSpan.classList.add('text-[#115640]');
                usersIcon.setAttribute('stroke', '#115640');
                chevronUsersMenu.classList.remove('text-black');
                chevronUsersMenu.classList.add('rotate-90', 'text-[#115640]');
            }

            function closeUsers() {
                usersMenu.classList.add('max-h-0');
                usersMenu.classList.remove('max-h-[500px]');
                usersToggle.classList.remove('bg-[#E8EFEC]');
                usersSpan.classList.remove('text-[#115640]');
                usersIcon.setAttribute('stroke', '#1E1E1E');
                chevronUsersMenu.classList.remove('rotate-90', 'text-[#115640]');
                chevronUsersMenu.classList.add('text-black');
            }

            if(isActive){
                openUsers();
            }

            usersToggle.addEventListener('click', () => {
                if (usersMenu.classList.contains('max-h-0')) {
                    openUsers();
                } else {
                    closeUsers();
                }
            });

        });
        </script>
        @endif

        {{-- SETTINGS MENU --}}
        @php
            $settingsMenu = [            
                'settings.permissions-settings',
            ];

            $showSettings = collect($settingsMenu)->contains(fn($p) => auth()->check() && auth()->user()->hasPermission($p));
        @endphp

        @if ($showSettings)
        <li 
            id="listSettings" 
            class="rounded-lg mt-2{{ request()->routeIs('settings/permissions*') || request()->routeIs('settings/permissions*') }}">
            <button 
                id="settingsToggle"
                class="cursor-pointer w-full text-left rounded-lg px-3 py-2 grid place-items-center lg:flex lg:items-center lg:justify-between">
                <div class="lg:flex! lg:items-center! lg:justify-start! lg:gap-3!">
                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_68_15999)">
                            <path d="M2.6665 14V9.33333M2.6665 6.66667V2M7.99984 14V8M7.99984 5.33333V2M13.3332 14V10.6667M13.3332 8V2M0.666504 9.33333H4.6665M5.99984 5.33333H9.99984M11.3332 10.6667H15.3332" id="settingsIcon" stroke="#1E1E1E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_68_15999">
                                <rect width="20" height="20" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>

                    <span id="settingsSpan" class="text-[#010508] font-semibold sm:hidden lg:inline">
                        Settings
                </div>
                </span>
                <i id="chevronSettingsMenu" class="sm:hidden! lg:inline-block! fas fa-chevron-right transition-transform duration-300 text-black" style="font-size: 16px;"></i>
            </button>
            <div 
                id="settingsMenu" 
                class="block mt-2 overflow-hidden transition-all duration-300 max-h-0 {{ request()->is('settings*') }}">
                <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">  
                    {{-- SETTINGS PERMISSIONS --}}
                    @if(auth()->check() && auth()->user()->hasPermission('settings.permissions-settings'))
                    <a 
                        class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('settings/permissions*') }}" href="{{ route('settings.permissions-settings.index') }}">
                        <span class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('settings/permissions*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">
                        </span>
                        <span class="{{ request()->is('settings/permissions*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                            Permissions Settings
                        </span>
                    </a>
                    @endif
                    {{-- @if(auth()->check() && auth()->user()->role?->code === 'super_admin')
                    <a class="collapse-item reset-data" href="{{ route('settings.seeder.run') }}">Reset Data</a>
                    @endif --}}
                </div>
            </div>
        </li>
        @php
            $isSettingsActive = request()->is('settings/permissions*');
        @endphp

        <script>
        document.addEventListener('DOMContentLoaded', () => {

            const settingsToggle = document.getElementById('settingsToggle');
            const settingsMenu = document.getElementById('settingsMenu');
            const settingsIcon = document.getElementById('settingsIcon');
            const settingsSpan = document.getElementById('settingsSpan');
            const chevronSettingsMenu = document.getElementById('chevronSettingsMenu');

            const isActive = @json($isSettingsActive);

            function openSettings() {
                settingsMenu.classList.remove('max-h-0');
                settingsMenu.classList.add('max-h-[500px]');
                settingsToggle.classList.add('bg-[#E8EFEC]');
                settingsSpan.classList.add('text-[#115640]');
                settingsIcon.setAttribute('stroke', '#115640');
                chevronSettingsMenu.classList.remove('text-black');
                chevronSettingsMenu.classList.add('rotate-90', 'text-[#115640]');
            }

            function closeSettings() {
                settingsMenu.classList.add('max-h-0');
                settingsMenu.classList.remove('max-h-[500px]');
                settingsToggle.classList.remove('bg-[#E8EFEC]');
                settingsSpan.classList.remove('text-[#115640]');
                settingsIcon.setAttribute('stroke', '#0D0F11');
                chevronSettingsMenu.classList.remove('rotate-90', 'text-[#115640]');
                chevronSettingsMenu.classList.add('text-black');
            }

            if(isActive){
                openSettings();
            }

            settingsToggle.addEventListener('click', () => {
                if (settingsMenu.classList.contains('max-h-0')) {
                    openSettings();
                } else {
                    closeSettings();
                }
            });

        });
        </script>
        @endif
    </div>

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

    {{-- USER INFO --}}
    <div class="mt-auto py-4 border-t border-[#CFD5DC]">
        {{-- TOGGLE USER INFO --}}
        <div id="toggleUserInfo" class="lg:flex lg:justify-between lg:items-center cursor-pointer px-3 xl:px-5 grid grid-cols-1 place-items-center">
            <div class="lg:flex lg:items-center lg:gap-3">
                {{-- USER AVATAR --}}
                <div    
                    class="rounded-circle bg-[#115641] flex items-center justify-center sm:w-[35px]! sm:h-[35px]! xl:w-[45px] xl:h-[45px]">
                    <i class="fas fa-user text-white text-xs xl:text-sm"></i>
                </div>
                {{-- USER NAME & ROLE --}}
                <div class="lg:flex! lg:flex-col! sm:hidden!">
                    <span class="text-sm xl:text-lg font-semibold text-black">
                        {{ auth()->user()?->name ?? 'User' }}
                    </span>
                    <small class="text-xs xl:text-sm text-black mt-1">
                        {{ auth()->user()?->role?->name ?? 'User' }}
                    </small>
                </div>
            </div>
            {{-- ICON CHEVRON --}}
            <i id="chevronUserInfo" class="sm:hidden! lg:inline-block! fas fa-chevron-up transition-transform duration-300 text-black" style="font-size: 16px;"></i>
        </div>
        
        {{-- DETAIL USER INFO --}}
        <div id="userDropdown"
            class="absolute bottom-[70px] left-4 right-4 bg-white rounded-xl shadow-xl p-4 opacity-0 scale-95 pointer-events-none transition-all duration-200">

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
                <button class="cursor-pointer w-full px-3 py-2 rounded hover:bg-gray-100 flex justify-center items-center gap-2">
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