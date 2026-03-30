@extends('layouts.app')

@section('content')
    <section class="min-h-screen">
        <div class="flex items-center gap-2 text-[#115640] pt-4">
            <x-icon.production/>
            <h1 class="font-semibold lg:text-2xl text-lg">Purchasing Log</h1>
        </div>

        {{-- FOR ALL STAGE --}}
        <div id="forAllCardsCounts" class="grid max-xl:grid-cols-2 grid-cols-3 gap-3 mt-4">
            {{-- INVOICE RECEIVED --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Invoice Received</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.task/>
                    </div>
                </div>

                <div>
                    <p id="countInvoiceReceived" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Invoice Received</p>
                </div>
            </div>

            {{-- VENDOR PROCESSING --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.15s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Vendor Processing</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.refresh/>
                    </div>
                </div>

                <div>
                    <p id="countVendorProcessing" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Vendor Processing</p>
                </div>
            </div>

            {{-- READY FOR HANDOVER --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.30s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Ready For Handover</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.package/>
                    </div>
                </div>

                <div>
                    <p id="countReadyForHandover" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Ready For Handover</p>
                </div>
            </div>

            {{-- COMPLETED --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.40s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Completed</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.check/>
                    </div>
                </div>

                <div>
                    <p id="countAllCompleted" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Completed</p>
                </div>
            </div>

            {{-- PENDING --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.55s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Pending</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#E8B931]">
                        <x-icon.clock/>
                    </div>
                </div>

                <div>
                    <p id="countAllPending" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Pendings</p>
                </div>
            </div>

            {{-- CANCELED --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.65s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Canceled</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#EC221F]">
                        <x-icon.cross/>
                    </div>
                </div>

                <div>
                    <p id="countAllCanceled" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Canceled</p>
                </div>
            </div>

        </div>

        {{-- STAGE INVOICE RECEIVED CARDS --}}
        <div id="forInvoiceReceivedCardsCounts" class="grid grid-cols-3 gap-3 mt-4">
            
            {{-- WAITING COUNT CARDS --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Waiting</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.clock/>
                    </div>
                </div>

                <div>
                    <p id="countWaitingList" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Waiting List</p>
                </div>
            </div>

            {{-- ACCEPTED COUNT CARDS --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.35s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Accepted</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.check/>
                    </div>
                </div>

                <div>
                    <p id="countAcceptedList" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Accepted</p>
                </div>
            </div>

            {{-- ON PROGRESS COUNT CARDS --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.70s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">In progress Production</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.refresh/>
                    </div>
                </div>

                <div>
                    <p id="countOnProgressList" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 On Progress Production</p>
                </div>
            </div>
        </div>

        {{-- STAGE VENDOR PROCESSING --}}
        <div id="forVendorProcessingCardsCounts" class="grid grid-cols-2 2xl:grid-cols-4 gap-3 mt-4">
            
            {{-- PERCENTAGE PRODUCTION --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">In Production</h1>
                    
                <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.bar/>
                    </div>
                </div>

                <div class="mb-5">
                    <p id="countOnProduction" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 On Production</p>
                </div>

                <div class="flex items-center justify-start gap-1 text-xs">
                    {{-- COUNTS FOR 50 PERCENTS --}}
                    <div class="flex items-center gap-1 p-1 border border-[#D9D9D9] rounded-lg bg-white">
                        <span id="fiftyPercentsCounts">0</span>
                        <span class="text-[#EC221F] font-semibold">50%</span>
                    </div>

                    {{-- COUNTS FOR 75 PERCENTS --}}
                    <div class="flex items-center gap-1 p-1 border border-[#D9D9D9]  rounded-lg bg-white">
                        <span id="seventyPercentsCounts">0</span>
                        <span class="text-[#E8B931] font-semibold">70%</span>
                    </div>

                    {{-- COUNTS FOR 100 PERCENTS --}}
                    <div class="flex items-center gap-1 p-1 border border-[#D9D9D9]  rounded-lg bg-white">
                        <span id="hundredPercentsCounts">0</span>
                        <span class="text-[#417866] font-semibold">100%</span>
                    </div>
                </div>
            </div>

            {{-- RUNNING TEST --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.10s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Running Test</h1>
                    
                    <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.square-check/>
                    </div>
                </div>

                <div>
                    <p id="countRunningTestList" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Running Tested</p>
                </div>
            </div>

            {{--  MACHINE COMPLETED --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.25s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Machine Completed</h1>
                    
                    <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.circle-check/>
                    </div>
                </div>

                <div>
                    <p id="countMachineCompletedList" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Machine Completed</p>
                </div>
            </div>

            {{-- DOCUMENT REGISTRATION --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.35s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Document Registration</h1>
                    
                    <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.task/>
                    </div>
                </div>

                <div>
                    <p id="countDocumentRegisterList" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Document Registered</p>
                </div>
            </div>

            {{-- DELIVERY --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.50s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Delivery</h1>
                    
                    <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.package/>
                    </div>
                </div>

                <div class="mb-5">
                    <p id="countTotalDelivery" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Total Delivery</p>
                </div>

                <span id="countWaitingDelivery" class="text-xs block">0 Waiting To Delivery</span>
                <span id="countOnDelivery" class="text-xs block">0 On Delivery to Indonesia</span>
                <span id="countArrivedDelivery" class="text-xs block">0 Arrived in Indonesia</span>
            </div>

            {{-- DELIVERY TO CUSTOMER --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.60s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Delivery Customer</h1>
                    
                    <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.package/>
                    </div>
                </div>

                <div>
                    <p id="countDeliveryCustomer" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Delivery Customer</p>
                </div>
            </div>

            {{-- ON PROGRESS INSTEAD --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.75s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">On Progress Install</h1>
                    
                    <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.refresh/>
                    </div>
                </div>

                <div>
                    <p id="countProgressInstall" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Progress Install</p>
                </div>
            </div>

            {{--  RUNNING TEST FINAL --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.85s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Running Test Final</h1>
                    
                    <div class="p-2 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.square-check/>
                    </div>
                </div>

                <div>
                    <p id="countRunningTestFinal" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Running Test Final</p>
                </div>
            </div>
        </div>

        {{-- READY FOR HANDOVER --}}
        <div id="forReadyHandoverCardsCounts" class="grid grid-cols-1 mt-4">

            {{--  BAST (Bukti Acara Serah Terima) --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">BAST</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.handshake/>
                    </div>
                </div>

                <div>
                    <p id="countBAST" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 BAST</p>
                </div>
            </div>
        </div>

        {{-- COMPLETED --}}
        <div id="forCompletedCardsCounts" class="grid grid-cols-1 mt-4">

            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Completed</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                        <x-icon.check/>
                    </div>
                </div>

                <div>
                    <p id="countCompletedOnly" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Completed</p>
                </div>
            </div>
        </div>

        {{-- PENDING --}}
        <div id="forPendingCardsCounts" class="grid grid-cols-1 mt-4">

            {{-- PENDING --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Pending</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#E8B931]">
                        <x-icon.clock/>
                    </div>
                </div>

                <div>
                    <p id="countPendingOnly" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Pending</p>
                </div>
            </div>

        </div>

        {{-- CANCELED  --}}
        <div id="forCancelCardsCounts" class="grid grid-cols-1 mt-4">

            {{-- CANCELED --}}
            <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.30s;">
                <div class="flex justify-between items-center">
                    
                    <h1 class="text-[#757575] font-semibold">Canceled</h1>
                    
                    <div class="p-3 border border-[#D9D9D9] rounded-md text-[#EC221F]">
                        <x-icon.cross/>
                    </div>
                </div>

                <div>
                    <p id="countCanceledOnly" class="2xl:text-xl text-lg font-semibold text-[#1E1E1E]">0 Canceled</p>
                </div>
            </div>

        </div>

        {{-- TABLES CONTENTS --}}
        <div class="mt-4 rounded-lg border-[#D9D9D9]">
            {{-- NAVIGATION TABLES --}}
            <div
                class="bg-white items-stretch lg:flex justify-between border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">

                {{-- SEARCH TABLES --}}
                <div class="border border-gray-300 rounded-lg flex items-center gap-3 px-2 lg:hidden">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z"
                            fill="#6B7786" />
                    </svg>
                    <input type="text" placeholder="Search"
                        class="searchInput w-full px-3 py-3 border-none focus:outline-[#115640] " />
                </div>
                <div class="lg:w-1/6! border border-gray-300 rounded-lg lg:flex! items-center hidden">
                    <div class="px-2">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z"
                                fill="#6B7786" />
                        </svg>
                    </div>
                    <input type="text" placeholder="Search"
                        class="searchInput w-full px-3 py-3 border-none focus:outline-[#115640]" />
                </div>

                {{-- NAVIGATION STATUS TABLES --}}
                <div class="lg:w-5/6! border border-[#D5D5D5] rounded-lg grid grid-cols-7">
                    @foreach(['all', 'invoiceReceived', 'vendorProcessing', 'readyForHandover', 'completed', 'pending', 'canceled'] as $tab)
                    {{-- NAVIGATION STATUS --}}
                    <div data-status="{{ $tab }}"
                        class="max-lg:text-sm text-center cursor-pointer py-2 h-full border-r border-r-[#D5D5D5] nav-purchase">
                        <p class="text-[#1E1E1E]">
                            {{ $loop->first ? 'All Stage' : ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', $tab)) }}

                        </p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- CONTENTS TABLES --}}
            @foreach(['all', 'invoiceReceived', 'vendorProcessing', 'readyForHandover', 'completed', 'pending', 'canceled'] as $tab)
            <div data-status-wrapper="{{ $tab }}" class="purchasing-table-container {{ $loop->first ? '' : 'hidden' }}">
                <div class="max-xl:overflow-x-scroll">
                    <table id="{{ $tab }}PurchasingTable" class="w-full bg-white rounded-br-lg rounded-bl-lg]">
                        {{-- HEADER TABLE --}}
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="p-1 lg:p-3">Created At</th>
                                <th class="p-1 lg:p-3">Lead Name</th>
                                <th class="p-1 lg:p-3">Company</th>
                                <th class="p-1 lg:p-3">Phone</th>
                                <th class="p-1 lg:p-3">Customer Type</th>
                                <th class="p-1 lg:p-3">Needs</th>
                                <th class="p-1 lg:p-3">Tonase</th>
                                @if ($tab === 'all')
                                    <th class="p-1 lg:p-3">Stage</th>
                                @endif
                                <th class="p-1 lg:p-3">Status</th>
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
@endsection

@section('scripts')
    <script>

    const DEFAULT_PAGE_SIZE = 10;

    const pageState = { 
        all: 1,
        invoiceReceived: 1,
        vendorProcessing: 1,
        readyForHandover: 1,
        completed: 1,
        pending: 1,
        canceled: 1
    };
    
    const pageSizeState = { 
        all: DEFAULT_PAGE_SIZE, 
        invoiceReceived: DEFAULT_PAGE_SIZE, 
        vendorProcessing: DEFAULT_PAGE_SIZE, 
        readyForHandover: DEFAULT_PAGE_SIZE, 
        completed: DEFAULT_PAGE_SIZE, 
        pending: DEFAULT_PAGE_SIZE, 
        canceled: DEFAULT_PAGE_SIZE };
    
    const totals = {};

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

    function reloadTab(tab, page = 1) {
        pageState[tab] = page;

        if (tab === 'all') loadAll();
        else if (tab === 'invoiceReceived') loadInvoiceReceived();
        else if (tab === 'vendorProcessing') loadVendorProcessing();
        else if (tab === 'readyForHandover') loadHandover();
        else if (tab === 'completed') loadCompleted();
        else if (tab === 'pending') loadPending();
        else if (tab === 'canceled') loadCanceled();
    }

    // LOAD API EVERY TAB/STAGE NAVIGATION 
    @include('pages.purchasing.script-load-api');

    async function loadGrid() {
        try {
            const response = await fetch("api/purchasing/summary");

            if(!response.ok) {
                throw new Error("Network response was not ok");
            }

            const result = await response.json();

            if (result.status !== "success") {
                throw new Error("API returned failed status");
            }

            const data = result.Data;

            // FOR LOAD ALL STAGE 
            // (#forAllCardsCounts)
            $("#countInvoiceReceived").text(data.invoice_received.total + " Invoice Received");
            $("#countVendorProcessing").text(data.vendor_processing.total + " Vendor Processing");
            $("#countReadyForHandover").text(data.handover.total + " Ready For Handover");
            $("#countAllCompleted").text(data.completed.total + " Completed");
            $("#countAllPending").text(data.pending.total + " Pending");
            $("#countAllCanceled").text(data.canceled.total + " Canceled");

            // FOR LOAD INVOICE RECEIVED STAGE
            // (#forInvoiceReceivedCardsCounts)
            $("#countWaitingList").text(data.invoice_received.waiting + " Waiting List");
            $("#countAcceptedList").text(data.invoice_received.accepted + " Accepted");
            $("#countOnProgressList").text(data.invoice_received.on_progress_production + " On Progress Production");

            // FOR VENDOR PROCESSING
            // (#forVendorProcessingCardsCounts)
            const totalOnProduction = data.vendor_processing.half_pct + data.vendor_processing.almst_pct + data.vendor_processing.full_pct

            $("#countOnProduction").text(totalOnProduction + " On Production");
            $("#fiftyPercentsCounts").text(data.vendor_processing.half_pct);
            $("#seventyPercentsCounts").text(data.vendor_processing.almst_pct);
            $("#hundredPercentsCounts").text(data.vendor_processing.full_pct);
            $("#countRunningTestList").text(data.vendor_processing.running_test + " Running Test");
            $("#countDocumentRegisterList").text(data.vendor_processing.document_registration + " Document Registration");

            const totalOnDelivery = 
                data.vendor_processing.waiting_to_deliver +
                data.vendor_processing.delivery_to_indonesia +
                data.vendor_processing.arrived_in_indonesia; 

            $("#countTotalDelivery").text(totalOnDelivery + " Total Delivery");
            $("#countWaitingDelivery").text(data.vendor_processing.waiting_to_deliver + " Waiting To Delivery");
            $("#countOnDelivery").text(data.vendor_processing.delivery_to_indonesia + " On Delivery To Indonesia");
            $("#countArrivedDelivery").text(data.vendor_processing.arrived_in_indonesia + " Arrived In Indonesia");
            
            $("#countDeliveryCustomer").text(data.vendor_processing.delivery_to_customer + " Delivery Customer");
            $("#countProgressInstall").text(data.vendor_processing.on_progress_install + " Progress Install");
            $("#countRunningTestFinal").text(data.vendor_processing.on_progress_install + " Running Test Final");
            
            $("#countBAST").text(data.handover.bast + " BAST");
            $("#countCompletedOnly").text(data.completed.total + " Completed");
            $("#countPendingOnly").text(data.pending.total + " Pending");
            $("#countCanceledOnly").text(data.canceled.total + " Cancel");



        } catch (error) {
            console.error("Error loading:", error);
        }
    }
    
    function getSearchValue() {
        let value = '';

        $('.searchInput').each(function () {
            const currentVal = $(this).val().trim();
            if (currentVal !== '') {
                value = currentVal;
                return false;
            }
        });

        return value;
    }
    

    $(document).on('input', '.searchInput', function () {
        const value = $(this).val();

        $('.searchInput').not(this).val(value);
    });

    let searchTimer = null;

    $(document).on('input', '.searchInput', function () {
        const value = $(this).val();

        $('.searchInput').not(this).val(value);

        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            pageState.all = 1;
            pageState.invoiceReceived = 1;
            pageState.vendorProcessing = 1;
            pageState.readyForHandover = 1;
            pageState.completed = 1;
            pageState.pending = 1;
            pageState.canceled = 1;

            const activeTab = $('.nav-purchase.active-nav').data('status') || 'all';

            reloadTab(activeTab, 1);
        }, 500);
    });

    document.addEventListener('DOMContentLoaded', function () {
        const sections = {
            all: $("#forAllCardsCounts"),
            invoiceReceived: $("#forInvoiceReceivedCardsCounts"),
            vendorProcessing: $("#forVendorProcessingCardsCounts"),
            readyForHandover: $("#forReadyHandoverCardsCounts"),
            completed: $("#forCompletedCardsCounts"),
            pending: $("#forPendingCardsCounts"),
            canceled: $("#forCancelCardsCounts"),
        };

        function resetAnimation($container) {
            $container.find(".animate__animated").each(function () {
                $(this)
                    .removeClass("animate__fadeInUp")
                    .width(); // trigger reflow
                $(this).addClass("animate__fadeInUp");
            });
        }

        function showSection(status) {
            $.each(sections, function (_, $section) {
                $section.addClass('hidden');
            });

            if (sections[status] && sections[status].length) {
                sections[status].removeClass('hidden');
                resetAnimation(sections[status]);
            }
        }

        $('.nav-purchase').on('click', function () {
            const status = $(this).data('status');

            $('.purchasing-table-container').addClass('hidden');
            $(`[data-status-wrapper='${status}']`).removeClass('hidden');

            $('.nav-purchase').removeClass('active-nav');
            $(this).addClass('active-nav');

            reloadTab(status);
            showSection(status);
        });

        $('.nav-purchase[data-status="all"]').trigger('click');

        loadGrid();        
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
        .nav-purchase {
            border-bottom: 4px solid transparent;
        }

        .nav-purchase.active-nav {
            border-bottom: 4px solid #115640;
            color: white;
        }

        .purchasing-table-container {
            display: block;
        }

        .bi-three-dots::before {
            -webkit-text-stroke: 0.6px;
        }
    </style>
@endsection