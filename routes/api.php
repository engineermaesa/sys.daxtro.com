<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Leads\LeadActivityController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Leads\ImportLeadController;
use App\Http\Controllers\Leads\ColdLeadController;
use App\Http\Controllers\Leads\WarmLeadController;
use App\Http\Controllers\Leads\HotLeadController;
use App\Http\Controllers\Leads\DealLeadController;
use App\Http\Controllers\Leads\MeetingController;

Route::post('leads/register', [LeadRegisterController::class, 'store'])->name('api.leads.register');
Route::get('leads/sources', [LeadRegisterController::class, 'sources'])->name('api.leads.sources');
Route::get('leads/segments', [LeadRegisterController::class, 'segments'])->name('api.leads.segments');
Route::get('leads/regions', [LeadRegisterController::class, 'regions'])->name('api.leads.regions');

Route::get('/dashboard/mkt5a', [DashboardController::class, 'mkt5a']);
Route::get('/dashboard/sales-segment-performance', [DashboardController::class, 'salesSegmentPerformance']);
Route::get('/dashboard/regional-performance', [DashboardController::class, 'regionalPerformance']);
Route::get('/dashboard/source-conversion-stats', [DashboardController::class, 'sourceConversion']);
Route::get('/dashboard/source-monthly-stats', [DashboardController::class, 'sourceMonthlyStats']);
Route::get('/dashboard/dealing-list', [DashboardController::class, 'dealingList']);
Route::get('/dashboard/warm-hot-list', [DashboardController::class, 'warmHotList']);
Route::get('/dashboard/potential-dealing', [DashboardController::class, 'potentialDealing']);

// =====================================
// Authentication (API)
// =====================================
Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('api.login');
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('api.logout')->middleware('auth');


// =====================================
// LEADS (API)
// =====================================
Route::group([
    'prefix' => 'leads',
    'as' => '',
    'namespace' => 'App\\Http\\Controllers\\Leads',
    'middleware' => ['web'],
], function () {

    // FOR AVAILABLE LEADS (API)
    Route::prefix('available')->group(function () {

        Route::get('/', [LeadController::class, 'available'])
            ->name('api.leads.available');

        Route::post('/list', [LeadController::class, 'availableList'])
            ->name('api.leads.available.list');

        Route::get('/export', [LeadController::class, 'availableExport'])
            ->name('api.leads.available.export');

        Route::get('/form/{id?}', [LeadController::class, 'form'])
            ->name('api.leads.form');

        Route::post('/save/{id?}', [LeadController::class, 'save'])
            ->name('api.leads.save');
    });

    // FOR CLAIMING LEADS
    Route::post('/{id}/claim', 'LeadController@claim')->name('api.leads.claim');

    // FOR ACTIVITY LOGS
    Route::prefix('{id}/activity-logs')->group(function () {

        Route::get('/', [LeadActivityController::class, 'logs'])
            ->name('api.leads.activity.logs');

        Route::post('/', [LeadActivityController::class, 'save'])
            ->name('api.leads.activity.save');
    });

    // FOR MANAGE / ADMIN LEADS (API)
    Route::prefix('manage')->group(function () {

        Route::get('/', [LeadController::class, 'manage'])
            ->name('api.leads.manage');

        Route::post('/list', [LeadController::class, 'manageList'])
            ->name('api.leads.manage.list');

        Route::get('/counts', [LeadController::class, 'manageCounts'])
            ->name('api.leads.manage.counts');

        Route::get('/export', [LeadController::class, 'manageExport'])
            ->name('api.leads.manage.export');

        Route::get('/form/{id?}', [LeadController::class, 'form'])
            ->name('api.leads.manage.form');

        Route::delete('/delete/{id}', [LeadController::class, 'delete'])
            ->name('api.leads.manage.delete');
    });

    // FOR IMPORT (API)
    Route::prefix('import')->group(function () {
        Route::get('/', [ImportLeadController::class, 'index'])->name('api.leads.import');
        Route::get('/template', [ImportLeadController::class, 'template'])->name('api.leads.import.template');
        Route::post('/preview', [ImportLeadController::class, 'preview'])->name('api.leads.import.preview');
        Route::post('/submit', [ImportLeadController::class, 'store'])->name('api.leads.import.store');
    });

    // FOR MY LEADS (API)
    Route::prefix('my')->group(function () {
        Route::get('/', [LeadController::class, 'my'])->name('api.leads.my');
        Route::get('/form/{id?}', [LeadController::class, 'form'])->name('api.leads.my.form');

        Route::prefix('cold')->group(function () {
            Route::get('/list', [ColdLeadController::class, 'myColdList'])->name('api.leads.my.cold.list');

            Route::get('manage/form/{id?}', [LeadController::class, 'form'])->name('api.leads.my.cold.manage');

            Route::get('meeting/{claim}', [ColdLeadController::class, 'meeting'])->name('api.leads.my.cold.meeting');

            Route::get('meeting/{id}/reschedule', [ColdLeadController::class, 'reschedule'])->name('api.leads.my.cold.meeting.reschedule');

            Route::post('meeting/save/{id?}', [MeetingController::class, 'save'])->name('api.leads.my.cold.meeting.save');

            Route::get('meeting/{id}/result', [MeetingController::class, 'resultForm'])->name('api.leads.my.cold.meeting.result');

            Route::post('meeting/{id}/result', [MeetingController::class, 'result'])->name('api.leads.my.cold.meeting.result.save');

            Route::post('meeting/{id}/cancel', [MeetingController::class, 'cancel'])->name('api.leads.my.cold.meeting.cancel');

            Route::post('trash/{claim}', [ColdLeadController::class, 'trash'])->name('api.leads.my.cold.trash');
        });

        Route::prefix('warm')->group(function () {
            Route::get('/list', [WarmLeadController::class, 'myWarmList'])->name('api.leads.my.warm.list');

            Route::get('manage/form/{id?}', [LeadController::class, 'form'])->name('api.leads.my.warm.manage');

            Route::get('quotation/{claim}', [WarmLeadController::class, 'createQuotation'])->name('api.leads.my.warm.quotation.create');

            Route::post('quotation/{claim}', [WarmLeadController::class, 'storeQuotation'])->name('api.leads.my.warm.quotation.store');

            Route::post('trash/{claim}', [WarmLeadController::class, 'trash'])->name('api.leads.my.warm.trash');
        });

        Route::get('hot/list', [HotLeadController::class, 'myHotList'])->name('api.leads.my.hot.list');

        Route::get('deal/list', [DealLeadController::class, 'myDealList'])->name('api.leads.my.deal.list');

        Route::post('counts', [LeadController::class, 'myCounts'])->name('api.leads.my.counts');
    });
});

// =====================================
// TRASH-LEADS (API)
// =====================================

Route::group([
    'prefix' => 'trash-leads',
    'as' => 'trash-leads.',
    'namespace' => 'App\\Http\\Controllers\\Leads',
    'middleware' => ['web'],
], function () {
    Route::get('/', 'TrashLeadController@index')->name('index');
    Route::get('form/{id}', 'TrashLeadController@form')->name('form');
    Route::get('cold/list', 'TrashLeadController@coldList')->name('cold.list');
    Route::get('warm/list', 'TrashLeadController@warmList')->name('warm.list');
    Route::post('restore/{claim}', 'TrashLeadController@restore')->name('restore');
    Route::post('assign/{claim}', 'TrashLeadController@assign')->name('assign');
});

// =====================================
// ORDERS (API)
// =====================================
Route::group([
    'prefix' => 'orders',
    'as' => 'orders.',
    'namespace' => 'App\\Http\\Controllers\\Orders',
    'middleware' => ['api'],
], function () {
    Route::get('/', 'OrderController@index')->name('index');
    Route::post('/list', 'OrderController@list')->name('list');
    Route::post('/counts', 'OrderController@counts')->name('counts');
    Route::get('/export', 'OrderController@export')->name('export');
    Route::get('/{id}', 'OrderController@show')->name('show');
    Route::get('/{id}/progress', 'OrderProgressControll    er@form')->name('progress.form');
    Route::post('/{id}/progress', 'OrderProgressController@save')->name('progress.save');
    Route::get('/{id}/progress-logs', 'OrderProgressController@logs')->name('progress.logs');
    Route::get('/{id}/activity-logs', 'OrderController@activityLogs')->name('activity.logs');
    Route::post('/{order}/terms/{term}/request-proforma', 'OrderController@requestProforma')->name('terms.proforma.request');
    Route::post('/{order}/terms/{term}/request-invoice', 'OrderController@requestInvoice')->name('terms.invoice.request');
    Route::get('/file/{type}/{file}', 'OrderController@downloadFile')->name('file.download');
});

// =====================================
// FINANCE REQUEST (API)
// =====================================
Route::group([
    'prefix' => 'finance-requests',
    'as' => 'finance-requests.',
    'namespace' => 'App\\Http\\Controllers\\Finance',
    'middleware' => ['api'],
], function () {
    Route::get('/', 'FinanceRequestController@index')->name('index');
    Route::post('/list', 'FinanceRequestController@list')->name('list');
    Route::post('/{id}/approve', 'FinanceRequestController@approve')->name('approve');
    Route::post('/{id}/reject', 'FinanceRequestController@reject')->name('reject');
    Route::get('/{id}', 'FinanceRequestController@form')->name('form');
    Route::post('/approve-with-realization', 'FinanceRequestController@approveWithRealization')->name('approve-with-realization');
});

// =====================================
// USERS (API)
// =====================================

