@extends('layouts.app')

@section('content')
<section class="min-h-screen text-xs! lg:text-sm!">
    {{-- HEADER PAGES --}}
    <div class="pt-4">
        <div class="flex items-center gap-3">
            <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                    fill="#115640" />
            </svg>
            <h1 class="text-[#115640] font-semibold text-lg lg:text-2xl">Leads</h1>
        </div>
        <p class="mt-1 text-[#115640] text-sm lg:text-lg">All Leads</p>
    </div>

    {{-- ALL CARDS COUNTS --}}
    <div id="forAllCardsCounts" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-3 mt-4">
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
    <div id="forColdCardsCounts" class="hidden grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-3 mt-4">
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
    <div id="forWarmCardsCounts" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-3 mt-4">
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
    <div id="forHotCardsCounts" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-3 gap-3 mt-4">
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
            class="bg-white lg:flex justify-between items-center border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">

            {{-- FOR SMALL SCREEN SECTION --}}
            <div class="sm:grid sm:grid-cols-2 sm:grid-cols-[3fr_1fr] gap-4 lg:hidden">
                <div class="border border-gray-300 rounded-lg flex items-center p-2">
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
                <button id="manageExportTriggerMobile" type="button" data-export-trigger="smallScreen" class="cursor-pointer bg-[#115640] rounded-lg flex justify-center items-center lg:hidden">
                    <div class="w-full flex items-center justify-center text-center gap-3 px-3 py-2 text-white">
                        <x-icon.download />
                        <span data-export-label>Export Excel</span>
                    </div>
                </button>
            </div>

            {{-- SEARCH TABLES --}}
            <div class="xl:w-[10%]! border border-gray-300 rounded-lg lg:flex! items-center p-2 hidden">
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
            <div class="xl:w-[80%]! gap-3 flex items-center">
                {{-- FILTER BRANCH SALES DATE --}}
                <div class="w-1/2 grid grid-cols-4 bg-white border border-[#D9D9D9] rounded-lg">

                    {{-- BRANCH --}}
                    <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E] p-3">
                        <select id="branchesQuery"
                        class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- SALES --}}
                    <div class="flex items-center justify-center gap-2 border-r border-r-[#D9D9D9] cursor-pointer h-full px-2 text-[#1E1E1E] p-3">
                        <select id="salesQuery"
                        class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Sales</option>
                            @foreach($salesUsers as $sales)
                                <option value="{{ $sales->id }}" data-branch-id="{{ $sales->branch_id }}">{{ $sales->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- DATES --}}
                    <div
                    class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">

                        {{-- TOGGLE --}}
                        <div id="openDateDropdown" class="flex justify-center items-center gap-2">
                            <p id="dateLabel" class="font-medium text-black">Date</p>
                            <i id="iconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                        </div>

                        {{-- DATE DROPDOWN --}}
                        <div id="dateDropdown"
                            class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

                            <h3 class="font-semibold mb-2">Select Date Range</h3>

                                <div class="flex justify-center items-center">
                                <input type="text" id="source-date-range" class="shadow-none w-full" placeholder="Select date range">
                                </div>

                            <div class="flex justify-end gap-2 mt-3">

                                <button id="cancelDate" class="px-3 py-1 text-[#303030]">
                                    Cancel
                                </button>

                                <button id="applyDate"
                                    class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                                    Apply
                                </button>

                            </div>
                        </div>
                    </div>  

                    {{-- RESET FILTERS --}}
                    <div id="generalFilterReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
                        <i id="resetQuery" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                        <p class="font-medium text-[#900B09]">Reset Filter</p>
                    </div>
                </div>
                <div class="w-1/2 border border-[#D5D5D5] rounded-lg grid grid-cols-5">
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
            </div>

            {{-- EXPORT EXCELS LEADS --}}
            <button id="manageExportTriggerDesktop" type="button" data-export-trigger="largeScreen" class="xl:w-[10%]! cursor-pointer bg-[#115640] rounded-lg hidden lg:inline!">
                <div class="w-full flex items-center justify-center text-center gap-3 px-3 py-2 text-white">
                    <x-icon.download />
                    <span data-export-label>Export Excel</span>
                </div>
            </button>
        </div>

        <form id="manageExportForm" action="{{ route('leads.manage.export') }}" method="POST" class="hidden">
            @csrf
        </form>

        {{-- CONTENTS TABLES --}}
        <div>
            {{-- ALL STAGE TABLE --}}
            <div data-tab-container="all" class="leads-table-container">
                <div class="max-xl:overflow-x-scroll">
                    <table id="allLeadsTableNew" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                        {{-- HEADER TABLE --}}
                        <thead id="allHead" class="text-[#1E1E1E]"></thead>
                        <tbody id="allBody"></tbody>
                    </table>
                </div>

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
            <div class="overflow-x-scroll">
                <table id="{{ $tab }}LeadsTableNew" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                    {{-- HEADER TABLE --}}
                    <thead id="{{ $tab }}Head" class="text-[#1E1E1E]"></thead>
                    <tbody id="{{ $tab }}Body" class="text-[#1E1E1E]"></tbody>
                </table>
            </div>

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

{{-- ACTIVITY LOGS MODAL --}}
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-[#1E1E1E] text-lg font-semibold">Activity Logs</h5>
                <button type="button" class="close cursor-pointer" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="border border-[#D9D9D9] rounded-lg mb-3">
                    <table class="w-full">
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="lg:p-3 p-1">Date</th>
                                <th class="lg:p-3 p-1">Activity</th>
                                <th class="lg:max-w-60 lg:p-3 p-1">Note</th>
                                <th class="lg:p-3 p-1">Attachment</th>
                                <th class="lg:p-3 p-1">User</th>
                            </tr>
                        </thead>
                        <tbody class="text-[#1E1E1E]"></tbody>
                    </table>
                </div>
                <form id="activityLogForm">
                    <div class="w-full! grid! sm:grid-cols-1 lg:grid-cols-2! justify-between! gap-3! text-[#1E1E1E]!">
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
                                    <option disabled>──────────── Initiation ────────────</option>
                                    @foreach ($activities as $act)
                                    @if ($act->id == 5)
                                    <option disabled>──────────── Hold Cold ────────────</option>
                                    @endif

                                    <option value="{{ $act->id }}">
                                        {{ $act->code }} - {{ $act->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- NOTE FIELD / ATTACHMENT FILES AND SUBMIT BUTTON --}}
                        <div class="grid sm:grid-cols-[3fr_1fr] lg:grid-cols-2">
                            <input type="text" name="note"
                                class="px-3 py-2 border border-[#D9D9D9] rounded-lg w-full focus:outline-none"
                                placeholder="Type Note Here...">
                            {{-- ATTACHMENT FILES AND SUBMIT BUTTON --}}
                            <div class="flex items-center justify-end sm:gap-2 lg:gap-5">
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
                                        class="cursor-pointer bg-[#115640] px-1 lg:px-5 py-2 text-white rounded-lg ">+
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

{{-- QUOTATION LOGS MODAL --}}
<div class="modal fade" id="quotationLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-[#083224] text-lg font-semibold">Quotation Logs</h5>
                <button type="button" class="close cursor-pointer" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="border border-[#D9D9D9] rounded-lg mb-3">
                    <table class="w-full">
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="lg:p-3 p-1">Date</th>
                                <th class="lg:p-3 p-1">Action</th>
                                <th class="lg:p-3 p-1">User</th>
                            </tr>
                        </thead>
                        <tbody class="text-[#1E1E1E]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // CONTENTS TABLE AND STAGE TABLE SECTION
        let fp = null;
        const DEFAULT_PAGE_SIZE = 10;
        const pageState = { all: 1, cold: 1, warm: 1, hot: 1, deal: 1 };
        const pageSizeState = { all: DEFAULT_PAGE_SIZE, cold: DEFAULT_PAGE_SIZE, warm: DEFAULT_PAGE_SIZE, hot: DEFAULT_PAGE_SIZE, deal: DEFAULT_PAGE_SIZE };
        const appliedDateFilter = {
            start_date: '',
            end_date: '',
        };

        const totals = {
            all: {{ $leadCounts['all'] ?? 0 }},
            cold: {{ $leadCounts['cold'] ?? 0 }},
            warm: {{ $leadCounts['warm'] ?? 0 }},
            hot: {{ $leadCounts['hot'] ?? 0 }},
            deal: {{ $leadCounts['deal'] ?? 0 }}
        };

        const selectedLeadIds = new Set();
        let isManageExportSubmitting = false;

        const manageTableConfigs = {
            all: {
                headId: 'allHead',
                bodyId: 'allBody',
                columns: [
                    { label: 'Lead Name', key: 'lead_name', className: 'p-2 font-medium text-left' },
                    { label: 'Branch Name', key: 'branch_name', className: 'p-2' },
                    { label: 'Sales Name', key: 'sales_name', className: 'p-2' },
                    { label: 'Telephone', key: 'phone', className: 'p-2' },
                    { label: 'Source', key: 'source_name', className: 'p-2' },
                    { label: 'Needs', key: 'needs', className: 'p-2' },
                    { label: 'Industry', key: 'existing_industries', className: 'p-2' },
                    { label: 'City', key: 'city_name', className: 'p-2' },
                    { label: 'Regional', key: 'regional_name', className: 'p-2' },
                    { label: 'Customer Type', key: 'customer_type', className: 'p-2' },
                    { label: 'ACT Last Time', key: 'act_last_time', className: 'p-2' },
                    { label: 'ACT Status', key: 'act_status', className: 'p-2' },
                    { label: 'Created At', key: 'created_at', className: 'p-2' },
                    { label: 'Claimed At', key: 'claimed_at', className: 'p-2' },
                    { label: 'Stage', key: 'status_name', type: 'status', className: 'text-center capitalize p-2' },
                    { label: 'Action', key: 'actions', type: 'html', className: 'text-center p-2', exportable: false }
                ]
            },
            cold: {
                headId: 'coldHead',
                bodyId: 'coldBody',
                columns: [
                    { label: 'Lead Name', key: 'lead_name', className: 'p-2 font-medium text-left' },
                    { label: 'Branch Name', key: 'branch_name', className: 'p-2' },
                    { label: 'Sales Name', key: 'sales_name', className: 'p-2' },
                    { label: 'Telephone', key: 'phone', className: 'p-2' },
                    { label: 'Source', key: 'source_name', className: 'p-2' },
                    { label: 'Needs', key: 'needs', className: 'p-2' },
                    { label: 'Industry', key: 'existing_industries', className: 'p-2' },
                    { label: 'City', key: 'city_name', className: 'p-2' },
                    { label: 'Regional', key: 'regional_name', className: 'p-2' },
                    { label: 'Customer Type', key: 'customer_type', className: 'p-2' },
                    { label: 'ACT Last Time', key: 'act_last_time', className: 'p-2' },
                    { label: 'ACT Status', key: 'act_status', className: 'p-2' },
                    { label: 'Created At', key: 'created_at', className: 'p-2' },
                    { label: 'Claimed At', key: 'claimed_at', className: 'p-2' },
                    { label: 'Action', key: 'actions', type: 'html', className: 'text-center p-2', exportable: false }
                ]
            },
            warm: {
                headId: 'warmHead',
                bodyId: 'warmBody',
                columns: [
                    { label: 'Lead Name', key: 'lead_name', className: 'p-2 font-medium text-left' },
                    { label: 'Branch Name', key: 'branch_name', className: 'p-2' },
                    { label: 'Sales Name', key: 'sales_name', className: 'p-2' },
                    { label: 'Telephone', key: 'phone', className: 'p-2' },
                    { label: 'Source', key: 'source_name', className: 'p-2' },
                    { label: 'Needs', key: 'needs', className: 'p-2' },
                    { label: 'Industry', key: 'existing_industries', className: 'p-2' },
                    { label: 'City', key: 'city_name', className: 'p-2' },
                    { label: 'Regional', key: 'regional_name', className: 'p-2' },
                    { label: 'Customer Type', key: 'customer_type', className: 'p-2' },
                    { label: 'Quotation Number', key: 'quotation_number', className: 'p-2' },
                    { label: 'Quotation Price', key: 'quotation_price', className: 'p-2' },
                    { label: 'Quotation Created', key: 'quot_created', className: 'p-2' },
                    { label: 'Quotation End Date', key: 'quot_end_date', className: 'p-2' },
                    { label: 'ACT Last Time', key: 'act_last_time', className: 'p-2' },
                    { label: 'ACT Status', key: 'act_status', className: 'p-2' },
                    { label: 'Created At', key: 'created_at', className: 'p-2' },
                    { label: 'Claimed At', key: 'claimed_at', className: 'p-2' },
                    { label: 'Action', key: 'actions', type: 'html', className: 'text-center p-2', exportable: false }
                ]
            },
            hot: {
                headId: 'hotHead',
                bodyId: 'hotBody',
                columns: [
                    { label: 'Lead Name', key: 'lead_name', className: 'p-2 font-medium text-left' },
                    { label: 'Branch Name', key: 'branch_name', className: 'p-2' },
                    { label: 'Sales Name', key: 'sales_name', className: 'p-2' },
                    { label: 'Telephone', key: 'phone', className: 'p-2' },
                    { label: 'Source', key: 'source_name', className: 'p-2' },
                    { label: 'Needs', key: 'needs', className: 'p-2' },
                    { label: 'Industry', key: 'existing_industries', className: 'p-2' },
                    { label: 'City', key: 'city_name', className: 'p-2' },
                    { label: 'Regional', key: 'regional_name', className: 'p-2' },
                    { label: 'Customer Type', key: 'customer_type', className: 'p-2' },
                    { label: 'Quotation Number', key: 'quotation_number', className: 'p-2' },
                    { label: 'Quotation Price', key: 'quotation_price', className: 'p-2' },
                    { label: 'Invoice', key: 'invoice_number', className: 'p-2' },
                    { label: 'Invoice Price', key: 'invoice_price', className: 'p-2' },
                    { label: 'Quotation Created', key: 'quot_created', className: 'p-2' },
                    { label: 'Quotation End Date', key: 'quot_end_date', className: 'p-2' },
                    { label: 'ACT Last Time', key: 'act_last_time', className: 'p-2' },
                    { label: 'ACT Status', key: 'act_status', className: 'p-2' },
                    { label: 'Created At', key: 'created_at', className: 'p-2' },
                    { label: 'Claimed At', key: 'claimed_at', className: 'p-2' },
                    { label: 'Action', key: 'actions', type: 'html', className: 'text-center p-2', exportable: false }
                ]
            },
            deal: {
                headId: 'dealHead',
                bodyId: 'dealBody',
                columns: [
                    { label: 'Lead Name', key: 'lead_name', className: 'p-2 font-medium text-left' },
                    { label: 'Branch Name', key: 'branch_name', className: 'p-2' },
                    { label: 'Sales Name', key: 'sales_name', className: 'p-2' },
                    { label: 'Telephone', key: 'phone', className: 'p-2' },
                    { label: 'Source', key: 'source_name', className: 'p-2' },
                    { label: 'Needs', key: 'needs', className: 'p-2' },
                    { label: 'Industry', key: 'existing_industries', className: 'p-2' },
                    { label: 'City', key: 'city_name', className: 'p-2' },
                    { label: 'Regional', key: 'regional_name', className: 'p-2' },
                    { label: 'Customer Type', key: 'customer_type', className: 'p-2' },
                    { label: 'Quotation Number', key: 'quotation_number', className: 'p-2' },
                    { label: 'Quotation Price', key: 'quotation_price', className: 'p-2' },
                    { label: 'Invoice', key: 'invoice_number', className: 'p-2' },
                    { label: 'Invoice Price', key: 'invoice_price', className: 'p-2' },
                    { label: 'Quotation Created', key: 'quot_created', className: 'p-2' },
                    { label: 'Quotation End Date', key: 'quot_end_date', className: 'p-2' },
                    { label: 'ACT Last Time', key: 'act_last_time', className: 'p-2' },
                    { label: 'ACT Status', key: 'act_status', className: 'p-2' },
                    { label: 'Created At', key: 'created_at', className: 'p-2' },
                    { label: 'Claimed At', key: 'claimed_at', className: 'p-2' },
                    { label: 'Action', key: 'actions', type: 'html', className: 'text-center p-2', exportable: false }
                ]
            }
        };

        function getManageTableConfig(tab) {
            return manageTableConfigs[tab] || manageTableConfigs.all;
        }

        function getActiveManageTab() {
            const activeTab = document.querySelector('.nav-leads.active-nav');
            return activeTab?.dataset?.tab || 'all';
        }

        function getManageColumnCount(tab) {
            return getManageTableConfig(tab).columns.length + 1;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getManageStatusClass(status) {
            if (status === 'Published') return 'status-trash';
            if (status === 'Cold') return 'status-cold';
            if (status === 'Warm') return 'status-warm';
            if (status === 'Hot') return 'status-hot';
            if (status === 'Deal') return 'status-deal';
            if (status === 'Trash Cold' || status === 'Trash Warm' || status === 'Trash Hot') return 'status-trash';
            return '';
        }

        function getManageStatusDotClass(status) {
            if (status === 'Trash Cold') return 'dot-trash-cold';
            if (status === 'Trash Warm') return 'dot-trash-warm';
            if (status === 'Trash Hot') return 'dot-trash-hot';
            return '';
        }

        function clearManageSelections() {
            selectedLeadIds.clear();
            updateExportButtonState();
            Object.keys(manageTableConfigs).forEach(updateSelectAllCheckbox);
        }

        function updateExportButtonState() {
            const selectedCount = selectedLeadIds.size;
            const label = selectedCount > 0
                ? `Export Excel (${selectedCount} Selected)`
                : 'Export Excel';

            document.querySelectorAll('[data-export-label]').forEach((node) => {
                node.textContent = label;
            });
        }

        function renderManageTableHead(tab) {
            const config = getManageTableConfig(tab);
            const thead = document.getElementById(config.headId);

            if (!thead) {
                return;
            }

            const columnsHtml = config.columns.map((column) => {
                const alignmentClass = column.type === 'html' || column.type === 'status'
                    ? 'text-center'
                    : 'text-left';

                return `<th class="p-2 ${alignmentClass}">${escapeHtml(column.label)}</th>`;
            }).join('');

            thead.innerHTML = `
                <tr class="border-b border-b-[#D9D9D9]">
                    <th class="p-2 text-center w-10">
                        <input
                            type="checkbox"
                            class="manage-select-all cursor-pointer"
                            data-tab="${tab}"
                            aria-label="Select all leads on current page"
                        >
                    </th>
                    ${columnsHtml}
                </tr>
            `;
        }

        function renderManageTableMessage(tab, message, className = 'text-gray-500') {
            const config = getManageTableConfig(tab);
            const tbody = document.getElementById(config.bodyId);

            if (!tbody) {
                return;
            }

            tbody.innerHTML = `
                <tr>
                    <td colspan="${getManageColumnCount(tab)}" class="p-4 text-center ${className}">
                        ${escapeHtml(message)}
                    </td>
                </tr>
            `;

            updateSelectAllCheckbox(tab);
        }

        function renderManageCell(column, row) {
            const value = row?.[column.key];

            if (column.type === 'html') {
                return value ?? '-';
            }

            if (column.type === 'status') {
                const status = value ?? '-';
                const dotClass = getManageStatusDotClass(status);

                return `
                    <span class="block px-2 py-1 rounded-sm flex items-center justify-center ${getManageStatusClass(status)}">
                        ${escapeHtml(status)}
                        ${dotClass ? `<span class="${dotClass}"></span>` : ''}
                    </span>
                `;
            }

            return escapeHtml(value ?? '-');
        }

        function renderManageRows(tab, leads) {
            const config = getManageTableConfig(tab);
            const tbody = document.getElementById(config.bodyId);

            if (!tbody) {
                return;
            }

            if (!leads || leads.length === 0) {
                renderManageTableMessage(tab, 'Data tidak ditemukan');
                return;
            }

            tbody.innerHTML = leads.map((row) => {
                const leadId = String(row.id ?? '');
                const checked = selectedLeadIds.has(leadId) ? 'checked' : '';

                const cellsHtml = config.columns.map((column) => {
                    const className = column.className || 'p-2';
                    return `<td class="${className}">${renderManageCell(column, row)}</td>`;
                }).join('');

                return `
                    <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]! font-medium!" data-lead-id="${escapeHtml(leadId)}">
                        <td class="p-2 text-center align-middle">
                            <input
                                type="checkbox"
                                class="lead-row-checkbox cursor-pointer"
                                data-tab="${tab}"
                                value="${escapeHtml(leadId)}"
                                ${checked}
                                aria-label="Select lead ${escapeHtml(row.lead_name ?? leadId)}"
                            >
                        </td>
                        ${cellsHtml}
                    </tr>
                `;
            }).join('');

            updateSelectAllCheckbox(tab);
        }

        function updateSelectAllCheckbox(tab) {
            const config = getManageTableConfig(tab);
            const selectAll = document.querySelector(`.manage-select-all[data-tab="${tab}"]`);

            if (!selectAll) {
                return;
            }

            const rowCheckboxes = Array.from(document.querySelectorAll(`#${config.bodyId} .lead-row-checkbox`));
            const checkedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
            const hasRows = rowCheckboxes.length > 0;

            selectAll.disabled = !hasRows;
            selectAll.checked = hasRows && checkedCount === rowCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        }

        function submitManageExport() {
            const form = document.getElementById('manageExportForm');

            if (!form || isManageExportSubmitting) {
                return;
            }

            isManageExportSubmitting = true;

            const activeTab = getActiveManageTab();
            const selectedIds = Array.from(selectedLeadIds);
            const exportMode = selectedIds.length > 0 ? 'selected' : 'all_filtered';

            form.querySelectorAll('input[type="hidden"][data-generated="true"]').forEach((input) => input.remove());

            const generatedFields = [];
            const addField = (name, value) => {
                if (value === null || value === undefined || value === '') {
                    return;
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                input.setAttribute('data-generated', 'true');
                generatedFields.push(input);
            };

            addField('export_mode', exportMode);

            if (activeTab && activeTab !== 'all') {
                addField('stage', activeTab);
            } else {
                addField('stage', 'all');
            }

            const filter = getManageGeneralFilter();
            addField('branch_id', filter.branch_id);
            addField('sales_id', filter.sales_id);
            addField('start_date', filter.start_date);
            addField('end_date', filter.end_date);
            addField('search', filter.search);
            addField('export_file_name', buildManageExportFileName());

            selectedIds.forEach((id) => {
                addField('lead_ids[]', id);
            });

            generatedFields.forEach((input) => form.appendChild(input));
            form.submit();

            window.setTimeout(() => {
                isManageExportSubmitting = false;
            }, 1500);
        }

        function resetAllTabPages() {
            pageState.all = 1;
            pageState.cold = 1;
            pageState.warm = 1;
            pageState.hot = 1;
            pageState.deal = 1;
        }

        function getManageGeneralFilter() {
            return {
                branch_id: $('#branchesQuery').val() || '',
                sales_id: $('#salesQuery').val() || '',
                start_date: appliedDateFilter.start_date || '',
                end_date: appliedDateFilter.end_date || '',
                search: getSearchQuery(),
            };
        }

        function getSelectedOptionLabel(selectId) {
            const select = document.getElementById(selectId);

            if (!select || !select.value) {
                return '';
            }

            const selectedOption = select.options[select.selectedIndex];
            return selectedOption ? String(selectedOption.textContent || '').trim() : '';
        }

        function normalizeManageExportValue(value) {
            return String(value ?? '')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function getManageStageLabel(stage) {
            const stageLabels = {
                all: 'All Stage',
                cold: 'Cold',
                warm: 'Warm',
                hot: 'Hot',
                deal: 'Deal',
            };

            return stageLabels[stage] || stageLabels.all;
        }

        function buildManageExportFileName() {
            const filter = getManageGeneralFilter();
            const activeTab = getActiveManageTab();
            const branchLabel = normalizeManageExportValue(getSelectedOptionLabel('branchesQuery'));
            const salesLabel = normalizeManageExportValue(getSelectedOptionLabel('salesQuery'));
            const searchValue = normalizeManageExportValue(filter.search);
            const parts = [];

            if (searchValue) {
                parts.push(`[SEARCHED - ${searchValue}]`);
            }

            if (branchLabel) {
                parts.push(`[Branch - ${branchLabel}]`);
            }

            if (salesLabel) {
                parts.push(`[Sales - ${salesLabel}]`);
            }

            if (filter.start_date && filter.end_date) {
                parts.push(`[Date - ${filter.start_date} to ${filter.end_date}]`);
            } else if (filter.start_date) {
                parts.push(`[Date - ${filter.start_date}]`);
            } else if (filter.end_date) {
                parts.push(`[Date - ${filter.end_date}]`);
            }

            parts.push(`[Stage - ${getManageStageLabel(activeTab)}]`);

            return parts.join(' - ');
        }

        function applyManageGeneralFilterToParams(params, options = {}) {
            const includeSearch = options.includeSearch !== false;
            const filter = getManageGeneralFilter();

            if (filter.branch_id && !params.has('branch_id')) {
                params.append('branch_id', filter.branch_id);
            }

            if (filter.sales_id && !params.has('sales_id')) {
                params.append('sales_id', filter.sales_id);
            }

            if (filter.start_date && !params.has('start_date')) {
                params.append('start_date', filter.start_date);
            }

            if (filter.end_date && !params.has('end_date')) {
                params.append('end_date', filter.end_date);
            }

            if (includeSearch && filter.search && !params.has('search')) {
                params.append('search', filter.search);
            }
        }

        function buildManageListUrl(tab, page, perPage) {
            const params = new URLSearchParams();
            params.append('page', page);
            params.append('per_page', perPage);

            if (tab !== 'all') {
                params.append('stage', tab);
            }

            applyManageGeneralFilterToParams(params);

            return `/api/leads/manage/list?${params.toString()}`;
        }

        function syncSalesOptionsWithBranch() {
            const branchSelect = document.getElementById('branchesQuery');
            const salesSelect = document.getElementById('salesQuery');

            if (!branchSelect || !salesSelect) {
                return;
            }

            const selectedBranch = branchSelect.value || '';
            const salesOptions = Array.from(salesSelect.options).slice(1);

            salesOptions.forEach((option) => {
                const optionBranchId = option.dataset.branchId || '';
                const isVisible = !selectedBranch || optionBranchId === selectedBranch;

                option.hidden = !isVisible;
                option.disabled = !isVisible;
            });

            const selectedSalesOption = salesSelect.options[salesSelect.selectedIndex];
            if (selectedSalesOption && selectedSalesOption.disabled) {
                salesSelect.value = '';
            }
        }

        function closeDateDropdown() {
            const dropdown = document.getElementById('dateDropdown');
            const chevron = document.getElementById('iconDate');

            if (dropdown) {
                dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            }

            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }

        function initManageFlatpickr() {
            const input = document.getElementById('source-date-range');

            if (!input || typeof flatpickr === 'undefined') {
                return;
            }

            fp = flatpickr(input, {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d',
            });
        }

        function initManageDateFilter() {
            const openBtn = document.getElementById('openDateDropdown');
            const dropdown = document.getElementById('dateDropdown');
            const chevron = document.getElementById('iconDate');
            const cancelBtn = document.getElementById('cancelDate');
            const applyBtn = document.getElementById('applyDate');
            const dateLabel = document.getElementById('dateLabel');

            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    if (dropdown) {
                        dropdown.classList.toggle('opacity-0');
                        dropdown.classList.toggle('scale-95');
                        dropdown.classList.toggle('pointer-events-none');
                    }

                    if (chevron) {
                        chevron.classList.toggle('rotate-180');
                    }

                    if (fp) {
                        fp.open();
                    }
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    closeDateDropdown();
                });
            }

            if (applyBtn) {
                applyBtn.addEventListener('click', function () {
                    const dates = fp?.selectedDates ?? [];

                    if (dates.length !== 2) {
                        return;
                    }

                    appliedDateFilter.start_date = flatpickr.formatDate(dates[0], 'Y-m-d');
                    appliedDateFilter.end_date = flatpickr.formatDate(dates[1], 'Y-m-d');

                    if (dateLabel) {
                        dateLabel.innerText = `${appliedDateFilter.start_date} -> ${appliedDateFilter.end_date}`;
                    }

                    clearManageSelections();
                    resetAllTabPages();
                    reloadTab(getActiveManageTab());
                    closeDateDropdown();
                });
            }
        }

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
                $(".leads-table-container").hide();

                $(`[data-tab-container='${tab}']`).show();

                $(".nav-leads").removeClass("active-nav");
                $(this).addClass("active-nav");

                clearManageSelections();
                reloadTab(tab);

                $.each(sections, function (key, value) {
                    value.addClass("hidden");
                });


                sections[tab].removeClass("hidden");

                // Reset animation
                resetAnimation(sections[tab]);

            });

            Object.keys(manageTableConfigs).forEach(renderManageTableHead);
            updateExportButtonState();
            syncSalesOptionsWithBranch();
            initManageFlatpickr();
            initManageDateFilter();

            $('#branchesQuery').on('change', function () {
                syncSalesOptionsWithBranch();
                clearManageSelections();
                resetAllTabPages();
                reloadTab(getActiveManageTab());
            });

            $('#salesQuery').on('change', function () {
                clearManageSelections();
                resetAllTabPages();
                reloadTab(getActiveManageTab());
            });

            $('#generalFilterReset').on('click', function () {
                $('#branchesQuery').val('');
                $('#salesQuery').val('');

                if (fp) {
                    fp.clear();
                }

                appliedDateFilter.start_date = '';
                appliedDateFilter.end_date = '';
                $('#source-date-range').val('');
                $('#dateLabel').text('Date');

                syncSalesOptionsWithBranch();
                closeDateDropdown();
                clearManageSelections();
                resetAllTabPages();
                reloadTab(getActiveManageTab());
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
            loadManageLeads(tab);
        }

        // LOAD THE MAIN DATA TO THE TABLE (DATA-CONTAINER)
        async function loadManageLeads(tab) {
            const config = getManageTableConfig(tab);
            const page = pageState[tab] || 1;
            const perPage = pageSizeState[tab] || DEFAULT_PAGE_SIZE;

            renderManageTableHead(tab);
            renderManageTableMessage(tab, 'Loading data...', 'text-[#1E1E1E] opacity-50');

            try {
                const response = await fetch(buildManageListUrl(tab, page, perPage), {
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                updatePagerUI(tab, result.total);
                totals[tab] = result.total || 0;
                renderManageRows(tab, result.data || []);
            } catch (error) {
                console.error(`Gagal load leads (${config.bodyId}):`, error);
                renderManageTableMessage(tab, 'Gagal memuat data', 'text-red-500');
            }
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
            const inputs = document.querySelectorAll('#searchInput');

            for (let input of inputs) {
                if (input.offsetParent !== null) { 
                    // ini cek yang visible
                    return input.value.trim();
                }
            }

            return '';
        }

        function renderTableData(leads) {
            const tbody = document.getElementById('allBody');
            tbody.innerHTML = '';

        }

        $('#manageExportTriggerMobile').off('click.manageExport').on('click.manageExport', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            submitManageExport();
        });

        $('#manageExportTriggerDesktop').off('click.manageExport').on('click.manageExport', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            submitManageExport();
        });

        $(document).on('change', '.lead-row-checkbox', function () {
            const leadId = String(this.value || '');

            if (!leadId) {
                return;
            }

            if (this.checked) {
                selectedLeadIds.add(leadId);
            } else {
                selectedLeadIds.delete(leadId);
            }

            updateSelectAllCheckbox(this.dataset.tab || getActiveManageTab());
            updateExportButtonState();
        });

        $(document).on('change', '.manage-select-all', function () {
            const tab = this.dataset.tab || getActiveManageTab();
            const config = getManageTableConfig(tab);
            const rowCheckboxes = document.querySelectorAll(`#${config.bodyId} .lead-row-checkbox`);

            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = this.checked;

                if (this.checked) {
                    selectedLeadIds.add(String(checkbox.value));
                } else {
                    selectedLeadIds.delete(String(checkbox.value));
                }
            });

            updateSelectAllCheckbox(tab);
            updateExportButtonState();
        });

        let debounceTimer;
        const searchInputs = document.querySelectorAll('#searchInput');

        function handleSearchInputDebounced(input) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const activeNav = document.querySelector('.nav-leads.active-nav');
                const currentTab = activeNav ? activeNav.dataset.tab : 'all';

                clearManageSelections();
                resetAllTabPages();
                reloadTab(currentTab);
            }, 500);
        }

        searchInputs.forEach(input => {
            input.addEventListener('input', function () {
                handleSearchInputDebounced(this);
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
                    rows += '<tr class="border-t border-t-[#D9D9D9]">' +
                        '<td class="lg:p-3 sm:p-1">' + item.logged_at + '</td>' +
                        '<td class="lg:p-3 sm:p-1">' + item.code + ' - ' + item.activity + '</td>' +
                        '<td class="lg:max-w-60! lg:truncate! lg:p-3! sm:p-1!">' + (item.note || '') + '</td>' +
                        '<td class="lg:p-3 sm:p-1">' + (item.attachment ? '<a href="' + item.attachment +
                            '" class="btn btn-sm btn-outline-secondary">Download</a>' : '-') +
                        '</td>' +
                        '<td class="lg:p-3 sm:p-1">' + item.user + '</td>' +
                        '</tr>';
                });
                tbody.html(rows || '<tr><td colspan="5" class="text-center p-3">No logs</td></tr>');
            });
        });

        // ACTIVITY LOG
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
                            rows += '<tr class="border-t border-t-[#D9D9D9]">' +
                                '<td class="lg:p-3 sm:p-1">' + item.logged_at + '</td>' +
                                '<td class="lg:p-3 sm:p-1">' + item.code + ' - ' + item.activity + '</td>' +
                                '<td class="lg:max-w-60! lg:truncate! lg:p-3! sm:p-1!">' + (item.note || '') + '</td>' +
                                '<td class="lg:p-3 sm:p-1">' + (item.attachment ? '<a href="' + item
                                    .attachment +
                                    '" class="btn btn-sm btn-outline-secondary">Download</a>' :
                                    '-') + '</td>' +
                                '<td class="lg:p-3 sm:p-1">' + item.user + '</td>' +
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
                    rows += '<tr class="border-t border-t-[#D9D9D9]">' +
                        '<td class="p-3">' + item.logged_at + '</td>' +
                        '<td class="p-3">' + item.action + '</td>' +
                        '<td class="p-3">' + item.user + '</td>' +
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
