<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadRegisterController;
use App\Http\Controllers\DashboardController;

Route::post('leads/register', [LeadRegisterController::class, 'store'])->name('api.leads.register');
Route::get('leads/sources', [LeadRegisterController::class, 'sources'])->name('api.leads.sources');
Route::get('leads/segments', [LeadRegisterController::class, 'segments'])->name('api.leads.segments');
Route::get('leads/regions', [LeadRegisterController::class, 'regions'])->name('api.leads.regions');

Route::get('/dashboard/mkt5a', [DashboardController::class, 'mkt5a']);
Route::get('/dashboard/source-conversion-stats', [DashboardController::class, 'sourceConversion']);
Route::get('/dashboard/source-monthly-stats', [DashboardController::class, 'sourceMonthlyStats']);
Route::get('/dashboard/dealing-list', [DashboardController::class, 'dealingList']);
