@extends('layouts.app')

@section('content')

    <section class="min-h-screen sm:text-xs! lg:text-sm!">
        {{-- HEADER PAGES --}}
        <div class="pt-4">
            <div class="flex items-center gap-2">
                <svg width="20" height="19" viewBox="0 0 20 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.35 16.175L17.875 12.625C18.075 12.425 18.3125 12.325 18.5875 12.325C18.8625 12.325 19.1 12.425 19.3 12.625C19.5 12.825 19.6 13.0625 19.6 13.3375C19.6 13.6125 19.5 13.85 19.3 14.05L15.05 18.3C14.85 18.5 14.6125 18.6 14.3375 18.6C14.0625 18.6 13.825 18.5 13.625 18.3L11.5 16.175C11.3167 15.975 11.225 15.7375 11.225 15.4625C11.225 15.1875 11.325 14.95 11.525 14.75C11.725 14.55 11.9583 14.45 12.225 14.45C12.4917 14.45 12.725 14.55 12.925 14.75L14.35 16.175ZM5.7125 9.7125C5.90417 9.52083 6 9.28333 6 9C6 8.71667 5.90417 8.47917 5.7125 8.2875C5.52083 8.09583 5.28333 8 5 8C4.71667 8 4.47917 8.09583 4.2875 8.2875C4.09583 8.47917 4 8.71667 4 9C4 9.28333 4.09583 9.52083 4.2875 9.7125C4.47917 9.90417 4.71667 10 5 10C5.28333 10 5.52083 9.90417 5.7125 9.7125ZM5.7125 5.7125C5.90417 5.52083 6 5.28333 6 5C6 4.71667 5.90417 4.47917 5.7125 4.2875C5.52083 4.09583 5.28333 4 5 4C4.71667 4 4.47917 4.09583 4.2875 4.2875C4.09583 4.47917 4 4.71667 4 5C4 5.28333 4.09583 5.52083 4.2875 5.7125C4.47917 5.90417 4.71667 6 5 6C5.28333 6 5.52083 5.90417 5.7125 5.7125ZM13 10C13.2833 10 13.5208 9.90417 13.7125 9.7125C13.9042 9.52083 14 9.28333 14 9C14 8.71667 13.9042 8.47917 13.7125 8.2875C13.5208 8.09583 13.2833 8 13 8H9C8.71667 8 8.47917 8.09583 8.2875 8.2875C8.09583 8.47917 8 8.71667 8 9C8 9.28333 8.09583 9.52083 8.2875 9.7125C8.47917 9.90417 8.71667 10 9 10H13ZM13 6C13.2833 6 13.5208 5.90417 13.7125 5.7125C13.9042 5.52083 14 5.28333 14 5C14 4.71667 13.9042 4.47917 13.7125 4.2875C13.5208 4.09583 13.2833 4 13 4H9C8.71667 4 8.47917 4.09583 8.2875 4.2875C8.09583 4.47917 8 4.71667 8 5C8 5.28333 8.09583 5.52083 8.2875 5.7125C8.47917 5.90417 8.71667 6 9 6H13ZM2 18C1.45 18 0.979167 17.8042 0.5875 17.4125C0.195833 17.0208 0 16.55 0 16V2C0 1.45 0.195833 0.979167 0.5875 0.5875C0.979167 0.195833 1.45 0 2 0H16C16.55 0 17.0208 0.195833 17.4125 0.5875C17.8042 0.979167 18 1.45 18 2V8.875C18 9.14167 17.9458 9.39583 17.8375 9.6375C17.7292 9.87917 17.5833 10.0917 17.4 10.275L14.35 13.35L13.625 12.625C13.2417 12.2417 12.7708 12.05 12.2125 12.05C11.6542 12.05 11.1833 12.2417 10.8 12.625L9.4 14.05C9.2 14.25 9.05 14.4708 8.95 14.7125C8.85 14.9542 8.8 15.2 8.8 15.45C8.8 15.6833 8.83333 15.8958 8.9 16.0875C8.96667 16.2792 9.06667 16.4667 9.2 16.65C9.4 16.9333 9.4375 17.2292 9.3125 17.5375C9.1875 17.8458 8.96667 18 8.65 18H2Z" fill="#115640"/>
                </svg>
                <h1 class="text-[#115640] font-semibold lg:text-2xl text-lg">Quotations</h1>
            </div>
            <p class="mt-1 text-[#115640] lg:text-lg text-sm">Quotation Approvals</p>
        </div>

        {{-- All CARDS COUNTS --}}
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

        {{-- PENDING CARDS COUNTS --}}
        <div id="forHotCardsCounts" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-3 mt-4">
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

        {{-- COMPLETED CARDS COUNTS --}}
        <div id="forDealCardsCounts" class="hidden grid grid-cols-1 gap-3 mt-4">
            {{-- LEADS DEAL COUNTS CARDS --}}
            <div class="flex justify-between items-start bg-white p-4 rounded-xl border border-[#D9D9D9] animate__animated animate__fadeInUp"
                style="animation-delay: 0.15s;">
                <div>
                    <div class="flex items-center gap-2 px-3 rounded-lg bg-[#CFF7D3]">
                        <p class="text-[#14AE5C] text-4xl">•</p>
                        <p class="font-semibold text-[#1E1E1E]">Total Deal Leads</p>
                    </div>
                    <p class="mt-auto text-2xl font-bold pt-3 text-black">
                        <span id="summary-deal-total">{{ $leadCounts['deal'] }}</span>
                    </p>
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
                class="bg-white lg:grid lg:grid-cols-[3fr_1fr] border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">

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
                        <input type="text" placeholder="Search"
                            class="searchInput w-full px-3 py-1 border-none focus:outline-[#115640] " />
                    </div>
                    <div class=" bg-[#115640] rounded-lg flex justify-center items-center">
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

                {{-- SEARCH AND FILTERS --}}
                <div class="lg:grid lg:grid-cols-[1fr_4fr] gap-4 max-lg:hidden">
                    {{-- SEARCH TABLES --}}
                    <div class="border border-gray-300 rounded-lg lg:flex! items-center p-2 hidden h-full">
                        <div class="px-2">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z"
                                    fill="#6B7786" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Search"
                            class="searchInput w-full px-3 py-1 border-none focus:outline-[#115640] " />
                    </div>

                    {{-- FILTERS MENUS --}}
                    <div class="grid grid-cols-5 items-center border border-gray-300 rounded-lg text-[#1E1E1E] max-lg:text-xs! h-full">

                        {{-- BRANCHES --}}
                        <div class="flex items-center justify-center gap-2 border-r border-[#D9D9D9] cursor-pointer py-2 h-full px-2">
                            <select id="branch-filter-new"
                                class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" data-label="{{ $branch->name }}">
                                    {{ $branch->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- SALES --}}
                        <div class="flex items-center justify-center gap-2 border-r border-[#D9D9D9] cursor-pointer py-2 h-full px-2">
                            <select id="sales-filter-new"
                                class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Sales</option>
                            @foreach($sales as $salesUser)
                                <option value="{{ $salesUser->id }}" data-branch-id="{{ $salesUser->branch_id }}" data-label="{{ $salesUser->name }}">
                                    {{ $salesUser->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- SOURCES --}}
                        <div id="filterSources" class="flex items-center justify-center gap-2 border-r border-[#D9D9D9] cursor-pointer py-2 h-full px-2">
                            <select id="source-filter-new"
                            class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Source</option>
                            @foreach($leadSources as $source)
                                <option value="{{ $source->id }}">{{ $source->name }}</option>
                            @endforeach
                            </select>
                        </div>

                        {{-- DATES --}}
                        <div class="cursor-pointer w-full relative grid grid-cols-1 items-center h-full border-r border-[#D9D9D9]">

                            {{-- TOGGLE --}}
                            <div id="openDateDropdown" class="flex justify-center items-center gap-2">
                                <p id="dateLabel" class="font-medium text-black">Date</p>
                                <i id="iconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                            </div>

                            {{-- DATE DROPDOWN --}}
                            <div id="dateDropdown" class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

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
                        <div id="resetFilter" class="flex items-center justify-center gap-2 py-2 cursor-pointer h-full">
                            <i id="chevronFiltersReset" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                            <p class="font-medium text-[#900B09]">Reset Filter</p>
                        </div>
                    </div>
                </div>

                {{-- NAVIGATION STAGE AND ADD MANUAL LEADS --}}
                <div class="lg:grid lg:grid-cols-1 gap-4 max-lg:hidden">

                    {{-- NAVIGATION STAGE TABLES --}}
                    <div class="border border-[#D5D5D5] rounded-lg grid grid-cols-3 h-full">
                        @foreach (['warm', 'hot', 'deal'] as $tab)
                        {{-- NAVIGATION STATUS --}}
                        <div data-status="{{ $tab }}"
                            class="text-center cursor-pointer py-2 h-full border-r border-r-[#D5D5D5] nav-leads-active flex items-center justify-center">
                            <p class="text-[#083224]">
                                {{ ucfirst($tab) }}
                                <span id="nav-count-{{ $tab }}" class="{{ 
                                                $tab === 'warm' 
                                                    ? 'span-warm' 
                                                    : ($tab == 'hot'
                                                        ? 'span-hot'
                                                        : 'span-deal'
                                                        )
                                                }}">
                                    {{ $leadCounts[$tab] }}
                                </span>
                            </p>
                        </div>
                        @endforeach
                    </div>

                </div>

            </div>

            {{-- CONTENTS TABLES --}}
            @foreach(['warm', 'hot', 'deal'] as $tab)
                <div data-status-wrapper="{{ $tab }}" class="leads-table-container {{ $loop->first ? '' : 'hidden' }}">
                    <div class="max-xl:overflow-x-scroll">
                        <table id="{{ $tab }}LeadsTableNew" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                            {{-- HEADER TABLE --}}
                            <thead class="text-[#1E1E1E]">
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-1 lg:p-3">Claimed At</th>
                                    <th class="p-1 lg:p-3">Branch Name</th>
                                    <th class="p-1 lg:p-3">Sales Name</th>
                                    <th class="p-1 lg:p-3">Lead Name</th>
                                    <th class="p-1 lg:p-3">Telephone</th>
                                    <th class="p-1 lg:p-3">Source</th>
                                    <th class="p-1 lg:p-3">Needs</th>

                                    <th class="p-1 lg:p-3">Segment</th>

                                    <th class="p-1 lg:p-3">City</th>
                                    <th class="p-1 lg:p-3">Regional</th>
                                    <th class="p-1 lg:p-3">Province</th>

                                    @if ($tab === 'warm')
                                    <th class="p-1 lg:p-3">Status</th>
                                    @endif

                                    @if ($tab === 'hot')
                                    <th class="p-1 lg:p-3">Quotation Expire In</th>
                                    @endif

                                    <th class="p-1 lg:p-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="{{ $tab }}BodyTable"></tbody>
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
    @php
      $myRoutes = [];
      if(\Illuminate\Support\Facades\Route::has('leads.my.warm.list')) {
        $myRoutes['warm'] = route('leads.my.warm.list');
      }
      if(\Illuminate\Support\Facades\Route::has('leads.my.hot.list')) {
        $myRoutes['hot'] = route('leads.my.hot.list');
      }
      if(\Illuminate\Support\Facades\Route::has('leads.my.deal.list')) {
        $myRoutes['deal'] = route('leads.my.deal.list');
      }
    @endphp
    
    const myRoutes = @json($myRoutes);

    // LEADS
    const DEFAULT_PAGE_SIZE = 10;
    const pageState = { warm: 1, hot: 1, deal: 1 };
    const pageSizeState = { warm: DEFAULT_PAGE_SIZE, hot: DEFAULT_PAGE_SIZE, deal: DEFAULT_PAGE_SIZE };

    const totals = {
        warm : {{ $leadCounts['warm'] ?? 0}},
        hot : {{ $leadCounts['hot'] ?? 0}},
        deal : {{ $leadCounts['deal'] ?? 0}}
    };
    const summaryEndpoint = @json(url('/leads/my/summary'));

    let searchTimeout = null;
    let searchState = '';
    let activeTabState = 'warm';
    let branchSelected = '';
    let salesSelected = '';
    let sourceSelected = '';
    let filterStartDate = '';
    let filterEndDate = '';
    let fp = null;

    function resetAllPages() {
        Object.keys(pageState).forEach(key => {
            pageState[key] = 1;
        });
    }

    function getSearchQuery() {
        return searchState;
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value ?? 0;
        }
    }

    function buildFilterParams() {
        const params = new URLSearchParams();
        const searchQuery = getSearchQuery();

        if (searchQuery) {
            params.append('search', searchQuery);
        }
        if (sourceSelected) {
            params.append('sources', sourceSelected);
        }
        if (branchSelected) {
            params.append('branch_id', branchSelected);
        }
        if (salesSelected) {
            params.append('sales_id', salesSelected);
        }
        if (filterStartDate) {
            params.append('start_date', filterStartDate);
        }
        if (filterEndDate) {
            params.append('end_date', filterEndDate);
        }

        return params;
    }

    function updateBadgeCounts() {
        const navCounts = {
            warm: totals.warm || 0,
            hot: totals.hot || 0,
            deal: totals.deal || 0,
        };

        Object.entries(navCounts).forEach(([tab, value]) => {
            setText(`nav-count-${tab}`, value);
        });
    }

    function renderDealSummary(deal = {}) {
        setText('summary-deal-total', deal.total || 0);
    }

    function syncSalesOptionsWithBranch() {
        const salesSelect = document.getElementById('sales-filter-new');
        if (!salesSelect) return;

        Array.from(salesSelect.options).slice(1).forEach(option => {
            const optionBranchId = option.dataset.branchId || '';
            const isVisible = !branchSelected || optionBranchId === branchSelected;

            option.hidden = !isVisible;
            option.disabled = !isVisible;
        });

        const selectedOption = salesSelect.options[salesSelect.selectedIndex];
        if (selectedOption && selectedOption.disabled) {
            salesSelect.value = '';
            salesSelected = '';
        }
    }

    async function refreshDynamicCounts() {
        try {
            const params = buildFilterParams();
            const queryString = params.toString();
            const url = queryString ? `${summaryEndpoint}?${queryString}` : summaryEndpoint;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch summary (${response.status})`);
            }

            const result = await response.json();
            const leadCounts = result.leadCounts || {};

            totals.warm = leadCounts.warm || 0;
            totals.hot = leadCounts.hot || 0;
            totals.deal = leadCounts.deal || 0;

            updateBadgeCounts();
            renderWarmSummary(result.warm || {});
            renderHotSummary(result.hot || {});
            renderDealSummary(result.deal || {});
        } catch (error) {
            console.error('Summary fetch error:', error);
        }
    }

    function refreshActiveTabAndCounts() {
        reloadTab(activeTabState || 'warm');
        refreshDynamicCounts();
    }

    const searchInputs = document.querySelectorAll('.searchInput');
    searchInputs.forEach(input => {
        input.addEventListener('input', function () {
            clearTimeout(searchTimeout);

            const query = this.value;
            searchState = query.trim();
            searchInputs.forEach(el => {
                if (el !== this) el.value = query;
            });

            searchTimeout = setTimeout(() => {
                resetAllPages();

                const activeTab = activeTabState || 'warm';

                console.log(`Searching for: "${searchState}" on tab: ${activeTab}`);

                refreshActiveTabAndCounts();
            }, 500);
        });
    });

    function initSourceFilter() {
        const sourceSelect = document.getElementById('source-filter-new');
        if (!sourceSelect) return;

        sourceSelected = sourceSelect.value || '';

        sourceSelect.addEventListener('change', function () {
            sourceSelected = this.value || '';
            resetAllPages();
            refreshActiveTabAndCounts();
        });
    }

    function initBranchSalesFilters() {
        const branchSelect = document.getElementById('branch-filter-new');
        const salesSelect = document.getElementById('sales-filter-new');

        if (branchSelect) {
            branchSelected = branchSelect.value || '';
            branchSelect.addEventListener('change', function () {
                branchSelected = this.value || '';
                syncSalesOptionsWithBranch();
                resetAllPages();
                refreshActiveTabAndCounts();
            });
        }

        if (salesSelect) {
            salesSelected = salesSelect.value || '';
            salesSelect.addEventListener('change', function () {
                salesSelected = this.value || '';
                resetAllPages();
                refreshActiveTabAndCounts();
            });
        }

        syncSalesOptionsWithBranch();
    }

    function initFlatpickr() {
        const input = document.getElementById('source-date-range');
        if (input && typeof flatpickr !== 'undefined') {
            fp = flatpickr(input, {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d',
                onClose: function () {
                    // keep input populated; apply via Apply button
                }
            });
        }
    }

    function filterDate() {
        const openBtn = document.getElementById('openDateDropdown');
        const dropdown = document.getElementById('dateDropdown');
        const chevron = document.getElementById('iconDate');
        const label = document.getElementById('dateLabel');

        function closeDropdown() {
            if (dropdown) {
                dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            }
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }

        if (openBtn) {
            openBtn.onclick = () => {
                if (dropdown) {
                    dropdown.classList.toggle('opacity-0');
                    dropdown.classList.toggle('scale-95');
                    dropdown.classList.toggle('pointer-events-none');
                }
                if (chevron) chevron.classList.toggle('rotate-180');
                if (fp) fp.open();
            };
        }

        const cancelBtn = document.getElementById('cancelDate');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                closeDropdown();
            });
        }

        const applyBtn = document.getElementById('applyDate');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                const dates = (fp && fp.selectedDates) ? fp.selectedDates : [];
                if (dates.length !== 2) return;

                filterStartDate = fp.formatDate(dates[0], 'Y-m-d');
                filterEndDate = fp.formatDate(dates[1], 'Y-m-d');

                if (label) {
                    label.innerText = `${filterStartDate} -> ${filterEndDate}`;
                }

                resetAllPages();
                refreshActiveTabAndCounts();
                closeDropdown();
            });
        }
    }

    function resetFilter() {
        clearTimeout(searchTimeout);
        searchState = '';
        sourceSelected = '';
        branchSelected = '';
        salesSelected = '';
        filterStartDate = '';
        filterEndDate = '';

        $('.searchInput').val('');
        $('#branch-filter-new').val('');
        $('#sales-filter-new').val('');
        $('#source-filter-new').val('');
        $('#dateLabel').text('Date');
        $('#source-date-range').val('');
        $('#dateDropdown').addClass('opacity-0 scale-95 pointer-events-none');
        $('#iconDate').removeClass('rotate-180');

        if (fp) {
            fp.clear();
        }

        syncSalesOptionsWithBranch();
        resetAllPages();
        refreshActiveTabAndCounts();
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

        pageState[tab] = (pageState[tab] || 1) + 1;
        reloadTab(tab);
    }

    function reloadTab(tab) {
        if (myRoutes[tab]) {
            initTable(tab, myRoutes[tab]);
        } else {
            console.warn('No route defined for ' + tab);
        }
    }

    function initTailwindTable(tab){
      if(!myRoutes[tab]){
        console.warn('No route defined for '+tab);
        return;
      }

      initTable(tab, myRoutes[tab]);
    }

    document.addEventListener("DOMContentLoaded", function() {
        initSourceFilter();
        initBranchSalesFilters();
        initFlatpickr();
        filterDate();
        $('#resetFilter').on('click', resetFilter);

        initTable('warm', `${myRoutes['warm']}`);
        initTable('hot', `${myRoutes['hot']}`);
        initTable('deal', `${myRoutes['deal']}`);

        const sections = {
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

            const selected = $(this).data("status");

            $.each(sections, function (key, value) {
                value.addClass("hidden");
            });

            sections[selected].removeClass("hidden");

            resetAnimation(sections[selected]);

        });

        const navTabs = document.querySelectorAll('.nav-leads-active');
        const tableWrappers = document.querySelectorAll('[data-status-wrapper]');

        function switchTab(statusTarget) {
            activeTabState = statusTarget;
            
            tableWrappers.forEach(wrapper => {
                wrapper.classList.add('hidden');
            });

            const targetWrapper = document.querySelector(`[data-status-wrapper="${statusTarget}"]`);
            if (targetWrapper) {
                targetWrapper.classList.remove('hidden');
            }

            // ===== NAV ACTIVE STYLE =====
            navTabs.forEach(tab => {
                if (tab.getAttribute('data-status') === statusTarget) {
                    tab.classList.add('border-b-2', 'border-b-[#115640]', 'text-white'); 
                } else {
                    tab.classList.remove('border-b-2', 'border-b-[#115640]', 'text-white');
                }
            });

            // ===== CARDS SWITCH =====
            const allCards = ['forWarmCardsCounts', 'forHotCardsCounts', 'forDealCardsCounts'];

            // hide semua dulu
            allCards.forEach(id => {
                document.getElementById(id)?.classList.add('hidden');
            });

            // mapping status ke card
            const map = {
                warm: 'forWarmCardsCounts',
                hot: 'forHotCardsCounts',
                deal: 'forDealCardsCounts'
            };

            const targetCard = map[statusTarget?.toLowerCase()];

            if (targetCard) {
                document.getElementById(targetCard)?.classList.remove('hidden');
            }
        }

        switchTab('warm');

        navTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const statusTarget = this.getAttribute('data-status');
                switchTab(statusTarget);

                if (myRoutes[statusTarget]) {
                    reloadTab(statusTarget);
                }
            });
        });
    });


    async function initTable(selector, route) {
        const page = pageState[selector] || 1;
        const perPage = pageSizeState[selector] || DEFAULT_PAGE_SIZE;
        const params = buildFilterParams();
        params.set('page', page);
        params.set('per_page', perPage);

        const tbody = document.getElementById(`${selector}BodyTable`);

        try {
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12" class="text-center py-3 text-[#1E1E1E]">
                            Loading data...
                        </td>
                    </tr>
                `;
            }

            const response = await fetch(`${route}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch table data (${response.status})`);
            }

            const result = await response.json();
            totals[selector] = result.total || 0;

            updatePagerUI(selector, result.total);
            tbody.innerHTML = '';

            if (result.data && result.data.length > 0) {
                result.data.forEach(row => {
                    let statusContent = '';

                    if (selector === 'warm') {
                        statusContent = row.meeting_status ?? '-';
                    } else {
                        const d = row.expire_in;
    
                        if (d === null || d === undefined) {
                            statusContent = '-';
                        } 
                        else if (d > 7) {
                            statusContent = `<span class="span-deal rounded-sm font-normal! inline-block">${d} Days left</span>`;
                        } 
                        else if (d > 2) {
                            statusContent = `<span class="span-warm rounded-sm font-normal! inline-block">${d} Days left</span>`;
                        } 
                        else if (d === 1) {
                            statusContent = `<span class="span-hot rounded-sm font-normal! inline-block">Tomorrow</span>`;
                        } 
                        else if (d === 0) {
                            statusContent = `<span class="span-hot rounded-sm font-normal! inline-block">Today</span>`;
                        } 
                        else {
                            statusContent = `<span class="span-hot rounded-sm font-normal! inline-block">Expired</span>`;
                        }                    
                    }
    
                    tbody.innerHTML += `
                        <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                            <td class="hidden">${row.id}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.claimed_at}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.branch?.name ?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.sales?.name ?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.name}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.phone ?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.source?.name ?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.needs?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.segment?.name ?? row.lead?.customer_type ?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.region?.name ?? row.lead?.alternate_location?.region_name ?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.region?.regional?.name ?? row.lead?.alternate_location?.regional_name ?? 'Not Set'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.lead?.province ?? row.lead?.alternate_location?.province_name ?? 'Not Set'}</td>
    
                            ${selector !== 'deal' ? `
                                <td class="p-1 md:p-2 lg:p-3">
                                    <span>${statusContent || '-'}</span>
                                </td>
                            ` : ''}
                            
                            <td class="text-center p-1 md:p-2 lg:p-3">${row.actions}</td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center p-3 text-[#1E1E1E]">No data available</td></tr>`;
            }

        } catch (error) {
            console.error("Fetch error:", error);
        }
    }

    $(document).ready(function () {
        updateBadgeCounts();
        renderDealSummary({ total: totals.deal });
        refreshDynamicCounts();
    });

    function renderWarmSummary(warm) {
        // TOTAL WARM
        $("#summary-warm-total").text(warm.total);
        $("#summary-warm-no-quotation").text(`( ${warm.no_quotation} No Quotation )`);

        // PENDING & REJECTED
        $("#summary-warm-total-pending").text(warm.approval_status);
        $("#summary-warm-pending-count").text(`( ${warm.pending} Pending & `);
        $("#summary-warm-rejected-count").text(`${warm.rejected} Rejected )`);
        
        // QUOTATIONS
        $("#summary-warm-quotations").text(`${warm.quotation_published}`);
    }

    function renderHotSummary(hot) {
        // TOTAL HOT
        $("#summary-hot-total").text(hot.total);
        $("#summary-hot-expire-7-days").text(hot.expiring_7_days);
        $("#summary-hot-expire-8-days-more").text(hot.expiring_8_plus_days);
    }

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
                    refreshActiveTabAndCounts();
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

                if (res && res.moved_to_trash) {
                    if (typeof resetAllPages === 'function') resetAllPages();
                    ['warm', 'hot', 'deal'].forEach(function(tab) {
                        if (typeof reloadTab === 'function' && myRoutes[tab]) {
                            reloadTab(tab);
                        }
                    });
                    refreshDynamicCounts();
                } else {
                    refreshActiveTabAndCounts();
                }
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
                    '<td class="lg:p-3 p-1">' + item.logged_at + '</td>' +
                    '<td class="lg:p-3 p-1">' + item.action + '</td>' +
                    '<td class="lg:p-3 p-1">' + item.user + '</td>' +
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
                    resetAllPages();
                    ['warm', 'hot', 'deal'].forEach(function(tab) {
                        if (myRoutes[tab]) {
                            reloadTab(tab);
                        }
                    });
                    refreshDynamicCounts();
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
