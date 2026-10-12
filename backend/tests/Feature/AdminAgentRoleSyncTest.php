<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminAgentRoleSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_granting_agent_role_creates_profile_and_lists_user_with_both_roles_as_agent(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('user');
        Role::findOrCreate('agent');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = User::factory()->create([
            'name' => 'Role Change Test User',
            'email' => 'role-change@example.test',
        ]);
        $client->assignRole('user');

        $hiddenClient = User::factory()->create([
            'name' => 'User Only Must Stay Hidden',
            'email' => 'user-only@example.test',
        ]);
        $hiddenClient->assignRole('user');
        Agent::query()->create([
            'user_id' => $hiddenClient->id,
            'is_active' => true,
            'verification_status' => 'approved',
        ]);

        $this->actingAs($admin)->patch('/admin/users/'.$client->id, [
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'locale' => 'ar',
            'is_active' => '1',
            'roles' => ['user', 'agent'],
        ])->assertRedirect(route('admin.users.index'));

        $client->refresh();
        $this->assertTrue($client->hasRole('user'));
        $this->assertTrue($client->hasRole('agent'));
        $this->assertDatabaseHas('agents', [
            'user_id' => $client->id,
            'is_active' => 0,
            'verification_status' => 'pending',
        ]);

        $this->actingAs($admin)->get('/admin/agents')
            ->assertOk()
            ->assertSee('Role Change Test User')
            ->assertDontSee('User Only Must Stay Hidden');

        $this->getJson('/api/v1/agents')
            ->assertOk()
            ->assertJsonMissing(['name' => 'User Only Must Stay Hidden']);
    }
}
