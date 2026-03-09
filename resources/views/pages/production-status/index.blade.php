@extends('layouts.app')

@section('content')
<section class="min-h-screen">
    <div class="flex items-center gap-2 text-[#115640] pt-4">
        <x-icon.production/>
        <h1 class="font-semibold lg:text-2xl text-lg">Production Status</h1>
    </div>

    {{-- INVOICE RECEIVED CARDS --}}
    <div id="forInvoiceReceivedCardsCounts" class="grid grid-cols-1 xl:grid-cols-3 gap-3 mt-4">
        
        {{-- WAITING COUNT CARDS --}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Waiting</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.clock/>
                </div>
            </div>

            <div>
                <p id="countWaitingList" class="text-2xl font-semibold text-[#1E1E1E]">0 Waiting List</p>
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
                <p id="countAcceptedList" class="text-2xl font-semibold text-[#1E1E1E]">0 Accepted</p>
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
                <p id="countOnProgressList" class="text-2xl font-semibold text-[#1E1E1E]">0 In Progress Production</p>
            </div>
        </div>
    </div>

    {{-- VENDOR PROCESSING --}}
    <div id="forVendorProcessing" class="grid grid-cols-1 xl:grid-cols-3 gap-3 mt-4">
        
        {{-- PERCENTAGE PRODUCTION --}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0s;">
            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">In Production</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.bar/>
                </div>
            </div>

            <div>
                <p id="countOnProduction" class="text-2xl font-semibold text-[#1E1E1E]">0 In Production</p>
            </div>

            <div class="flex items-center justify-start gap-3 mt-3">
                {{-- COUNTS FOR 50 PERCENTS --}}
                <div class="flex items-center gap-1 p-2 border border-[#D9D9D9]  rounded-lg bg-white">
                    <span id="fiftyPercentsCounts">0</span>
                    <span class="text-[#EC221F] font-semibold">50%</span>
                </div>

                {{-- COUNTS FOR 75 PERCENTS --}}
                <div class="flex items-center gap-1 p-2 border border-[#D9D9D9]  rounded-lg bg-white">
                    <span id="seventyPercentsCounts">0</span>
                    <span class="text-[#E8B931] font-semibold">70%</span>
                </div>

                {{-- COUNTS FOR 100 PERCENTS --}}
                <div class="flex items-center gap-1 p-2 border border-[#D9D9D9]  rounded-lg bg-white">
                    <span id="hundredPercentsCounts">0</span>
                    <span class="text-[#417866] font-semibold">100%</span>
                </div>
            </div>
        </div>

        {{-- RUNNING TEST --}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.35s;">
            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Running Test</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.square-check/>
                </div>
            </div>

            <div>
                <p id="countRunningTestList" class="text-2xl font-semibold text-[#1E1E1E]">0 Running Test</p>
            </div>
        </div>

        {{--  MACHINE COMPLETED --}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg animate__animated animate__fadeInUp" style="animation-delay: 0.70s;">
            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Machine Completed</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.square-check/>
                </div>
            </div>

            <div>
                <p id="countMachineCompletedList" class="text-2xl font-semibold text-[#1E1E1E]">0 Machine Completed</p>
            </div>
        </div>
    </div>
</section>
@endsection