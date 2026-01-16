<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Company; 
use App\Models\CompanyOwner;
use App\Models\Category;
use App\Models\Site;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Get the first active site
        $site = Site::where('is_active', true)->first();
        if (!$site) {
            $this->command->error('No active site found. Please create a site first.');
            return;
        }

        // Clear the cache of permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ================= PERMISSIONS =================
        $permissions = [
            // User permissions
            'view users', 'create users', 'edit users', 'delete users', 'approve requests', 'users.import', 'users.export',

            // Customer request permissions
            'view supplier requests', 'create supplier requests', 'edit supplier requests', 'delete supplier requests', 
            'approve supplier requests', 'reject supplier requests',
            
            // Supplier permissions
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers', 'suppliers.import', 'suppliers.export',
            
            // Customer permissions
            'view customers', 'create customers', 'edit customers', 'delete customers', 'customers.import', 'customers.export',
            
            // Customer request permissions
            'view customer requests', 'create customer requests', 'edit customer requests', 'delete customer requests',
            'approve customer requests', 'reject customer requests',
            
            // Inventory item permissions
            'view inventory items', 'create inventory items', 'edit inventory items', 'delete inventory items',
            'add new category', 'inventory.import', 'inventory.export',
            
            // Purchase order permissions
            'view purchase orders', 'create purchase orders', 'edit purchase orders', 'delete purchase orders',
            'purchase_orders.export',
            
            // Request for Quotation permissions
            'View Request For Quotations','Create Request For Quotations','Edit Request For Quotations','Delete Request For Quotations',
            'Handle Request For Quotations','Request For Quotations.export','Approve Request For Quotation','Send Request For Quotations',
            'Cancel Request For Quotations','Reopen Request For Quotations','Convert Request For Quotations to Draft',
            
            // Supplier/ Purchase quotation permissions
            'View Purchase Quotations','Create Purchase Quotations','Edit Purchase Quotations','Delete Purchase Quotations',
            'Handle Purchase Quotations','Purchase Quotations.export','Approve Purchase Quotation','Reject Purchase Quotation',
            'Convert to Purchase Order from Purchase Quotation','Convert Back to Draft Purchase Quotation','Create Rejection Note for Purchase Quotation',
            
            // Customer order permissions
            'view customer orders','create customer orders','edit customer orders','delete customer orders','customer_orders.export',
            
            // Sample order permissions
            'view sample orders','create sample orders','edit sample orders','delete sample orders','sample orders.export',
            
            // Warehouse permissions
            'view warehouses','create warehouses','edit warehouses','delete warehouses','warehouses.export',
            
            // Inventory Location permissions
            'view inventory locations','create inventory locations','edit inventory locations','delete inventory locations',
            'inventory location.import','inventory location.export',
            
            // Third Party Services permissions
            'view third party services','create third party services','edit third party services','delete third party services',
            'third party services.export','create third party service payments',
            
            // Production Machines permissions
            'view production machines','create production machines','edit production machines','delete production machines',
            'production machines.import','production machines.export',
            
            // Production Line Operations permissions
            'view workstations','create workstations','edit workstations','delete workstations','workstations.export',
            
            // Production Line permissions
            'view production lines','create production lines','edit production lines','delete production lines',
            'production lines.import','production-lines.export',
            
            // Register Arrival permissions
            'view register arrivals','create register arrivals','re-correct register arrivals',
            
            // Release Material permissions
            'view release materials','create release materials','re-correct release materials','release materials.export',
            
            // Material QC permissions
            'view material qc','create material qc','re-correct material qc',
            
            // Activity log permissions
            'view self activity logs','view other users activity logs',
            
            // Change daily for production data 
            'select_previous_performance_dates','select_next_operation_dates',
            
            // view audit columns
            'view audit columns',
            
            // Customer advance invoices
            'view cus_adv_invoice','create cus_adv_invoices','delete cus_adv_invoice','cus_adv_invoice.export',
            
            // Cutting stations
            'view cutting stations','create cutting stations','edit cutting stations','delete cutting stations','cutting_station.export',
            
            // Cutting records
            'view cutting records','recorrect cutting records','create cutting records','cutting_record.export',
            
            // Assign daily operation
            'view assign daily operations','create assign daily operations','edit assign daily operations','assign daily operation.export',
            
            // Enter performance records
            'view performace records','create performace records','performance_record.export',
            
            // End of day reports
            'view end of day reports','create end of day reports','end_of_day_report.export',
            
            // Material QC reports
            'create material qc records','material qc.export',
            
            // Final products QC reports
            'view product qc records','create product qc records','product qc.export',
            
            // Non-Inventory items
            'view non inventory items','create non inventory items','non inventory item.export',
            
            // Purchase order invoices
            'view purchase order invoices','create purchase order invoices','purchase_order_invoices.export','pay purchase order invoice',
            
            // Purchase order Advance invoices
            'view supplier advance invoices','supplier advance invoices.export','create supplier advance invoices','pay supp adv invoice',
            
            // Stocks
            'view stocks','create emergency stocks','stocks.import','stock.export',
            
            // Temporary Operations
            'view temporary operations','create temporary operations','temporary operations.export',
            
            // Change dates for CO/SO expences/discounts
            'view order discount','view order expences','edit order discount','edit order expences','delete order discount','delete order expences',
            'backdate order discount','future order discount',
            
            // Change dates for payments
            'Allow Backdated Payments','Allow Future Payments',
            
            // Company settings
            'view company settings','edit company settings',

            // Employee management
            'view employees', 'create employees', 'edit employees', 'delete employees', 'Manage Employee Profile', 'Manage EPF ETF Groups',

            // Delivery terms
            'view delivery terms', 'create delivery terms', 'edit delivery terms', 'delete delivery terms',

            // Delivery methods
            'view delivery methods', 'create delivery methods', 'edit delivery methods', 'delete delivery methods',

            // Payment terms
            'view payment terms', 'create payment terms', 'edit payment terms', 'delete payment terms',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ================= ROLES =================
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $superuser = Role::firstOrCreate(['name' => 'superuser']);  
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $qc = Role::firstOrCreate(['name' => 'Quality Control']);

        // Assign all permissions to the admin role
        $admin->givePermissionTo(Permission::all());

        // Manager & Employee limited permissions
        $manager->givePermissionTo(['view users', 'create users', 'edit users', 'approve requests']);
        $employee->givePermissionTo(['view users']);

        // ================= DEFAULT SUPERUSER =================
        $superuserUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'admin',
                'password' => bcrypt('12345678'), 
                'phone_1' => 123456789, 
                'nic' => 123456789,
                'address_line_1' => 'Default Address',
                'city' => 'Default City',
                'site_id' => $site->id,
            ]
        );
        $superuserUser->assignRole('admin'); // Assign admin role

        // ================= COMPANY & OWNER =================
        $company = Company::firstOrCreate(
            ['name' => 'Textile Manufacturing Co.'],
            [
                'address_line_1' => '123 Industrial Zone',
                'address_line_2' => 'Garment Street',
                'address_line_3' => '',
                'city' => 'Colombo',
                'postal_code' => '01000',
                'country' => 'Sri Lanka',
                'primary_phone' => '+94112345678',
                'secondary_phone' => '+94112345679',
                'email' => 'owner@textileco.com',
                'started_date' => '2010-01-15',
                'special_notes' => 'Leading textile manufacturer since 2010',
                'site_id' => $site->id,
            ]
        );

        CompanyOwner::firstOrCreate(
            ['company_id' => $company->id],
            [
                'name' => 'Mr. Rajapakse',
                'address_line_1' => '456 Owners Avenue',
                'address_line_2' => 'Highland Gardens',
                'address_line_3' => '',
                'city' => 'Colombo',
                'postal_code' => '01002',
                'country' => 'Sri Lanka',
                'phone_1' => '+94119876543',
                'phone_2' => '+94119876544',
                'email' => 'owner@textileco.com',
                'joined_date' => '2010-01-15',
                'site_id' => $site->id,
            ]
        );

        // ================= DEFAULT CATEGORIES =================
        Category::firstOrCreate(['name' => 'Waste Item', 'site_id' => $site->id]);
        Category::firstOrCreate(['name' => 'By Products', 'site_id' => $site->id]);

        $this->command->info('Roles, permissions, default user, company, and categories seeded for site ID: ' . $site->id);
    }
}
