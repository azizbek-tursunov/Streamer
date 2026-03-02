<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clean existing cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Step 1: Define explicit permissions
        $permissions = [
            'view-cameras',                // Kameralar -> Ro'yxat
            'manage-cameras',              // Modify camera list
            'view-camera-grid',            // Kameralar -> Mozaika
            'view-auditoriums',            // O'quv jarayoni -> Auditoriyalar
            'manage-auditorium-faculty',   // Fakultetga biriktirish button
            'sync-auditoriums',            // HEMIS sinxronlash button
            'manage-auditorium-cameras',   // Kamera ulash dialog
            'add-feedbacks',               // Baholash button in auditoriums
            'view-feedbacks',              // Dars tahlili
            'manage-users',                // Foydalanuvchilar and hemis auth
        ];

        // Step 2: Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Step 3: Create internal roles and assign specific permissions
        
        // --- IT-Technician ---
        $itTechnicianRole = Role::firstOrCreate(['name' => 'it-technician']);
        $itTechnicianRole->syncPermissions([
            'view-cameras',
            'manage-cameras',
            'view-camera-grid',
            'view-auditoriums',
            'manage-auditorium-faculty',
            'sync-auditoriums',
            'manage-auditorium-cameras',
        ]);

        // --- Department ---
        $departmentRole = Role::firstOrCreate(['name' => 'department']);
        $departmentRole->syncPermissions([
            'view-camera-grid',
            'view-auditoriums',
            'add-feedbacks',
            'view-feedbacks',
        ]);

        // --- Deans ---
        $deansRole = Role::firstOrCreate(['name' => 'deans']);
        $deansRole->syncPermissions([
            'view-camera-grid',
            'view-auditoriums',
        ]);

        // --- Admin ---
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions); // Gives all explicit permissions

        // Step 4: Create Super-Admin role (Globally catches all permissions inherently via explicit checks)
        // Super admin generally intercepts via a Gate logic in AuthServiceProvider, but we establish the Role here natively
        Role::firstOrCreate(['name' => 'super-admin']);

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
