<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage users', 'manage agents', 'manage properties', 'approve properties',
            'manage viewing requests', 'manage settings', 'view reports',
            'create properties', 'edit own properties', 'view incoming requests',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $agent = Role::findOrCreate('agent');
        $agent->syncPermissions(['create properties', 'edit own properties', 'view incoming requests']);

        Role::findOrCreate('user');

        $this->call([
            LocationSeeder::class,
        ]);
    }
}
