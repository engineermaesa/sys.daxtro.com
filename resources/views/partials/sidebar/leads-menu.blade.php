<li id="listLeads"
     class="{{ (request()->is('leads/*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*') || request()->is('lost-leads*')) ? '' : '' }} rounded-lg mt-2">
  <button
        class="cursor-pointer w-full text-left rounded-lg p-3 grid place-items-center lg:flex lg:items-center lg:justify-between"
        href="#" data-toggle="collapse" data-target="#collapseLeads"
        aria-expanded="{{ request()->is('leads*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*') || request()->is('lost-leads*') }}"
        id="leadsToggle">
        <div class="lg:flex lg:items-center lg:justify-start lg:gap-3">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path id="leadsIcon"
                    d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                    fill="#0D0F11" />
            </svg>
            <span id="leadsSpan" class="sidebar-label text-[#1E1E1E] font-semibold sm:hidden lg:inline">Leads</span>
        </div>
        <i id="chevronLeadsMenu"
            class="sidebar-chevron sm:hidden! lg:inline-block! fas fa-chevron-right transition-transform duration-300 text-black"
            style="font-size: 16px;"></i>
    </button>
    <div id="leadsMenu"
        class="sidebar-submenu block mt-2 overflow-hidden transition-all duration-300 max-h-0 {{ request()->is('leads*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*') || request()->is('lost-leads*') }} text-sm">
        <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">
            {{-- AVAILABLE LEADS --}}
            @if(auth()->check() && (auth()->user()->hasPermission('leads.available') ||
            auth()->user()->hasPermission('leads.available')))
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/available*') }}"
                href="{{ route('leads.available') }}">
                <span
                    class="block sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->is('leads/available*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                </span>
                <span
                    class="{{ request()->is('leads/available*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                    Available Leads
                </span>
            </a>
            @endif

            {{-- MY LEADS --}}
            @if(auth()->check() && (auth()->user()->hasPermission('leads.my') ||
            auth()->user()->hasPermission('leads.my')))
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/my*') || request()->is('quotations*') || request()->is('payment-confirmation*') }}"
                href="{{ route('leads.my') }}">
                <span
                    class="block h-[20px] w-[3px] {{ request()->is('leads/my*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                </span>
                <span
                    class="{{ request()->is('leads/my*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                    My Leads
                </span>
            </a>
            @endif

            {{-- LEADS MANAGE --}}
            @if(auth()->check() && auth()->user()->hasPermission('leads.manage'))
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/manage*') || request()->is('quotations*') || request()->is('payment-confirmation*') }}"
                href="{{ route('leads.manage') }}">
                <span
                    class="block h-[20px] w-[3px] {{ request()->is('leads/manage*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                </span>
                <span
                    class="{{ request()->is('leads/manage*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                    All Leads
                </span>
            </a>
            @endif

            {{-- IMPORTS LEAD --}}
            @if(auth()->check() && auth()->user()->role?->code === 'super_admin')
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('leads/import*') }}"
                href="{{ route('leads.import') }}">
                <span
                    class="block h-[20px] w-[3px] {{ request()->is('leads/import*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                </span>
                <span
                    class="{{ request()->is('leads/import*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                    Import Leads
                </span>
            </a>
            @endif

            {{-- TRASH LEADS --}}
            @if(auth()->check() && auth()->user()->hasPermission('leads.trash'))
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('trash-leads*') }}"
                href="{{ route('trash-leads.index') }}">
                <span
                    class="block h-[20px] w-[3px] {{ request()->is('trash-leads*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                </span>
                <span
                    class="{{ request()->is('trash-leads*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                    Trash Leads
                </span>
            </a>
            @endif

            {{-- LOST LEADS --}}
            @if(auth()->check() && auth()->user()->hasPermission('leads.lost'))
            <a class="flex items-center sm:gap-2 lg:gap-3 {{ request()->is('lost-leads*') }}"
                href="{{ route('lost-leads.index') }}">
                <span
                    class="block h-[20px] w-[3px] {{ request()->is('lost-leads*') ? 'bg-[#115640]' : 'bg-[#6B7786]' }}">

                </span>
                <span
                    class="{{ request()->is('lost-leads*') ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">
                    Lost Leads
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
request()->is('trash-leads*') ||
request()->is('lost-leads*');
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
        leadsToggle.classList.add('bg-[#CFE7DE]');
        leadsSpan.classList.add('text-[#115640]');
        leadsIcon.setAttribute('fill', '#115640');
        chevronLeadsMenu.classList.remove('text-black');
        chevronLeadsMenu.classList.add('rotate-90', 'text-[#115640]');
    }

    function closeLeads() {
        leadsMenu.classList.add('max-h-0');
        leadsMenu.classList.remove('max-h-[500px]');
        leadsToggle.classList.remove('bg-[#CFE7DE]');
        leadsSpan.classList.remove('text-[#115640]');
        leadsIcon.setAttribute('fill', '#0D0F11');
        chevronLeadsMenu.classList.remove('rotate-90', 'text-[#115640]');
        chevronLeadsMenu.classList.add('text-black');
    }

    if(isActive){
        openLeads();
    }

    leadsToggle.addEventListener('click', () => {
        const sidebarWrapper = document.getElementById('sidebarWrapper');
        if (sidebarWrapper && sidebarWrapper.classList.contains('sidebar-collapsed')) return;

        if (leadsMenu.classList.contains('max-h-0')) {
            openLeads();
        } else {
            closeLeads();
        }
    });

});
</script>
