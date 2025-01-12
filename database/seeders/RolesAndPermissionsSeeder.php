<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

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

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $superuserRole = Role::firstOrCreate(['name' => 'superuser']);  // Add superuser role

        // Assign permissions to roles
        $admin->givePermissionTo(Permission::all());
        $manager->givePermissionTo(['view users', 'create users', 'edit users', 'approve requests']);
        $employee->givePermissionTo(['view users']);

        // Create a Superuser and assign role
        $superuser = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'admin',
            'password' => bcrypt('12345678'), // Hash the password
        ]);
        
        // Assign all permissions to the superuser role
        $superuser->assignRole('admin');  // Assigning the superuser role
    }
}
