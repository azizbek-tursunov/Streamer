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
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'manage-cameras',
            'manage-streams',
            'manage-sync',
            'view-streams',
            'add-comments',
            'analyze-comments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions
        
        // it-technician role
        $itTechnicianRole = Role::firstOrCreate(['name' => 'it-technician']);
        $itTechnicianRole->givePermissionTo([
            'manage-cameras',
            'manage-streams',
            'manage-sync',
        ]);

        // department role
        $departmentRole = Role::firstOrCreate(['name' => 'department']);
        $departmentRole->givePermissionTo([
            'view-streams',
            'add-comments',
            'analyze-comments',
        ]);

        // deans role
        $deansRole = Role::firstOrCreate(['name' => 'deans']);
        $deansRole->givePermissionTo([
            'view-streams',
        ]);

        // Super-Admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());

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
