<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_ui_preferences_persist_on_the_user(): void
    {
        $role = Role::findOrCreate('admin');
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($role);

        $response = $this->actingAs($admin)->postJson('/admin/preferences/ui', [
            'theme' => 'dark',
            'sidebar_collapsed' => true,
            'density' => 'compact',
            'table_page_size' => 50,
        ]);

        $response->assertOk();
        $response->assertJsonPath('preferences.theme', 'dark');
        $response->assertJsonPath('preferences.sidebar_collapsed', true);

        $saved = $admin->fresh()->ui_preferences;
        $this->assertSame('dark', $saved['theme']);
        $this->assertTrue($saved['sidebar_collapsed']);
        $this->assertSame('compact', $saved['density']);
        $this->assertSame(50, $saved['table_page_size']);
    }
}
