# CLAUDE.md — Daxtro CRM System

> Complete guide for AI-assisted development on this project.
> Read this before making any changes.

---

## 1. Project Overview

**Project Name:** Daxtro CRM (`sys-daxtro-com`)

**Main Purpose:**
A B2B sales CRM platform for managing the full lifecycle of sales leads — from initial capture through quotation, order, payment, and invoice. It also provides multi-role dashboards with KPI tracking, regional analytics, and lead performance reporting.

**Business Domain:**
- **Lead Management:** Capture, claim, nurture, and close leads across Cold → Warm → Hot → Deal stages.
- **Regional Reach & Lead Volume Analytics:** Visualize where leads are coming from across regions, provinces, and branches. Track lead volume trends over time.
- **Sales KPI Dashboards:** Per-sales, per-branch-manager, and per-superadmin views of targets vs. achievement (revenue, leads, visits).
- **Quotation & Order Pipeline:** Full flow from quotation creation, BM/Director approval, proforma invoice, payment confirmation, to order fulfillment.
- **Finance & Incentives:** Finance request approvals, expense realizations, and incentive balance tracking.

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12.x |
| PHP | ^8.2 |
| Database | MySQL (`daxtccqs_daxx`) |
| Frontend | Blade templates + Tailwind CSS v4 + Vanilla JS |
| Asset Pipeline | Vite 6 + `laravel-vite-plugin` |
| DataTables | `yajra/laravel-datatables` ^12 |
| PDF Generation | `barryvdh/laravel-dompdf`, `webklex/laravel-pdfmerger` |
| Excel Export/Import | `phpoffice/phpspreadsheet` ^4 |
| Timezone | Asia/Jakarta (all Carbon instances must use this timezone) |
| Queue | Sync (default); scheduler runs every minute via `schedule:run` |

---

## 3. Core Features

### 3.1 Lead Lifecycle & Status Flow

Leads move through a defined status pipeline:

```
PUBLISHED (1)  →  COLD (2)  →  WARM (3)  →  HOT (4)  →  DEAL (5)
                      ↓            ↓           ↓
                 TRASH_COLD (6)  TRASH_WARM (7)  TRASH_HOT (8)
```

- **Available Leads (Published):** Admin-published leads that any sales in the right branch can claim.
- **My Leads:** Leads currently claimed by the authenticated sales user.
- **Lead Claiming (`lead_claims`):** A sales user claims a lead; the claim record tracks `claimed_at` and `released_at`. Only one active claim (no `released_at`, no `trash_note`) is valid at a time.
- **Auto-Trash:** Scheduled commands automatically trash stale leads (Cold leads with no meetings/activities after 30 days).

### 3.2 Regional Reach / Lead Volume Analytics

The **Regional Reach** tab (present in all three dashboard roles) shows:
- A filterable breakdown of leads by **region** and **province**.
- Lead volume per region plotted over a selected time range.
- Filters: branch, sales, date range, compare period.

This is served by `DashSummaryController`, `BMSummaryController`, and `LeadSummaryController` (under `App\Http\Controllers\Dashboard\`).

### 3.3 Role-Based Dashboards

Three distinct dashboard views, each with their own set of summary tabs:

| Role | Controller | Dashboard Views |
|---|---|---|
| `super_admin` / `sales_director` | `DashSummaryController` | General KPI, General Trends, Regional Reach, Leads Performance, Active Opportunities, Agent, Filtering |
| `branch_manager` | `BMSummaryController` | Sales KPI, Sales Trends, Regional Reach, Leads Performance, Active Opportunities, Agent, Filtering |
| `sales` | `LeadSummaryController` | Personal KPI, Personal Trends, Regional Reach, Leads Performance, Active Opportunities, Filtering |

All summary controllers share a `grid()` method that returns JSON for the main KPI card, plus separate methods for each tab (e.g., `leadVolume()`, `regionalReach()`, `leadsPerformance()`, `activeOpportunities()`).

### 3.4 Target System

User targets are stored in three fields on `users` with a custom pipe-encoded format:

```
"total_value|{\"1\":{\"amount\":500000},\"2\":{\"amount\":600000},...}"
```

- `target` — monthly sales revenue targets
- `target_leads` — monthly new lead targets
- `target_visit` — monthly visit/meeting targets

Accessor helpers on `User`: `getMonthlyTargetsAttribute()`, `getTargetTotalAttribute()`, `getMonthlyLeadsTargetsAttribute()`, `getMonthlyVisitTargetsAttribute()`.

### 3.5 Quotation & Order Flow

```
Lead (DEAL) → Quotation → QuotationItems → QuotationReview (approval)
           → Proforma Invoice → PaymentConfirmation
           → Order → OrderItems → Invoice
```

- Quotations have statuses (`pending`, `approved`, `rejected`, `expired`).
- Booking fee and payment terms are tracked per quotation.
- Finance requests sit between quotation approval and order creation.

### 3.6 Scheduled Commands (run every minute via scheduler)

| Command | Class | Purpose |
|---|---|---|
| `leads:expire` | `ExpireLeads` | Mark leads past expiry date |
| `meetings:expire` | `ExpireMeetings` | Mark meetings as expired |
| `leads:trash-warm` | `TrashWarmLeads` | Auto-trash stale Warm leads |
| `leads:trash-unscheduled` | `TrashUnscheduledLeads` | Auto-trash leads with no scheduled follow-up |
| `quotations:expire` | `ExpireQuotations` | Mark expired quotations |

---

## 4. Important Models & Relationships

### Geographic Hierarchy

```
Regional (ref_regionals)
  └── Province (ref_provinces)
        └── Region (ref_regions)
              └── Branch (ref_branches)
```

### Core Models

| Model | Table | Key Relationships |
|---|---|---|
| `User` | `users` | `belongsTo UserRole`, `belongsTo Branch`; has pipe-encoded target fields |
| `UserRole` | `user_roles` | `belongsToMany UserPermission` via `user_role_permissions` |
| `UserPermission` | `user_permissions` | Code-based permission gates (e.g. `leads.manage`, `orders`) |
| `Lead` | `leads` | `belongsTo Region`, `Branch`, `LeadStatus`, `LeadSource`, `LeadSegment`, `User (first_sales_id)`, `Agent`; `hasMany LeadClaim`, `LeadMeeting`, `LeadStatusLog` |
| `LeadClaim` | `lead_claims` | `belongsTo Lead`, `User (sales_id)`; active = `released_at IS NULL AND trash_note IS NULL` |
| `LeadStatus` | `lead_statuses` | Constants: PUBLISHED=1, COLD=2, WARM=3, HOT=4, DEAL=5, TRASH_COLD=6, TRASH_WARM=7, TRASH_HOT=8 |
| `Region` | `ref_regions` | `belongsTo Branch`, `Provincial`, `Regional`; `hasMany Lead`, `User` |
| `Branch` | `ref_branches` | `belongsTo Company`; has `target` field |
| `Province` | `ref_provinces` | `belongsTo Regional`; `hasMany Region` |
| `Regional` | `ref_regionals` | `hasMany Province` |
| `Product` | `ref_products` | `belongsToMany ProductCategory`, `Part`; has tiered pricing fields |
| `Quotation` | `quotations` | `belongsTo Lead`; `hasMany QuotationItems`, `QuotationPaymentTerm`, `Proforma`, `QuotationReview` |
| `Order` | `orders` | `belongsTo Lead`; `hasMany OrderItems`, `OrderPaymentTerm`, `Invoice` (via Proforma) |
| `Agent` | `agents` | External lead agents; `belongsTo Branch`; linked to `Lead` |

### Permission Check Pattern

```php
// In controllers / blade
$user->hasPermission('leads.manage');   // checks user_role_permissions
hasPermission($user, 'orders');          // global helper in app/helpers.php
hasRole($user, 'branch_manager');        // global helper
```

---

## 5. Folder Structure

```
app/
├── Console/Commands/       # Scheduled artisan commands (auto-trash, expire leads/meetings/quotations)
├── Http/
│   ├── Classes/            # ActivityLogger
│   ├── Controllers/
│   │   ├── Dashboard/      # DashSummaryController, BMSummaryController, LeadSummaryController
│   │   ├── Finance/        # FinanceRequestController
│   │   ├── Leads/          # LeadController, ImportLeadController, WarmLeadController, etc.
│   │   ├── Masters/        # Region, Branch, Province, Product, Agent, etc.
│   │   ├── Orders/         # QuotationController, OrderController, ExpenseRealizationController
│   │   ├── Users/          # AdminController, UserRoleController, PermissionController
│   │   └── DashboardController.php  # Legacy main dashboard (still used)
│   └── Middleware/
├── Models/
│   ├── Leads/              # Lead, LeadClaim, LeadMeeting, LeadStatus, LeadStatusLog, ...
│   ├── Masters/            # Region, Branch, Province, Regional, Product, Agent, ...
│   ├── Orders/             # Quotation, Order, Proforma, Invoice, PaymentLog, ...
│   └── User.php, UserRole.php, UserPermission.php, ...
├── Observers/
├── Policies/
├── Services/
│   ├── AutoTrashService.php         # Bulk auto-trash logic used by scheduled commands
│   └── MyLeadQueryService.php       # Reusable lead/claim query builder with role filters
└── helpers.php                      # hasRole(), hasPermission(), format_needs_label()

resources/
├── views/
│   ├── pages/
│   │   ├── dashboard/
│   │   │   ├── super-admin/         # general-kpi, general-trends, regional-reach, ...
│   │   │   ├── branch-manager/      # sales-kpi, sales-trends, regional-reach, ...
│   │   │   └── sales/               # personal-kpi, personal-trends, regional-reach, ...
│   │   ├── leads/                   # available, my, manage, form, import
│   │   ├── masters/
│   │   ├── orders/
│   │   └── ...
│   ├── layouts/
│   ├── components/
│   └── pdfs/
├── css/app.css
└── js/app.js

routes/
├── web.php          # All web routes (~27K — large, role-grouped with middleware)
├── api.php          # API routes for AJAX/DataTables calls
└── console.php      # Scheduled command registration

database/
├── migrations/
├── seeders/         # Includes RoleSeeder, PermissionSeeder, RegionSeeder, etc.
└── factories/
```

---

## 6. Key Directories & Files Most Frequently Edited

| File / Directory | Why It's Edited Often |
|---|---|
| `app/Http/Controllers/Dashboard/DashSummaryController.php` | Super Admin dashboard KPIs, lead volume, regional reach (114K — very large) |
| `app/Http/Controllers/Dashboard/BMSummaryController.php` | Branch Manager dashboard (106K) |
| `app/Http/Controllers/Dashboard/LeadSummaryController.php` | Sales personal dashboard (65K) |
| `app/Http/Controllers/Leads/LeadController.php` | Core lead CRUD, claim/release, status transitions (130K) |
| `app/Http/Controllers/Leads/ImportLeadController.php` | Lead import from spreadsheet (55K) |
| `app/Http/Controllers/Leads/WarmLeadController.php` | Warm lead management (24K) |
| `app/Http/Controllers/Leads/TrashLeadController.php` | Trash lead logic (38K) |
| `app/Http/Controllers/Orders/QuotationController.php` | Quotation creation and approval (20K) |
| `app/Http/Controllers/Orders/OrderController.php` | Order management (27K) |
| `app/Http/Controllers/Finance/FinanceRequestController.php` | Finance approvals (43K) |
| `app/Models/Leads/Lead.php` | Core lead model — add relationships here |
| `app/Models/User.php` | Target accessors, role/permission relationships |
| `app/Services/MyLeadQueryService.php` | Shared lead query logic with role-based scoping |
| `app/Services/AutoTrashService.php` | Auto-trash business logic |
| `resources/views/pages/dashboard/*/regional-reach.blade.php` | Regional Reach tab UI (3 role variants) |
| `resources/views/pages/leads/manage.blade.php` | Lead management table (102K) |
| `routes/web.php` | Route additions (27K — organized by role/feature group) |
| `routes/api.php` | API route additions for AJAX endpoints |
| `database/seeders/` | Data seeding for roles, permissions, regions, products |

---

## 7. Coding Conventions & Best Practices

### General
- **PHP 8.2+** features are available: readonly properties, enum, match expressions, named arguments, fibers.
- Follow **PSR-4** autoloading. Models go under `App\Models\{Domain}\`.
- Use **SoftDeletes** on all master data models (`Region`, `Branch`, `Province`, `Product`, etc.).

### Controllers
- Controllers are organized by domain folder (`Leads/`, `Masters/`, `Dashboard/`, etc.).
- Dashboard summary controllers expose JSON-only endpoints (return `response()->json(...)`).
- Always validate `Request` input using `$request->validate([...])` at the top of each method.
- All date/time work must use `Carbon::now('Asia/Jakarta')` — never bare `now()` or `Carbon::now()`.

### Role & Permission Checks
- Always check roles using `$user->role?->code` (not `$user->role_id`).
- Use `$user->hasPermission('permission.code')` for granular feature gates.
- Use the global helpers `hasRole()` and `hasPermission()` from `app/helpers.php` in Blade.
- Role codes in use: `super_admin`, `sales_director`, `branch_manager`, `sales`, `finance`, `finance_director`, `accountant`, `accountant_director`, `purchasing`.

### Lead & Claim Queries
- **Always** use `MyLeadQueryService` for building lead/claim queries — it handles role-scoping automatically.
- An **active claim** = `lead_claims` row where `released_at IS NULL AND trash_note IS NULL`.
- Status transitions must also create a `LeadStatusLog` record.

### Target Fields
- The `target`, `target_leads`, and `target_visit` fields on `users` use a pipe-encoded format: `"annual_total|{...monthly_json...}"`.
- Use the provided model accessors (`getMonthlyTargetsAttribute`, etc.) — never parse the raw string manually.

### Frontend (Blade)
- Tailwind CSS v4 — use utility classes directly. No custom PostCSS plugins needed beyond the Tailwind Vite plugin.
- DataTables (Yajra) is used for all tabular data — server-side processing via AJAX to `/api/` routes.
- Dashboard tabs load data via `fetch()` / `axios` calling JSON API endpoints.

### Database
- Reference/lookup tables use the `ref_` prefix (e.g., `ref_branches`, `ref_regions`, `ref_provinces`, `ref_products`).
- All timestamps use UTC in the database; convert to `Asia/Jakarta` in PHP using Carbon.
- Migration files are date-prefixed. Always check for column existence before adding/removing in migrations.

---

## 8. How to Run the Project

### Local Development

```bash
# Install PHP dependencies
rtk composer install

# Install Node dependencies
rtk npm install

# Copy environment file and configure
cp .env.example .env
rtk php artisan key:generate

# Run database migrations
rtk php artisan migrate

# Seed the database (roles, permissions, regions, products, users)
rtk php artisan db:seed

# Start all services (server + queue + logs + vite hot-reload)
rtk composer run dev
```

### Common Artisan Commands

```bash
# Run scheduler (must be running for auto-trash and expiry to work)
rtk php artisan schedule:run

# Individual scheduled jobs (can be run manually for testing)
rtk php artisan leads:expire
rtk php artisan meetings:expire
rtk php artisan leads:trash-warm
rtk php artisan leads:trash-unscheduled
rtk php artisan quotations:expire

# Clear caches
rtk php artisan config:clear
rtk php artisan cache:clear
rtk php artisan view:clear
rtk php artisan route:clear

# Build frontend assets for production
rtk npm run build

# Run tests
rtk php artisan test
```

### Database

- **Connection:** MySQL, database `daxtccqs_daxx`
- **A full SQL snapshot** is available in the project root: `daxtccqs_daxx Final Februari.sql`

---

## 9. RTK Instructions

**Always prefix every terminal command with `rtk`.**

RTK is a token-optimized CLI proxy that reduces token usage by 60–90% on dev operations. It transparently rewrites your commands and filters verbose output.

```bash
# Correct usage
rtk php artisan migrate
rtk composer install
rtk npm run dev
rtk git status
rtk ls -la app/Models/

# Incorrect (do NOT use bare commands)
php artisan migrate      # ❌
composer install         # ❌
git status               # ❌
```

RTK meta-commands (use directly without a subcommand):

```bash
rtk gain              # Show token savings analytics
rtk gain --history    # Show command history with savings
rtk discover          # Analyze history for missed optimization opportunities
rtk proxy <cmd>       # Execute a raw command without filtering (debug only)
```
