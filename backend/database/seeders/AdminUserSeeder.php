<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /**
     * Create (or update) the default admin account used to log into the
     * control panel. Credentials can be provided via environment variables
     * so the same image is safe for local and Railway deployments.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $email = trim((string) env('ADMIN_EMAIL', ''));
        $name = trim((string) env('ADMIN_NAME', 'مدير النظام'));
        $password = (string) env('ADMIN_PASSWORD', '');

        // Never ship or silently create an administrator with a public default password.
        // On a fresh installation, provide ADMIN_EMAIL and ADMIN_PASSWORD as server secrets.
        if ($email === '' || $password === '') {
            $this->command?->warn('Admin account was not created: set ADMIN_EMAIL and ADMIN_PASSWORD explicitly, then run AdminUserSeeder.');
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \\InvalidArgumentException('ADMIN_EMAIL must be a valid email address.');
        }

        if (strlen($password) < 12
            || ! preg_match('/[a-z]/', $password)
            || ! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[0-9]/', $password)
            || ! preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new \\InvalidArgumentException('ADMIN_PASSWORD must be at least 12 characters and include upper/lowercase letters, a number, and a symbol.');
        }
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => env('ADMIN_PHONE', ''),
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions([
            'manage users', 'manage agents', 'manage properties', 'approve properties',
            'manage viewing requests', 'manage settings', 'view reports',
            'create properties', 'edit own properties', 'view incoming requests',
        ]);

        $user->syncRoles(['admin']);

        $this->command?->info("Admin account ready for {$email}; credentials were supplied through server environment.");
    }
}
