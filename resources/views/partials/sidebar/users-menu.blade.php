<li id="listUsers" class="{{ request()->is('users*') }} rounded-lg mt-2">
    <button id="usersToggle"
        class="cursor-pointer w-full text-left rounded-lg px-3 py-2 grid place-items-center lg:flex lg:items-center lg:justify-between">
        <div class="lg:flex! lg:items-center! lg:justify-start! lg:gap-3!">
            <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path id="usersIcon"
                    d="M11.4665 12.8V11.4667C11.4665 10.7595 11.1855 10.0812 10.6854 9.5811C10.1853 9.081 9.50705 8.80005 8.7998 8.80005H3.46647C2.75923 8.80005 2.08095 9.081 1.58085 9.5811C1.08076 10.0812 0.799805 10.7595 0.799805 11.4667V12.8M8.7998 3.46672C8.7998 4.93947 7.6059 6.13338 6.13314 6.13338C4.66038 6.13338 3.46647 4.93947 3.46647 3.46672C3.46647 1.99396 4.66038 0.800049 6.13314 0.800049C7.6059 0.800049 8.7998 1.99396 8.7998 3.46672Z"
                    stroke="#1E1E1E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>

            <span id="usersSpan" class="sidebar-label text-[#010508] font-semibold sm:hidden lg:inline">Users</span>
        </div>
        <i id="chevronUsersMenu"
            class="sidebar-chevron sm:hidden! lg:inline-block! fas fa-chevron-right transition-transform duration-300 text-black"
            style="font-size: 16px;"></i>
    </button>
    <div id="usersMenu"
        class="sidebar-submenu block mt-2 overflow-hidden transition-all duration-300 max-h-0 {{ request()->is('users*') }}">
        <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">
            {{-- USERS MANAGE --}}
            @if(auth()->check() && auth()->user()->hasPermission('users.manage'))
            @php
                $manageUsersLabel = auth()->user()->role?->code === 'branch_manager'
                    ? 'Manage Sales'
                    : 'Manage Users';
            @endphp
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('users*') || request()->routeIs('users/form')}}"
                href="{{ route('users.index') }}">
                <span
                    class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('users') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">
                </span>
                <span
                    class="{{ request()->is('users') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                    {{ $manageUsersLabel }}
                </span>
            </a>
            @endif

            {{-- USER ROLES --}}
            @if(auth()->check() && auth()->user()->hasPermission('users.roles'))
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('users/roles*') }}"
                href="{{ route('users.roles.index') }}">
                <span
                    class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('users/roles*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                </span>
                <span
                    class="{{ request()->is('users/roles*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
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
        usersToggle.classList.add('bg-[#CFE7DE]');
        usersSpan.classList.add('text-[#115640]');
        usersIcon.setAttribute('stroke', '#115640');
        chevronUsersMenu.classList.remove('text-black');
        chevronUsersMenu.classList.add('rotate-90', 'text-[#115640]');
    }

    function closeUsers() {
        usersMenu.classList.add('max-h-0');
        usersMenu.classList.remove('max-h-[500px]');
        usersToggle.classList.remove('bg-[#CFE7DE]');
        usersSpan.classList.remove('text-[#115640]');
        usersIcon.setAttribute('stroke', '#1E1E1E');
        chevronUsersMenu.classList.remove('rotate-90', 'text-[#115640]');
        chevronUsersMenu.classList.add('text-black');
    }

    if(isActive){
        openUsers();
    }

    usersToggle.addEventListener('click', () => {
        const sidebarWrapper = document.getElementById('sidebarWrapper');
        if (sidebarWrapper && sidebarWrapper.classList.contains('sidebar-collapsed')) return;

        if (usersMenu.classList.contains('max-h-0')) {
            openUsers();
        } else {
            closeUsers();
        }
    });

});
</script>