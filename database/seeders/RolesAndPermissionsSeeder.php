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
        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);
        Permission::firstOrCreate(['name' => 'approve requests']);

        // Define roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $superuserRole = Role::firstOrCreate(['name' => 'superuser']);  // Add superuser role

        // Assign permissions to roles
        $admin->givePermissionTo(Permission::all());
        $manager->givePermissionTo(['create users', 'edit users', 'approve requests']);
        $employee->givePermissionTo(['create users']);

        // Create a Superuser and assign role
        $superuser = User::firstOrCreate([
            'email' => 'superuser@example.com',
        ], [
            'name' => 'Super User',
            'password' => bcrypt('12345678'), // Hash the password
        ]);
        
        // Assign all permissions to the superuser role
        $superuser->assignRole('superuser');  // Assigning the superuser role
    }
}
