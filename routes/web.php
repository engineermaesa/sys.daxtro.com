<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Finance\FinanceRequestController;

// Temporary debug route to inspect session id and CSRF token
// Route::get('/debug/csrf', function () {
//     return response()->json([
//         'session_id' => session()->getId(),
//         'session_token' => session('_token'),
//         'csrf_token' => csrf_token(),
//     ]);
// });

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('contact-us', [\App\Http\Controllers\ContactUsController::class, 'create'])->name('contact-us');
    Route::post('contact-us', [\App\Http\Controllers\ContactUsController::class, 'store'])->name('contact-us.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')->middleware('auth');


Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // =====================================
    // DASHBOARD 
    // =====================================
    Route::post('dashboard/sales-achievement-donut', [DashboardController::class, 'salesAchievementDonut'])
        ->name('dashboard.sales-achievement-donut');
    Route::post('dashboard/sales-performance-bar', [DashboardController::class, 'salesPerformanceBar'])
        ->name('dashboard.sales-performance-bar');

    Route::post('dashboard/sales-achievement-monthly-percent', [DashboardController::class, 'salesAchievementMonthlyPercent'])->name('dashboard.sales-achievement-monthly-percent');

    Route::post('dashboard/target-vs-sales-monthly', [DashboardController::class, 'targetVsSalesMonthly'])
        ->name('dashboard.target-vs-sales-monthly');

    Route::post('dashboard/sales-achievement-trend', [DashboardController::class, 'salesAchievementTrend'])->name('dashboard.sales-achievement-trend');
    Route::post('dashboard/leads-branch-trend', [DashboardController::class, 'leadsBranchTrend'])->name('dashboard.leads-branch-trend');
    Route::post('dashboard/branch-sales-trend', [DashboardController::class, 'branchSalesTrend'])->name('dashboard.branch-sales-trend');
    Route::post('dashboard/group4/cold-warm', [DashboardController::class, 'coldToWarmStats'])->name('dashboard.group4.cold-warm');
    Route::post('dashboard/group4/warm-hot', [DashboardController::class, 'warmToHotStats'])->name('dashboard.group4.warm-hot');
    Route::post('dashboard/group3/lead-overview', [DashboardController::class, 'leadOverviewStats'])->name('dashboard.group3.lead-overview');
    Route::post('dashboard/group7/leads-source', [DashboardController::class, 'leadSourceStats'])->name('dashboard.group7.leads-source');
    Route::post('dashboard/group6/quotation-status', [DashboardController::class, 'quotationStatusStats'])->name('dashboard.group6.quotation-status');
    Route::post('dashboard/group5/lead-total', [DashboardController::class, 'leadStatusTotal'])->name('dashboard.group5.lead-total');
    Route::post('dashboard/orders-monthly', [DashboardController::class, 'ordersMonthlyStats'])->name('dashboard.orders-monthly');

    Route::get('incentives', [\App\Http\Controllers\IncentiveController::class, 'index'])->name('incentives.dashboard');

    Route::get('attachments/{id}', [\App\Http\Controllers\AttachmentController::class, 'download'])->name('attachments.download');


    // =====================================
    // LEADS ✔
    // =====================================
    Route::group([
        'prefix' => 'leads',
        'as' => '',
        'namespace' => 'App\\Http\\Controllers\\Leads',
    ], function () {

        Route::get('/available', 'LeadController@available')->name('leads.available');
        Route::post('/available/list', 'LeadController@availableList')->name('leads.available.list');
        Route::get('/available/export', 'LeadController@availableExport')->name('leads.available.export');
        Route::get('/available/form/{id?}', 'LeadController@form')->name('leads.form');
        Route::post('/available/save/{id?}', 'LeadController@save')->name('leads.save');

        Route::post('/{id}/claim', 'LeadController@claim')->name('leads.claim');

        Route::get('/{id}/activity-logs', 'LeadActivityController@logs')->name('leads.activity.logs');
        Route::post('/{id}/activity-logs', 'LeadActivityController@save')->name('leads.activity.save');

        Route::get('/manage', 'LeadController@manage')->name('leads.manage');
        Route::post('/manage/list', 'LeadController@manageList')->name('leads.manage.list');
        Route::post('/manage/counts', 'LeadController@manageCounts')->name('leads.manage.counts');
        Route::get('/manage/export', 'LeadController@manageExport')->name('leads.manage.export');
        Route::get('/manage/form/{id?}', 'LeadController@form')->name('leads.manage.form');
        Route::delete('/manage/delete/{id}', 'LeadController@delete')->name('leads.manage.delete');

        Route::get('/import', 'ImportLeadController@index')->name('leads.import');
        Route::get('/import/template', 'ImportLeadController@template')->name('leads.import.template');
        Route::post('/import/preview', 'ImportLeadController@preview')->name('leads.import.preview');
        Route::post('/import/submit', 'ImportLeadController@store')->name('leads.import.store');

        Route::prefix('my')->group(function () {
            Route::get('/', 'LeadController@my')->name('leads.my');
            Route::get('/form/{id?}', 'LeadController@form')->name('leads.my.form');

            Route::prefix('cold')->name('leads.my.cold.')->group(function () {
                Route::get('manage/form/{id?}', 'LeadController@form')->name('manage');
                Route::get('meeting/{claim}', 'ColdLeadController@meeting')->name('meeting');
                Route::get('meeting/{id}/reschedule', 'ColdLeadController@reschedule')->name('meeting.reschedule');
                Route::post('meeting/save/{id?}', 'MeetingController@save')->name('meeting.save');
                Route::get('meeting/{id}/result', 'MeetingController@resultForm')->name('meeting.result');
                Route::post('meeting/{id}/result', 'MeetingController@result')->name('meeting.result.save');
                Route::post('meeting/{id}/cancel', 'MeetingController@cancel')->name('meeting.cancel');
                Route::post('trash/{claim}', 'ColdLeadController@trash')->name('trash');
            });

            Route::prefix('warm')->name('leads.my.warm.')->group(function () {
                Route::get('manage/form/{id?}', 'LeadController@form')->name('manage');
                Route::get('quotation/{claim}', 'WarmLeadController@createQuotation')->name('quotation.create');
                Route::post('quotation/{claim}', 'WarmLeadController@storeQuotation')->name('quotation.store');
                Route::post('trash/{claim}', 'WarmLeadController@trash')->name('trash');
            });
        });
    });

    Route::group([
        'prefix' => 'trash-leads',
        'as' => 'trash-leads.',
        'namespace' => 'App\\Http\\Controllers\\Leads',
    ], function () {
        Route::get('/', 'TrashLeadController@index')->name('index');
        Route::get('form/{id}', 'TrashLeadController@form')->name('form');
        Route::post('cold/list', 'TrashLeadController@coldList')->name('cold.list');
        Route::post('warm/list', 'TrashLeadController@warmList')->name('warm.list');
        Route::post('restore/{claim}', 'TrashLeadController@restore')->name('restore');
        Route::post('assign/{claim}', 'TrashLeadController@assign')->name('assign');
    });


    // =====================================
    // ORDERS ✔
    // =====================================
    Route::group([
        'prefix' => 'orders',
        'as' => 'orders.',
        'namespace' => 'App\\Http\\Controllers\\Orders',
    ], function () {
        Route::get('/', 'OrderController@index')->name('index');
        Route::get('/{id}', 'OrderController@show')->name('show');
    });

    // =====================================
    // EXPENSE REALIZATIONS (Arahnya Kemana X)
    // =====================================

    Route::group([
        'prefix' => 'expense-realizations',
        'as' => 'expense-realizations.',
        'namespace' => 'App\\Http\\Controllers\\Orders',
    ], function () {
        Route::get('/', 'ExpenseRealizationController@index')->name('index');
        Route::post('/list', 'ExpenseRealizationController@list')->name('list');
        Route::get('/create/{meetingExpenseId?}', 'ExpenseRealizationController@create')->name('create');
        Route::post('/', 'ExpenseRealizationController@store')->name('store');
        Route::get('/{id}', 'ExpenseRealizationController@show')->name('show');
        Route::get('/{id}/edit', 'ExpenseRealizationController@edit')->name('edit');
        Route::put('/{id}', 'ExpenseRealizationController@update')->name('update');
        Route::post('/{id}/submit', 'ExpenseRealizationController@submit')->name('submit');
    });

    Route::get('/debug/expense-realizations', function () {
        $count = \App\Models\Orders\ExpenseRealization::count();
        $meetingExpenseCount = \App\Models\Orders\MeetingExpense::where('status', 'approved')->count();
        $data = \App\Models\Orders\ExpenseRealization::with(['sales', 'meetingExpense.meeting.lead'])->get();

        return response()->json([
            'expense_realization_count' => $count,
            'approved_meeting_expense_count' => $meetingExpenseCount,
            'data' => $data
        ]);
    });

    // =====================================
    // PAYMENT CONFIRMATION CC ✔
    // =====================================
    Route::group([
        'prefix' => 'payment-confirmation',
        'as' => 'payment-confirmation.',
        'namespace' => 'App\\Http\\Controllers\\Payment',
    ], function () {
        Route::get('lead/{lead}/terms/{term}/confirm-payment', 'PaymentConfirmationController@paymentConfirmationForm')->name('terms.payment.confirm.form');
        Route::post('lead/{lead}/terms/{term}/confirm-payment', 'PaymentConfirmationController@confirmPayment')->name('terms.payment.confirm');
    });


    // =====================================
    // QUOTATIONS 
    // =====================================
    Route::group([
        'prefix' => 'quotations',
        'as' => 'quotations.',
        'namespace' => 'App\\Http\\Controllers\\Orders',
    ], function () {
        Route::get('/{id}/download', 'QuotationController@download')->name('download');
        Route::get('/{id}/logs', 'QuotationController@logs')->name('logs');
        Route::get('/{id}', 'QuotationController@show')->name('show');
        Route::post('/{id}/approve', 'QuotationController@approve')->name('approve');
        Route::post('/{id}/reject', 'QuotationController@reject')->name('reject');
        Route::post('/{id}/signed-documents', 'QuotationController@uploadSignedDocument')->name('signed-documents.upload');
    });

    // =====================================
    // FINANCE REQUEST (API) ✔
    // =====================================
    Route::prefix('finance-requests')
        ->name('finance-requests.')
        ->group(function () {
            Route::get('/', [FinanceRequestController::class, 'index'])->name('index');
            Route::get('/{id}', [FinanceRequestController::class, 'form'])->name('form');
        });


    Route::get('api/expense-types', function () {
        return \App\Models\Masters\ExpenseType::all();
    })->name('api.expense-types');

    Route::get('api/meeting-expense-details/{id}', function ($id) {
        return \App\Models\Orders\MeetingExpenseDetail::where('meeting_expense_id', $id)
            ->with('expenseType')
            ->get();
    })->name('api.meeting-expense-details');


    // =====================================
    // MASTERS ✔
    // =====================================
    Route::group([
        'prefix' => 'masters',
        'as' => 'masters.',
        'namespace' => 'App\\Http\\Controllers\\Masters',
    ], function () {
        Route::name('banks.')->prefix('banks')->group(function () {
            Route::get('/', 'BankController@index')->name('index');
            Route::get('/form/{id?}', 'BankController@form')->name('form');
        });

        Route::name('accounts.')->prefix('accounts')->group(function () {
            Route::get('/', 'AccountController@index')->name('index');
            Route::get('/form/{id?}', 'AccountController@form')->name('form');
        });

        Route::name('product-categories.')->prefix('product-categories')->group(function () {
            Route::get('/', 'ProductCategoryController@index')->name('index');
            Route::get('/form/{id?}', 'ProductCategoryController@form')->name('form');
        });

        Route::name('products.')->prefix('products')->group(function () {
            Route::get('/', 'ProductController@index')->name('index');

            Route::post('/list', 'ProductController@list')->name('list');

            Route::get('/form/{id?}', 'ProductController@form')->name('form');
            Route::post('/save/{id?}', 'ProductController@save')->name('save');
            Route::delete('/delete/{id}', 'ProductController@delete')->name('delete');
        });

        Route::name('parts.')->prefix('parts')->group(function () {
            Route::get('/', 'PartController@index')->name('index');
            Route::get('/form/{id?}', 'PartController@form')->name('form');
        });

        Route::name('companies.')->prefix('companies')->group(function () {
            Route::get('/', 'CompanyController@index')->name('index');
            Route::post('/list', 'CompanyController@list')->name('list');
            Route::get('/form/{id?}', 'CompanyController@form')->name('form');
        });

        Route::name('provinces.')->prefix('provinces')->group(function () {
            Route::get('/', 'ProvinceController@index')->name('index');
            Route::get('/form/{id?}', 'ProvinceController@form')->name('form');
        });

        Route::name('regions.')->prefix('regions')->group(function () {
            Route::get('/', 'RegionController@index')->name('index');
            Route::get('/form/{id?}', 'RegionController@form')->name('form');
            Route::get('/provinces', 'RegionController@provinces')->name('provinces');
        });

        Route::name('branches.')->prefix('branches')->group(function () {
            Route::get('/', 'BranchController@index')->name('index');
            Route::get('/form/{id?}', 'BranchController@form')->name('form');
        });

        Route::name('expense-types.')->prefix('expense-types')->group(function () {
            Route::get('/', 'ExpenseTypeController@index')->name('index');
            Route::get('/form/{id?}', 'ExpenseTypeController@form')->name('form');
        });

        Route::name('customer-types.')->prefix('customer-types')->group(function () {
            Route::get('/', 'CustomerTypeController@index')->name('index');
            Route::get('/form/{id?}', 'CustomerTypeController@form')->name('form');
        });
    });

    // =====================================
    // USERS ✔
    // =====================================
    Route::group([
        'prefix' => 'users',
        'as' => 'users.',
        'namespace' => 'App\\Http\\Controllers\\Users',
    ], function () {
        // API
        Route::get('/', 'AdminController@index')->name('index');
        Route::get('/form/{id?}', 'AdminController@form')->name('form');

        Route::get('roles', 'UserRoleController@index')->name('roles.index');
        Route::get('roles/form/{id?}', 'UserRoleController@form')->name('roles.form');

        Route::get('permissions', 'PermissionController@index')->name('permissions.index');
        Route::get('permissions/form/{id?}', 'PermissionController@form')->name('permissions.form');
    });

    // =====================================
    // SETTINGS ✔
    // =====================================
    Route::group([
        'prefix' => 'settings',
        'as' => 'settings.',
        'namespace' => 'App\\Http\\Controllers\\Users',
    ], function () {
        Route::get('permissions', 'PermissionSettingController@index')->name('permissions-settings.index');
        Route::get('permissions/form/{roleId}', 'PermissionSettingController@form')->name('permissions-settings.form');
    });
});
