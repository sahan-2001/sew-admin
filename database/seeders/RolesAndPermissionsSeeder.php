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

        // Define permissions
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);
        Permission::firstOrCreate(['name' => 'approve requests']);

        // Customer request permissions
        Permission::firstOrCreate(['name' => 'view supplier requests']);
        Permission::firstOrCreate(['name' => 'create supplier requests']);
        Permission::firstOrCreate(['name' => 'edit supplier requests']);
        Permission::firstOrCreate(['name' => 'delete supplier requests']);
        Permission::firstOrCreate(['name' => 'approve supplier requests']);
        Permission::firstOrCreate(['name' => 'reject supplier requests']);

        // Customer permissions
        Permission::firstOrCreate(['name' => 'view suppliers']);
        Permission::firstOrCreate(['name' => 'create suppliers']);
        Permission::firstOrCreate(['name' => 'edit suppliers']);
        Permission::firstOrCreate(['name' => 'delete suppliers']);

        // Customer permissions
        Permission::firstOrCreate(['name' => 'view customers']);
        Permission::firstOrCreate(['name' => 'create customers']);
        Permission::firstOrCreate(['name' => 'edit customers']);
        Permission::firstOrCreate(['name' => 'delete customers']);

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

        // Purchase order permissions
        Permission::firstOrCreate(['name' => 'view purchase orders']);
        Permission::firstOrCreate(['name' => 'create purchase orders']);
        Permission::firstOrCreate(['name' => 'edit purchase orders']);
        Permission::firstOrCreate(['name' => 'delete purchase orders']);

        // Customer order permissions
        Permission::firstOrCreate(['name' => 'view customer orders']);
        Permission::firstOrCreate(['name' => 'create customer orders']);
        Permission::firstOrCreate(['name' => 'edit customer orders']);
        Permission::firstOrCreate(['name' => 'delete customer orders']);

        // Sample order permissions
        Permission::firstOrCreate(['name' => 'view sample orders']);
        Permission::firstOrCreate(['name' => 'create sample orders']);
        Permission::firstOrCreate(['name' => 'edit sample orders']);
        Permission::firstOrCreate(['name' => 'delete sample orders']);

        // Warehouse permissions
        Permission::firstOrCreate(['name' => 'view warehouses']);
        Permission::firstOrCreate(['name' => 'create warehouses']);
        Permission::firstOrCreate(['name' => 'edit warehouses']);
        Permission::firstOrCreate(['name' => 'delete warehouses']);

        // Inventory Location permissions
        Permission::firstOrCreate(['name' => 'view inventory locations']);
        Permission::firstOrCreate(['name' => 'create inventory locations']);
        Permission::firstOrCreate(['name' => 'edit inventory locations']);
        Permission::firstOrCreate(['name' => 'delete inventory locations']);

        // Third Party Services permissions
        Permission::firstOrCreate(['name' => 'view third party services']);
        Permission::firstOrCreate(['name' => 'create third party services']);
        Permission::firstOrCreate(['name' => 'edit third party services']);
        Permission::firstOrCreate(['name' => 'delete third party services']);

        // Production Machines permissions
        Permission::firstOrCreate(['name' => 'view production machines']);
        Permission::firstOrCreate(['name' => 'create production machines']);
        Permission::firstOrCreate(['name' => 'edit production machines']);
        Permission::firstOrCreate(['name' => 'delete production machines']);

        // Production Line Operations permissions
        Permission::firstOrCreate(['name' => 'view workstations']);
        Permission::firstOrCreate(['name' => 'create workstations']);
        Permission::firstOrCreate(['name' => 'edit workstations']);
        Permission::firstOrCreate(['name' => 'delete workstations']);

        // Production Line permissions
        Permission::firstOrCreate(['name' => 'view production lines']);
        Permission::firstOrCreate(['name' => 'create production lines']);
        Permission::firstOrCreate(['name' => 'edit production lines']);
        Permission::firstOrCreate(['name' => 'delete production lines']);

        // Register Arrival permissions
        Permission::firstOrCreate(['name' => 'view register arrivals']);
        Permission::firstOrCreate(['name' => 'create register arrivals']);
        Permission::firstOrCreate(['name' => 're-correct register arrivals']);

        // Release Material permissions
        Permission::firstOrCreate(['name' => 'view release materials']);
        Permission::firstOrCreate(['name' => 'create release materials']);
        Permission::firstOrCreate(['name' => 're-correct release materials']);

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
            'Finance Manager',
            'QC',
            'Technician',
            'Cutting Supervisor',
            'Sewing Line Supervisor'
        ];

        

        // Create a Superuser and assign role
        $superuser = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'admin',
            'password' => bcrypt('12345678'), // Hash the password
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
