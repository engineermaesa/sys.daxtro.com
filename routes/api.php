<?php

use App\Http\Controllers\AfterSales\Dats\AgreementController as DatsAgreementController;
use App\Http\Controllers\AfterSales\Dats\AgreementTemplateController;
use App\Http\Controllers\AfterSales\Dats\DocumentController as DatsDocumentController;
use App\Http\Controllers\AfterSales\Dats\InspectionTemplateController;
use App\Http\Controllers\AfterSales\Dats\SatisfactionSurveyController as DatsSatisfactionSurveyController;
use App\Http\Controllers\AfterSales\Dats\ServiceCustomerController;
use App\Http\Controllers\AfterSales\Dats\ServiceFormController as DatsServiceFormController;
use App\Http\Controllers\AfterSales\Dats\SparepartController;
use App\Http\Controllers\AfterSales\Dats\SurveyLinkController as DatsSurveyLinkController;
// LEADS (API)
use App\Http\Controllers\AfterSales\Dats\SurveyTemplateController;
use App\Http\Controllers\AfterSales\Dats\TechnicianController as DatsTechnicianController;
use App\Http\Controllers\AfterSales\Dats\TicketController as DatsTicketController;
use App\Http\Controllers\AfterSales\Survey\CustomerSurveyController;
use App\Http\Controllers\AfterSales\Survey\InspectionPhotoController;
use App\Http\Controllers\Api\LeadRegisterController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Dashboard\AfterSalesSummaryController;
use App\Http\Controllers\Dashboard\BMSummaryController;
// MASTERS (API)
use App\Http\Controllers\Dashboard\DashSummaryController;
use App\Http\Controllers\Dashboard\LeadSummaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Leads\ColdLeadController;
use App\Http\Controllers\Leads\DealLeadController;
use App\Http\Controllers\Leads\HotLeadController;
use App\Http\Controllers\Leads\ImportLeadController;
use App\Http\Controllers\Leads\LeadActivityController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Leads\MeetingController;
use App\Http\Controllers\Leads\SummaryController;
use App\Http\Controllers\Leads\WarmLeadController;
use App\Http\Controllers\Masters\AccountController;
use App\Http\Controllers\Masters\AgentController;
use App\Http\Controllers\Masters\BankController;
use App\Http\Controllers\Masters\BranchController;
use App\Http\Controllers\Masters\CompanyController;
// Purchasing
use App\Http\Controllers\Masters\CustomerTypeController;
// AFTERSALES DATS (API)
use App\Http\Controllers\Masters\ExpenseTypeController;
use App\Http\Controllers\Masters\PartController;
use App\Http\Controllers\Masters\ProductCategoryController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\Masters\ProductTypeController;
use App\Http\Controllers\Masters\ProvinceController;
use App\Http\Controllers\Masters\RegionController;
use App\Http\Controllers\Masters\SourceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Purchasing\PurchaseController;
use App\Http\Controllers\Users\AdminController;
use App\Http\Controllers\Users\PermissionController;
use App\Http\Controllers\Users\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'web', 'auth'])->prefix('aftersales/survey-templates')->name('aftersales.survey-templates.')->group(function () {
    Route::get('/', [SurveyTemplateController::class, 'index'])->name('index');
    Route::get('/question-scores', [SurveyTemplateController::class, 'questionScores'])->name('question-scores');
    Route::post('/draft', [SurveyTemplateController::class, 'createDraft'])->name('draft');
    Route::get('/{surveyTemplate}', [SurveyTemplateController::class, 'show'])->name('show');
    Route::put('/{surveyTemplate}', [SurveyTemplateController::class, 'update'])->name('update');
    Route::post('/{surveyTemplate}/preview', [SurveyTemplateController::class, 'preview'])->name('preview');
    Route::post('/{surveyTemplate}/activate', [SurveyTemplateController::class, 'activate'])->name('activate');
});

Route::middleware(['api', 'web', 'auth'])->prefix('aftersales/inspection-templates')->name('aftersales.inspection-templates.')->group(function () {
    Route::get('/', [InspectionTemplateController::class, 'index'])->name('index');
    Route::post('/draft', [InspectionTemplateController::class, 'createDraft'])->name('draft');
    Route::get('/{inspectionTemplate}', [InspectionTemplateController::class, 'show'])->name('show');
    Route::put('/{inspectionTemplate}', [InspectionTemplateController::class, 'update'])->name('update');
    Route::post('/{inspectionTemplate}/preview', [InspectionTemplateController::class, 'preview'])->name('preview');
    Route::post('/{inspectionTemplate}/activate', [InspectionTemplateController::class, 'activate'])->name('activate');
});

Route::middleware(['api', 'web', 'auth'])->prefix('aftersales/agreement-templates')->name('aftersales.agreement-templates.')->group(function () {
    Route::get('/', [AgreementTemplateController::class, 'index'])->name('index');
    Route::post('/draft', [AgreementTemplateController::class, 'createDraft'])->name('draft');
    Route::get('/{agreementTemplate}', [AgreementTemplateController::class, 'show'])->name('show');
    Route::put('/{agreementTemplate}', [AgreementTemplateController::class, 'update'])->name('update');
    Route::post('/{agreementTemplate}/preview', [AgreementTemplateController::class, 'preview'])->name('preview');
    Route::post('/{agreementTemplate}/activate', [AgreementTemplateController::class, 'activate'])->name('activate');
});

// =====================================
// NOTIFICATIONS (API)
// =====================================
Route::middleware(['api', 'web', 'auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
});

Route::post('leads/register', [LeadRegisterController::class, 'store'])->name('api.leads.register');
Route::get('leads/sources', [LeadRegisterController::class, 'sources'])->name('api.leads.sources');
Route::get('leads/segments', [LeadRegisterController::class, 'segments'])->name('api.leads.segments');
Route::get('leads/regions', [LeadRegisterController::class, 'regions'])->name('api.leads.regions');

Route::get('/dashboard/mkt5a', [DashboardController::class, 'mkt5a']);
// Route::get('/dashboard/sales-segment-performance', [DashboardController::class, 'salesSegmentPerformance']);
Route::get('/dashboard/regional-performance', [DashboardController::class, 'regionalPerformance']);
Route::get('/dashboard/source-conversion-stats', [DashboardController::class, 'sourceConversion']);
Route::get('/dashboard/source-monthly-stats', [DashboardController::class, 'sourceMonthlyStats']);
Route::get('/dashboard/dealing-list', [DashboardController::class, 'dealingList']);
Route::get('/dashboard/warm-hot-list', [DashboardController::class, 'warmHotList']);
Route::get('/dashboard/potential-dealing', [DashboardController::class, 'potentialDealing']);

// =====================================
// DASHBOARD (SuperAdmin)
// =====================================
Route::get('/dashboard/grid', [DashSummaryController::class, 'grid']);
Route::get('/dashboard/active-opportunities', [DashSummaryController::class, 'ActiveOpportunities']);
Route::get('/dashboard/sales-trend', [DashSummaryController::class, 'SalesTrend']);
Route::get('/dashboard/agent-summary', [DashSummaryController::class, 'AgentSummary']);
Route::get('/dashboard/leads-performance', [DashSummaryController::class, 'LeadsPerformance']);
Route::get('/dashboard/lead-volume', [DashSummaryController::class, 'leadVolume']);

Route::get('/dashboard/source-conversion-lists', [DashSummaryController::class, 'SourceConversionLists']);
Route::get('/dashboard/sales-segment-performance', [DashSummaryController::class, 'SalesSegmentPerformance']);
Route::get('/dashboard/source-monitoring-chart', [DashSummaryController::class, 'SourceMonitoringChart']);

// =====================================
// DASHBOARD (BM)
// =====================================
Route::group([
    'middleware' => ['api', 'web', 'auth'],
], function () {
    Route::get('/dashboard/bm/grid', [BMSummaryController::class, 'grid']);
    Route::get('/dashboard/bm/active-opportunities', [BMSummaryController::class, 'ActiveOpportunities']);
    Route::get('/dashboard/bm/sales-trend', [BMSummaryController::class, 'SalesTrend']);
    Route::get('/dashboard/bm/agent-summary', [BMSummaryController::class, 'AgentSummary']);
    Route::get('/dashboard/bm/leads-performance', [BMSummaryController::class, 'LeadsPerformance']);
    Route::get('/dashboard/bm/lead-volume', [BMSummaryController::class, 'leadVolume']);
});

// DASHBOARD (After Sales)
Route::group([
    'middleware' => ['api', 'web', 'auth'],
], function () {
    Route::get('/dashboard/after-sales/grid', [AfterSalesSummaryController::class, 'grid']);
    Route::get('/dashboard/after-sales/provinces', [AfterSalesSummaryController::class, 'provinces']);
    Route::get('/dashboard/after-sales/province-distribution', [AfterSalesSummaryController::class, 'provinceDistribution']);
});

// =====================================
// LEAD CLAIMS (API)
// =====================================

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

    // Dashboard Leads Summary (API)
    Route::get('/grid', [LeadSummaryController::class, 'grid'])->name('grid');
    Route::get('/lead-volume', [LeadSummaryController::class, 'leadVolume'])->name('lead-volume');
    Route::get('/active-opportunities', [LeadSummaryController::class, 'ActiveOpportunities'])->name('active-opportunities');
    Route::get('/leads-performance', [LeadSummaryController::class, 'LeadsPerformance'])->name('leads-performance');
    Route::get('/personal-trend', [LeadSummaryController::class, 'PersonalTrend'])->name('personal-trend');
    Route::get('/summary', [LeadSummaryController::class, 'Summary'])->name('summary');

    Route::prefix('available')->name('availables.')->group(function () {
        Route::get('/index', [LeadController::class, 'available'])
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
    Route::prefix('manage')->name('manage.')->group(function () {
        Route::get('/list', [LeadController::class, 'manageList'])
            ->name('list');
        Route::get('/counts', [LeadController::class, 'manageCounts'])
            ->name('counts');
        Route::get('/summary', [LeadController::class, 'manageSummary'])
            ->name('summary');

        Route::post('/bulk-trash', [LeadController::class, 'manageBulkTrash'])
            ->name('bulk-trash');

        Route::get('/form/{id?}', [LeadController::class, 'form'])
            ->name('form');
        Route::delete('/delete/{id}', [LeadController::class, 'delete'])
            ->name('delete');
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

        // PRIORITY TO USE LARAVEL SANCTUM
        // API FOR GRID BUT NOT SAFE BECAUSE NO MIDDLEWARE CHECK
        Route::get('/summary', [SummaryController::class, 'index'])->withoutMiddleware(['web', 'auth'])->name('summary');
        //

        Route::get('/all', [LeadController::class, 'myAllList'])->name('all');
        Route::get('/form/{id?}', [LeadController::class, 'form'])->name('form');

        Route::prefix('cold')->name('cold.')->group(function () {
            Route::get('/list', [ColdLeadController::class, 'myColdList'])->name('list');
            Route::get('manage/form/{id?}', [LeadController::class, 'form'])->name('manage');
            Route::get('meeting/{claim}', [ColdLeadController::class, 'meeting'])->name('meeting');
            Route::get('meeting/{id}/reschedule', [ColdLeadController::class, 'reschedule'])->name('meeting.reschedule');
            Route::post('meeting/save/{id?}', [MeetingController::class, 'save'])->name('meeting.save');
            Route::get('meeting/{id}/result', [MeetingController::class, 'resultForm'])
                ->name('meeting.result')
                ->withoutMiddleware(['web', 'auth']);
            Route::post('meeting/{id}/result', [MeetingController::class, 'result'])
                ->name('meeting.result.save')
                ->withoutMiddleware(['web', 'auth']);
            Route::post('meeting/{id}/cancel', [MeetingController::class, 'cancel'])->name('meeting.cancel');
            Route::post('trash/{claim}', [ColdLeadController::class, 'trash'])->name('trash');
        });

        Route::prefix('warm')->name('warm.')->group(function () {
            Route::get('/list', [WarmLeadController::class, 'myWarmList'])->name('list');
            Route::get('manage/form/{id?}', [LeadController::class, 'form'])->name('manage');
            Route::get('quotation/{claim}', [WarmLeadController::class, 'createQuotation'])->name('quotation.create');
            Route::post('quotation/{claim}', [WarmLeadController::class, 'storeQuotation'])->name('quotation.store');
            Route::post('quotation/{claim}/final-submit', [WarmLeadController::class, 'finalSubmitQuotation'])->name('quotation.final-submit');
            Route::post('trash/{claim}', [WarmLeadController::class, 'trash'])->name('trash');
        });

        Route::get('hot/list', [HotLeadController::class, 'myHotList'])->name('hot.list');
        Route::get('deal/list', [DealLeadController::class, 'myDealList'])->name('deal.list');
        Route::post('counts', [LeadController::class, 'myCounts'])->name('counts');
    });

    // (summary route restored inside 'my' group with web/auth removed for API access)
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
    Route::get('form/{id}', 'TrashLeadController@form')->name('form');

    Route::get('all/list', 'TrashLeadController@allList')->name('all.list');
    Route::get('cold/list', 'TrashLeadController@coldList')->name('cold.list');
    Route::get('warm/list', 'TrashLeadController@warmList')->name('warm.list');
    Route::get('hot/list', 'TrashLeadController@hotList')->name('hot.list');

    Route::post('restore/{claim?}', 'TrashLeadController@restore')->name('restore');
    Route::post('assign/{claim?}', 'TrashLeadController@assign')->name('assign');
});

Route::group([
    'prefix' => 'lost-leads',
    'as' => 'lost-leads.',
    'namespace' => 'App\\Http\\Controllers\\Leads',
    'middleware' => ['api', 'web', 'auth'],
], function () {
    Route::get('list', 'LostLeadController@hotList')->name('hot.list');
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
    Route::get('/list', 'OrderController@list')->name('list');
    Route::get('/counts', 'OrderController@counts')->name('counts');
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
// FINANCE REQUEST (API)
// =====================================
Route::group([
    'prefix' => 'finance-requests',
    'as' => 'finance-requests.',
    'namespace' => 'App\\Http\\Controllers\\Finance',
    'middleware' => ['api'],
], function () {
    Route::get('/', 'FinanceRequestController@index')->name('index');
    Route::get('/list', 'FinanceRequestController@list')->name('list');
    Route::get('/counts', 'FinanceRequestController@counts')->name('counts');
    Route::post('/{id}/approve', 'FinanceRequestController@approve')->name('approve');
    Route::post('/{id}/reject', 'FinanceRequestController@reject')->name('reject');
    Route::get('/{id}', 'FinanceRequestController@form')->name('form');
    Route::post('/approve-with-realization', 'FinanceRequestController@approveWithRealization')->name('approve-with-realization');
});

// =====================================
// DOCUMENTS (API)
// =====================================
Route::group([
    'prefix' => 'documents',
    'as' => 'documents.',
    'namespace' => 'App\\Http\\Controllers\\Documents',
    // Pakai auth supaya bisa filter berdasarkan grade user
    'middleware' => ['api', 'web', 'auth'],
], function () {
    Route::get('/list', 'DocumentController@list')->name('index');
    Route::get('/form/{id?}', 'DocumentController@form')->name('form');
    Route::post('/save/{id?}', 'DocumentController@save')->name('save');
    Route::delete('/delete/{id}', 'DocumentController@delete')->name('delete');
    Route::get('/download/{id}', 'DocumentController@download')->name('download');
});

// =====================================
// MASTERS (API)
// =====================================

Route::group([
    'prefix' => 'masters',
    'as' => 'masters.',
    'middleware' => ['api', 'web', 'auth'],
], function () {

    // AGENTS (API)
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/list', [AgentController::class, 'list'])->name('list');
        Route::get('/form/{id?}', [AgentController::class, 'form'])->name('form');
        Route::post('/save/{id?}', [AgentController::class, 'save'])->name('save');
    });

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

    // SOURCES (API)
    Route::prefix('sources')->name('sources.')->group(function () {
        Route::get('/list', [SourceController::class, 'list'])->name('list');
        Route::get('/form/{id?}', [SourceController::class, 'form'])->name('form');
        Route::post('/save/{id?}', [SourceController::class, 'save'])->name('save');
        Route::delete('/delete/{id}', [SourceController::class, 'delete'])->name('delete');
    });

    // PRODUCT CATEGORIES (API)
    Route::prefix('product-categories')->name('product-categories.')->group(function () {
        Route::get('/list', [ProductCategoryController::class, 'list'])->name('list');
        Route::get('/form/{id?}', [ProductCategoryController::class, 'form'])->name('form');
        Route::post('/save/{id?}', [ProductCategoryController::class, 'save'])->name('save');
        Route::delete('/delete/{id}', [ProductCategoryController::class, 'delete'])->name('delete');
    });

    // PRODUCT TYPES (API)
    Route::prefix('product-types')->name('product-types.')->group(function () {
        Route::get('/list', [ProductTypeController::class, 'list'])->name('list');
        Route::get('/form/{id?}', [ProductTypeController::class, 'form'])->name('form');
        Route::post('/save/{id?}', [ProductTypeController::class, 'save'])->name('save');
        Route::delete('/delete/{id}', [ProductTypeController::class, 'delete'])->name('delete');
    });

    // PRODUCTS (API)
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/list', [ProductController::class, 'list'])->name('list');
        Route::get('/form/{id?}', [ProductController::class, 'form'])->name('form');
        Route::post('/save/{id?}', [ProductController::class, 'save'])->name('save');
        Route::delete('/delete/{id}', [ProductController::class, 'delete'])->name('delete');
        Route::get('/import', [ProductController::class, 'importForm'])->name('import.form');
        // Import endpoints (preview & submit) available via API
        Route::post('/import/preview', [ProductController::class, 'importPreview'])->name('import.preview');
        Route::post('/import/submit', [ProductController::class, 'importStore'])->name('import.store');
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

// CUSTOMER SATISFACTION SURVEY
Route::group([
    'prefix' => 'survey/{token}',
    'as' => 'survey.',
    'middleware' => ['api', 'throttle:30,1'],
], function () {
    Route::get('/', [CustomerSurveyController::class, 'show'])->name('show');
    Route::post('/otp/send', [CustomerSurveyController::class, 'sendOtp'])->name('otp.send');
    Route::post('/otp/verify', [CustomerSurveyController::class, 'verifyOtp'])->name('otp.verify');
    Route::get('/form', [CustomerSurveyController::class, 'form'])->name('form');
    Route::post('/submit', [CustomerSurveyController::class, 'submit'])->name('submit');
});

Route::group([
    'prefix' => 'inspection-photo/{token}',
    'as' => 'inspection-photo.',
    'middleware' => ['api', 'throttle:30,1'],
], function () {
    Route::get('/', [InspectionPhotoController::class, 'show'])->name('show');
    Route::post('/', [InspectionPhotoController::class, 'upload'])->name('upload');
});

// =====================================
// AFTERSALES DATS (API)
// =====================================
Route::group([
    'prefix' => 'aftersales',
    'as' => 'aftersales.',
    'middleware' => ['api', 'web', 'auth'],
], function () {

    // SERVICE CUSTOMERS + PRODUCT UNITS (API)
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::post('/machine-name-preview', [ServiceCustomerController::class, 'machineNamePreview'])->name('machine-name-preview');
        Route::get('/machine-references', [ServiceCustomerController::class, 'machineReferences'])->name('machine-references');
        Route::get('/imports/template', [\App\Http\Controllers\AfterSales\Dats\CustomerImportController::class, 'template'])->name('imports.template');
        Route::post('/imports', [\App\Http\Controllers\AfterSales\Dats\CustomerImportController::class, 'upload'])->name('imports.upload');
        Route::get('/imports/{import}', [\App\Http\Controllers\AfterSales\Dats\CustomerImportController::class, 'show'])->name('imports.show');
        Route::put('/imports/{import}/review', [\App\Http\Controllers\AfterSales\Dats\CustomerImportController::class, 'review'])->name('imports.review');
        Route::post('/imports/{import}/apply', [\App\Http\Controllers\AfterSales\Dats\CustomerImportController::class, 'apply'])->name('imports.apply');
        Route::get('/imports/{import}/export', [\App\Http\Controllers\AfterSales\Dats\CustomerImportController::class, 'export'])->name('imports.export');
        Route::get('/deal-reviews', [\App\Http\Controllers\AfterSales\Dats\CustomerDealController::class, 'index'])->name('deal-reviews.index');
        Route::get('/deal-reviews/pending-count', [\App\Http\Controllers\AfterSales\Dats\CustomerDealController::class, 'pendingCount'])->name('deal-reviews.pending-count');
        Route::get('/deal-reviews/{deal}', [\App\Http\Controllers\AfterSales\Dats\CustomerDealController::class, 'show'])->name('deal-reviews.show');
        Route::post('/deal-reviews/{deal}/resolve', [\App\Http\Controllers\AfterSales\Dats\CustomerDealController::class, 'resolve'])->name('deal-reviews.resolve');
        Route::post('/products/{product}/move', [ServiceCustomerController::class, 'moveProduct'])->name('products.move');
        Route::get('/', [ServiceCustomerController::class, 'index'])->name('index');
        Route::post('/', [ServiceCustomerController::class, 'store'])->name('store');
        Route::get('/provinces', [ServiceCustomerController::class, 'provinces'])->name('provinces');
        Route::get('/regions', [ServiceCustomerController::class, 'regions'])->name('regions');
        Route::get('/{customer}/phone-changes', [ServiceCustomerController::class, 'phoneChanges'])->name('phone-changes');
        Route::get('/{customer}', [ServiceCustomerController::class, 'show'])->name('show');
        Route::put('/{customer}', [ServiceCustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [ServiceCustomerController::class, 'destroy'])->name('destroy');
        Route::post('/{customer}/products', [ServiceCustomerController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [ServiceCustomerController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [ServiceCustomerController::class, 'destroyProduct'])->name('products.destroy');
    });

    // TECHNICIANS (API)
    Route::prefix('technicians')->name('technicians.')->group(function () {
        Route::get('/me/overall-rating', \App\Http\Controllers\AfterSales\Dats\TechnicianRatingController::class)->name('me.overall-rating');
        Route::get('/', [DatsTechnicianController::class, 'index'])->name('index');
        Route::post('/', [DatsTechnicianController::class, 'store'])->name('store');
        Route::get('/{technician}', [DatsTechnicianController::class, 'show'])->name('show');
        Route::put('/{technician}', [DatsTechnicianController::class, 'update'])->name('update');
        Route::delete('/{technician}', [DatsTechnicianController::class, 'destroy'])->name('destroy');
        Route::get('/{technician}/kpi', [DatsTechnicianController::class, 'kpi'])->name('kpi');
    });

    // SPAREPARTS + STOCK MOVEMENTS (API)
    Route::prefix('spareparts')->name('spareparts.')->group(function () {
        Route::get('/imports/template', [\App\Http\Controllers\AfterSales\Dats\SparepartImportController::class, 'template'])->name('imports.template');
        Route::post('/imports', [\App\Http\Controllers\AfterSales\Dats\SparepartImportController::class, 'upload'])->name('imports.upload');
        Route::get('/imports/{import}', [\App\Http\Controllers\AfterSales\Dats\SparepartImportController::class, 'show'])->name('imports.show');
        Route::put('/imports/{import}/review', [\App\Http\Controllers\AfterSales\Dats\SparepartImportController::class, 'review'])->name('imports.review');
        Route::post('/imports/{import}/apply', [\App\Http\Controllers\AfterSales\Dats\SparepartImportController::class, 'apply'])->name('imports.apply');
        Route::get('/imports/{import}/export', [\App\Http\Controllers\AfterSales\Dats\SparepartImportController::class, 'export'])->name('imports.export');
        Route::get('/', [SparepartController::class, 'index'])->name('index');
        Route::post('/', [SparepartController::class, 'store'])->name('store');
        Route::get('/{sparepart}', [SparepartController::class, 'show'])->name('show');
        Route::put('/{sparepart}', [SparepartController::class, 'update'])->name('update');
        Route::delete('/{sparepart}', [SparepartController::class, 'destroy'])->name('destroy');
        Route::get('/{sparepart}/stock-movements', [SparepartController::class, 'stockMovements'])->name('stock-movements.index');
        Route::post('/{sparepart}/stock-movements', [SparepartController::class, 'storeStockMovement'])->name('stock-movements.store');
    });

    // TICKETS (API)
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [DatsTicketController::class, 'index'])->name('index');
        Route::post('/', [DatsTicketController::class, 'store'])->name('store');
        Route::get('/next-code', [DatsTicketController::class, 'nextCode'])->name('next-code');
        Route::get('/exchange-rates', [DatsTicketController::class, 'exchangeRates'])->name('exchange-rates');
        Route::get('/supervisors', [DatsTicketController::class, 'supervisors'])->name('supervisors');
        Route::get('/ready-to-close', [DatsTicketController::class, 'readyToClose'])->name('ready-to-close');
        Route::post('/{ticket}/happy-call', [DatsTicketController::class, 'happyCall'])->name('happy-call');
        Route::get('/{ticket}', [DatsTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [DatsTicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [DatsTicketController::class, 'destroy'])->name('destroy');
        Route::post('/{ticket}/recoming/reschedule', [DatsTicketController::class, 'reschedule'])->name('recoming.reschedule');
        Route::post('/{ticket}/parts', [DatsTicketController::class, 'addPart'])->name('parts.store');
        Route::put('/{ticket}/parts/{part}', [DatsTicketController::class, 'updatePart'])->name('parts.update');
        Route::post('/{ticket}/assign', [DatsTicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/on-site', [DatsTicketController::class, 'onSite'])->name('on-site');
        Route::post('/{ticket}/repair', [DatsTicketController::class, 'repair'])->name('repair');
        Route::post('/{ticket}/waiting-sparepart', [DatsTicketController::class, 'waitingSparepart'])->name('waiting-sparepart');
        Route::post('/{ticket}/documentation', [DatsTicketController::class, 'storeDocumentation'])->name('documentation.store');
        Route::post('/{ticket}/satisfaction', [DatsTicketController::class, 'storeSatisfaction'])->name('satisfaction.store');
        Route::get('/{ticket}/survey-link', [DatsSurveyLinkController::class, 'show'])->name('survey-link.show');
        Route::post('/{ticket}/survey-link/share', [DatsSurveyLinkController::class, 'share'])->name('survey-link.share');
        Route::post('/{ticket}/survey-link/regenerate', [DatsSurveyLinkController::class, 'regenerate'])->name('survey-link.regenerate');
        Route::post('/{ticket}/survey-response', [DatsSurveyLinkController::class, 'enterOnBehalf'])->name('survey-response.store');
        Route::get('/{ticket}/survey-response', [DatsSurveyLinkController::class, 'responseForm'])->name('survey-response.form');

        // SERVICE FORM, SERVICE AGREEMENT & DOCUMENTS
        Route::get('/{ticket}/documents', [DatsDocumentController::class, 'index'])->name('documents.index');
        Route::get('/{ticket}/service-form', [DatsServiceFormController::class, 'show'])->name('service-form.show');
        Route::post('/{ticket}/service-form/submit', [DatsServiceFormController::class, 'submit'])->name('service-form.submit');
        Route::post('/{ticket}/service-form/diagnosis/photos', [\App\Http\Controllers\AfterSales\Dats\ServiceDiagnosisController::class, 'upload'])->name('diagnosis.photos.store');
        Route::put('/{ticket}/service-form/diagnosis/photos/{photo}', [\App\Http\Controllers\AfterSales\Dats\ServiceDiagnosisController::class, 'update'])->name('diagnosis.photos.update');
        Route::delete('/{ticket}/service-form/diagnosis/photos/{photo}', [\App\Http\Controllers\AfterSales\Dats\ServiceDiagnosisController::class, 'destroy'])->name('diagnosis.photos.destroy');
        Route::put('/{ticket}/service-form', [DatsServiceFormController::class, 'saveDraft'])->name('service-form.update');
        Route::post('/{ticket}/service-form/photo-links', [DatsServiceFormController::class, 'photoLink'])->name('service-form.photo-links');
        Route::get('/{ticket}/service-form/photos/{photo}', [DatsServiceFormController::class, 'photo'])->name('service-form.photos.show');
        Route::get('/{ticket}/agreement', [DatsAgreementController::class, 'show'])->name('agreement.show');
        Route::put('/{ticket}/agreement', [DatsAgreementController::class, 'saveDraft'])->name('agreement.update');
        Route::post('/{ticket}/documents/{type}/signatures', [DatsDocumentController::class, 'sign'])->name('documents.signatures.store');
        Route::get('/{ticket}/documents/{type}/signatures/{role}/image', [DatsDocumentController::class, 'signatureImage'])->name('documents.signature');
        Route::post('/{ticket}/documents/{type}/save', [DatsDocumentController::class, 'save'])->name('documents.save');
        Route::post('/{ticket}/documents/{type}/unlock', [DatsDocumentController::class, 'unlock'])->name('documents.unlock');
        Route::get('/{ticket}/documents/{type}/pdf', [DatsDocumentController::class, 'pdf'])->name('documents.pdf');
        Route::get('/{ticket}/documents/{type}/dispatches', [DatsDocumentController::class, 'dispatches'])->name('documents.dispatches');
        Route::post('/{ticket}/documents/{type}/dispatches/{dispatch}/resend', [DatsDocumentController::class, 'resend'])->name('documents.dispatches.resend');
        Route::post('/{ticket}/recoming', [DatsTicketController::class, 'recoming'])->name('recoming');
        Route::post('/{ticket}/close', [DatsTicketController::class, 'close'])->name('close');
        Route::post('/{ticket}/visits', [DatsTicketController::class, 'storeVisit'])->name('visits.store');
        Route::put('/{ticket}/visits/{visit}', [DatsTicketController::class, 'updateVisit'])->name('visits.update');
        Route::post('/{ticket}/work-orders', [DatsTicketController::class, 'issueWorkOrder'])->name('work-orders.store');
        Route::post('/{ticket}/work-orders/{workOrder}/sign', [DatsTicketController::class, 'signWorkOrder'])->name('work-orders.sign');
    });

    Route::prefix('satisfaction-surveys')->name('satisfaction-surveys.')->group(function () {
        Route::get('/', [DatsSatisfactionSurveyController::class, 'index'])->name('index');
        Route::get('/{satisfaction}', [DatsSatisfactionSurveyController::class, 'show'])->name('show');
        Route::get('/{satisfaction}/form', [DatsSatisfactionSurveyController::class, 'form'])->name('form');
        Route::get('/{satisfaction}/signature', [DatsSatisfactionSurveyController::class, 'signature'])->name('signature');
        Route::post('/{satisfaction}/review', [DatsSatisfactionSurveyController::class, 'review'])->name('review');
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

// =====================================
// PURCHASING (API) - PUBLIC (NO AUTH)
// =====================================

Route::group([
    'prefix' => 'purchasing',
    'as' => 'purchasing.',
    'namespace' => 'App\\Http\\Controllers\\Purchasing',
    'middleware' => ['api'],
], function () {
    Route::get('/list', [PurchaseController::class, 'list'])->name('list');
    Route::get('/form/{id?}', [PurchaseController::class, 'form'])->name('form');
    Route::post('/save/{id?}', [PurchaseController::class, 'save'])->name('save');
    Route::get('/download/{id}', [PurchaseController::class, 'download'])->name('download');
    Route::get('/summary', [PurchaseController::class, 'summary'])->name('summary');
});
