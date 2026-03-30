<li id="listSettings"
    class="rounded-lg mt-2{{ request()->routeIs('settings/permissions*') || request()->routeIs('settings/permissions*') }}">
    <button id="settingsToggle"
        class="cursor-pointer w-full text-left rounded-lg px-3 py-2 grid place-items-center lg:flex lg:items-center lg:justify-between">
        <div class="lg:flex! lg:items-center! lg:justify-start! lg:gap-3!">
            <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_68_15999)">
                    <path
                        d="M2.6665 14V9.33333M2.6665 6.66667V2M7.99984 14V8M7.99984 5.33333V2M13.3332 14V10.6667M13.3332 8V2M0.666504 9.33333H4.6665M5.99984 5.33333H9.99984M11.3332 10.6667H15.3332"
                        id="settingsIcon" stroke="#1E1E1E" stroke-width="1.6" stroke-linecap="round"
                        stroke-linejoin="round" />
                </g>
                <defs>
                    <clipPath id="clip0_68_15999">
                        <rect width="20" height="20" fill="white" />
                    </clipPath>
                </defs>
            </svg>

            <span id="settingsSpan" class="sidebar-label text-[#010508] font-semibold sm:hidden lg:inline">
                Settings
            </span>
        </div>
        <i id="chevronSettingsMenu"
            class="sidebar-chevron sm:hidden! lg:inline-block! fas fa-chevron-right transition-transform duration-300 text-black"
            style="font-size: 16px;"></i>
    </button>
    <div id="settingsMenu"
        class="sidebar-submenu block mt-2 overflow-hidden transition-all duration-300 max-h-0 {{ request()->is('settings*') }}">
        <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">
            {{-- SETTINGS PERMISSIONS --}}
            @if(auth()->check() && auth()->user()->hasPermission('settings.permissions-settings'))
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->routeIs('settings/permissions*') }}"
                href="{{ route('settings.permissions-settings.index') }}">
                <span
                    class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('settings/permissions*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">
                </span>
                <span
                    class="sidebar-label {{ request()->is('settings/permissions*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
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
        settingsToggle.classList.add('bg-[#CFE7DE]');
        settingsSpan.classList.add('text-[#115640]');
        settingsIcon.setAttribute('stroke', '#115640');
        chevronSettingsMenu.classList.remove('text-black');
        chevronSettingsMenu.classList.add('rotate-90', 'text-[#115640]');
    }

    function closeSettings() {
        settingsMenu.classList.add('max-h-0');
        settingsMenu.classList.remove('max-h-[500px]');
        settingsToggle.classList.remove('bg-[#CFE7DE]');
        settingsSpan.classList.remove('text-[#115640]');
        settingsIcon.setAttribute('stroke', '#0D0F11');
        chevronSettingsMenu.classList.remove('rotate-90', 'text-[#115640]');
        chevronSettingsMenu.classList.add('text-black');
    }

    if(isActive){
        openSettings();
    }

    settingsToggle.addEventListener('click', () => {
        const sidebarWrapper = document.getElementById('sidebarWrapper');
        if (sidebarWrapper && sidebarWrapper.classList.contains('sidebar-collapsed')) return;

        if (settingsMenu.classList.contains('max-h-0')) {
            openSettings();
        } else {
            closeSettings();
        }
    });

});
</script>