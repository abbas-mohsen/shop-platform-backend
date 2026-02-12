<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create
                            {--email= : The email for the super admin}
                            {--name= : The name for the super admin}
                            {--password= : The password (will be prompted if omitted)}
                            {--promote : Promote an existing user by email instead of creating one}';

    protected $description = 'Create a new super admin or promote an existing user to super_admin';

    public function handle(): int
    {
        if ($this->option('promote')) {
            return $this->promoteExisting();
        }

        return $this->createNew();
    }

    /**
     * Promote an existing user to super_admin.
     */
    private function promoteExisting(): int
    {
        $email = $this->option('email') ?: $this->ask('Email of the user to promote');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with email: {$email}");
            return self::FAILURE;
        }

        if ($user->isSuperAdmin()) {
            $this->info("{$user->name} is already a super admin.");
            return self::SUCCESS;
        }

        $user->update([
            'role'     => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $this->info("✓ {$user->name} ({$user->email}) promoted to super_admin.");

        return self::SUCCESS;
    }

    /**
     * Create a brand-new super admin user.
     */
    private function createNew(): int
    {
        $name     = $this->option('name')     ?: $this->ask('Name');
        $email    = $this->option('email')    ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password (min 8 chars)');

        // Validate
        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $this->info("✓ Super admin created: {$user->name} ({$user->email})");

        return self::SUCCESS;
    }
}
