<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Dashboard', 'code' => 'dashboard', 'description' => 'Access dashboard'],
            ['name' => 'Agents', 'code' => 'masters.agents', 'description' => 'Manage agents'],
            ['name' => 'Source', 'code' => 'masters.sources', 'description' => 'Manage lead sources'],
            ['name' => 'Banks', 'code' => 'masters.banks', 'description' => 'Manage banks'],
            ['name' => 'Accounts', 'code' => 'masters.accounts', 'description' => 'Manage accounts'],
            ['name' => 'Product Categories', 'code' => 'masters.product-categories', 'description' => 'Manage product categories'],
            ['name' => 'Product Types', 'code' => 'masters.product-types', 'description' => 'Manage product types'],
            ['name' => 'Products', 'code' => 'masters.products', 'description' => 'Manage products'],
            ['name' => 'Parts', 'code' => 'masters.parts', 'description' => 'Manage parts'],
            ['name' => 'Companies', 'code' => 'masters.companies', 'description' => 'Manage companies'],
            ['name' => 'Provinces', 'code' => 'masters.provinces', 'description' => 'Manage provinces'],
            ['name' => 'Ref Provinces', 'code' => 'masters.provinces', 'description' => 'Manage ref provinces'],
            ['name' => 'Regions', 'code' => 'masters.regions', 'description' => 'Manage regions'],
            ['name' => 'Branches', 'code' => 'masters.branches', 'description' => 'Manage branches'],
            ['name' => 'Expense Types', 'code' => 'masters.expense-types', 'description' => 'Manage expense types'],
            ['name' => 'Customer Types', 'code' => 'masters.customer-types', 'description' => 'Manage customer types'],
            ['name' => 'Manage Leads', 'code' => 'leads.manage', 'description' => 'Manage leads'],
            ['name' => 'Available Leads', 'code' => 'leads.available', 'description' => 'View available leads'],
            ['name' => 'My Leads', 'code' => 'leads.my', 'description' => 'View my leads'],
            ['name' => 'Import Leads', 'code' => 'leads.import', 'description' => 'Import leads'],
            ['name' => 'Trash Leads', 'code' => 'leads.trash', 'description' => 'View trash leads'],
            ['name' => 'Orders', 'code' => 'orders', 'description' => 'Manage orders'],
            ['name' => 'Incentive Dashboard', 'code' => 'incentives.view', 'description' => 'View incentive balance and logs'],
            ['name' => 'Finance Requests', 'code' => 'finance.requests', 'description' => 'Handle finance approvals'],
            ['name' => 'Purchasing Log', 'code' => 'purchasing.log', 'description' => 'Access purchasing log'],
            ['name' => 'Manage Users', 'code' => 'users.manage', 'description' => 'Manage users'],
            ['name' => 'Roles', 'code' => 'users.roles', 'description' => 'Manage roles'],
            ['name' => 'Permissions Settings', 'code' => 'settings.permissions-settings', 'description' => 'Manage permissions settings'],
            ['name' => 'Settings', 'code' => 'settings.general-settings', 'description' => 'Manage general settings'],
            ['name' => 'Quotation Approvals', 'code' => 'quotation.approvals', 'description' => 'Quotation Approvals'],
            ['name' => 'Customers', 'code' => 'customers.view', 'description' => 'View customer technical specification data'],
            ['name' => 'Satisfaction Survey', 'code' => 'satisfaction-survey.view', 'description' => 'View technician satisfaction survey responses'],
            ['name' => 'Review Satisfaction Survey', 'code' => 'satisfaction-survey.review', 'description' => 'Mark satisfaction survey responses as reviewed'],

            // DATS (Aftersales Ticketing System)
            ['name' => 'Manage Tickets', 'code' => 'aftersales.tickets.manage', 'description' => 'Create and manage aftersales tickets'],
            ['name' => 'Schedule Recoming', 'code' => 'aftersales.tickets.recoming', 'description' => 'Schedule a follow-up visit (Recoming) on own assigned ticket'],
            ['name' => 'Share Survey Link', 'code' => 'aftersales.survey-link.share', 'description' => 'Copy/share the satisfaction survey link of own assigned ticket'],
            ['name' => 'Change Customer Phone', 'code' => 'aftersales.customer-phone.change', 'description' => 'Change the customer WhatsApp number (OTP destination)'],
            ['name' => 'View Own Tickets', 'code' => 'aftersales.tickets.view-own', 'description' => 'Technician: see and open own assigned tickets'],
            ['name' => 'View All Tickets', 'code' => 'aftersales.tickets.view-all', 'description' => 'Read-only access to all aftersales tickets and their documents'],
            ['name' => 'Sign Documents', 'code' => 'aftersales.documents.sign', 'description' => 'SPV Technician: review and sign Service Form and Service Agreement of the ticket supervised'],
            ['name' => 'Fill Documents', 'code' => 'aftersales.documents.fill', 'description' => 'Technician: fill and sign Service Form and Service Agreement of own tickets'],
            ['name' => 'Assign Technician', 'code' => 'aftersales.tickets.assign', 'description' => 'Assign technician to a ticket'],
            ['name' => 'Close Tickets', 'code' => 'aftersales.tickets.close', 'description' => 'Close aftersales tickets'],
            ['name' => 'Submit Documentation', 'code' => 'aftersales.documentation.submit', 'description' => 'Submit ticket repair documentation'],
            ['name' => 'Submit Satisfaction', 'code' => 'aftersales.satisfaction.submit', 'description' => 'Submit ticket customer satisfaction assessment'],
            ['name' => 'Issue Work Orders', 'code' => 'aftersales.work-orders.issue', 'description' => 'Issue and print ticket work orders'],
            ['name' => 'Manage Service Customers', 'code' => 'masters.service-customers', 'description' => 'Manage aftersales service customers and units'],
            ['name' => 'Manage Technicians', 'code' => 'masters.technicians', 'description' => 'Manage aftersales technician masters'],
            ['name' => 'Manage Spareparts', 'code' => 'masters.spareparts', 'description' => 'Manage aftersales sparepart master'],
            ['name' => 'Manage Stock Movements', 'code' => 'aftersales.stock.manage', 'description' => 'Manage sparepart stock movements'],
            ['name' => 'View Technician KPI', 'code' => 'aftersales.kpi.view', 'description' => 'View technician KPI dashboard'],
        ];

        foreach ($permissions as $perm) {
            DB::table('user_permissions')->updateOrInsert(
                ['code' => $perm['code']],
                $perm
            );
        }
    }
}
