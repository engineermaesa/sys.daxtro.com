@extends('layouts.app')

@section('content')

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
                                <input type="text" name="note" class="form-control form-control-sm"
                                    placeholder="Note">
                            </div>
                            <div class="col-md-3">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="activity_attachment"
                                        name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
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
            <p class="mt-1 text-[#115640] text-lg">All Leads</p>
        </div>

        {{-- ALL CARDS COUNTS --}}
        <div id="forAllCardsCounts" class="grid grid-cols-4 gap-3 mt-4">
            {{-- COLD CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0s;">
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
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.30s;">
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
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.45s;">
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
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.6s;">
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

        {{-- COLD CARDS COUNTS --}}
        <div id="forColdCardsCounts" class="hidden grid grid-cols-4 gap-3 mt-4">
            {{-- LEADS COLD COUNTS CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0s;">
                <div>
                    <div class="flex items-center gap-2 px-3 rounded-lg bg-[#E1EBFA]">
                        <p class="text-[#3F80EA] text-4xl">•</p>
                        <p class="font-semibold text-[#1E1E1E]">Total Cold Leads</p>
                    </div>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-cold-total">0</span>
                        <span id="summary-cold-raw" class="text-sm text-[#757575] font-semibold">
                            0
                        </span>
                    </p>
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
            {{-- INITIATION CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.30s;">
                <div>
                    <p class="font-semibold text-[#1E1E1E]">Initiations</p>
                    <p id="summary-initiation-count" class="mt-auto text-2xl font-bold pt-3 text-black">
                        0
                    </p>
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
            {{-- PENDING APPROVAL CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.45s;">
                <div>
                    <p class="font-semibold text-[#1E1E1E]">Pending Approval</p>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-total-pending">0</span>
                        <span class="text-sm text-[#757575] font-semibold">
                            <span id="summary-pending-count">0</span>
                            <span id="summary-rejected-count">0</span>
                        </span>
                    </p>
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
            {{-- MEETING SCHEDULE CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.6s;">
                <div>
                    <p class="font-semibold text-[#1E1E1E]">Meeting Scheduled</p>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-total-meeting">0</span>
                        <span class="text-sm text-[#757575] font-semibold">
                            <span id="summary-meeting-online">0</span>
                            <span id="summary-meeting-offline">0</span>
                        </span>
                    </p>
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
        </div>

        {{-- WARM CARDS COUNTS --}}
        <div id="forWarmCardsCounts" class="hidden grid grid-cols-3 gap-3 mt-4">
            {{-- LEADS WARM COUNTS CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.00s;">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 rounded-lg bg-[#FFF1C2]">
                        <p class="text-[#E5A000] text-4xl">•</p>
                        <p class="font-semibold text-[#1E1E1E]">Total Warm Leads</p>
                    </div>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-warm-total">0</span>
                        <span class="text-sm text-[#757575] font-semibold">
                            <span id="summary-warm-no-quotation">0</span>
                        </span>
                    </p>
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
            {{-- PENDING APPROVAL CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.30s;">
                <div>
                    <p class="font-semibold text-[#1E1E1E]">Pending Approval</p>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-warm-total-pending">0</span>
                        <span class="text-sm text-[#757575] font-semibold">
                            <span id="summary-warm-pending-count">0</span>
                            <span id="summary-warm-rejected-count">0</span>
                        </span>
                    </p>
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
            {{-- QUOTATIONS PUBLISHED CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.6s;">
                <div>
                    <p class="font-semibold text-[#1E1E1E]">Quotations Published</p>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-warm-quotations">0</span>
                    </p>
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
        </div>

        {{-- HOT CARDS COUNTS --}}
        <div id="forHotCardsCounts" class="hidden grid grid-cols-3 gap-3 mt-4">
            {{-- LEADS HOT COUNTS CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.00s;">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 rounded-lg bg-[#FDD3D0]">
                        <p class="text-[#EC221F] text-4xl">•</p>
                        <p class="font-semibold text-[#1E1E1E]">Total Hot Leads (Committed)</p>
                    </div>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-hot-total">0</span>
                    </p>
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
            {{-- EXPIRING IN 7 DAYS CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.30s;">
                <div>
                    <p class="font-semibold text-[#1E1E1E]">Expiring in 7 Days</p>
                    <p class="mt-auto text-2xl font-bold pt-3 text-[#1E1E1E]">
                        <span id="summary-hot-expire-7-days">0</span>
                    </p>
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
            {{-- EXPIRING IN 8+ DAYS CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.6s;">
                <div>
                    <p class="font-semibold text-[#1E1E1E]">Expiring in 8+ Days</p>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-hot-expire-8-days-more">0</span>
                    </p>
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
        </div>

        {{-- DEAL CARDS COUNTS --}}
        <div id="forDealCardsCounts" class="hidden grid grid-cols-1 gap-3 mt-4">
            {{-- LEADS DEAL COUNTS CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.15s;">
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
        <div class="mt-4 rounded-lg border-[#D9D9D9]">
            {{-- NAVIGATION TABLES --}}
            <div
                class="bg-white flex justify-between items-center border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg">
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
                {{-- EXPORT EXCELS LEADS --}}
                <button id="btnExport" class="cursor-pointer bg-[#115640] rounded-lg w-1/6">
                    <div class="w-full flex items-center justify-center text-center gap-3 px-3 py-2 text-white">
                        <x-icon.download/>
                        Export Excel
                    </div>
                </button>
            </div>

            {{-- CONTENTS TABLES --}}
            <div>
                {{-- ALL STAGE TABLE --}}
                <div data-tab-container="all" class="leads-table-container">
                    <table id="allLeadsTableNew" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                        {{-- HEADER TABLE --}}
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="hidden">ID (hidden)</th>
                                <th class="font-bold text-left p-2">Lead Name</th>
                                <th class="p-2">Sales Name</th>
                                <th class="p-2">Telephone</th>
                                <th class="p-2">Source</th>
                                <th class="p-2">Needs</th>
                                <th class="p-2">Industry</th>
                                <th class="p-2">City</th>
                                <th class="p-2">Regional</th>
                                <th class="p-2">Customer Type</th>
                                <th class="p-2">ACT Last Time</th>
                                <th class="p-2">ACT Status</th>
                                <th class="text-center w-12.5 p-2">Stage</th>
                                <th class="text-center p-2">Action</th>
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
                <table id="{{ $tab }}LeadsTableNew" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                    {{-- HEADER TABLE --}}
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#D9D9D9]">
                            <th class="font-bold text-left p-2">Lead Name</th>
                            <th class="p-2">Sales Name</th>
                            <th class="p-2">Telephone</th>
                            <th class="p-2">Source</th>
                            <th class="p-2">Needs</th>
                            <th class="p-2">Industry</th>
                            <th class="p-2">City</th>
                            <th class="p-2">Regional</th>
                            <th class="p-2">Customer Type</th>
                            <th class="p-2">Quotation Number</th>
                            <th class="p-2">Quotation Price</th>
                            @if ($tab === 'hot' || $tab === 'deal')
                                <th class="p-2">Invoice</th>
                                <th class="p-2">Invoice Price</th>
                            @endif
                            <th class="p-2">Quotation Created</th>
                            <th class="p-2">Quotation End Date</th>
                            <th class="p-2">ACT Last Time</th>
                            <th class="p-2">ACT Status</th>
                            <th class="text-center p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="{{ $tab }}Body" class="text-[#1E1E1E]"></tbody>
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
@endsection

@section('scripts')
    <script>
        // CONTENTS TABLE AND STAGE TABLE SECTION
        const DEFAULT_PAGE_SIZE = 10;
        const pageState = { all: 1, cold: 1, warm: 1, hot: 1, deal: 1 };
        const pageSizeState = { all: DEFAULT_PAGE_SIZE, cold: DEFAULT_PAGE_SIZE, warm: DEFAULT_PAGE_SIZE, hot: DEFAULT_PAGE_SIZE, deal: DEFAULT_PAGE_SIZE };

        const totals = {
            all: {{ $leadCounts['all'] ?? 0 }},
            cold: {{ $leadCounts['cold'] ?? 0 }},
            warm: {{ $leadCounts['warm'] ?? 0 }},
            hot: {{ $leadCounts['hot'] ?? 0 }},
            deal: {{ $leadCounts['deal'] ?? 0 }}
        };

        $(document).ready(function () {
            const sections = {
                all: $("#forAllCardsCounts"),
                cold: $("#forColdCardsCounts"),
                warm: $("#forWarmCardsCounts"),
                hot: $("#forHotCardsCounts"),
                deal: $("#forDealCardsCounts"),
            };

            function resetAnimation($container) {
                $container.find(".animate__animated").each(function () {
                    $(this)
                        .removeClass("animate__fadeInUp")
                        .width(); // trigger reflow
                    $(this).addClass("animate__fadeInUp");
                });
            }


            $(".nav-leads").on("click", function () {
                const tab = $(this).data("tab");
                console.log(tab)
                $(".leads-table-container").hide();

                $(`[data-tab-container='${tab}']`).show();

                $(".nav-leads").removeClass("active-nav");
                $(this).addClass("active-nav");

                reloadTab(tab);

                $.each(sections, function (key, value) {
                    value.addClass("hidden");
                });


                sections[tab].removeClass("hidden");

                // Reset animation
                resetAnimation(sections[tab]);

            });

            $(".nav-leads[data-tab='all']").trigger("click");

        });

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
            pageState[tab] = (pageState[tab] || 1) + 1;
            reloadTab(tab);
        }

        function reloadTab(tab) {
            if (tab === 'all') loadAllLeads();
            else if (tab === 'cold') loadColdLeads();
            else if (tab === 'warm') loadWarmLeads();
            else if (tab === 'hot') loadHotLeads();
            else if (tab === 'deal') loadDealLeads();
        }

        // LOAD THE MAIN DATA TO THE TABLE (DATA-CONTAINER)
        async function loadAllLeads() {
            const page = pageState.all || 1;
            const perPage = pageSizeState.all || DEFAULT_PAGE_SIZE;

            const response = await fetch(`/api/leads/manage/list?page=${page}&per_page=${perPage}&search=${encodeURIComponent(getSearchQuery())}`, {
                credentials: 'same-origin'
            });
            const result = await response.json();

            updatePagerUI('all', result.total);
            const tbody = document.getElementById('allBody');
            tbody.innerHTML = '';
            totals.all = result.total || 0;

            result.data.forEach(row => {
                tbody.innerHTML += `
                <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]! font-medium!">
                    <td class="hidden">${row.id}</td>
                    <td class="p-2 font-medium">${row.lead_name ?? ''}</td>
                    <td class="p-2">${row.sales_name ?? '-'}</td>
                    <td class="p-2">${row.phone ?? ''}</td>
                    <td class="p-2">${row.source_name ?? ''}</td>
                    <td class="p-2">${row.needs ?? ''}</td>
                    <td class="p-2">${row.existing_industries ?? ''}</td>
                    <td class="p-2">${row.city_name ?? ''}</td>
                    <td class="p-2">${row.regional_name ?? ''}</td>
                    <td class="p-2">${row.customer_type ?? ''}</td>
                    <td class="p-2">${row.act_last_time ?? ''}</td>
                    <td class="p-2">${row.act_status ?? ''}</td>
                    <td class="text-center capitalize p-2">
                        <span class="block px-2 py-1 rounded-sm flex items-center justify-center 
                            ${
                                    row.status_name === 'Published' ? 'status-trash' :
                                    row.status_name === 'Cold' ? 'status-cold' :
                                    row.status_name === 'Warm' ? 'status-warm' :
                                    row.status_name === 'Hot' ? 'status-hot' :
                                    row.status_name === 'Deal' ? 'status-deal' :
                                    row.status_name === 'Trash Cold' ? 'status-trash' :
                                    row.status_name === 'Trash Warm' ? 'status-trash' :
                                    row.status_name === 'Trash Hot' ? 'status-trash' :
                                    ''
                            }">
                                ${row.status_name ?? ''}
                                <span class="
                                ${
                                    row.status_name === 'Trash Cold' ? 'dot-trash-cold' :
                                    row.status_name === 'Trash Warm' ? 'dot-trash-warm' :
                                    row.status_name === 'Trash Hot' ? 'dot-trash-hot' :
                                    ''
                                }"></span>
                        </span>
                    </td>
                    <td class="text-center p-2">
                        ${row.actions ?? '-'}
                    </td>
                </tr>
            `;
            });
        }
        
        async function loadColdLeads() {
            const page = pageState.cold || 1;
            const perPage = pageSizeState.cold || DEFAULT_PAGE_SIZE;

            const response = await fetch(`/api/leads/manage/list?page=${page}&per_page=${perPage}&stage=cold&search=${encodeURIComponent(getSearchQuery())}`, { 
                credentials: 'same-origin' 
            });
            const result = await response.json();

            const tbody = document.getElementById('coldBody');
            tbody.innerHTML = '';
            updatePagerUI('cold', result.total);
            totals.cold = result.total || 0;

            result.data.forEach(row => {
                tbody.innerHTML += `
                    <tr class="border-b border-b-[#D9D9D9]">
                        <td class="hidden">${row.id}</td>
                        <td class="p-2">${row.lead_name ?? ''}</td>
                        <td class="p-2">${row.sales_name ?? ''}</td>
                        <td class="p-2">${row.phone ?? ''}</td>
                        <td class="p-2">${row.source_name ?? ''}</td>
                        <td class="p-2">${row.needs ?? ''}</td>
                        <td class="p-2">${row.existing_industries ?? ''}</td>
                        <td class="p-2">${row.city_name ?? ''}</td>
                        <td class="p-2">${row.regional_name ?? ''}</td>
                        <td class="p-2">${row.customer_type ?? ''}</td>
                        <td class="p-2">${row.quotation_number ?? ''}</td>
                        <td class="p-2">${row.quotation_price ?? ''}</td>
                        <td class="p-2">${row.quot_created ?? ''}</td>
                        <td class="p-2">${row.quot_end_date ?? ''}</td>
                        <td class="p-2">${row.act_last_time ?? ''}</td>
                        <td class="p-2">${row.act_status ?? ''}</td>
                        <td class="p-2 text-center">${row.actions ?? ''}</td>
                    </tr>
                `;
            });
        }

        async function loadWarmLeads() {
            const page = pageState.warm || 1;
            const perPage = pageSizeState.warm || DEFAULT_PAGE_SIZE;

            const response = await fetch(`/api/leads/manage/list?page=${page}&per_page=${perPage}&stage=warm&search=${encodeURIComponent(getSearchQuery())}`, { 
                credentials: 'same-origin' 
            });
            const result = await response.json();

            const tbody = document.getElementById('warmBody');
            tbody.innerHTML = '';
            updatePagerUI('warm', result.total);
            totals.warm = result.total || 0;

            result.data.forEach(row => {
                tbody.innerHTML += `
                    <tr class="border-b border-b-[#D9D9D9]">
                        <td class="hidden">${row.id}</td>
                        <td class="p-2">${row.lead_name ?? ''}</td>
                        <td class="p-2">${row.sales_name ?? ''}</td>
                        <td class="p-2">${row.phone ?? ''}</td>
                        <td class="p-2">${row.source_name ?? ''}</td>
                        <td class="p-2">${row.needs ?? ''}</td>
                        <td class="p-2">${row.existing_industries ?? ''}</td>
                        <td class="p-2">${row.city_name ?? ''}</td>
                        <td class="p-2">${row.regional_name ?? ''}</td>
                        <td class="p-2">${row.customer_type ?? ''}</td>
                        <td class="p-2">${row.quotation_number ?? ''}</td>
                        <td class="p-2">${row.quotation_price ?? ''}</td>
                        <td class="p-2">${row.quot_created ?? ''}</td>
                        <td class="p-2">${row.quot_end_date ?? ''}</td>
                        <td class="p-2">${row.act_last_time ?? ''}</td>
                        <td class="p-2">${row.act_status ?? ''}</td>
                        <td class="p-2 text-center">${row.actions ?? ''}</td>
                    </tr>
                `;
            });
        }
        
        async function loadHotLeads() {
            const page = pageState.hot || 1;
            const perPage = pageSizeState.hot || DEFAULT_PAGE_SIZE;

            const response = await fetch(`/api/leads/manage/list?page=${page}&per_page=${perPage}&stage=hot&search=${encodeURIComponent(getSearchQuery())}`, { 
                credentials: 'same-origin' 
            });
            const result = await response.json();

            const tbody = document.getElementById('hotBody');
            tbody.innerHTML = '';
            updatePagerUI('hot', result.total);
            totals.hot = result.total || 0;

            result.data.forEach(row => {
                tbody.innerHTML += `
                    <tr class="border-b border-b-[#D9D9D9]">
                        <td class="hidden">${row.id}</td>
                        <td class="p-2">${row.lead_name ?? ''}</td>
                        <td class="p-2">${row.sales_name ?? ''}</td>
                        <td class="p-2">${row.phone ?? ''}</td>
                        <td class="p-2">${row.source_name ?? ''}</td>
                        <td class="p-2">${row.needs ?? ''}</td>
                        <td class="p-2">${row.existing_industries ?? ''}</td>
                        <td class="p-2">${row.city_name ?? ''}</td>
                        <td class="p-2">${row.regional_name ?? ''}</td>
                        <td class="p-2">${row.customer_type ?? ''}</td>
                        <td class="p-2">${row.quotation_number ?? ''}</td>
                        <td class="p-2">${row.quotation_price ?? ''}</td>
                        <td class="p-2">${row.invoice_number ?? ''}</td>
                        <td class="p-2">${row.invoice_price ?? ''}</td>
                        <td class="p-2">${row.quot_created ?? ''}</td>
                        <td class="p-2">${row.quot_end_date ?? ''}</td>
                        <td class="p-2">${row.act_last_time ?? ''}</td>
                        <td class="p-2">${row.act_status ?? ''}</td>
                        <td class="p-2 text-center">${row.actions ?? ''}</td>
                    </tr>
                `;
            });
        }

        async function loadDealLeads() {
            const page = pageState.deal || 1;
            const perPage = pageSizeState.deal || DEFAULT_PAGE_SIZE;

            const response = await fetch(`/api/leads/manage/list?page=${page}&per_page=${perPage}&stage=deal&search=${encodeURIComponent(getSearchQuery())}`, { 
                credentials: 'same-origin' 
            });
            const result = await response.json();

            const tbody = document.getElementById('dealBody');
            tbody.innerHTML = '';
            updatePagerUI('deal', result.total);
            totals.deal = result.total || 0;

            result.data.forEach(row => {
                tbody.innerHTML += `
                    <tr class="border-b border-b-[#D9D9D9]">
                        <td class="hidden">${row.id}</td>
                        <td class="p-2">${row.lead_name ?? ''}</td>
                        <td class="p-2">${row.sales_name ?? ''}</td>
                        <td class="p-2">${row.phone ?? ''}</td>
                        <td class="p-2">${row.source_name ?? ''}</td>
                        <td class="p-2">${row.needs ?? ''}</td>
                        <td class="p-2">${row.existing_industries ?? ''}</td>
                        <td class="p-2">${row.city_name ?? ''}</td>
                        <td class="p-2">${row.regional_name ?? ''}</td>
                        <td class="p-2">${row.customer_type ?? ''}</td>
                        <td class="p-2">${row.quotation_number ?? ''}</td>
                        <td class="p-2">${row.quotation_price ?? ''}</td>
                        <td class="p-2">${row.invoice_number ?? ''}</td>
                        <td class="p-2">${row.invoice_price ?? ''}</td>
                        <td class="p-2">${row.quot_created ?? ''}</td>
                        <td class="p-2">${row.quot_end_date ?? ''}</td>
                        <td class="p-2">${row.act_last_time ?? ''}</td>
                        <td class="p-2">${row.act_status ?? ''}</td>
                        <td class="p-2 text-center">${row.actions ?? ''}</td>
                    </tr>
                `;
            });
        }
        
        // LOAD THE SUMMARY ON CARDS (FORCardsCounts)
        $(document).ready(function () {
            loadColdSummary();
            loadWarmSummary();
            loadHotSummary();
        });

        function loadColdSummary(){
            $.ajax({
                url: "/api/leads/my/summary",
                type: "GET",
                dataType: "json",
                success: function (response) {
                    renderColdSummary(response.cold);
                },
                error: function (xhr) {
                    console.log("There are error when fetch summary:", xhr.responseText);
                }
            });
        }

        function renderColdSummary(cold) {
            $("#summary-cold-total").text(cold.total);
            $("#summary-cold-raw").text(`( ${cold.raw} Raw Leads )`);
            $("#summary-initiation-count").text(cold.initiation);
            $("#summary-total-pending").text(cold.approval_status);
            $("#summary-pending-count").text(`( ${cold.pending} Pending & `);
            $("#summary-rejected-count").text(`${cold.rejected} Rejected )`);
            $("#summary-total-meeting").text(cold.meeting_scheduled);
            $("#summary-meeting-online").text(`( ${cold.meet_online} Online & `);
            $("#summary-meeting-offline").text(`${cold.meet_offline} Offline )`);
        }

        function loadWarmSummary(){
            $.ajax({
                url: "/api/leads/my/summary",
                type: "GET",
                dataType: "json",
                success: function (response) {
                    renderWarmSummary(response.warm);
                },
                error: function (xhr) {
                    console.log("There are error when fetch summary:", xhr.responseText);
                }
            });
        }

        function renderWarmSummary(warm) {
            $("#summary-warm-total").text(warm.total);
            $("#summary-warm-no-quotation").text(`( ${warm.no_quotation} No Quotation )`);
            $("#summary-warm-total-pending").text(warm.approval_status);
            $("#summary-warm-pending-count").text(`( ${warm.pending} Pending & `);
            $("#summary-warm-rejected-count").text(`${warm.rejected} Rejected )`);
            $("#summary-warm-quotations").text(`${warm.quotation_published}`);
        }
        
        function loadHotSummary(){
            $.ajax({
                url: "/api/leads/my/summary",
                type: "GET",
                dataType: "json",
                success: function (response) {
                    renderHotSummary(response.hot);
                },
                error: function (xhr) {
                    console.log("There are error when fetch summary:", xhr.responseText);
                }
            });
        }

        function renderHotSummary(hot) {
            $("#summary-hot-total").text(hot.total);
            $("#summary-hot-expire-7-days").text(hot.expiring_7_days);
            $("#summary-hot-expire-8-days-more").text(hot.expiring_8_plus_days);
        }

        // SEARCH SECTION BY SEARCHINPUT
        function getSearchQuery() {
            const el = document.getElementById('searchInput');
            return (el?.value || '').trim();
        }

        function renderTableData(leads) {
            const tbody = document.getElementById('allBody');
            tbody.innerHTML = '';

            if (!leads || leads.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="14" class="text-center p-4 text-gray-500">
                            Data tidak ditemukan
                        </td>
                    </tr>
                `;
                return;
            }

            let rowsHtml = '';
            leads.forEach(lead => {
                rowsHtml += `
                    <tr class="border-b border-b-[#D9D9D9] hover:bg-gray-50 text-sm text-[#1E1E1E]">
                        <td class="hidden">${lead.id || ''}</td>
                        <td class="text-left p-2">${lead.lead_name || '-'}</td>
                        <td class="p-2">${lead.sales_name || '-'}</td>
                        <td class="p-2">${lead.phone || '-'}</td>
                        <td class="p-2">${lead.source_name || '-'}</td>
                        <td class="p-2">${lead.needs || '-'}</td>
                        <td class="p-2">${lead.existing_industries || '-'}</td>
                        <td class="p-2">${lead.city_name || '-'}</td>
                        <td class="p-2">${lead.regional_name || '-'}</td>
                        <td class="p-2">${lead.customer_type || '-'}</td>
                        <td class="p-2">${lead.act_last_time || '-'}</td>
                        <td class="p-2">${lead.act_status || '-'}</td>
                        <td class="p-2">
                            <span class="block px-2 py-1 rounded-sm flex items-center justify-center 
                            ${
                                    lead.status_name === 'Published' ? 'status-trash' :
                                    lead.status_name === 'Cold' ? 'status-cold' :
                                    lead.status_name === 'Warm' ? 'status-warm' :
                                    lead.status_name === 'Hot' ? 'status-hot' :
                                    lead.status_name === 'Deal' ? 'status-deal' :
                                    lead.status_name === 'Trash Cold' ? 'status-trash' :
                                    lead.status_name === 'Trash Warm' ? 'status-trash' :
                                    lead.status_name === 'Trash Hot' ? 'status-trash' :
                                    ''
                            }">
                                ${lead.status_name ?? ''}
                                <span class="
                                ${
                                    lead.status_name === 'Trash Cold' ? 'dot-trash-cold' :
                                    lead.status_name === 'Trash Warm' ? 'dot-trash-warm' :
                                    lead.status_name === 'Trash Hot' ? 'dot-trash-hot' :
                                    ''
                                }"></span>
                            </span>
                        </td>
                        <td class="p-2">
                            ${lead.actions}
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = rowsHtml;
        }

        async function fetchSearchResults(query) {
            const apiUrl = `https://sys-daxtro-com-main.test/api/leads/manage/list?search=${encodeURIComponent(query)}`;

            try {
                const tbody = document.getElementById('allBody');
                tbody.innerHTML = '<tr><td colspan="14" class="text-center p-4">Mencari data...</td></tr>';

                const response = await fetch(apiUrl, {
                    method: 'GET', 
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });

                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                const result = await response.json();
                
                const leadsData = result.data ? result.data : result;
                
                renderTableData(leadsData);
                
            } catch (error) {
                console.error("Failed to fetch data search:", error);
                document.getElementById('allBody').innerHTML = `
                    <tr>
                        <td colspan="14" class="text-center p-4 text-red-500">
                            Terjadi kesalahan saat memuat data.
                        </td>
                    </tr>
                `;
            }
        }

        let debounceTimer;
        const searchInputElement = document.getElementById('searchInput');

        if (searchInputElement) {
            searchInputElement.addEventListener('input', function() {
                const query = getSearchQuery();
                
                clearTimeout(debounceTimer);
                
                debounceTimer = setTimeout(() => {
                    fetchSearchResults(query);
                }, 500); 
            });
        }
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
