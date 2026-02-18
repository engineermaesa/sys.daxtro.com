<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// LEADS (API)
use App\Http\Controllers\Leads\LeadActivityController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Leads\ImportLeadController;
use App\Http\Controllers\Leads\ColdLeadController;
use App\Http\Controllers\Leads\WarmLeadController;
use App\Http\Controllers\Leads\HotLeadController;
use App\Http\Controllers\Leads\DealLeadController;
use App\Http\Controllers\Leads\MeetingController;

// MASTERS (API)
use App\Http\Controllers\Masters\AccountController;
use App\Http\Controllers\Masters\BankController;
use App\Http\Controllers\Masters\BranchController;
use App\Http\Controllers\Masters\CompanyController;
use App\Http\Controllers\Masters\CustomerTypeController;
use App\Http\Controllers\Masters\ExpenseTypeController;
use App\Http\Controllers\Masters\PartController;
use App\Http\Controllers\Masters\ProductCategoryController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\Masters\ProvinceController;
use App\Http\Controllers\Masters\RegionController;
use App\Http\Controllers\Users\AdminController;
use App\Http\Controllers\Users\PermissionController;
use App\Http\Controllers\Users\UserRoleController;

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
    'as' => 'leads.',
    'name' => 'leads.',
    'namespace' => 'App\\Http\\Controllers\\Leads',
    'middleware' => ['api', 'web', 'auth'],
], function () {

    // FOR AVAILABLE LEADS (API)
    Route::prefix('available')->name('availables.')->group(function () {

        Route::get('/', [LeadController::class, 'available'])
            ->name('index');

        Route::get('/list', [LeadController::class, 'availableList'])
            ->name('list');

        Route::get('/export', [LeadController::class, 'availableExport'])
            ->name('export');

        Route::get('/form/{id?}', [LeadController::class, 'form'])
            ->name('form');

        Route::post('/save/{id?}', [LeadController::class, 'save'])
            ->name('save');
    });

    // FOR CLAIMING LEADS
    Route::post('/{id}/claim', 'LeadController@claim')->name('claim');

    // FOR ACTIVITY LOGS
    Route::prefix('{id}/activity-logs')->name('activity-logs.')->group(function () {

        Route::get('/', [LeadActivityController::class, 'logs'])
            ->name('logs');

        Route::post('/', [LeadActivityController::class, 'save'])
            ->name('save');
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
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('/', [LeadController::class, 'my'])->name('index');
        Route::get('/all', [LeadController::class, 'myAllList'])->name('all');
        Route::get('/form/{id?}', [LeadController::class, 'form'])->name('form');

        Route::prefix('cold')->name('cold.')->group(function () {
            Route::get('/list', [ColdLeadController::class, 'myColdList'])->name('list');

            Route::get('manage/form/{id?}', [LeadController::class, 'form'])->name('manage');

            Route::get('meeting/{claim}', [ColdLeadController::class, 'meeting'])->name('meeting');

            Route::get('meeting/{id}/reschedule', [ColdLeadController::class, 'reschedule'])->name('meeting.reschedule');

            Route::post('meeting/save/{id?}', [MeetingController::class, 'save'])->name('meeting.save');

            Route::get('meeting/{id}/result', [MeetingController::class, 'resultForm'])->name('meeting.result');

            Route::post('meeting/{id}/result', [MeetingController::class, 'result'])->name('meeting.result.save');

            Route::post('meeting/{id}/cancel', [MeetingController::class, 'cancel'])->name('meeting.cancel');

            Route::post('trash/{claim}', [ColdLeadController::class, 'trash'])->name('trash');
        });

        Route::prefix('warm')->name('warm.')->group(function () {
            Route::get('/list', [WarmLeadController::class, 'myWarmList'])->name('list');

            Route::get('manage/form/{id?}', [LeadController::class, 'form'])->name('manage');

            Route::get('quotation/{claim}', [WarmLeadController::class, 'createQuotation'])->name('quotation.create');

            Route::post('quotation/{claim}', [WarmLeadController::class, 'storeQuotation'])->name('quotation.store');

            Route::post('trash/{claim}', [WarmLeadController::class, 'trash'])->name('trash');
        });

        Route::get('hot/list', [HotLeadController::class, 'myHotList'])->name('hot.list');

        Route::get('deal/list', [DealLeadController::class, 'myDealList'])->name('deal.list');

        Route::post('counts', [LeadController::class, 'myCounts'])->name('counts');
    });
});

// =====================================
// TRASH-LEADS (API)
// =====================================

Route::group([
    'prefix' => 'trash-leads',
    'as' => 'trash-leads.',
    'namespace' => 'App\\Http\\Controllers\\Leads',
    'middleware' => ['api', 'web', 'auth'],
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
// MASTERS (API)
// =====================================

Route::group([
    'prefix' => 'masters',
    'as' => 'masters.',
    'middleware' => ['api', 'web', 'auth'],
], function () {

    // BANKS (API)
    Route::prefix('banks')->name('banks.')->group(function () {

        Route::get('/list', [BankController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [BankController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [BankController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [BankController::class, 'delete'])->name('delete');
    });

    // ACCOUNTS (API)
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/list', [AccountController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [AccountController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [AccountController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [AccountController::class, 'delete'])->name('delete');
    });

    // PRODUCT CATEGORIES (API)
    Route::prefix('product-categories')->name('product-categories.')->group(function () {
        Route::get('/list', [ProductCategoryController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [ProductCategoryController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [ProductCategoryController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [ProductCategoryController::class, 'delete'])->name('delete');
    });

    // PRODUCTS (API)
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/list', [ProductController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [ProductController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [ProductController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [ProductController::class, 'delete'])->name('delete');
    });

    // PARTS (API)
    Route::prefix('parts')->name('parts.')->group(function () {

        Route::get('/list', [PartController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [PartController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [PartController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [PartController::class, 'delete'])->name('delete');
    });


    // COMPANIES (API)
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/list', [CompanyController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [CompanyController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [CompanyController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [CompanyController::class, 'delete'])->name('delete');
    });

    //  PROVICENS (API)
    Route::prefix('provinces')->name(value: 'provinces.')->group(function () {
        Route::get('/list', [ProvinceController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [ProvinceController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [ProvinceController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [ProvinceController::class, 'delete'])->name('delete');
    });

    //  REGIONS (API)
    Route::prefix('regions')->name(value: 'regions.')->group(function () {
        Route::get('/list', [RegionController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [RegionController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [RegionController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [RegionController::class, 'delete'])->name('delete');
    });

    // BRANCHES (API)
    Route::prefix('branches')->name(value: 'branches.')->group(function () {
        Route::get('/list', [BranchController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [BranchController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [BranchController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [BranchController::class, 'delete'])->name('delete');
    });

    // EXPENSES-TYPE (API)
    Route::prefix('expense-types')->name(value: 'expense-types.')->group(function () {
        Route::get('/list', [ExpenseTypeController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [ExpenseTypeController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [ExpenseTypeController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [ExpenseTypeController::class, 'delete'])->name('delete');
    });

    // CUSTOMER-TYPE (API)
    Route::prefix('customer-types')->name(value: 'customer-types.')->group(function () {
        Route::get('/list', [CustomerTypeController::class, 'list'])->name('list');

        Route::get('/form/{id?}', [CustomerTypeController::class, 'form'])->name('form');

        Route::post('/save/{id?}', [CustomerTypeController::class, 'save'])->name('save');

        Route::delete('/delete/{id}', [CustomerTypeController::class, 'delete'])->name('delete');
    });
});

// =====================================
// USERS (API)
// =====================================
Route::group([
    'prefix' => 'users',
    'as' => 'users.',
    'name' => 'users.',
    'namespace' => 'App\\Http\\Controllers\\Users',
    'middleware' => ['api', 'web', 'auth'],
], function () {
    Route::get('branches-by-company/{companyId}', [AdminController::class, 'branchesByCompany'])->name('branches.by-company');
    Route::get('regions-by-branch/{branchId}', [AdminController::class, 'regionByBranch'])->name('regions.by-branch');
    Route::get('sales-by-branch/{branchId}', [AdminController::class, 'salesByBranch'])->name('sales.by-branch');

    // USER (API)
    Route::get('/list', [AdminController::class, 'list'])->name('list');
    Route::get('/form/{id?}', [AdminController::class, 'form'])->name('form');
    Route::post('/save/{id?}', [AdminController::class, 'save'])->name('save');
    Route::delete('/delete/{id}', [AdminController::class, 'delete'])->name('delete');

    // ROLES (API)
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/list', [UserRoleController::class, 'list'])->name('list');
        Route::get('/form/{id?}', [UserRoleController::class, 'form'])->name('form');
        Route::post('/save/{id?}', [UserRoleController::class, 'save'])->name('save');
        Route::delete('/delete/{id}', [UserRoleController::class, 'delete'])->name('delete');
    });

    // PERMISSIONS (API)
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/list', [PermissionController::class, 'list'])->name('list');
        Route::get('/form/{id?}', [PermissionController::class, 'form'])->name('form');
        Route::post('/save/{id?}', [PermissionController::class, 'save'])->name('save');
        Route::delete('/delete/{id}', [PermissionController::class, 'delete'])->name('delete');
    });
});

// =====================================
// SETTINGS
// =====================================
Route::group([
    'prefix' => 'settings',
    'as' => 'settings.',
    'namespace' => 'App\\Http\\Controllers\\Users',
    'middleware' => ['api', 'web', 'auth'],
], function () {
    Route::get('permissions', [\App\Http\Controllers\Users\PermissionSettingController::class, 'index'])->name('permissions-settings.index');
    Route::get('permissions/list', [\App\Http\Controllers\Users\PermissionSettingController::class, 'list'])->name('permissions-settings.list');
    Route::get('permissions/form/{roleId}', [\App\Http\Controllers\Users\PermissionSettingController::class, 'form'])->name('permissions-settings.form');
    Route::post('permissions/save/{roleId}', [\App\Http\Controllers\Users\PermissionSettingController::class, 'save'])->name('permissions-settings.save');
    Route::get('seeder', [\App\Http\Controllers\Users\SeederController::class, 'run'])->name('seeder.run');
});
