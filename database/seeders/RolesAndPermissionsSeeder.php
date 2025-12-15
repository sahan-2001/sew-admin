<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Company; 
use App\Models\CompanyOwner;
use App\Models\Category;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Clear the cache of permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // User permissions
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);
        Permission::firstOrCreate(['name' => 'approve requests']);
        Permission::firstOrCreate(['name' => 'users.import']);
        Permission::firstOrCreate(['name' => 'users.export']);

        // Customer request permissions
        Permission::firstOrCreate(['name' => 'view supplier requests']);
        Permission::firstOrCreate(['name' => 'create supplier requests']);
        Permission::firstOrCreate(['name' => 'edit supplier requests']);
        Permission::firstOrCreate(['name' => 'delete supplier requests']);
        Permission::firstOrCreate(['name' => 'approve supplier requests']);
        Permission::firstOrCreate(['name' => 'reject supplier requests']);

        // Supplier permissions
        Permission::firstOrCreate(['name' => 'view suppliers']);
        Permission::firstOrCreate(['name' => 'create suppliers']);
        Permission::firstOrCreate(['name' => 'edit suppliers']);
        Permission::firstOrCreate(['name' => 'delete suppliers']);
        Permission::firstOrCreate(['name' => 'suppliers.import']);
        Permission::firstOrCreate(['name' => 'suppliers.export']);
        
        // Customer permissions
        Permission::firstOrCreate(['name' => 'view customers']);
        Permission::firstOrCreate(['name' => 'create customers']);
        Permission::firstOrCreate(['name' => 'edit customers']);
        Permission::firstOrCreate(['name' => 'delete customers']);
        Permission::firstOrCreate(['name' => 'customers.import']);
        Permission::firstOrCreate(['name' => 'customers.export']);

        // Customer request permissions
        Permission::firstOrCreate(['name' => 'view customer requests']);
        Permission::firstOrCreate(['name' => 'create customer requests']);
        Permission::firstOrCreate(['name' => 'edit customer requests']);
        Permission::firstOrCreate(['name' => 'delete customer requests']);
        Permission::firstOrCreate(['name' => 'approve customer requests']);
        Permission::firstOrCreate(['name' => 'reject customer requests']);

        // Inventory item permissions
        Permission::firstOrCreate(['name' => 'view inventory items']);
        Permission::firstOrCreate(['name' => 'create inventory items']);
        Permission::firstOrCreate(['name' => 'edit inventory items']);
        Permission::firstOrCreate(['name' => 'delete inventory items']);
        Permission::firstOrCreate(['name' => 'add new category']);
        Permission::firstOrCreate(['name' => 'inventory.import']);
        Permission::firstOrCreate(['name' => 'inventory.export']);

        // Purchase order permissions
        Permission::firstOrCreate(['name' => 'view purchase orders']);
        Permission::firstOrCreate(['name' => 'create purchase orders']);
        Permission::firstOrCreate(['name' => 'edit purchase orders']);
        Permission::firstOrCreate(['name' => 'delete purchase orders']);
        Permission::firstOrCreate(['name' => 'purchase_orders.export']);

        // Customer order permissions
        Permission::firstOrCreate(['name' => 'view customer orders']);
        Permission::firstOrCreate(['name' => 'create customer orders']);
        Permission::firstOrCreate(['name' => 'edit customer orders']);
        Permission::firstOrCreate(['name' => 'delete customer orders']);
        Permission::firstOrCreate(['name' => 'customer_orders.export']);

        // Sample order permissions
        Permission::firstOrCreate(['name' => 'view sample orders']);
        Permission::firstOrCreate(['name' => 'create sample orders']);
        Permission::firstOrCreate(['name' => 'edit sample orders']);
        Permission::firstOrCreate(['name' => 'delete sample orders']);
        Permission::firstOrCreate(['name' => 'sample orders.export']);

        // Warehouse permissions
        Permission::firstOrCreate(['name' => 'view warehouses']);
        Permission::firstOrCreate(['name' => 'create warehouses']);
        Permission::firstOrCreate(['name' => 'edit warehouses']);
        Permission::firstOrCreate(['name' => 'delete warehouses']);
        Permission::firstOrCreate(['name' => 'warehouses.export']);

        // Inventory Location permissions
        Permission::firstOrCreate(['name' => 'view inventory locations']);
        Permission::firstOrCreate(['name' => 'create inventory locations']);
        Permission::firstOrCreate(['name' => 'edit inventory locations']);
        Permission::firstOrCreate(['name' => 'delete inventory locations']);
        Permission::firstOrCreate(['name' => 'inventory location.import']);
        Permission::firstOrCreate(['name' => 'inventory location.export']);

        // Third Party Services permissions
        Permission::firstOrCreate(['name' => 'view third party services']);
        Permission::firstOrCreate(['name' => 'create third party services']);
        Permission::firstOrCreate(['name' => 'edit third party services']);
        Permission::firstOrCreate(['name' => 'delete third party services']);
        Permission::firstOrCreate(['name' => 'third party services.export']);
        Permission::firstOrCreate(['name' => 'create third party service payments']);

        // Production Machines permissions
        Permission::firstOrCreate(['name' => 'view production machines']);
        Permission::firstOrCreate(['name' => 'create production machines']);
        Permission::firstOrCreate(['name' => 'edit production machines']);
        Permission::firstOrCreate(['name' => 'delete production machines']);
        Permission::firstOrCreate(['name' => 'production machines.import']);
        Permission::firstOrCreate(['name' => 'production machines.export']);

        // Production Line Operations permissions
        Permission::firstOrCreate(['name' => 'view workstations']);
        Permission::firstOrCreate(['name' => 'create workstations']);
        Permission::firstOrCreate(['name' => 'edit workstations']);
        Permission::firstOrCreate(['name' => 'delete workstations']);
        Permission::firstOrCreate(['name' => 'workstations.export']);

        // Production Line permissions
        Permission::firstOrCreate(['name' => 'view production lines']);
        Permission::firstOrCreate(['name' => 'create production lines']);
        Permission::firstOrCreate(['name' => 'edit production lines']);
        Permission::firstOrCreate(['name' => 'delete production lines']);
        Permission::firstOrCreate(['name' => 'production lines.import']);
        Permission::firstOrCreate(['name' => 'production-lines.export']);

        // Register Arrival permissions
        Permission::firstOrCreate(['name' => 'view register arrivals']);
        Permission::firstOrCreate(['name' => 'create register arrivals']);
        Permission::firstOrCreate(['name' => 're-correct register arrivals']);

        // Release Material permissions
        Permission::firstOrCreate(['name' => 'view release materials']);
        Permission::firstOrCreate(['name' => 'create release materials']);
        Permission::firstOrCreate(['name' => 're-correct release materials']);
        Permission::firstOrCreate(['name' => 'release materials.export']);

        // Material QC permissions
        Permission::firstOrCreate(['name' => 'view material qc']);
        Permission::firstOrCreate(['name' => 'create material qc']);
        Permission::firstOrCreate(['name' => 're-correct material qc']);

        // Activity log permissions
        Permission::firstOrCreate(['name' => 'view self activity logs']);
        Permission::firstOrCreate(['name' => 'view other users activity logs']);

        // Change daily for production data 
        Permission::firstOrCreate(['name' => 'select_previous_performance_dates']);
        Permission::firstOrCreate(['name' => 'select_next_operation_dates']);

        // view audit columns
        Permission::firstOrCreate(['name' => 'view audit columns']);

        // Customer advance invoices
        Permission::firstOrCreate(['name' => 'view cus_adv_invoice']);
        Permission::firstOrCreate(['name' => 'create cus_adv_invoices']);
        Permission::firstOrCreate(['name' => 'delete cus_adv_invoice']);
        Permission::firstOrCreate(['name' => 'cus_adv_invoice.export']);
        Permission::firstOrCreate(['name' => 'create cus_adv_invoices']);

        // Cutting stations
        Permission::firstOrCreate(['name' => 'view cutting stations']);
        Permission::firstOrCreate(['name' => 'create cutting stations']);
        Permission::firstOrCreate(['name' => 'edit cutting stations']);
        Permission::firstOrCreate(['name' => 'delete cutting stations']);
        Permission::firstOrCreate(['name' => 'cutting_station.export']);
        
        // Cutting records
        Permission::firstOrCreate(['name' => 'view cutting records']);
        Permission::firstOrCreate(['name' => 'recorrect cutting records']);
        Permission::firstOrCreate(['name' => 'create cutting records']);
        Permission::firstOrCreate(['name' => 'cutting_record.export']);

        // Assign daily operation
        Permission::firstOrCreate(['name' => 'view assign daily operations']);
        Permission::firstOrCreate(['name' => 'create assign daily operations']);
        Permission::firstOrCreate(['name' => 'edit assign daily operations']);
        Permission::firstOrCreate(['name' => 'assign daily operation.export']);

        // Enter performance records
        Permission::firstOrCreate(['name' => 'view performace records']);
        Permission::firstOrCreate(['name' => 'create performace records']);
        Permission::firstOrCreate(['name' => 'performance_record.export']);

        // End of day reports
        Permission::firstOrCreate(['name' => 'view end of day reports']);
        Permission::firstOrCreate(['name' => 'create end of day reports']);
        Permission::firstOrCreate(['name' => 'end_of_day_report.export']);

        // Material QC reports
        Permission::firstOrCreate(['name' => 'create material qc records']);
        Permission::firstOrCreate(['name' => 'material qc.export']);

        // Final products QC reports
        Permission::firstOrCreate(['name' => 'view product qc records']);
        Permission::firstOrCreate(['name' => 'create product qc records']);
        Permission::firstOrCreate(['name' => 'product qc.export']);

        // Non-Inventory items
        Permission::firstOrCreate(['name' => 'view non inventory items']);
        Permission::firstOrCreate(['name' => 'create non inventory items']);
        Permission::firstOrCreate(['name' => 'non inventory item.export']);

        // Purchase order invoices
        Permission::firstOrCreate(['name' => 'view purchase order invoices']);
        Permission::firstOrCreate(['name' => 'create purchase order invoices']);
        Permission::firstOrCreate(['name' => 'purchase_order_invoices.export']);
        Permission::firstOrCreate(['name' => 'pay purchase order invoice']);

        // Purchase order Advance invoices
        Permission::firstOrCreate(['name' => 'view supplier advance invoices']);
        Permission::firstOrCreate(['name' => 'supplier advance invoices.export']);
        Permission::firstOrCreate(['name' => 'create supplier advance invoices']);
        Permission::firstOrCreate(['name' => 'pay supp adv invoice']);

        // Stocks
        Permission::firstOrCreate(['name' => 'view stocks']);
        Permission::firstOrCreate(['name' => 'create emergency stocks']);
        Permission::firstOrCreate(['name' => 'stocks.import']);
        Permission::firstOrCreate(['name' => 'stock.export']);

        // Temporary Operations
        Permission::firstOrCreate(['name' => 'view temporary operations']);
        Permission::firstOrCreate(['name' => 'create temporary operations']);
        Permission::firstOrCreate(['name' => 'temporary operations.export']);

        // Change dates for CO/SO expences/discounts
        Permission::firstOrCreate(['name' => 'view order discount']);
        Permission::firstOrCreate(['name' => 'view order expences']);

        Permission::firstOrCreate(['name' => 'edit order discount']);
        Permission::firstOrCreate(['name' => 'edit order expences']);
        Permission::firstOrCreate(['name' => 'delete order discount']);
        Permission::firstOrCreate(['name' => 'delete order expences']);

        Permission::firstOrCreate(['name' => 'backdate order discount']);
        Permission::firstOrCreate(['name' => 'future order discount']);

        // Change dates for payments
        Permission::firstOrCreate(['name' => 'Allow Backdated Payments']);
        Permission::firstOrCreate(['name' => 'Allow Future Payments']);

        // Company settings
        Permission::firstOrCreate(['name' => 'view company settings']);
        Permission::firstOrCreate(['name' => 'edit company settings']);



        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $superuser = Role::firstOrCreate(['name' => 'superuser']);  
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $qc = Role::firstOrCreate(['name' => 'Quality Control']);

        // Assign permissions to roles
        $admin->givePermissionTo(Permission::all());
        $manager->givePermissionTo(['view users', 'create users', 'edit users', 'approve requests']);
        $employee->givePermissionTo(['view users']);

        // Create specific position roles
        $positions = [
            'GM' => 'General Manager',
            'FIN' => 'Finance Manager',
            'QC' => 'Quality Controller',
            'TECH' => 'Technician',
            'CUT_SUP' => 'Cutting Supervisor',
            'SEW_SUP' => 'Sewing Line Supervisor',
        ]; 

        // Create a Superuser and assign role
        $superuser = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'admin',
            'password' => bcrypt('12345678'), 
            'phone_1' => 123456789, 
            'nic' => 123456789,
            'address_line_1' => 'Default Address',
            'city' => 'Default City',
        ]);

        // Create the main company
        $company = Company::firstOrCreate([
            'name' => 'Textile Manufacturing Co.',
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
        ]);

        // Create the company owner
        CompanyOwner::firstOrCreate([
            'company_id' => $company->id,
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
        ]);
        
        // Create Default categories
        Category::firstOrCreate(['name' => 'Waste Item']);
        Category::firstOrCreate(['name' => 'By Products']);
        
        // // Assign all permissions to the superuser role
        $superuser->assignRole('admin');  // Assigning the superuser role
    }
}
