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

        $email = env('ADMIN_EMAIL', 'admin@wajhatak.app');
        $name = env('ADMIN_NAME', 'مدير النظام');
        $password = env('ADMIN_PASSWORD', 'admin123');

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

        $this->command?->info("Admin account ready: {$email} (password from ADMIN_PASSWORD or 'admin123')");
    }
}
