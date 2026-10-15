@if(auth()->check() && auth()->user()->hasPermission('aftersales.tickets.manage'))
<style>
    #documentTemplatesToggle[aria-expanded="false"] .sidebar-chevron { transform: rotate(180deg); }
</style>
<li class="rounded-lg mt-2">
    <button id="documentTemplatesToggle" type="button" class="cursor-pointer w-full flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('aftersales.pages.document-templates.*') ? 'bg-[#CFE7DE] text-[#115640]' : 'text-[#1E1E1E]' }}" onclick="const menu=document.getElementById('documentTemplatesMenu'); menu.hidden=!menu.hidden; this.setAttribute('aria-expanded', !menu.hidden)" aria-expanded="true" aria-controls="documentTemplatesMenu">
        <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span class="sidebar-label {{ request()->routeIs('aftersales.pages.document-templates.*') ? 'text-[#115640]' : 'text-[#1E1E1E]' }} font-semibold text-left">Document Templates</span><i class="bi bi-chevron-up sidebar-chevron ml-auto text-black" aria-hidden="true"></i>
    </button>
    <div id="documentTemplatesMenu" class="sidebar-submenu mt-2 overflow-hidden">
        <div class="lg:pl-4 lg:space-y-2 pl-2 space-y-1">
        @foreach(['service-agreement' => 'Service Agreement', 'service-inspection-report' => 'Service Inspection & Report', 'satisfaction-survey' => 'Satisfaction Survey'] as $key => $label)
        <a href="{{ route('aftersales.pages.document-templates.show', $key) }}" class="flex items-center sm:gap-2 lg:gap-3" @if(request()->route('template') === $key) aria-current="page" @endif>
            <span class="block shrink-0 sm:h-[15px] lg:h-[20px] w-[3px] {{ request()->route('template') === $key ? 'bg-[#115640]' : 'bg-[#6B7786]' }}" aria-hidden="true"></span>
            <span class="sidebar-label {{ request()->route('template') === $key ? 'text-[#115640]' : 'text-[#6B7786]' }} font-semibold sm:text-xs lg:text-sm">{{ $label }}</span>
        </a>
        @endforeach
        </div>
    </div>
</li>
@endif
