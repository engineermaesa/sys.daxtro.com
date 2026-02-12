<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;


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



// Authentication (API)

Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('api.login');
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('api.logout')->middleware('auth');



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
    Route::get('/{id}/progress', 'OrderProgressController@form')->name('progress.form');
    Route::post('/{id}/progress', 'OrderProgressController@save')->name('progress.save');
    Route::get('/{id}/progress-logs', 'OrderProgressController@logs')->name('progress.logs');
    Route::get('/{id}/activity-logs', 'OrderController@activityLogs')->name('activity.logs');
    Route::post('/{order}/terms/{term}/request-proforma', 'OrderController@requestProforma')->name('terms.proforma.request');
    Route::post('/{order}/terms/{term}/request-invoice', 'OrderController@requestInvoice')->name('terms.invoice.request');
    Route::get('/file/{type}/{file}', 'OrderController@downloadFile')->name('file.download');
});

// =====================================
// FINANCE (API)
// =====================================
