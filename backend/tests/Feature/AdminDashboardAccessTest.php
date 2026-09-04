<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_admin_can_log_in_and_reach_the_dashboard(): void
    {
        $this->seed(\Database\Seeders\DemoDataSeeder::class);

        $admin = User::query()->where('email', 'admin@wajhatak.app')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->is_active);

        $login = $this->post('/login', [
            'email' => 'admin@wajhatak.app',
            'password' => 'LuxAdmin2026!',
        ]);

        $login->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->get('/admin')->assertOk();
        $this->get('/admin/users')->assertOk();
    }

    public function test_unauthenticated_users_cannot_reach_the_dashboard(): void
    {
        $this->seed(\Database\Seeders\DemoDataSeeder::class);

        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_inactive_user_is_denied_dashboard_access(): void
    {
        $this->seed(\Database\Seeders\DemoDataSeeder::class);
        $role = Role::findOrCreate('admin');

        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole($role);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}