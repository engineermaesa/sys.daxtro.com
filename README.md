# Daxtro ERP System

A comprehensive Enterprise Resource Planning (ERP) for Daxtro for managing sales, leads, meetings, expenses, and business operations.

## Features

### Lead Management
- **Lead Tracking**: Manage leads through different stages (Cold, Warm, Hot, Deal)
- **Lead Claims**: Sales team can claim and manage leads
- **Lead Sources**: Track leads from various sources (Website, Referral, Agent, etc.)
- **Lead Segments**: Categorize leads (Government, Corporate, Personal, FOB, BDI)
- **Auto-Trash System**: Automatically archive inactive leads after 10 days

### Meeting Management
- **Meeting Scheduling**: Schedule online and offline meetings*
- **Meeting Types**: 
  - Online: Zoom/Google Meet, Video Call
  - Offline: Office Visit, Client Visit, EXPO
- **Meeting Rescheduling**: Track reschedule history with reasons
- **Meeting Results**: Record meeting outcomes (Success/Failed)
- **Lead Details in Meetings**: Track multiple leads per meeting with product and pricing info
- **EXPO Auto-Fill**: Quick setup for exhibition meetings

* Update 16 December 2025: Feature for Meeting Scheduling is not yet finished according to user's needs, please tweak accordingly.

### Expense Management
- **Meeting Expenses**: Track expenses for offline meetings
- **Expense Types**: Transportation, Accommodation, Meals, etc.
- **Finance Approval**: Submit expenses for finance team approval
- **Expense Status Tracking**: Draft, Submitted, Approved, Rejected

### Dashboard & Analytics
- **Sales Achievement**: Donut charts and monthly percentages
- **Sales Performance**: Bar charts by sales team
- **Target vs Sales**: Monthly comparison trends
- **Lead Conversion**: Cold to Warm, Warm to Hot statistics
- **Lead Overview**: Total leads by status
- **Lead Sources**: Distribution analysis
- **Quotation Status**: Track quote pipeline
- **Branch Performance**: Sales trends by branch

### User Management
- **Role-Based Access**: Super Admin, Branch Manager, Sales, Finance
- **Branch Assignment**: Users assigned to specific branches
- **Activity Logging**: Track all user actions
- **Authentication**: Secure login with password reset

### Product Management
- **Product Catalog**: SKU-based product management
- **Segment Pricing**: Different prices for Government, Corporate, Personal, FOB, BDI
- **Product Categories**: Organize products by type

### Regional Management
- **Branches**: Multiple branch locations
- **Regions**: Geographic regions within branches
- **Provinces & Cities**: Complete Indonesia location data

## 🛠️ Technology Stack
- **Framework**: Laravel 12.18.0
- **PHP Version**: 8.2.12
- **Database**: MySQL
- **Frontend**: 
  - Bootstrap 5
  - jQuery
  - Select2
  - DataTables
  - Chart.js
  - SweetAlert2
- **Additional Packages**:
  - Yajra DataTables
  - Laravel Livewire
  - Intervention Image (if used)

## Requirements
- PHP >= 8.2
- Composer
- MySQL >= 5.7 or MariaDB >= 10.3
- Node.js & NPM (for asset compilation)
- Apache web server

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/engineermaesa/sys.daxtro.com.git
   cd sys.daxtro.com
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Configure database**
DM me via WA for env file, i forgor and can't be bothered to .gitignore the files.

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Compile assets**
   ```bash
   npm run dev
   # or for production
   npm run build
   ```

8. **Start development server**
   ```bash
   php artisan serve
   ```

9. **Access the application**
    - URL: `http://127.0.0.1:8000`


## Project Structure

```
sys.daxtro.com/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           # Authentication controllers
│   │   │   ├── Leads/          # Lead & meeting management
│   │   │   ├── Masters/        # Master data controllers
│   │   │   └── DashboardController.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── Leads/             # Lead-related models
│   │   ├── Masters/           # Master data models
│   │   ├── Orders/            # Expense models
│   │   └── User.php
│   ├── Services/
│   │   └── AutoTrashService.php
│   └── Classes/
│       └── ActivityLogger.php
├── config/
│   └── cities.php             # Indonesia cities configuration
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── layouts/           # Layout templates
│   │   ├── pages/
│   │   │   ├── leads/         # Lead views
│   │   │   ├── dashboard/     # Dashboard views
│   │   │   └── masters/       # Master data views
│   │   └── partials/          # Reusable components
│   └── js/
│       └── app.js
├── routes/
│   └── web.php
└── public/
    └── index.php
```

Disclaimer: The listed directories above only contain files that were modified during Batch 9 Internship Program. Further documentation about other features are not listed, as they were horribly vibecoded by the former vendor and also NOT explored during The Woeful Intern (me)'s short stay in this dumpster fire of a company. Please tweak according to requirements given by the user. xoxo

## Database Tables

### Core Tables
- `users` - User accounts
- `roles` - User roles
- `branches` - Branch locations
- `regions` - Geographic regions
- `provinces` - Province data

### Lead Management
- `leads` - Main lead data
- `lead_claims` - Lead assignments to sales
- `lead_statuses` - Lead status definitions
- `lead_status_logs` - Lead status history
- `lead_sources` - Lead source types
- `lead_segments` - Lead categorization

### Meeting Management
- `lead_meetings` - Meeting schedules
- `lead_meeting_details` - Multiple leads per meeting
- `lead_meeting_reschedules` - Reschedule history
- `meeting_types` - Meeting type definitions

### Products & Pricing
- `ref_products` - Product catalog
- `ref_expense_types` - Expense categories

### Expenses
- `meeting_expenses` - Meeting expense records
- `meeting_expense_details` - Expense line items
- `finance_requests` - Expense approval requests

## User Roles & Permissions

### Super Admin
- Full system access
- Manage all branches and regions
- View all leads and meetings
- System configuration

### Branch Manager
- Manage branch operations
- View branch-specific data
- Approve local operations
- Monitor branch performance

### Sales
- Claim and manage leads
- Schedule meetings
- Submit expenses
- Track personal performance

### Finance
- Review expense requests
- Approve/reject expenses
- Financial reporting

## Key Workflows*
* This is about the gist of it. Feel free to explore the current system for further context.

### Lead Management Flow
1. Lead enters system (from various sources)
2. Sales claims lead → Status: Cold
3. Meeting scheduled → Status remains Cold
4. Meeting completed successfully → Status: Warm
5. Follow-up activities → Status: Hot
6. Deal closed → Status: Deal
7. Inactive leads (10 days) → Auto-trash

### Meeting Flow
1. Sales schedules meeting (online/offline)
2. For offline: Submit expenses for approval
3. Finance reviews and approves/rejects
4. Meeting occurs
5. Sales records result (Success/Failed)
6. System updates lead status based on result

### Expense Flow
1. Sales schedules offline meeting
2. Enter expense details
3. Submit to finance (status: submitted)
4. Finance reviews
5. Approve → Sales can proceed
6. Reject → Sales revises and resubmits

## API Endpoints (AJAX)

- `POST /dashboard/sales-achievement-donut` - Sales achievement data
- `POST /dashboard/sales-performance-bar` - Sales performance data
- `POST /dashboard/target-vs-sales-monthly` - Monthly targets
- `POST /leads/my/cold/list` - Cold leads datatable
- `POST /leads/my/warm/list` - Warm leads datatable
- `POST /leads/my/hot/list` - Hot leads datatable
- `POST /leads/my/deal/list` - Deal leads datatable

## Configuration

### Cities Configuration
Edit `config/cities.php` to update Indonesia city list:
```php
return [
    'Jakarta Pusat',
    'Jakarta Utara',
    // ... more cities
];
```

### Auto-Trash Settings
Configure in `app/Services/AutoTrashService.php`:
```php
const INACTIVE_DAYS = 10; // Days before auto-trash
```

## Troubleshooting

### Migration Not Working
- Add the tables directly using PHPMyAdmin with MySQL.

### CSRF Token Mismatch
- Clear browser cache and cookies
- Check `APP_URL` in `.env` matches your domain
- Ensure session driver is properly configured
- Didn't work? Ask your favorite LLM.

### Select2 Not Working
- Run `npm install`
- Compile assets: `npm run dev`
- Clear browser cache
- Didn't work? Ask your favorite LLM.

### Database Connection Error
- Verify MySQL service is running
- Check `.env` database credentials
- Didn't work? Ask your favorite LLM.

## Development Notes

### Adding New Features
1. Create migration: `php artisan make:migration create_table_name`*
2. Create model: `php artisan make:model ModelName`*
3. Create controller: `php artisan make:controller ControllerName`
4. Add routes in `routes/web.php`
5. Create views in `resources/views/`

* refer to Migration Not Working in Troubleshooting section.

### Code Standards
- Follow PSR-12 coding standards
- Use type hints for method parameters
- Add doc blocks for complex methods
- Keep controllers thin, use services for business logic
- Leave clear comments

## Contributing
1. Fork the repository
2. Create feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -m 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit pull request

## License
This project is proprietary software. All rights reserved.

**Built with 💔 using Laravel**
Test

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