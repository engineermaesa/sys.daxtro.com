@extends('layouts.app')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>My Leads tes</strong>
            <div class="d-flex gap-2">
                <a href="{{ route('leads.my.form') }}" class="btn btn-sm btn-primary mr-2">
                    <i class="bi bi-plus-circle me-1"></i> Add Manual Leads
                </a>
                <button id="toggleFilterBtn" class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse"
                    data-target="#filterCollapse">
                    <i class="bi bi-funnel-fill me-1"></i> Toggle Filters
                </button>
            </div>
        </div>
        <div class="collapse" id="filterCollapse">
            <div class="card-body pt-3 pb-0">
                <div id="filterNote" class="text-muted small mb-2"></div>
                <div class="row mb-3" id="dateFilterRow">
                    <div class="col-md-3">
                        <input type="date" id="filter_start" class="form-control form-control-sm"
                            placeholder="Start date" onfocus="this.showPicker()">
                    </div>
                    <div class="col-md-3">
                        <input type="date" id="filter_end" class="form-control form-control-sm" placeholder="End date"
                            onfocus="this.showPicker()">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFilter">
                            <i class="bi bi-search me-1"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-4">

            {{-- Custom Full-Width Tab Navigation --}}
            <ul class="nav nav-tabs mb-3 w-100 no-border" id="leadTabs" role="tablist">
                @foreach (['cold', 'warm', 'hot', 'deal'] as $tab)
                <li class="nav-item flex-fill text-center" style="border: none;">
                    <a class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $tab }}-tab" data-toggle="tab"
                        href="#{{ $tab }}" role="tab" style="border: none; font-weight: 500;">
                        {{ ucfirst($tab) }}
                        <span
                            class="badge badge-pill badge-
                                    {{ $tab === 'cold' ? 'primary' : ($tab === 'warm' ? 'warning' : ($tab === 'hot' ? 'danger' : 'success')) }}">
                            {{ $leadCounts[$tab] ?? 0 }}
                        </span>
                    </a>
                </li>
                @endforeach
            </ul>

            {{-- TABLES --}}
            <div class="tab-content" id="leadTabsContent">
                @foreach (['cold', 'warm', 'hot', 'deal'] as $tab)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tab }}" role="tabpanel"
                    aria-labelledby="{{ $tab }}-tab">
                    <div class="table-responsive">
                        <table id="{{ $tab }}LeadsTable" class="table table-sm w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID (hidden)</th>
                                    @if ($tab === 'cold')
                                    <th>Nama</th>
                                    <th>Sales Name</th>
                                    <th>Telephone</th>
                                    <th>Source</th>
                                    <th>Needs</th>
                                    <th>Industry</th>
                                    <th>City</th>
                                    <th>Regional</th>
                                    <th class="text-center">Status</th>
                                    @else
                                    <th>Claimed At</th>
                                    <th>Lead Name</th>
                                    <th>Industry</th>
                                    <th class="text-center">Status</th>
                                    @endif
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>
</section>

<!-- Activity Logs Modal -->
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Logs</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Note</th>
                                <th>Attachment</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <form id="activityLogForm">
                    <div class="form-row align-items-end">
                        <div class="col-md-3">
                            <select name="activity_id" class="form-control form-control-sm" required>
                                <option value="">-- Activity --</option>
                                @foreach ($activities as $act)
                                <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="logged_at" class="form-control form-control-sm"
                                value="{{ date('Y-m-d') }}" required onfocus="this.showPicker()">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Note">
                        </div>
                        <div class="col-md-3">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="activity_attachment" name="attachment"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <label class="custom-file-label" for="activity_attachment">Attachment</label>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-sm btn-primary">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
                    <p class="font-semibold text-[#1E1E1E]">Total Cold Leads</p>
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
                <input type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] " />
            </div>
            {{-- NAVIGATION STATUS TABLES --}}
            <div class="w-4/6 border border-[#D5D5D5] rounded-lg grid grid-cols-5">
                @foreach (['all', 'cold', 'warm', 'hot', 'deal'] as $tab)
                {{-- NAVIGATION STATUS --}}
                <div data-tab="{{ $tab }}"
                    class="text-center cursor-pointer py-2 h-full border-r border-r-[#D5D5D5] nav-leads-active">
                    <p class="text-[#083224]">
                        {{ $loop->first ? 'All Status' : ucfirst($tab) }}
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
            {{-- ALL combined table --}}
            <div data-tab-container="all" class="leads-table-container">
                <table id="allLeadsTableNew" class="w-full">
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#CFD5DC]">
                            <th class="hidden">ID (hidden)</th>
                            <th class="font-bold text-left p-3">Nama</th>
                            <th>Sales Name</th>
                            <th>Telephone</th>
                            <th>Source</th>
                            <th>Needs</th>
                            <th>Customer Type</th>
                            <th>City</th>
                            <th>Regional</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="allBody"></tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center my-2">
                    <div>
                        Show
                        <select id="allPageSizeSelect" class="form-select form-select-sm d-inline-block w-auto ms-2" onchange="changePageSize('all', this.value)">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        rows
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div id="allShowing">Showing 0-0 of 0</div>
                        <button id="allPrevBtn" class="btn btn-sm btn-outline-secondary" onclick="goPrev('all')">&lt;</button>
                        <button id="allNextBtn" class="btn btn-sm btn-outline-secondary" onclick="goNext('all')">&gt;</button>
                    </div>
                </div>
            </div>

            @foreach(['cold', 'warm', 'hot', 'deal'] as $tab)
            <div data-tab-container="{{ $tab }}" class="leads-table-container">
                <table id="{{ $tab }}LeadsTableNew" class="w-full">
                    {{-- HEADER TABLE --}}
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#CFD5DC]">
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
                            <th class="text-center">Status</th>
                            @else
                            <th class="p-3">Claimed At</th>
                            <th>Lead Name</th>
                            <th>Industry</th>
                            <th class="text-center">Status</th>
                            @endif
                            <th class="text-center">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody id="{{ $tab }}Body"></tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center my-2">
                    <div>
                        Show
                        <select id="{{ $tab }}PageSizeSelect" class="form-select form-select-sm d-inline-block w-auto ms-2" onchange="changePageSize('{{ $tab }}', this.value)">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        rows
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div id="{{ $tab }}Showing">Showing 0-0 of 0</div>
                        <button id="{{ $tab }}PrevBtn" class="btn btn-sm btn-outline-secondary" onclick="goPrev('{{ $tab }}')">&lt;</button>
                        <button id="{{ $tab }}NextBtn" class="btn btn-sm btn-outline-secondary" onclick="goNext('{{ $tab }}')">&gt;</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    // LEADS
        const DEFAULT_PAGE_SIZE = 10;
        const pageState = { all: 1, cold: 1, warm: 1, hot: 1, deal: 1 };
        const pageSizeState = { all: DEFAULT_PAGE_SIZE, cold: DEFAULT_PAGE_SIZE, warm: DEFAULT_PAGE_SIZE, hot: DEFAULT_PAGE_SIZE, deal: DEFAULT_PAGE_SIZE };

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

        const response = await fetch("{{ route('leads.my.cold.list') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                start_date: document.getElementById('filter_start')?.value,
                end_date: document.getElementById('filter_end')?.value,
                start: 0,
                length: 100000,
                draw: 1
            })
        });


        const result = await response.json();
        console.log('loadColdLeads result:', result);

        const tbody = document.getElementById('coldBody');

        tbody.innerHTML = '';

        const total = (result.data || []).length;
        updatePagerUI('cold', total);
        const start = (pageState.cold - 1) * (pageSizeState.cold || DEFAULT_PAGE_SIZE);
        const pageData = (result.data || []).slice(start, start + (pageSizeState.cold || DEFAULT_PAGE_SIZE));

        pageData.forEach(row => {

            let industry = 'Belum Diisi';

            if (row.industry?.trim()) {
                industry = row.industry;
            } else if (row.lead?.other_industry?.trim()) {
                industry = row.lead.other_industry;
            }

            tbody.innerHTML += `
                <tr class="border-b">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.name}</td>
                    <td>${row.sales_name}</td>
                    <td>${row.phone}</td>
                    <td>${row.source}</td>
                    <td>${row.needs}</td>
                    <td>${industry}</td>
                    <td>${row.city_name}</td>
                    <td>${row.regional_name}</td>
                    <td class="text-center">${row.meeting_status}</td>
                    <td class="text-center">${row.actions}</td>
                </tr>
            `;
        });
    }
        loadColdLeads();

        async function loadAllLeads() {
        const endpoints = [
            { url: "{{ route('leads.my.cold.list') }}", status: 'cold' },
            { url: "{{ route('leads.my.warm.list') }}", status: 'warm' },
            { url: "{{ route('leads.my.hot.list') }}", status: 'hot' },
            { url: "{{ route('leads.my.deal.list') }}", status: 'deal' }
        ];

        const token = '{{ csrf_token() }}';

        const fetches = endpoints.map(e =>
            fetch(e.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    start_date: document.getElementById('filter_start')?.value,
                    end_date: document.getElementById('filter_end')?.value,
                    start: 0,
                    length: 100000,
                    draw: 1
                })
            }).then(r => r.json()).then(json => ({ status: e.status, data: json.data || [] })).catch(() => ({ status: e.status, data: [] }))
        );

        const results = await Promise.all(fetches);

        // normalize and merge
        const merged = [];
        results.forEach(result => {
            result.data.forEach(row => {
                let industry = 'Belum Diisi';
                if (row.industry && row.industry.trim() !== '' && row.industry.trim() !== '-') {
                    industry = row.industry;
                } else if (row.lead && row.lead.other_industry && row.lead.other_industry.trim() !== '') {
                    industry = row.lead.other_industry;
                }

                merged.push({
                    id: row.id || 0,
                    name: row.name || row.lead_name || '',
                    sales_name: row.sales_name || '',
                    phone: row.phone || '',
                    source: row.source || row.source_name || '',
                    needs: row.needs || '',
                    customer_type: row.customer_type || '',
                    industry: industry,
                    city_name: row.city_name || '',
                    regional_name: row.regional_name || '',
                    status: result.status,
                    actions: row.actions || ''
                });
            });
        });

        // sort by id desc
        merged.sort((a, b) => (b.id || 0) - (a.id || 0));

        const total = merged.length;
        updatePagerUI('all', total);
        const start = (pageState.all - 1) * (pageSizeState.all || DEFAULT_PAGE_SIZE);
        const pageData = merged.slice(start, start + (pageSizeState.all || DEFAULT_PAGE_SIZE));

        const tbody = document.getElementById('allBody');
        tbody.innerHTML = '';

        pageData.forEach(row => {
            tbody.innerHTML += `
                <tr class="border-b">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.name}</td>
                    <td>${row.sales_name}</td>
                    <td>${row.phone}</td>
                    <td>${row.source}</td>
                    <td>${row.needs}</td>
                    <td>${row.customer_type || row.industry}</td>
                    <td>${row.city_name}</td>
                    <td>${row.regional_name}</td>
                    <td class="text-center">${row.status}</td>
                    <td class="text-center">${row.actions}</td>
                </tr>
            `;
        });
    }

        loadAllLeads();

        async function loadWarmLeads() {
        const response = await fetch("{{ route('leads.my.warm.list') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                start_date: document.getElementById('filter_start')?.value,
                end_date: document.getElementById('filter_end')?.value,
                start: 0,
                length: 100000,
                draw: 1
            })
        });

        const result = await response.json();

        const tbody = document.getElementById('warmBody');

        tbody.innerHTML = '';

        const total = (result.data || []).length;
        updatePagerUI('warm', total);
        const start = (pageState.warm - 1) * (pageSizeState.warm || DEFAULT_PAGE_SIZE);
        const pageData = (result.data || []).slice(start, start + (pageSizeState.warm || DEFAULT_PAGE_SIZE));

        pageData.forEach(row => {

            let industry = 'Belum Diisi';

            if (row.industry?.trim()) {
                industry = row.industry;
            } else if (row.lead?.other_industry?.trim()) {
                industry = row.lead.other_industry;
            }

            tbody.innerHTML += `
                <tr class="border-b">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.claimed_at}</td>
                    <td>${row.lead_name}</td>
                    <td>${industry}</td>
                    <td>${row.meeting_status}</td>
                    <td>${row.actions}</td>
                </tr>
            `;
        });
    }
        loadWarmLeads();
        
        async function loadHotLeads() {
        const response = await fetch("{{ route('leads.my.hot.list') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                start_date: document.getElementById('filter_start')?.value,
                end_date: document.getElementById('filter_end')?.value,
                start: 0,
                length: 100000,
                draw: 1
            })
        });

        const result = await response.json();

        const tbody = document.getElementById('hotBody');

        tbody.innerHTML = '';

        const total = (result.data || []).length;
        updatePagerUI('hot', total);
        const start = (pageState.hot - 1) * (pageSizeState.hot || DEFAULT_PAGE_SIZE);
        const pageData = (result.data || []).slice(start, start + (pageSizeState.hot || DEFAULT_PAGE_SIZE));

        pageData.forEach(row => {

            let industry = 'Belum Diisi';

            if (row.industry?.trim()) {
                industry = row.industry;
            } else if (row.lead?.other_industry?.trim()) {
                industry = row.lead.other_industry;
            }

            tbody.innerHTML += `
                <tr class="border-b">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.claimed_at}</td>
                    <td>${row.lead_name}</td>
                    <td>${industry}</td>
                    <td>${row.meeting_status}</td>
                    <td>${row.actions}</td>
                </tr>
            `;
        });
        }

        loadHotLeads();

        async function loadDealLeads() {
        const response = await fetch("{{ route('leads.my.deal.list') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                start_date: document.getElementById('filter_start')?.value,
                end_date: document.getElementById('filter_end')?.value,
                start: 0,
                length: 100000,
                draw: 1
            })
        });

        const result = await response.json();

        const tbody = document.getElementById('dealBody');

        tbody.innerHTML = '';

        const total = (result.data || []).length;
        updatePagerUI('deal', total);
        const start = (pageState.deal - 1) * (pageSizeState.deal || DEFAULT_PAGE_SIZE);
        const pageData = (result.data || []).slice(start, start + (pageSizeState.deal || DEFAULT_PAGE_SIZE));

        pageData.forEach(row => {

            let industry = 'Belum Diisi';

            if (row.industry?.trim()) {
                industry = row.industry;
            } else if (row.lead?.other_industry?.trim()) {
                industry = row.lead.other_industry;
            }

            tbody.innerHTML += `
                <tr class="border-b">
                    <td class="hidden">${row.id}</td>
                    <td class="p-3">${row.claimed_at}</td>
                    <td>${row.lead_name}</td>
                    <td>${industry}</td>
                    <td>${row.meeting_status}</td>
                    <td>${row.actions}</td>
                </tr>
            `;
        });
        }

        loadDealLeads();

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
                serverSide: true,
                ajax: {
                    url: route,
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        d.start_date = $('#filter_start').val();
                        d.end_date = $('#filter_end').val();
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

            const notes = {
                warm: 'Filter tanggal berdasarkan Tanggal Approve pertama',
                hot: 'Filter tanggal berdasarkan Booking Fee',
                deal: 'Filter tanggal berdasarkan Termin Satu (Complete)'
            };

            const $toggleFilterBtn = $('#toggleFilterBtn');
            const $filterCollapse = $('#filterCollapse');

            function updateBadgeCounts() {
                $.post('{{ route('leads.my.counts') }}', {
                    _token: '{{ csrf_token() }}',
                    start_date: $('#filter_start').val(),
                    end_date: $('#filter_end').val()
                }, function(data) {
                    $('#cold-tab .badge').text(data.cold);
                    $('#warm-tab .badge').text(data.warm);
                    $('#hot-tab .badge').text(data.hot);
                    $('#deal-tab .badge').text(data.deal);
                });
            }

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

            // Status nav (tailwind area) - show/hide table containers based on clicked status
            function setActiveNav(tab) {
                $('.nav-leads-active').removeClass('active-nav');
                $('.nav-leads-active[data-tab="' + tab + '"]').addClass('active-nav');

                if (tab === 'all') {
                    $('.leads-table-container').hide();
                    $('.leads-table-container[data-tab-container="all"]').show();
                } else {
                    $('.leads-table-container').hide();
                    $('.leads-table-container[data-tab-container="' + tab + '"]').show();
                }
            }

            // init: default to 'all'
            setActiveNav('all');

            $('.nav-leads-active').on('click', function() {
                const tab = $(this).data('tab');
                setActiveNav(tab);
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
                    rows += '<tr>' +
                        '<td>' + item.logged_at + '</td>' +
                        '<td>' + item.code + ' - ' + item.activity + '</td>' +
                        '<td>' + (item.note || '') + '</td>' +
                        '<td>' + (item.attachment ? '<a href="' + item.attachment +
                            '" class="btn btn-sm btn-outline-secondary">Download</a>' : '-') +
                        '</td>' +
                        '<td>' + item.user + '</td>' +
                        '</tr>';
                });
                tbody.html(rows || '<tr><td colspan="5" class="text-center">No logs</td></tr>');
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
                            rows += '<tr>' +
                                '<td>' + item.logged_at + '</td>' +
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
    .nav-leads-active.active-nav {
        background-color: #115640;
        color: #fff;
    }

    .leads-table-container {
        display: block;
    }
</style>
@endsection