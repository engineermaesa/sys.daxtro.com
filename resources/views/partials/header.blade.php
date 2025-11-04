<!-- Fixed Header for Content Area Only -->
<nav class="navbar navbar-expand navbar-light topbar mb-4 shadow" style="
    position: fixed; 
    top: 0; 
    left: 224px; 
    right: 0; 
    z-index: 1030; 
    background: linear-gradient(180deg, #033224 80%, #115641 300%);
    height: 70px;
    border: none;
    margin-bottom: 0 !important;
    padding: 0 2rem;
">
    <!-- Mobile Sidebar Toggle -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" style="color: white;">
        <i class="fa fa-bars"></i>
    </button>
    
    <!-- Right Side - User Profile -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" 
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color: white; text-decoration: none;">
                <!-- User Avatar -->
                <div class="rounded-circle bg-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 40px; height: 40px; margin-right: 12px;">
                    <i class="fas fa-user" style="color: #115641; font-size: 18px;"></i>
                </div>
                <!-- User Name -->
                <div class="d-flex flex-column align-items-start">
                    <span class="fw-bold text-white" style="font-size: 16px; line-height: 1.2;">
                        {{ auth()->user()->name ?? 'User' }}
                    </span>
                    <small class="text-white-50" style="font-size: 12px;">
                        {{ auth()->user()->role?->name ?? 'User' }}
                    </small>
                </div>
                <!-- Dropdown Arrow -->
                <i class="fas fa-chevron-down text-white ms-2" style="font-size: 12px; margin-left: 8px;"></i>
            </a>
            
            <!-- Dropdown Menu -->
            <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 animated--grow-in" 
                 aria-labelledby="userDropdown" style="border-radius: 12px; margin-top: 10px;">
                <div class="dropdown-header text-center py-3">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-2" 
                         style="width: 50px; height: 50px;">
                        <i class="fas fa-user text-white" style="font-size: 20px;"></i>
                    </div>
                    <h6 class="mb-0">{{ auth()->user()->name ?? 'User' }}</h6>
                    <small class="text-muted">{{ auth()->user()->email ?? '' }}</small>
                </div>
                <div class="dropdown-divider"></div>
                {{-- <a class="dropdown-item d-flex align-items-center" href="{{ route('dashboard') }}">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a> --}}
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Logout
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>
