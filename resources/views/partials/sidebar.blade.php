<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon">
            <img src="{{ asset('assets/images/favicon.png') }}" alt="DAXTRO Logo" style="max-height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">DAXTRO</div>
    </a>
    <hr class="sidebar-divider my-0">

    @if (auth()->check() && auth()->user()->hasPermission('dashboard'))
    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    @endif

    <hr class="sidebar-divider">    

    @php
        $showLeads = auth()->check() && (
            auth()->user()->hasPermission('leads.manage') ||
            auth()->user()->hasPermission('leads.available') ||
            auth()->user()->hasPermission('leads.my') ||
            auth()->user()->hasPermission('leads.trash')
        );
    @endphp
    @if ($showLeads)
    <li class="nav-item {{ (request()->is('leads*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*')) ? 'active' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLeads" aria-expanded="{{ (request()->is('leads*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*')) ? 'true' : 'false' }}" aria-controls="collapseLeads">
            <i class="fas fa-fw fa-address-book"></i>
            <span>Leads</span>
        </a>
        <div id="collapseLeads" class="collapse {{ (request()->is('leads*') || request()->is('quotations*') || request()->is('payment-confirmation*') || request()->is('trash-leads*')) ? 'show' : '' }}" aria-labelledby="headingLeads" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                @if(auth()->check() && (auth()->user()->hasPermission('leads.available') || auth()->user()->hasPermission('leads.available')))
                <a class="collapse-item {{ request()->is('leads/available*') ? 'active' : '' }}" href="{{ route('leads.available') }}">Available Leads</a>
                @endif
                @if(auth()->check() && (auth()->user()->hasPermission('leads.my') || auth()->user()->hasPermission('leads.my')))
                <a class="collapse-item {{ request()->is('leads/my*') || request()->is('quotations*') || request()->is('payment-confirmation*') ? 'active' : '' }}" href="{{ route('leads.my') }}">My Leads</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('leads.manage'))
                <a class="collapse-item {{ request()->is('leads/manage*') || request()->is('quotations*') || request()->is('payment-confirmation*') ? 'active' : '' }}" href="{{ route('leads.manage') }}">All Leads</a>
                @endif
                @if(auth()->check() && auth()->user()->role?->code === 'super_admin')
                <a class="collapse-item {{ request()->is('leads/import*') ? 'active' : '' }}" href="{{ route('leads.import') }}">Import Leads</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('leads.trash'))
                <a class="collapse-item {{ request()->is('trash-leads*') ? 'active' : '' }}" href="{{ route('trash-leads.index') }}">Trash Leads</a>
                @endif
            </div>
        </div>
    </li>
    @endif

    @if(auth()->check() && auth()->user()->hasPermission('orders'))
    <li class="nav-item {{ request()->routeIs('orders*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('orders.index') }}">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>Orders</span>
        </a>
    </li>
    @endif

    {{-- Finance Requests --}}
    @if(auth()->check() && auth()->user()->hasPermission('finance.requests'))
    <li class="nav-item {{ request()->routeIs('finance-requests.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('finance-requests.index') }}">
            <i class="fas fa-fw fa-file-invoice-dollar"></i>
            <span>Finance Requests</span>
        </a>
    </li>
    @endif

    @if(auth()->check() && auth()->user()->hasPermission('incentives.view'))
    <li class="nav-item {{ request()->routeIs('incentives.dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('incentives.dashboard') }}">
            <i class="fas fa-fw fa-gift"></i>
            <span>Incentives</span>
        </a>
    </li>
    @endif

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
    <li class="nav-item {{ request()->is('masters/*') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMasters" aria-expanded="{{ request()->is('masters/*') ? 'true' : 'false' }}" aria-controls="collapseMasters">
            <i class="fas fa-fw fa-cog"></i>
            <span>Masters</span>
        </a>
        <div id="collapseMasters" class="collapse {{ request()->is('masters/*') ? 'show' : '' }}" aria-labelledby="headingMasters" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                @if(auth()->check() && auth()->user()->hasPermission('masters.companies'))
                <a class="collapse-item {{ request()->routeIs('masters.companies*') ? 'active' : '' }}" href="{{ route('masters.companies.index') }}">Companies</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.provinces'))
                <a class="collapse-item {{ request()->routeIs('masters.provinces*') ? 'active' : '' }}" href="{{ route('masters.provinces.index') }}">Provinces</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.branches'))
                <a class="collapse-item {{ request()->routeIs('masters.branches*') ? 'active' : '' }}" href="{{ route('masters.branches.index') }}">Branches</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.regions'))
                <a class="collapse-item {{ request()->routeIs('masters.regions*') ? 'active' : '' }}" href="{{ route('masters.regions.index') }}">Regions</a>
                @endif
                {{-- @if(auth()->check() && auth()->user()->hasPermission('masters.banks'))
                <a class="collapse-item {{ request()->routeIs('masters.banks*') ? 'active' : '' }}" href="{{ route('masters.banks.index') }}">Banks</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.accounts'))
                <a class="collapse-item {{ request()->routeIs('masters.accounts*') ? 'active' : '' }}" href="{{ route('masters.accounts.index') }}">Accounts</a>
                @endif --}}
                @if(auth()->check() && auth()->user()->hasPermission('masters.expense-types'))
                <a class="collapse-item {{ request()->routeIs('masters.expense-types*') ? 'active' : '' }}" href="{{ route('masters.expense-types.index') }}">Expense Types</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.customer-types'))
                <a class="collapse-item {{ request()->routeIs('masters.customer-types*') ? 'active' : '' }}" href="{{ route('masters.customer-types.index') }}">Customer Types</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.product-categories'))
                <a class="collapse-item {{ request()->routeIs('masters.product-categories*') ? 'active' : '' }}" href="{{ route('masters.product-categories.index') }}">Product Categories</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.parts'))
                <a class="collapse-item {{ request()->routeIs('masters.parts*') ? 'active' : '' }}" href="{{ route('masters.parts.index') }}">Product Parts</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('masters.products'))
                <a class="collapse-item {{ request()->routeIs('masters.products*') ? 'active' : '' }}" href="{{ route('masters.products.index') }}">Products</a>
                @endif                                
            </div>
        </div>
    </li>
    @endif

    @php
        $userMenu = [
            'users.manage',
            'users.roles',
        ];
        $showUsers = collect($userMenu)->contains(fn($p) => auth()->check() && auth()->user()->hasPermission($p));
        $settingsMenu = [            
            'settings.permissions-settings',
        ];
        $showSettings = collect($settingsMenu)->contains(fn($p) => auth()->check() && auth()->user()->hasPermission($p));
    @endphp
    @if ($showUsers)
    <li class="nav-item {{ request()->is('users*') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUsers"
        aria-expanded="{{ request()->is('users*') ? 'true' : 'false' }}" aria-controls="collapseUsers">
            <i class="fas fa-fw fa-users"></i>
            <span>Users</span>
        </a>
        <div id="collapseUsers" class="collapse {{ request()->is('users*') ? 'show' : '' }}"
            aria-labelledby="headingUsers" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                @if(auth()->check() && auth()->user()->hasPermission('users.manage'))
                <a class="collapse-item {{ request()->routeIs('users.index') ||request()->routeIs('users.form') ? 'active' : '' }}" href="{{ route('users.index') }}">Manage Users</a>
                @endif
                @if(auth()->check() && auth()->user()->hasPermission('users.roles'))
                <a class="collapse-item {{ request()->routeIs('users.roles*') ? 'active' : '' }}" href="{{ route('users.roles.index') }}">Roles</a>
                @endif                
            </div>
        </div>
    </li>
    @endif

    @if ($showSettings)
    <li class="nav-item {{ (request()->routeIs('settings.permissions-settings*') || request()->routeIs('settings.permissions-settings*')) ? 'active' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSettings"
            aria-expanded="{{ (request()->routeIs('settings.permissions-settings*') || request()->routeIs('settings.permissions-settings*')) ? 'true' : 'false' }}" aria-controls="collapseSettings">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Settings</span>
        </a>
        <div id="collapseSettings" class="collapse {{ (request()->routeIs('settings.permissions-settings*') || request()->routeIs('settings.permissions-settings*')) ? 'show' : '' }}" aria-labelledby="headingSettings" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">                
                @if(auth()->check() && auth()->user()->hasPermission('settings.permissions-settings'))
                <a class="collapse-item {{ request()->routeIs('settings.permissions-settings*') ? 'active' : '' }}" href="{{ route('settings.permissions-settings.index') }}">Permissions Settings</a>
                @endif
                {{-- @if(auth()->check() && auth()->user()->role?->code === 'super_admin')
                <a class="collapse-item reset-data" href="{{ route('settings.seeder.run') }}">Reset Data</a>
                @endif --}}
            </div>
        </div>
    </li>
    @endif

    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>