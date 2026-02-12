<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed a default super admin account.
     * Safe to run multiple times — will skip if the user already exists.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@xtremefit.com');

        if (User::where('email', $email)->exists()) {
            $this->command->info("Super admin already exists: {$email}");
            return;
        }

        User::create([
            'name'     => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'email'    => $email,
            'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'Admin@123!')),
            'role'     => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $this->command->info("✓ Super admin created: {$email}");
        $this->command->warn("  Change the default password immediately!");
    }
}
