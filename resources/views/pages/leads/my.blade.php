@extends('layouts.app')

@section('content')

<!-- Quotation Logs Modal -->
<div class="modal fade" id="quotationLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quotation Logs</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="min-h-screen">
    {{-- HEADER PAGES --}}
    <div class="pt-4">
        <div class="flex items-center gap-3">
            <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                    fill="#115640" />
            </svg>
            <h1 class="text-[#115640] font-semibold text-2xl">Leads</h1>
        </div>
        <p class="mt-1 text-[#115640] text-lg">My Leads</p>
    </div>

    {{-- CARDS COUNTS --}}
    <div class="grid grid-cols-4 gap-3 mt-4">
        {{-- COLD CARDS --}}
        <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9]">
            <div>
                <div class="flex items-center gap-2 px-3     rounded-lg bg-[#E1EBFA]">
                    <p class="text-[#3F80EA] text-4xl">•</p>
                    <p class="font-semibold text-[#1E1E1E]">Total Cold Leads</p>
                </div>
                <p class="mt-auto text-2xl font-bold pt-3 text-black">{{ $leadCounts['cold'] }}</p>
            </div>
            <div>
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.21"
                        d="M44 0C52.8366 0 60 7.16344 60 16V44C60 52.8366 52.8366 60 44 60H16C7.16344 60 4.5101e-07 52.8366 0 44V16C0 7.16344 7.16344 4.5098e-07 16 0H44Z"
                        fill="#3F81EA" />
                    <path
                        d="M19.1111 40.8889H42.4444C43.3036 40.8889 44 41.5853 44 42.4444C44 43.3036 43.3036 44 42.4444 44H17.5556C16.6964 44 16 43.3036 16 42.4444V17.5556C16 16.6964 16.6964 16 17.5556 16C18.4147 16 19.1111 16.6964 19.1111 17.5556V40.8889Z"
                        fill="#3F81EA" />
                    <path opacity="0.6"
                        d="M24.9126 34.175C24.325 34.8017 23.3406 34.8335 22.7138 34.2459C22.0871 33.6583 22.0553 32.6739 22.6429 32.0472L28.4762 25.8249C29.0445 25.2188 29.9888 25.1662 30.6208 25.7056L35.2248 29.6343L41.2235 22.0361C41.7558 21.3618 42.734 21.2467 43.4083 21.779C44.0826 22.3114 44.1977 23.2895 43.6653 23.9638L36.6653 32.8305C36.1186 33.5231 35.1059 33.6227 34.4347 33.0499L29.7306 29.0358L24.9126 34.175Z"
                        fill="#3F81EA" />
                </svg>
            </div>
        </div>
        {{-- WARM CARDS --}}
        <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9]">
            <div>
                <div class="flex items-center gap-2 px-3 rounded-lg bg-[#FFF1C2]">
                    <p class="text-[#E5A000] text-4xl">•</p>
                    <p class="font-semibold text-[#1E1E1E]">Total Warm Leads</p>
                </div>
                <p class="mt-auto text-2xl font-bold pt-3 text-black">{{ $leadCounts['warm'] }}</p>
            </div>
            <div>
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.21"
                        d="M44 0C52.8366 0 60 7.16344 60 16V44C60 52.8366 52.8366 60 44 60H16C7.16344 60 4.5101e-07 52.8366 0 44V16C0 7.16344 7.16344 4.5098e-07 16 0H44Z"
                        fill="#E6A000" />
                    <path
                        d="M19.1111 40.8889H42.4444C43.3036 40.8889 44 41.5853 44 42.4444C44 43.3036 43.3036 44 42.4444 44H17.5556C16.6964 44 16 43.3036 16 42.4444V17.5556C16 16.6964 16.6964 16 17.5556 16C18.4147 16 19.1111 16.6964 19.1111 17.5556V40.8889Z"
                        fill="#E6A000" />
                    <path opacity="0.6"
                        d="M24.9126 34.175C24.325 34.8017 23.3406 34.8335 22.7138 34.2459C22.0871 33.6583 22.0553 32.6739 22.6429 32.0472L28.4762 25.8249C29.0445 25.2188 29.9888 25.1662 30.6208 25.7056L35.2248 29.6343L41.2235 22.0361C41.7558 21.3618 42.734 21.2467 43.4083 21.779C44.0826 22.3114 44.1977 23.2895 43.6653 23.9638L36.6653 32.8305C36.1186 33.5231 35.1059 33.6227 34.4347 33.0499L29.7306 29.0358L24.9126 34.175Z"
                        fill="#E6A000" />
                </svg>
            </div>
        </div>
        {{-- HOT CARDS --}}
        <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9]">
            <div>
                <div class="flex items-center gap-2 px-3 rounded-lg bg-[#FDD3D0]">
                    <p class="text-[#EC221F] text-4xl">•</p>
                    <p class="font-semibold text-[#1E1E1E]">Total Hot Leads</p>
                </div>
                <p class="mt-auto text-2xl font-bold pt-3 text-black">{{ $leadCounts['hot'] }}</p>
            </div>
            <div>
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.21"
                        d="M44 0C52.8366 0 60 7.16344 60 16V44C60 52.8366 52.8366 60 44 60H16C7.16344 60 4.5101e-07 52.8366 0 44V16C0 7.16344 7.16344 4.5098e-07 16 0H44Z"
                        fill="#EC221F" />
                    <path
                        d="M19.1111 40.8889H42.4444C43.3036 40.8889 44 41.5853 44 42.4444C44 43.3036 43.3036 44 42.4444 44H17.5556C16.6964 44 16 43.3036 16 42.4444V17.5556C16 16.6964 16.6964 16 17.5556 16C18.4147 16 19.1111 16.6964 19.1111 17.5556V40.8889Z"
                        fill="#EC221F" />
                    <path opacity="0.6"
                        d="M24.9131 34.175C24.3255 34.8017 23.3411 34.8335 22.7143 34.2459C22.0876 33.6583 22.0558 32.6739 22.6434 32.0472L28.4767 25.8249C29.045 25.2188 29.9893 25.1662 30.6213 25.7056L35.2253 29.6343L41.224 22.0361C41.7563 21.3618 42.7345 21.2467 43.4088 21.779C44.0831 22.3114 44.1982 23.2895 43.6658 23.9638L36.6658 32.8305C36.1191 33.5231 35.1063 33.6227 34.4351 33.0499L29.7311 29.0358L24.9131 34.175Z"
                        fill="#EC221F" />
                </svg>
            </div>
        </div>
        {{-- DEAL CARDS --}}
        <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9]">
            <div>
                <div class="flex items-center gap-2 px-3 rounded-lg bg-[#CFF7D3]">
                    <p class="text-[#14AE5C] text-4xl">•</p>
                    <p class="font-semibold text-[#1E1E1E]">Total Deal Leads</p>
                </div>
                <p class="mt-auto text-2xl font-bold pt-3 text-black">{{ $leadCounts['deal'] }}</p>
            </div>
            <div>
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.21"
                        d="M44 0C52.8366 0 60 7.16344 60 16V44C60 52.8366 52.8366 60 44 60H16C7.16344 60 4.5101e-07 52.8366 0 44V16C0 7.16344 7.16344 4.5098e-07 16 0H44Z"
                        fill="#14AE5C" />
                    <path
                        d="M19.1111 40.8889H42.4444C43.3036 40.8889 44 41.5853 44 42.4444C44 43.3036 43.3036 44 42.4444 44H17.5556C16.6964 44 16 43.3036 16 42.4444V17.5556C16 16.6964 16.6964 16 17.5556 16C18.4147 16 19.1111 16.6964 19.1111 17.5556V40.8889Z"
                        fill="#14AE5C" />
                    <path opacity="0.6"
                        d="M24.9131 34.175C24.3255 34.8017 23.3411 34.8335 22.7143 34.2459C22.0876 33.6583 22.0558 32.6739 22.6434 32.0472L28.4767 25.8249C29.045 25.2188 29.9893 25.1662 30.6213 25.7056L35.2253 29.6343L41.224 22.0361C41.7563 21.3618 42.7345 21.2467 43.4088 21.779C44.0831 22.3114 44.1982 23.2895 43.6658 23.9638L36.6658 32.8305C36.1191 33.5231 35.1063 33.6227 34.4351 33.0499L29.7311 29.0358L24.9131 34.175Z"
                        fill="#14AE5C" />
                </svg>
            </div>
        </div>
    </div>


    {{-- TABLES CONTENTS --}}
    <div class="mt-4 bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
        {{-- NAVIGATION TABLES --}}
        <div class="flex justify-between items-center border-b border-[#D9D9D9] p-3 gap-4">
            {{-- SEARCH TABLES --}}
            <div class="w-1/6 border border-gray-300 rounded-lg flex items-center p-2">
                <div class="px-2">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z"
                            fill="#6B7786" />
                    </svg>
                </div>
                <input id="searchInput" type="text" placeholder="Search"
                    class="w-full px-3 py-1 border-none focus:outline-[#115640] " />
            </div>
            {{-- NAVIGATION STATUS TABLES --}}
            <div class="w-4/6 border border-[#D5D5D5] rounded-lg grid grid-cols-5">
                @foreach (['all', 'cold', 'warm', 'hot', 'deal'] as $tab)
                {{-- NAVIGATION STATUS --}}
                <div data-tab="{{ $tab }}"
                    class="text-center cursor-pointer py-2 h-full border-r border-r-[#D5D5D5] nav-leads">
                    <p class="text-[#083224]">
                        {{ $loop->first ? 'All Stage' : ucfirst($tab) }}
                        <span class="{{ 
                                        $tab === 'all' 
                                            ? 'span-all' 
                                            : ($tab === 'cold' 
                                                ? 'span-cold' 
                                                : ($tab === 'warm' 
                                                    ? 'span-warm' 
                                                    : ($tab == 'hot'
                                                        ? 'span-hot'
                                                        : 'span-deal'
                                                        )
                                                )
                                            )
                                        }}">
                            {{ $loop->first ? '(' . $leadCounts['all'] . ')' : $leadCounts[$tab] }}
                        </span>
                    </p>
                </div>
                @endforeach
            </div>
            {{-- ADD MANUAL LEADS --}}
            <div class="w-1/6 bg-[#115640] rounded-lg">
                <a href="{{ route('leads.my.form') }}" class="flex justify-center items-center gap-3 px-3 py-2">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6 8H1C0.716667 8 0.479167 7.90417 0.2875 7.7125C0.0958333 7.52083 0 7.28333 0 7C0 6.71667 0.0958333 6.47917 0.2875 6.2875C0.479167 6.09583 0.716667 6 1 6H6V1C6 0.716667 6.09583 0.479167 6.2875 0.2875C6.47917 0.0958333 6.71667 0 7 0C7.28333 0 7.52083 0.0958333 7.7125 0.2875C7.90417 0.479167 8 0.716667 8 1V6H13C13.2833 6 13.5208 6.09583 13.7125 6.2875C13.9042 6.47917 14 6.71667 14 7C14 7.28333 13.9042 7.52083 13.7125 7.7125C13.5208 7.90417 13.2833 8 13 8H8V13C8 13.2833 7.90417 13.5208 7.7125 13.7125C7.52083 13.9042 7.28333 14 7 14C6.71667 14 6.47917 13.9042 6.2875 13.7125C6.09583 13.5208 6 13.2833 6 13V8Z"
                            fill="#E7F3EE" />
                    </svg>
                    <p class="text-white font-medium">Leads Manually</p>
                </a>
            </div>
        </div>

        {{-- CONTENTS TABLES --}}
        <div class="">
            {{-- ALL STAGE TABLE --}}
            <div data-tab-container="all" class="leads-table-container">
                <table id="allLeadsTableNew" class="w-full">
                    {{-- HEADER TABLE --}}
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#D9D9D9]">
                            <th class="hidden">ID (hidden)</th>
                            <th class="font-bold text-left p-3">Nama</th>
                            <th>Sales Name</th>
                            <th>Telephone</th>
                            <th>Source</th>
                            <th>Needs</th>
                            <th>Customer Type</th>
                            <th>City</th>
                            <th>Regional</th>
                            <th class="text-center w-12.5">stage</th>
                            <th class="text-center min-w-18.75">Action</th>
                        </tr>
                    </thead>
                    <tbody id="allBody"></tbody>
                </table>
                {{-- NAVIGATION ROWS --}}
                <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                    <div class="flex items-center gap-3">
                        <p class="font-semibold">Show Rows</p>
                        <select id="allPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                            onchange="changePageSize('all', this.value)">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <div id="allShowing" class="font-semibold">Showing 0-0 of 0</div>
                        <div>
                            <button id="allPrevBtn" class="btn bg-white border! border-[#D9D9D9]!"
                                onclick="goPrev('all')">
                                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                            </button>
                            <button id="allNextBtn" class="btn bg-white border! border-[#D9D9D9]!"
                                onclick="goNext('all')">
                                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- CONDITIONAL STAGE TABLE --}}
        @foreach(['cold', 'warm', 'hot', 'deal'] as $tab)
        <div data-tab-container="{{ $tab }}" class="leads-table-container">
            <table id="{{ $tab }}LeadsTableNew" class="w-full">
                {{-- HEADER TABLE --}}
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="hidden">ID (hidden)</th>
                        @if ($tab === 'cold')
                        <th class="font-bold text-left p-3">Nama</th>
                        <th>Sales Name</th>
                        <th>Telephone</th>
                        <th>Source</th>
                        <th>Needs</th>
                        <th>Customer Type</th>
                        <th>City</th>
                        <th>Regional</th>
                        <th class="text-left">Status</th>
                        @else
                        <th class="p-3">Claimed At</th>
                        <th>Lead Name</th>
                        <th>Industry</th>
                        <th class="text-left">Status</th>
                        @endif
                        <th class="text-center">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody id="{{ $tab }}Body"></tbody>
            </table>

            {{-- NAVIGATION ROWS --}}
            <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                <div class="flex items-center gap-3">
                    <p class="font-semibold">Show Rows</p>
                    <select id="{{ $tab }}PageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                        onchange="changePageSize('{{ $tab }}', this.value)">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div id="{{ $tab }}Showing" class="font-semibold">Showing 0-0 of 0</div>
                    <div>
                        <button id="{{ $tab }}PrevBtn"
                            class="btn btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                            onclick="goPrev('{{ $tab }}')">
                            <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                        </button>
                        <button id="{{ $tab }}NextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                            onclick="goNext('{{ $tab }}')">
                            <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</section>

<!-- Activity Logs Modal -->
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-[#083224] text-lg font-semibold">Activity Logs</h5>
                <button type="button" class="close cursor-pointer" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="border border-[#D9D9D9] rounded-lg mb-3">
                    <table class="w-full">
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="p-3">Date</th>
                                <th>Activity</th>
                                <th>Note</th>
                                <th>Attachment</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody class="text-[#1E1E1E]"></tbody>
                    </table>
                </div>
                <form id="activityLogForm">
                    <div class="w-full! grid! grid-cols-2! justify-between! gap-3! text-[#1E1E1E]!">
                        {{-- DATE AND ACTIVITY FIELD SELECT --}}
                        <div class="p-2 flex items-center border border-[#D9D9D9] justify-between rounded-lg gap-2">
                            <div class="w-1/4 flex items-center">
                                <input type="text" id="logged_at" name="logged_at"
                                    class="w-full text-sm rounded-lg outline-none" value="{{ date('Y-m-d') }}"
                                    placeholder="Select date" required>
                            </div>
                            <span class="w-px h-3/4 block bg-[#D9D9D9]"></span>
                            <div class="w-3/4 flex items-center">
                                <select name="activity_id" class="max-w-full! text-sm rounded-lg bg-white outline-none"
                                    required>
                                    <option value="" class="text-center">Activity Type</option>
                                    @foreach ($activities as $act)
                                    <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- NOTE FIELD / ATTACHMENT FILES AND SUBMIT BUTTON --}}
                        <div class="grid grid-cols-2">
                            <input type="text" name="note"
                                class="px-3 py-2 border border-[#D9D9D9] rounded-lg w-full focus:outline-none"
                                placeholder="Type Note Here...">
                            {{-- ATTACHMENT FILES AND SUBMIT BUTTON --}}
                            <div class="flex items-center justify-end gap-5 ">
                                <div class="">
                                    <input type="file" id="activity_attachment" name="attachment" class="hidden"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">

                                    <button type="button"
                                        onclick="document.getElementById('activity_attachment').click()"
                                        class="p-2 cursor-pointer">
                                        <svg width="20" height="20" viewBox="0 0 22 27" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M15.5786 22.0366C14.4231 24.0381 12.7373 25.3357 10.5213 25.9295C8.30529 26.5233 6.19655 26.2424 4.19507 25.0868C2.19359 23.9313 0.895959 22.2455 0.302185 20.0295C-0.291589 17.8135 -0.0106983 15.7048 1.14486 13.7033L7.31152 3.02232C8.14486 1.57895 9.35837 0.643746 10.9521 0.216716C12.5458 -0.210313 14.0643 -0.00716162 15.5077 0.826172C16.9511 1.6595 17.8863 2.87302 18.3133 4.46672C18.7403 6.06041 18.5372 7.57895 17.7038 9.02232L11.8705 19.126C11.3594 20.0112 10.6181 20.584 9.64673 20.8443C8.67534 21.1046 7.74701 20.9792 6.86174 20.468C5.97646 19.9569 5.40369 19.2157 5.1434 18.2443C4.88312 17.2729 5.00853 16.3446 5.51964 15.4593L11.0196 5.93301C11.2085 5.60584 11.4775 5.39549 11.8266 5.30195C12.1757 5.20841 12.5138 5.25608 12.841 5.44497C13.1682 5.63386 13.3785 5.90285 13.4721 6.25195C13.5656 6.60105 13.5179 6.93918 13.329 7.26634L7.82904 16.7926C7.6846 17.0428 7.64694 17.2969 7.71608 17.5549C7.78522 17.813 7.94488 18.0142 8.19507 18.1586C8.44525 18.3031 8.69936 18.3407 8.95739 18.2716C9.21541 18.2025 9.41665 18.0428 9.56109 17.7926L15.3944 7.68899C15.8418 6.86959 15.9524 6.0225 15.7262 5.14773C15.4999 4.27296 14.9826 3.60224 14.1743 3.13557C13.3661 2.66891 12.5217 2.55351 11.6414 2.7894C10.7611 3.02528 10.0876 3.54737 9.62092 4.35566L3.45426 15.0366C2.64612 16.3919 2.44818 17.8237 2.86043 19.3318C3.27268 20.84 4.16201 21.9886 5.5284 22.7774C6.87555 23.5552 8.29563 23.74 9.78864 23.3318C11.2816 22.9237 12.4418 22.0475 13.2692 20.7033L19.1025 10.5997C19.2914 10.2725 19.5604 10.0622 19.9095 9.96862C20.2586 9.87508 20.5967 9.92275 20.9239 10.1116C21.2511 10.3005 21.4614 10.5695 21.555 10.9186C21.6485 11.2677 21.6008 11.6058 21.4119 11.933L15.5786 22.0366Z"
                                                fill="#757575" />
                                        </svg>

                                    </button>
                                </div>
                                <div>
                                    <button type="submit"
                                        class="cursor-pointer bg-[#115640] px-5 py-2 text-white rounded-lg ">+
                                        Activity</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // LEADS
    const DEFAULT_PAGE_SIZE = 10;
    const pageState = { all: 1, cold: 1, warm: 1, hot: 1, deal: 1 };
    const pageSizeState = { all: DEFAULT_PAGE_SIZE, cold: DEFAULT_PAGE_SIZE, warm: DEFAULT_PAGE_SIZE, hot: DEFAULT_PAGE_SIZE, deal: DEFAULT_PAGE_SIZE };
    // local totals (initialize from server-rendered counts so UI shows values immediately)
    const totals = {
        all: {{ $leadCounts['all'] ?? 0 }},
        cold: {{ $leadCounts['cold'] ?? 0 }},
        warm: {{ $leadCounts['warm'] ?? 0 }},
        hot: {{ $leadCounts['hot'] ?? 0 }},
        deal: {{ $leadCounts['deal'] ?? 0 }}
    };

    // return trimmed search query value
    function getSearchQuery() {
        const el = document.getElementById('searchInput');
        return (el?.value || '').trim();
    }
    function updatePagerUI(tab, totalItems) {
        const pageSize = pageSizeState[tab] || DEFAULT_PAGE_SIZE;
        const totalPages = Math.max(1, Math.ceil((totalItems || 0) / pageSize));
        const page = pageState[tab] || 1;
        const prev = document.getElementById(tab + 'PrevBtn');
        const next = document.getElementById(tab + 'NextBtn');
        const showing = document.getElementById(tab + 'Showing');
        const info = document.getElementById(tab + 'PageInfo');
        if (prev) prev.disabled = page <= 1;
        if (next) next.disabled = page >= totalPages;
        const startIdx = totalItems === 0 ? 0 : (page - 1) * pageSize + 1;
        const endIdx = Math.min(totalItems, (page - 1) * pageSize + pageSize);
        if (showing) showing.innerText = `Showing ${startIdx}-${endIdx} of ${totalItems}`;
        if (info) info.innerText = page + ' / ' + totalPages;
    }

    function changePageSize(tab, value) {
        const size = parseInt(value, 10) || DEFAULT_PAGE_SIZE;
        pageSizeState[tab] = size;
        pageState[tab] = 1;
        reloadTab(tab);
    }

    function goPrev(tab) {
        if ((pageState[tab] || 1) > 1) {
            pageState[tab] = pageState[tab] - 1;
            reloadTab(tab);
        }
    }

    function goNext(tab) {
        const pageSize = pageSizeState[tab] || DEFAULT_PAGE_SIZE;
        const totalEl = document.getElementById(tab + 'Showing');
        // We rely on updatePagerUI to enable/disable next button correctly.
        pageState[tab] = (pageState[tab] || 1) + 1;
        reloadTab(tab);
    }

    function reloadTab(tab) {
        if (tab === 'all') loadAllLeads();
        if (tab === 'cold') loadColdLeads();
        if (tab === 'warm') loadWarmLeads();
        if (tab === 'hot') loadHotLeads();
        if (tab === 'deal') loadDealLeads();
    }

    async function loadColdLeads() {
        const page = pageState.cold || 1;

        const params = new URLSearchParams({
            page: page,
            start_date: document.getElementById('filter_start')?.value || '',
            end_date: document.getElementById('filter_end')?.value || '',
            search: getSearchQuery()
        });

        const response = await fetch(`{{ route('leads.my.cold.list') }}?${params.toString()}`, {
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            credentials: 'same-origin'
        });

        const result = await response.json();

        console.log('loadColdLeads result:', result);
        try { document.getElementById('leadsDebug').innerText = 'COLD → total:' + (result.total||0) + ' rows:' + (result.data?.length||0); } catch(e) {}

        const tbody = document.getElementById('coldBody');
        tbody.innerHTML = '';

        updatePagerUI('cold', result.total);

        // update local total and badges
        totals.cold = result.total || 0;
        updateBadgeCounts();

        result.data.forEach(row => {

            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9]">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.name}</td>
                    <td>${row.sales_name}</td>
                    <td>${row.phone}</td>
                    <td>${row.source}</td>
                    <td>${row.needs}</td>
                    <td>${row.industry}</td>
                    <td>${row.city_name}</td>
                    <td>${row.regional_name}</td>
                    <td class="text-left">${row.meeting_status}</td>
                    <td class="text-center">${row.actions}</td>
                </tr>
            `;
        });
}

    async function loadAllLeads() {
    const page = pageState.all || 1;
    const perPage = pageSizeState.all || DEFAULT_PAGE_SIZE;
    const response = await fetch(`/api/leads/my/all?page=${page}&per_page=${perPage}&search=${encodeURIComponent(getSearchQuery())}`, {
        credentials: 'same-origin'
    });

    const result = await response.json();

    console.log('loadAllLeads result:', result);
    try { document.getElementById('leadsDebug').innerText = 'ALL → total:' + (result.total||0) + ' rows:' + (result.data?.length||0); } catch(e) {}

    updatePagerUI('all', result.total);

    const tbody = document.getElementById('allBody');
    tbody.innerHTML = '';

    // update local total and badges
    totals.all = result.total || 0;
    updateBadgeCounts();

    result.data.forEach(row => {

        const industry =
            row.lead?.industry?.name ??
            row.lead?.other_industry ??
            '-';

        tbody.innerHTML += `
        <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
            <td class="hidden">${row.id}</td>
            <td class="p-3 font-medium">${row.lead?.name ?? ''}</td>
            <td>${row.sales?.name ?? '-'}</td>
            <td>${row.lead?.phone ?? ''}</td>
            <td>${row.lead?.source?.name ?? ''}</td>
            <td>${row.lead?.needs ?? ''}</td>
            <td>${industry}</td>
            <td>${row.lead?.region?.name ?? ''}</td>
            <td>${row.lead?.region?.regional?.name ?? ''}</td>
            <td class="text-center capitalize">
                <span class="block px-2 py-1 rounded-sm
                ${
                        row.lead?.status?.name === 'Cold' ? 'status-cold' :
                        row.lead?.status?.name === 'Warm' ? 'status-warm' :
                        row.lead?.status?.name === 'Hot' ? 'status-hot' :
                        row.lead?.status?.name === 'Deal' ? 'status-deal' :
                        ''
                }
                ">
                    ${row.lead?.status?.name ?? ''}
                </span>
            </td>
            <td class="text-center">
                ${row.actions ?? '-'}
            </td>
        </tr>
        `;
    });
}

    async function loadWarmLeads() {
        const page = pageState.warm || 1;

        const params = new URLSearchParams({
            page: page,
            start_date: document.getElementById('filter_start')?.value || '',
            end_date: document.getElementById('filter_end')?.value || '',
            search: getSearchQuery()
        });

        const response = await fetch(`{{ route('leads.my.warm.list') }}?${params.toString()}`, {
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            credentials: 'same-origin'
        });

        const result = await response.json();

        console.log('loadWarmLeads result:', result);
        try { document.getElementById('leadsDebug').innerText = 'WARM → total:' + (result.total||0) + ' rows:' + (result.data?.length||0); } catch(e) {}

        const tbody = document.getElementById('warmBody');
        tbody.innerHTML = '';

        updatePagerUI('warm', result.total);

        // update local total and badges
        totals.warm = result.total || 0;
        updateBadgeCounts();

        result.data.forEach(row => {

            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9]">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.claimed_at}</td>
                    <td>${row.lead_name}</td>
                    <td>${row.industry}</td>
                    <td class="text-left">${row.meeting_status}</td>
                    <td class="text-center">${row.actions}</td>
                </tr>
            `;
        });
}
    
    async function loadHotLeads() {
        const page = pageState.hot || 1;

        const params = new URLSearchParams({
            page: page,
            start_date: document.getElementById('filter_start')?.value || '',
            end_date: document.getElementById('filter_end')?.value || '',
            search: getSearchQuery()
        });

        const response = await fetch(`{{ route('leads.my.hot.list') }}?${params.toString()}`, {
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            credentials: 'same-origin'
        });

        const result = await response.json();

        console.log('loadHotLeads result:', result);
        try { document.getElementById('leadsDebug').innerText = 'HOT → total:' + (result.total||0) + ' rows:' + (result.data?.length||0); } catch(e) {}

        const tbody = document.getElementById('hotBody');
        tbody.innerHTML = '';

        updatePagerUI('hot', result.total);

        // update local total and badges
        totals.hot = result.total || 0;
        updateBadgeCounts();

        result.data.forEach(row => {

            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9]">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.claimed_at}</td>
                    <td>${row.lead_name}</td>
                    <td>${row.industry_name}</td>
                    <td class="text-left">${row.meeting_status}</td>
                    <td class="text-center">${row.actions}</td>
                </tr>
            `;
        });
    }

    async function loadDealLeads() {
        const page = pageState.deal || 1;

        const params = new URLSearchParams({
            page: page,
            start_date: document.getElementById('filter_start')?.value || '',
            end_date: document.getElementById('filter_end')?.value || '',
            search: getSearchQuery()
        });

        const response = await fetch(`{{ route('leads.my.deal.list') }}?${params.toString()}`, {
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            credentials: 'same-origin'
        });

        const result = await response.json();

        console.log('loadDealLeads result:', result);
        try { document.getElementById('leadsDebug').innerText = 'DEAL → total:' + (result.total||0) + ' rows:' + (result.data?.length||0); } catch(e) {}

        const tbody = document.getElementById('dealBody');
        tbody.innerHTML = '';

        updatePagerUI('deal', result.total);

        // update local total and badges
        totals.deal = result.total || 0;
        updateBadgeCounts();

        result.data.forEach(row => {

            tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9]">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.claimed_at}</td>
                    <td>${row.lead_name}</td>
                    <td>${row.industry_name}</td>
                    <td class="text-left">${row.meeting_status}</td>
                    <td class="text-center">${row.actions}</td>
                </tr>
            `;
        });
    }

    function initLeadTable(selector, route, type = 'default') {
            let columns;
            if (type === 'cold') {
                columns = [{
                        data: 'id',
                        visible: false
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'sales_name'
                    },
                    {
                        data: 'phone'
                    },
                    {
                        data: 'source',
                        name: 'source'
                    },
                    {
                        data: 'needs'
                    },
                    {
                        data: 'industry',
                        render: function(data, type, row) {
                            if (row.industry && row.industry.trim() !== '') {
                                return row.industry;
                            } else if (row.lead.other_industry && row.lead.other_industry.trim() !== '') {
                                return row.lead.other_industry;
                            } else {
                                return  'Belum Diisi';
                            }
                        }
                    },
                    {
                        data: 'city_name'
                    },
                    {
                        data: 'regional_name'
                    },
                    {
                        data: 'meeting_status',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ];
            } else {
                columns = [{
                        data: 'id',
                        visible: false
                    },
                    {
                        data: 'claimed_at',
                        name: 'claimed_at',
                        render: function(data) {
                            if (!data) return '';
                            return new Date(data).toLocaleString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                    },
                    {
                        data: 'lead_name',
                        name: 'lead_name'
                    },
                     {
                        data: 'industry',
                        render: function(data, type, row) {
                            if (row.industry && row.industry.trim() !== ''&& row.industry.trim() !== '-') {
                                return row.industry;
                            } else if (row.lead.other_industry && row.lead.other_industry.trim() !== ''&& row.industry.trim() !== '-') {
                                return row.lead.other_industry;
                            } else {
                                return  'Belum Diisi';
                            }
                        }
                    },
                    {
                        data: 'meeting_status',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ];
            }

            return $(selector).DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: route,
                    type: 'GET',
                    dataSrc: 'data',
                    data: function(d) {
                        // map DataTables params into API filter params
                        return {
                            start_date: $('#filter_start').val(),
                            end_date: $('#filter_end').val(),
                            search: $('#searchInput').val().trim()
                        };
                    },
                    xhrFields: {
                        withCredentials: true
                    }
                },
                columns: columns,
                order: [
                    [0, 'desc']
                ]
            });
        }

        $(function() {
            const coldTable = initLeadTable('#coldLeadsTable', '{{ route('leads.my.cold.list') }}', 'cold');
            const warmTable = initLeadTable('#warmLeadsTable', '{{ route('leads.my.warm.list') }}');
            const hotTable = initLeadTable('#hotLeadsTable', '{{ route('leads.my.hot.list') }}');
            const dealTable = initLeadTable('#dealLeadsTable', '{{ route('leads.my.deal.list') }}');

            // debounce helper
            function debounce(fn, wait) {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), wait);
                };
            }

            // debounced remote reload to avoid overwriting immediate DOM filter
            const remoteReloadDebounced = debounce(function() {
                try { coldTable.ajax.reload(); } catch(e) {}
                try { warmTable.ajax.reload(); } catch(e) {}
                try { hotTable.ajax.reload(); } catch(e) {}
                try { dealTable.ajax.reload(); } catch(e) {}

                // refresh server-backed custom loaders
                loadAllLeads();
                loadColdLeads();
                loadWarmLeads();
                loadHotLeads();
                loadDealLeads();

                updateBadgeCounts();
            }, 700);

            // called when search input changes
            function onSearchChanged() {
                const q = getSearchQuery();

                // reset pager
                pageState.all = pageState.cold = pageState.warm = pageState.hot = pageState.deal = 1;

                // For DataTables instances, use DataTables search (client-side)
                try { coldTable.search(q).draw(); } catch(e) {}
                try { warmTable.search(q).draw(); } catch(e) {}
                try { hotTable.search(q).draw(); } catch(e) {}
                try { dealTable.search(q).draw(); } catch(e) {}

                // Also apply DOM filtering to our custom "New" tables immediately
                applyDomFilterToCustomTable('allBody', q, 'all');
                applyDomFilterToCustomTable('coldBody', q, 'cold');
                applyDomFilterToCustomTable('warmBody', q, 'warm');
                applyDomFilterToCustomTable('hotBody', q, 'hot');
                applyDomFilterToCustomTable('dealBody', q, 'deal');

                // If there's a query, don't immediately trigger remote reload (avoid overwrite).
                // Only schedule remote reload when query is cleared.
                if (!q) {
                    remoteReloadDebounced();
                }
            }

            function applyDomFilterToCustomTable(tbodyId, query, tab) {
                const tbody = document.getElementById(tbodyId);
                if (!tbody) return;
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const q = (query || '').toLowerCase();
                let visible = 0;
                rows.forEach(r => {
                    if (!q) {
                        r.style.display = '';
                        visible++;
                        return;
                    }
                    const text = r.innerText.toLowerCase();
                    const show = text.indexOf(q) !== -1;
                    r.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                // update pager info for this tab to reflect filtered count
                const showingEl = document.getElementById(tab + 'Showing');
                if (showingEl) {
                    showingEl.innerText = q ? `Showing 1-${visible} of ${visible}` : `Showing 0-0 of ${totals[tab] || 0}`;
                }
            }

            const searchEl = document.getElementById('searchInput');
            if (searchEl) {
                searchEl.addEventListener('input', debounce(onSearchChanged, 350));
            }

            const notes = {
                warm: 'Filter tanggal berdasarkan Tanggal Approve pertama',
                hot: 'Filter tanggal berdasarkan Booking Fee',
                deal: 'Filter tanggal berdasarkan Termin Satu (Complete)'
            };

            const $toggleFilterBtn = $('#toggleFilterBtn');
            const $filterCollapse = $('#filterCollapse');

            window.updateBadgeCounts = function() {
                // Use local totals (keeps UI responsive and avoids extra server call)
                $('#cold-tab .badge').text(totals.cold || 0);
                $('#warm-tab .badge').text(totals.warm || 0);
                $('#hot-tab .badge').text(totals.hot || 0);
                $('#deal-tab .badge').text(totals.deal || 0);

                // update the summary nav counts
                $('.span-cold').text(totals.cold || 0);
                $('.span-warm').text(totals.warm || 0);
                $('.span-hot').text(totals.hot || 0);
                $('.span-deal').text(totals.deal || 0);
                $('.span-all').text('(' + (totals.all || 0) + ')');
            };

            function updateFilterVisibility() {
                const activeId = $('#leadTabs .nav-link.active').attr('id');
                if (activeId === 'cold-tab') {
                    $('#dateFilterRow').hide();
                    $('#filterNote').text('');
                    $filterCollapse.collapse('hide');
                    $toggleFilterBtn.hide();
                } else {
                    $('#dateFilterRow').show();
                    $toggleFilterBtn.show();
                    if (activeId === 'warm-tab') $('#filterNote').text(notes.warm);
                    if (activeId === 'hot-tab') $('#filterNote').text(notes.hot);
                    if (activeId === 'deal-tab') $('#filterNote').text(notes.deal);
                }
            }

            updateFilterVisibility();
            $('#leadTabs a[data-toggle="tab"]').on('shown.bs.tab', updateFilterVisibility);
            updateBadgeCounts();

            $('#btnFilter').on('click', function() {
                // reload both DataTables and our custom 'New' tables
                warmTable.ajax.reload();
                hotTable.ajax.reload();
                dealTable.ajax.reload();
                // reload custom loaders
                // reset pager to first page for all custom lists
                pageState.all = pageState.cold = pageState.warm = pageState.hot = pageState.deal = 1;
                loadAllLeads();
                loadColdLeads();
                loadWarmLeads();
                loadHotLeads();
                loadDealLeads();
                updateBadgeCounts();
            });

            const fileInput = document.getElementById('activity_attachment');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const name = e.target.files[0]?.name || 'Attachment';
                    e.target.nextElementSibling.innerText = name;
                });
            }

                function setActiveNav(tab) {
                    $('.nav-leads').removeClass('active-nav');
                    $('.nav-leads[data-tab="' + tab + '"]').addClass('active-nav');
                    
                    if (tab === 'all') {
                        $('.leads-table-container').hide();
                        $('.leads-table-container[data-tab-container="all"]').show();
                    } else {
                        $('.leads-table-container').hide();
                        $('.leads-table-container[data-tab-container="' + tab + '"]').show();
                    }

                    pageState[tab] = 1;
                    reloadTab(tab);
                }

            // init: default to 'all'
            setActiveNav('all');

            $('.nav-leads').on('click', function() {
                setActiveNav($(this).data('tab'));
            });
        });

        $(document).ready(function () {
            flatpickr("#logged_at", {
                dateFormat: "Y-m-d",
                defaultDate: "{{ date('Y-m-d') }}",
                allowInput: true,
                disableMobile: true
            });
        });

        // Cancel Meeting
        $(document).on('click', '.cancel-meeting', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const isOnline = $(this).data('online') === 1 || $(this).data('online') === '1';
            const isRejected = $(this).data('status') === 'rejected';
            const text = isOnline || isRejected ?
                'Are you sure you want to cancel this meeting?' :
                'Please return the expense to finance before cancelling. Have you returned it?';

            Swal.fire({
                title: 'Cancel Meeting',
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(url, {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    }, function(res) {
                        notif(res.message || 'Meeting canceled');
                        $('#coldLeadsTable').DataTable().ajax.reload();
                    }).fail(function(xhr) {
                        let err = 'Failed to cancel meeting';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            err = xhr.responseJSON.message;
                        }
                        notif(err, 'error');
                    });
                }
            });
        });

        // Activity Logs
        $(document).on('click', '.btn-activity-log', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const tbody = $('#activityLogModal tbody');
            $('#activityLogForm').data('url', url);
            tbody.html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
            $('#activityLogModal').modal('show');
            $.get(url, function(data) {
                let rows = '';
                data.forEach(function(item) {
                    rows += '<tr class="border-b border-b-[#D9D9D9]">' +
                        '<td class="p-3">' + item.logged_at + '</td>' +
                        '<td>' + item.code + ' - ' + item.activity + '</td>' +
                        '<td>' + (item.note || '') + '</td>' +
                        '<td>' + (item.attachment ? '<a href="' + item.attachment +
                            '" class="btn btn-sm btn-outline-secondary">Download</a>' : '-') +
                        '</td>' +
                        '<td>' + item.user + '</td>' +
                        '</tr>';
                });
                tbody.html(rows || '<tr><td colspan="5" class="text-center p-3">No logs</td></tr>');
            });
        });

        $('#activityLogForm').on('submit', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const formData = new FormData(this);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    notif(res.message || 'Saved');
                    $("#activityLogForm input[name='note']").val('');
                    $('#activity_attachment').val('');
                    $('#activity_attachment').next('.custom-file-label').text('Attachment');
                    $.get(url, function(data) {
                        let rows = '';
                        data.forEach(function(item) {
                            rows += '<tr class="border-b border-b-[#D9D9D9]">' +
                                '<td class="p-3">' + item.logged_at + '</td>' +
                                '<td>' + item.code + ' - ' + item.activity + '</td>' +
                                '<td>' + (item.note || '') + '</td>' +
                                '<td>' + (item.attachment ? '<a href="' + item
                                    .attachment +
                                    '" class="btn btn-sm btn-outline-secondary">Download</a>' :
                                    '-') + '</td>' +
                                '<td>' + item.user + '</td>' +
                                '</tr>';
                        });
                        $('#activityLogModal tbody').html(rows ||
                            '<tr><td colspan="5" class="text-center">No logs</td></tr>');
                    });
                },
                error: function(xhr) {
                    let err = 'Failed to save log';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        err = xhr.responseJSON.message;
                    }
                    notif(err, 'error');
                }
            });
        });

        // Quotation Logs
        $(document).on('click', '.btn-quotation-log', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const tbody = $('#quotationLogModal tbody');
            tbody.html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');
            $('#quotationLogModal').modal('show');
            $.get(url, function(data) {
                let rows = '';
                data.forEach(function(item) {
                    rows += '<tr>' +
                        '<td>' + item.logged_at + '</td>' +
                        '<td>' + item.action + '</td>' +
                        '<td>' + item.user + '</td>' +
                        '</tr>';
                });
                tbody.html(rows || '<tr><td colspan="3" class="text-center">No logs</td></tr>');
            });
        });

        // Trash Lead
        $(document).on('click', '.trash-lead', function(e) {
            e.preventDefault();
            const url = $(this).data('url');

            Swal.fire({
                title: 'Trash Lead',
                text: 'Provide a reason for trashing this lead',
                input: 'textarea',
                inputAttributes: {
                    required: true
                },
                inputPlaceholder: 'Enter reason here...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                preConfirm: (note) => {
                    if (!note) {
                        Swal.showValidationMessage('Note is required');
                    }
                    return note;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(url, {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        note: result.value
                    }, function(res) {
                        notif(res.message || 'Lead moved to trash');
                        $('#coldLeadsTable').DataTable().ajax.reload();
                        $('#warmLeadsTable').DataTable().ajax.reload();
                    }).fail(function(xhr) {
                        let err = 'Failed to trash lead';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            err = xhr.responseJSON.message;
                        }
                        notif(err, 'error');
                    });
                }
            });
        });
</script>
@endsection

@section('styles')
<style>
    .nav-tabs.full-clean {
        border-bottom: none;
        display: flex;
        width: 100%;
        justify-content: space-between;
    }

    .nav-tabs.full-clean .nav-item {
        flex: 1;
        text-align: center;
    }

    .nav-tabs.full-clean .nav-link {
        border: none;
        background: transparent;
        font-weight: 500;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        transition: background-color 0.3s ease;
    }

    .nav-tabs .nav-item.show .nav-link,
    .nav-tabs .nav-link.active {
        background-color: #115641 !important;
        color: white !important;
    }

    .nav-tabs.full-clean .nav-link .badge {
        margin-left: 0.4rem;
        font-size: 85%;
        vertical-align: middle;
    }

    /* Tailwind-style status nav active state */
    .nav-leads {
        border-bottom: 4px solid transparent;
    }

    .nav-leads.active-nav {
        border-bottom: 4px solid #115640;
        color: white;
    }

    .leads-table-container {
        display: block;
    }

    .bi-three-dots::before {
        -webkit-text-stroke: 0.6px;
    }
</style>
@endsection