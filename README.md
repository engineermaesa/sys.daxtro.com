# Daxtro CRM

A B2B sales CRM for managing the full lifecycle of sales leads — from initial capture through quotation, order, payment, and invoice. Includes multi-role dashboards with KPI tracking, regional analytics, and lead performance reporting.

**Architecture:** Monolith — single Laravel application serving both server-rendered Blade views and JSON/AJAX API endpoints.

---

## Features

### Lead Management

- **Lead Pipeline:** Cold → Warm → Hot → Deal status flow with full history logging
- **Available Leads:** Admin-published leads that sales staff can claim within their branch
- **Lead Claiming:** One active claim per lead tracked via `lead_claims`; releases on trash or reassignment
- **Meetings:** Schedule online (Zoom/Google Meet, video call) and offline (office visit, client visit, EXPO) meetings with reschedule history and outcome recording
- **Activity Logs:** Sales log activities against a lead at any stage
- **Lead Import:** Bulk lead import from spreadsheet with preview step
- **External Lead Registration API:** Token-authenticated endpoint for third-party lead submission
- **Trash & Restore:** Manual trash by sales, auto-trash by scheduler, restore and reassign by admin/BM
- **Lost Leads:** Separate view for leads that progressed to HOT but did not convert

### Auto-Trash (Scheduled)

Stale leads are automatically trashed by the scheduler:

- **Warm leads** with no quotation activity after 30 days
- **Unscheduled leads** with no follow-up after the defined threshold
- **Expiring leads** notified to sales and branch managers at day 28–29 (daily 08:00 WIB)

### Quotation & Order Pipeline

- Quotations created by sales from Warm leads, with line items and payment terms
- Multi-step approval: Sales → Branch Manager → Finance/Finance Director
- Proforma invoice generation and PDF download
- Payment confirmation submission with attachment
- Order management with progress logs
- Invoice generation per payment term
- Expense realization linked to approved meeting expenses

### Finance

- Finance request queue for proforma, payment confirmation, and invoice approvals
- Approve/reject with notes; requester is notified in real-time
- Expense realization submission and review flow

### Purchasing

- Internal purchase request creation and tracking
- Status management via `ref_purchasing_statuses`
- Document download per purchase record

### Dashboards & Analytics

| Role                            | Dashboard Tabs                                                                                                 |
| ------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `super_admin`, `sales_director` | General KPI, General Trends, Regional Reach, Leads Performance, Active Opportunities, Agent Summary, Filtering |
| `branch_manager`                | Sales KPI, Sales Trends, Regional Reach, Leads Performance, Active Opportunities, Agent Summary, Filtering     |
| `sales`                         | Personal KPI, Personal Trends, Regional Reach, Leads Performance, Active Opportunities, Filtering              |

All dashboards load data via AJAX to dedicated JSON endpoints. KPI cards, trend charts, regional breakdowns, and lead funnels are all server-rendered data.

### Real-Time Notifications

In-app notifications via database channel + Pusher WebSockets. See [Notifications](#notifications) for the full trigger map.

### Master Data Management

Agents, Banks, Accounts, Companies, Branches, Regions, Provinces, Products (with tiered segment pricing), Product Categories, Product Types, Parts, Expense Types, Customer Types.

### Document Management

Internal document library with grade-based visibility filtering and file download.

### User & Permission Management

Role-based access with granular permission codes. Permissions are assignable per role via the Settings UI. Users are scoped to a company and branch.

### Incentive Tracking

Incentive balance and log view for eligible users.

---

## Technology Stack

| Layer               | Technology                                                                |
| ------------------- | ------------------------------------------------------------------------- |
| Framework           | Laravel 12.x                                                              |
| PHP                 | ^8.2                                                                      |
| Database            | MySQL — database `daxtccqs_daxx`                                          |
| Frontend            | Blade templates + Tailwind CSS v4 + Vanilla JS + Axios                    |
| Asset Pipeline      | Vite 6 + `laravel-vite-plugin`                                            |
| DataTables          | `yajra/laravel-datatables` ^12 (server-side, via AJAX)                    |
| PDF Generation      | `barryvdh/laravel-dompdf` ^3.1, `webklex/laravel-pdfmerger` ^1.3          |
| Excel Import/Export | `phpoffice/phpspreadsheet` ^4.3                                           |
| Real-Time           | `pusher/pusher-php-server` 7.2 + `pusher-js` ^8.5 + `laravel-echo` ^2.3   |
| Queue               | Database (default); `QUEUE_CONNECTION=database` in `.env`                 |
| Timezone            | Asia/Jakarta (all Carbon instances must use this timezone)                |
| Scheduler           | Laravel scheduler via `schedule:run`; must run every minute in production |

---

## Requirements

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Node.js >= 18 & NPM
- A Pusher account (or Pusher-compatible server like Soketi/Reverb) for real-time notifications

---

## Installation

1. **Clone the repository**

    ```bash
    git clone <repository-url>
    cd sys-daxtro-com-main
    ```

2. **Install PHP dependencies**

    ```bash
    composer install
    ```

3. **Install Node dependencies**

    ```bash
    npm install
    ```

4. **Configure environment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    Then edit `.env` and set at minimum:

    ```env
    APP_NAME="Daxtro CRM"
    APP_URL=http://localhost

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=daxtccqs_daxx
    DB_USERNAME=root
    DB_PASSWORD=

    BROADCAST_CONNECTION=pusher
    PUSHER_APP_ID=
    PUSHER_APP_KEY=
    PUSHER_APP_SECRET=
    PUSHER_APP_CLUSTER=ap1

    LEAD_REGISTER_API_TOKEN=   # Token for external lead registration API
    ```

5. **Run migrations**

    ```bash
    php artisan migrate
    ```

6. **Seed the database**

    ```bash
    php artisan db:seed --class=RoleSeeder
    php artisan db:seed --class=PermissionSeeder
    php artisan db:seed --class=RolePermissionSeeder
    php artisan db:seed --class=CompanySeeder
    php artisan db:seed --class=BranchSeeder
    php artisan db:seed --class=RegionSeeder
    php artisan db:seed --class=UserSeeder
    ```

    For a full local dataset (products, meeting types, etc.), uncomment the relevant seeders in `DatabaseSeeder.php` and run `php artisan db:seed`.

7. **Start all services**

    ```bash
    composer run dev
    ```

    This starts the Laravel server, queue listener, log tail (Pail), and Vite dev server concurrently.

8. **Production build**

    ```bash
    npm run build
    php artisan config:cache
    php artisan route:cache
    ```

9. **Scheduler** — add to crontab on the server:
    ```
    * * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
    ```

---

## Project Structure

```
app/
├── Console/Commands/       # Scheduled artisan commands
│   ├── ExpireLeads.php
│   ├── ExpireMeetings.php
│   ├── ExpireQuotations.php
│   ├── TrashWarmLeads.php
│   ├── TrashUnscheduledLeads.php
│   └── NotifyExpiringLeads.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/            # External API (LeadRegisterController)
│   │   ├── Auth/           # Login, password reset
│   │   ├── Dashboard/      # DashSummaryController, BMSummaryController, LeadSummaryController
│   │   ├── Finance/        # FinanceRequestController
│   │   ├── Leads/          # LeadController, ColdLeadController, WarmLeadController,
│   │   │                   # HotLeadController, DealLeadController, TrashLeadController,
│   │   │                   # LostLeadController, MeetingController, ImportLeadController,
│   │   │                   # LeadActivityController
│   │   ├── Masters/        # Region, Branch, Province, Product, Agent, Bank, etc.
│   │   ├── Orders/         # QuotationController, OrderController, ExpenseRealizationController
│   │   ├── Payment/        # PaymentConfirmationController
│   │   ├── Purchasing/     # PurchaseController
│   │   ├── Users/          # AdminController, UserRoleController, PermissionController,
│   │   │                   # PermissionSettingController
│   │   ├── DashboardController.php   # Legacy dashboard (still active)
│   │   ├── NotificationController.php
│   │   ├── AttachmentController.php
│   │   ├── ContactUsController.php
│   │   └── IncentiveController.php
│   ├── Classes/
│   │   └── ActivityLogger.php
│   └── Middleware/
│       ├── Authenticate.php
│       ├── LockApp.php          # APP_LOCKED config gate
│       └── RedirectIfAuthenticated.php
├── Models/
│   ├── Leads/              # Lead, LeadClaim, LeadMeeting, LeadStatus, LeadStatusLog,
│   │                       # LeadSource, LeadSegment, LeadActivityLog, LeadActivityList,
│   │                       # LeadMeetingReschedule, LeadPicExtension
│   ├── Masters/            # Region, Branch, Province, Regional, Product, ProductCategory,
│   │                       # ProductType, Part, Agent, Bank, Account, Company,
│   │                       # CustomerType, ExpenseType, MeetingType, Industry, Jabatan
│   ├── Orders/             # Quotation, QuotationItems, QuotationPaymentTerm, QuotationReview,
│   │                       # QuotationLog, QuotationSignedDocument, Order, OrderItems,
│   │                       # OrderPaymentTerm, OrderProgressLog, Proforma, Invoice,
│   │                       # InvoicePayment, PaymentConfirmation, PaymentLog,
│   │                       # MeetingExpense, MeetingExpenseDetail, FinanceRequest,
│   │                       # ExpenseRealization, ExpenseRealizationDetail
│   ├── User.php
│   ├── UserRole.php
│   ├── UserPermission.php
│   ├── UserRolePermission.php
│   ├── UserActivityLog.php
│   ├── UserBalance.php
│   ├── UserBalanceLog.php
│   ├── Document.php
│   └── Attachment.php
├── Notifications/
│   ├── Leads/              # 7 notification classes
│   └── Orders/             # 5 notification classes
├── Observers/
│   ├── LeadObserver.php
│   └── LeadActivityLogObserver.php
└── Services/
    ├── AutoTrashService.php
    └── MyLeadQueryService.php

resources/
├── views/
│   ├── layouts/
│   ├── components/
│   ├── pdfs/               # Quotation, proforma, invoice PDF templates
│   └── pages/
│       ├── dashboard/
│       │   ├── super-admin/
│       │   ├── branch-manager/
│       │   └── sales/
│       ├── leads/
│       ├── orders/
│       ├── finance/
│       ├── masters/
│       ├── users/
│       ├── purchasing/
│       ├── documents/
│       ├── incentives/
│       └── settings/
├── css/app.css             # Tailwind CSS v4 entry point
└── js/app.js               # Vite JS entry point (Axios, Laravel Echo, Pusher)

routes/
├── web.php                 # All web routes
├── api.php                 # All AJAX/API routes
└── console.php             # Scheduler registration

database/
├── migrations/
└── seeders/
```

---

## Database Tables

### Authentication & Users

| Table                   | Purpose                                      |
| ----------------------- | -------------------------------------------- |
| `users`                 | User accounts with role, branch, and targets |
| `user_roles`            | Role definitions (code-based)                |
| `user_permissions`      | Permission definitions (code-based)          |
| `user_role_permissions` | Role ↔ permission pivot                      |
| `user_activity_logs`    | Audit log of user actions                    |
| `user_balance`          | Incentive balance per user                   |
| `user_balance_logs`     | Incentive balance change history             |

### Lead Management

| Table                      | Purpose                                                          |
| -------------------------- | ---------------------------------------------------------------- |
| `leads`                    | Core lead records                                                |
| `lead_claims`              | Lead ownership assignments (sales ↔ lead)                        |
| `lead_statuses`            | Status definitions (PUBLISHED, COLD, WARM, HOT, DEAL, TRASH\_\*) |
| `lead_status_logs`         | Status change history                                            |
| `lead_sources`             | Lead origin categories                                           |
| `lead_segments`            | Lead segment categories                                          |
| `lead_meetings`            | Meeting schedule records                                         |
| `lead_meeting_reschedules` | Reschedule history                                               |
| `lead_meeting_attachments` | Files attached to meetings                                       |
| `lead_activity_lists`      | Predefined activity type options                                 |
| `lead_activity_logs`       | Activity entries against a lead                                  |
| `lead_notes`               | Freeform notes on leads                                          |
| `lead_pic_extensions`      | PIC (Person in Charge) contact extensions                        |

### Quotation & Order Pipeline

| Table                        | Purpose                             |
| ---------------------------- | ----------------------------------- |
| `quotations`                 | Quotation records                   |
| `quotation_items`            | Line items per quotation            |
| `quotation_payment_terms`    | Payment term schedule per quotation |
| `quotation_reviews`          | BM/Finance approval records         |
| `quotation_logs`             | Quotation change log                |
| `quotation_signed_documents` | Uploaded signed quotation files     |
| `orders`                     | Confirmed orders                    |
| `order_items`                | Line items per order                |
| `order_payment_terms`        | Payment terms per order             |
| `order_progress_logs`        | Order fulfillment progress notes    |
| `proformas`                  | Proforma invoices                   |
| `invoices`                   | Final invoices                      |
| `invoice_items`              | Invoice line items                  |
| `invoice_payments`           | Payment records against invoices    |
| `payment_confirmations`      | Sales-submitted payment proof       |
| `payment_logs`               | Payment event history               |

### Finance & Expenses

| Table                         | Purpose                                           |
| ----------------------------- | ------------------------------------------------- |
| `finance_requests`            | Finance approval queue (proforma/payment/invoice) |
| `meeting_expenses`            | Meeting expense submissions                       |
| `meeting_expense_details`     | Expense line items                                |
| `expense_realizations`        | Post-meeting expense realization                  |
| `expense_realization_details` | Realization line items                            |

### Purchasing

| Table                     | Purpose                     |
| ------------------------- | --------------------------- |
| `purchasings`             | Internal purchase requests  |
| `ref_purchasing_statuses` | Purchase status definitions |

### Master / Reference Data

| Table                    | Purpose                              |
| ------------------------ | ------------------------------------ |
| `ref_regionals`          | Top-level regional groupings         |
| `ref_provinces`          | Provinces within a regional          |
| `ref_regions`            | Regions within a province            |
| `ref_branches`           | Branch offices                       |
| `ref_products`           | Product catalog with segment pricing |
| `ref_product_categories` | Product category definitions         |
| `ref_product_types`      | Product type definitions             |
| `ref_parts`              | Parts/components catalog             |
| `agents`                 | External lead agents                 |
| `ref_banks`              | Bank list                            |
| `ref_accounts`           | Bank account records                 |
| `ref_companies`          | Company records                      |
| `ref_meeting_types`      | Meeting type definitions             |
| `ref_expense_types`      | Expense category definitions         |
| `ref_customer_types`     | Customer type definitions            |

### System

| Table           | Purpose                                           |
| --------------- | ------------------------------------------------- |
| `notifications` | In-app notification store (Laravel notifications) |
| `documents`     | Internal document library                         |
| `attachments`   | Generic file attachment records                   |
| `sessions`      | Database-backed session store                     |
| `cache`         | Database cache store                              |

---

## User Roles & Permissions

### Roles

| Role Code             | Name                | Description                                   |
| --------------------- | ------------------- | --------------------------------------------- |
| `super_admin`         | Super Admin         | Full system access, cross-branch visibility   |
| `sales_director`      | Sales Director      | Same dashboard access as super admin          |
| `branch_manager`      | Branch Manager      | Branch-scoped operations and approvals        |
| `sales`               | Sales               | Lead claiming, meetings, quotations           |
| `finance`             | Finance             | Finance request approvals, payment review     |
| `finance_director`    | Finance Director    | Same as finance with director-level authority |
| `accountant`          | Accountant          | Accounting access                             |
| `accountant_director` | Accountant Director | Accounting director access                    |
| `purchasing`          | Purchasing          | Purchasing log access                         |

### Permission Codes

Permissions are assigned per role and checked with `$user->hasPermission('code')` or the `hasPermission()` / `hasRole()` helpers in `app/helpers.php`.

| Code                            | Feature                                                                  |
| ------------------------------- | ------------------------------------------------------------------------ |
| `dashboard`                     | Access dashboard                                                         |
| `leads.manage`                  | Admin lead management table                                              |
| `leads.available`               | View available (published) leads                                         |
| `leads.my`                      | View my leads pipeline                                                   |
| `leads.import`                  | Bulk lead import                                                         |
| `leads.trash`                   | View trash leads                                                         |
| `orders`                        | Order management                                                         |
| `quotation.approvals`           | Approve or reject quotations                                             |
| `finance.requests`              | Finance approval queue                                                   |
| `incentives.view`               | Incentive dashboard                                                      |
| `purchasing.log`                | Purchasing log                                                           |
| `users.manage`                  | Manage users                                                             |
| `users.roles`                   | Manage roles                                                             |
| `settings.permissions-settings` | Permission assignment UI                                                 |
| `settings.general-settings`     | General settings                                                         |
| `masters.*`                     | Various master data management (agents, banks, products, branches, etc.) |

---

## Key Workflows

### Lead Pipeline

```
Admin publishes lead (status = PUBLISHED)
    → Sales claims lead         → status: COLD
    → Sales schedules meeting
    → Meeting result: success   → status: WARM
    → Sales creates quotation   → status: HOT (after BM/Finance approval)
    → Payment confirmed         → status: DEAL
```

Leads that stagnate are auto-trashed by the scheduler (30-day threshold). Trashed leads can be restored and reassigned by admin or branch managers.

### Quotation Approval Flow

```
Sales creates quotation
    → Branch Manager reviews
        → Reject: Sales is notified, quotation rejected
        → Approve: Finance queue is notified
            → Finance/Finance Director reviews
                → Reject: Sales + BM notified
                → Approve: Sales + BM notified, proforma generated
```

### Payment Flow

```
Sales submits payment confirmation (with attachment)
    → Finance is notified
    → Finance approves → invoice generated
    → Finance rejects  → sales notified
```

### Meeting Expense Flow

```
Sales schedules offline meeting
    → Sales submits expense claim
    → Finance approves
    → Sales creates expense realization post-meeting
```

---

## Notifications

All notifications use the `database` channel (stored in `notifications` table) and are broadcast in real-time via Pusher private channels (`branch.{branch_id}`).

### Lead Notifications

| Event                          | Triggered By                    | Recipients                |
| ------------------------------ | ------------------------------- | ------------------------- |
| Lead published (available)     | System (LeadObserver)           | Sales in branch           |
| Lead created                   | System (LeadObserver)           | Branch Managers in branch |
| Lead claimed                   | Sales                           | Branch Managers in branch |
| Lead activity logged           | Sales (LeadActivityLogObserver) | Branch Managers in branch |
| Lead trashed (manual)          | Sales                           | Branch Managers in branch |
| Lead auto-trashed              | System (scheduler)              | Sales who held the claim  |
| Lead expiring soon (day 28–29) | System (daily 08:00 WIB)        | Sales + Branch Managers   |

### Quotation & Finance Notifications

| Event                             | Triggered By               | Recipients                       |
| --------------------------------- | -------------------------- | -------------------------------- |
| Quotation submitted               | Sales                      | Branch Manager                   |
| Quotation approved/rejected       | Branch Manager or Finance  | Sales (+ BM if Finance reviewed) |
| Quotation pending finance review  | Branch Manager             | All Finance + Finance Directors  |
| Payment confirmation submitted    | Sales                      | All Finance + Finance Directors  |
| Finance request approved/rejected | Finance / Finance Director | Request submitter                |

---

## API & AJAX Endpoints

All routes under `routes/api.php`. Authenticated routes require the `auth` middleware via session cookie.

### Notifications

| Method | Path                          | Description             |
| ------ | ----------------------------- | ----------------------- |
| GET    | `/notifications`              | List user notifications |
| GET    | `/notifications/unread-count` | Unread count            |
| POST   | `/notifications/{id}/read`    | Mark one as read        |
| POST   | `/notifications/read-all`     | Mark all as read        |

### Dashboard

| Method | Path                              | Description             |
| ------ | --------------------------------- | ----------------------- |
| GET    | `/dashboard/grid`                 | Super Admin KPI grid    |
| GET    | `/dashboard/lead-volume`          | Lead volume chart data  |
| GET    | `/dashboard/leads-performance`    | Lead performance stats  |
| GET    | `/dashboard/active-opportunities` | Active opportunities    |
| GET    | `/dashboard/agent-summary`        | Agent summary           |
| GET    | `/dashboard/bm/grid`              | Branch Manager KPI grid |
| GET    | `/dashboard/bm/lead-volume`       | BM lead volume          |
| GET    | `/leads/grid`                     | Sales personal KPI grid |
| GET    | `/leads/lead-volume`              | Sales lead volume       |
| GET    | `/leads/personal-trend`           | Sales personal trend    |

### Leads

| Method | Path                    | Description                  |
| ------ | ----------------------- | ---------------------------- |
| GET    | `/leads/available/list` | Available leads DataTable    |
| GET    | `/leads/my/cold/list`   | Cold leads DataTable         |
| GET    | `/leads/my/warm/list`   | Warm leads DataTable         |
| GET    | `/leads/my/hot/list`    | Hot leads DataTable          |
| GET    | `/leads/my/deal/list`   | Deal leads DataTable         |
| POST   | `/leads/{id}/claim`     | Claim a lead                 |
| GET    | `/leads/manage/list`    | Admin manage leads DataTable |
| POST   | `/leads/import/preview` | Import preview               |
| POST   | `/leads/import/submit`  | Import submit                |

### Trash Leads

| Method | Path                           | Description         |
| ------ | ------------------------------ | ------------------- |
| GET    | `/trash-leads/all/list`        | All trash DataTable |
| GET    | `/trash-leads/cold/list`       | Trash cold          |
| GET    | `/trash-leads/warm/list`       | Trash warm          |
| GET    | `/trash-leads/hot/list`        | Trash hot           |
| POST   | `/trash-leads/restore/{claim}` | Restore lead        |
| POST   | `/trash-leads/assign/{claim}`  | Reassign lead       |

### Quotations & Orders

| Method | Path                        | Description            |
| ------ | --------------------------- | ---------------------- |
| GET    | `/orders/list`              | Orders DataTable       |
| GET    | `/orders/{id}`              | Order detail           |
| POST   | `/quotations/{id}/approve`  | Approve quotation      |
| POST   | `/quotations/{id}/reject`   | Reject quotation       |
| GET    | `/quotations/{id}/download` | Download quotation PDF |

### Finance Requests

| Method | Path                             | Description                |
| ------ | -------------------------------- | -------------------------- |
| GET    | `/finance-requests/list`         | Finance requests DataTable |
| POST   | `/finance-requests/{id}/approve` | Approve request            |
| POST   | `/finance-requests/{id}/reject`  | Reject request             |

### Masters

All master data follows the same pattern under `/masters/{resource}`:

- `GET /list` — DataTable data
- `GET /form/{id?}` — Form data (create/edit)
- `POST /save/{id?}` — Save (create/update)
- `DELETE /delete/{id}` — Soft delete

Resources: `agents`, `banks`, `accounts`, `branches`, `regions`, `provinces`, `products`, `product-categories`, `product-types`, `parts`, `companies`, `expense-types`, `customer-types`

### External Lead Registration (no auth, token-protected)

| Method | Path                  | Description                        |
| ------ | --------------------- | ---------------------------------- |
| POST   | `/api/leads/register` | Register lead from external system |
| GET    | `/api/leads/sources`  | Available lead sources             |
| GET    | `/api/leads/segments` | Available lead segments            |
| GET    | `/api/leads/regions`  | Available regions                  |

Set `LEAD_REGISTER_API_TOKEN` in `.env`. The token must be passed as a Bearer token in the request header.

---

## Scheduled Commands

| Command                   | Class                   | Schedule        | Purpose                                       |
| ------------------------- | ----------------------- | --------------- | --------------------------------------------- |
| `leads:expire`            | `ExpireLeads`           | Every minute    | Mark leads past expiry date                   |
| `meetings:expire`         | `ExpireMeetings`        | Every minute    | Mark meetings as expired                      |
| `leads:trash-warm`        | `TrashWarmLeads`        | Every minute    | Auto-trash stale Warm leads (30 days)         |
| `leads:trash-unscheduled` | `TrashUnscheduledLeads` | Every minute    | Auto-trash leads with no follow-up scheduled  |
| `quotations:expire`       | `ExpireQuotations`      | Every minute    | Mark expired quotations                       |
| `leads:notify-expiring`   | `NotifyExpiringLeads`   | Daily 08:00 WIB | Notify sales + BM of leads nearing auto-trash |

Run any command manually for testing:

```bash
php artisan leads:trash-warm
php artisan leads:notify-expiring
```

---

## Configuration

### App Lock

Set `APP_LOCKED=true` in `.env` to put the application in read-only lockdown mode. Authenticated users will see a 403 page; login is still accessible.

### Pusher / Real-Time

Configure Pusher credentials in `.env`:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=ap1
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

To disable real-time broadcast locally, set `BROADCAST_CONNECTION=log`.

### Timezone

All `Carbon` instances must use `Asia/Jakarta`:

```php
Carbon::now('Asia/Jakarta')
```

Never use bare `now()` or `Carbon::now()` without the timezone argument.

---

## Troubleshooting

### Notifications not appearing in real-time

- Verify Pusher credentials in `.env`
- Confirm `BROADCAST_CONNECTION=pusher` (not `log`)
- Check browser console for WebSocket connection errors
- Ensure `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER` are set and assets are rebuilt after changes

### Auto-trash not running

- Confirm the scheduler cron (`schedule:run`) is active on the server
- Run manually to test: `php artisan leads:trash-warm`

### Queue jobs not processing

- Ensure `QUEUE_CONNECTION=database` in `.env`
- Run the queue worker: `php artisan queue:listen --tries=1`
- The `composer run dev` script starts the queue listener automatically

### CSRF Token Mismatch

- Verify `APP_URL` in `.env` matches the domain being used
- Clear session: `php artisan cache:clear` and clear browser cookies

### Assets not loading / Vite errors

```bash
npm install
npm run dev   # development
npm run build # production
```

### Cache issues

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## Development Notes

### Adding a New Feature

1. **Migration** — `php artisan make:migration create_table_name`
2. **Model** — place under `app/Models/{Domain}/`, use `SoftDeletes` on master data
3. **Controller** — place under `app/Http/Controllers/{Domain}/`
4. **Routes** — add web routes to `routes/web.php`, AJAX routes to `routes/api.php`
5. **Views** — place under `resources/views/pages/{domain}/`

### Lead & Claim Queries

Always use `MyLeadQueryService` for lead/claim queries — it handles branch and role scoping automatically. An active claim is defined as a `lead_claims` row where `released_at IS NULL AND trash_note IS NULL`.

### Role Checks

```php
// In controllers
$user->role?->code === 'branch_manager'
$user->hasPermission('leads.manage')

// In Blade
@if(hasRole($user, 'branch_manager'))
@if(hasPermission($user, 'leads.manage'))
```

### Target Fields

The `target`, `target_leads`, and `target_visit` fields on `users` use a pipe-encoded format:

```
"annual_total|{\"1\":{\"amount\":500000},\"2\":{\"amount\":600000},...}"
```

Use the model accessors (`monthly_targets`, `target_total`, `monthly_leads_targets`, `monthly_visit_targets`) — never parse the raw string directly.

### Status Transitions

When changing a lead's status, always create a corresponding `LeadStatusLog` record alongside the update.

### Notifications

Add new notifications under `app/Notifications/Leads/` or `app/Notifications/Orders/`. Implement both `toDatabase()` and `toBroadcast()` methods. Use `PrivateChannel('branch.{branch_id}')` for branch-scoped events.

---

## License

Proprietary software. All rights reserved.

---

```
         ****
     ****=  =****
  ***+:        -****
**+.              .+**                 *****
**=**+.        .+**=**             ****=  =****
**   :***:  -***:   **          ****:        :+***
**      .+**+       **        **+.               +**
**        **        **        **=**+.        .+**=**
**        **        **        **   .+**-  -***:   **
**        **        **        **       +**+       **
**        **        **        **        **        **
**        **        **        **        **        **
**        **        **       ***+       **       =**
**        **        **   ****-  -***.   **   .+***
**        **        *****+.        .+**=**-****
**        **       +**+                +****
**        **   :***:               :***-**
**        **=**+.               =**+.   **
**        **+                +**=       **
**          -***:        :***:          **
**             .+**=  =**+.             **
**+.               =**=               .+**
  ***+-             **             :****
     ****=          **          =****
         ***+.      **      .+***
            ****-   **   -****
               ****=**=****
                   ****
```

---

### Our loved mentors 💔💔💔

- [Rdhwnzaki](https://github.com/Rdhwnzaki) — [@ridhwanmz_](https://instagram.com/ridhwanmz_)

### Internship Contributors

**Batch 9**
- [Busan24](https://github.com/busan24) — [@budysantoso](https://instagram.com/budysantoso1)
- [stronovski](https://github.com/stronovski) — [@httpsashha](https://instagram.com/httpsashha)

**Batch 10**
- [Rizky Apryadi](#) — [@rizkyapryadi](https://instagram.com/rizkyapryadi)
- [joytimess](https://github.com/joytimess) — [@bmbgwijaya](https://instagram.com/bmbgwijaya)
