<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'manage users',
            'manage roles',
            'manage permissions',
            'view cameras',
            'manage cameras',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions

        // User role
        $role = Role::firstOrCreate(['name' => 'user']);
        $role->givePermissionTo('view cameras');

        // Admin role
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(Permission::all());

        // Super-Admin role
        $role = Role::firstOrCreate(['name' => 'super-admin']);
        // Super admin gets all permissions via Gate::before rule usually, but explicit assignment is fine too
        // or just give all permissions
        $role->givePermissionTo(Permission::all());

        // Create/Find Super Admin User
        $email = 'azizbektursunovofficial@gmail.com';
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Azizbek Tursunov',
                'email' => $email,
                'password' => Hash::make('password'), // Default password, user should change
                'email_verified_at' => now(),
            ]);
            $this->command->info("Created user: {$email}");
        }

        $user->assignRole('super-admin');
        $this->command->info("Assigned super-admin role to: {$email}");
    }
}
