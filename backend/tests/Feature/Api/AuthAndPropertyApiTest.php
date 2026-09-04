<?php

namespace Tests\Feature\Api;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAndPropertyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_a_guest_can_browse_only_published_properties(): void
    {
        $published = $this->createProperty(PropertyStatus::Published);
        $this->createProperty(PropertyStatus::Pending);

        $response = $this->getJson('/api/v1/properties');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $published->id);
        $this->getJson('/api/v1/properties/'.$published->id)
            ->assertOk()
            ->assertJsonPath('data.id', $published->id);
        $this->postJson('/api/v1/properties', [])->assertUnauthorized();
    }

    public function test_client_registration_returns_a_sanctum_token_and_user_role_without_an_agent_profile(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'مستخدم الاختبار',
            'email' => 'customer@laravel.com',
            'phone' => '0500000000',
            'password' => 'SecurePass2026',
            'password_confirmation' => 'SecurePass2026',
            'locale' => 'ar',
            'account_type' => 'client',
        ], ['X-Device-Name' => 'phpunit']);

        $response->assertCreated()->assertJsonPath('data.user.email', 'customer@laravel.com')->assertJsonPath('data.user.roles.0', 'user');
        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseMissing('agents', ['user_id' => User::query()->where('email', 'customer@laravel.com')->value('id')]);
    }

    public function test_agent_registration_creates_an_active_agent_profile_and_agent_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'وكيل الاختبار',
            'email' => 'agent@laravel.com',
            'phone' => '0500000002',
            'password' => 'SecurePass2026',
            'password_confirmation' => 'SecurePass2026',
            'account_type' => 'agent',
            'bio' => 'وكيل عقاري مختص في عقارات شمال الرياض.',
            'license_number' => 'LUX-AGENT-2026',
        ], ['X-Device-Name' => 'phpunit']);

        $response->assertCreated()->assertJsonPath('data.user.email', 'agent@laravel.com')->assertJsonPath('data.user.roles.0', 'agent');
        $userId = User::query()->where('email', 'agent@laravel.com')->value('id');
        $this->assertDatabaseHas('agents', ['user_id' => $userId, 'license_number' => 'LUX-AGENT-2026', 'is_active' => true]);
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_registration_rejects_any_account_type_outside_client_or_agent(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'مدير مزعوم',
            'email' => 'not-admin@laravel.com',
            'password' => 'SecurePass2026',
            'password_confirmation' => 'SecurePass2026',
            'account_type' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors('account_type');
    }

    public function test_a_user_can_favorite_a_published_property_but_cannot_create_a_listing(): void
    {
        $property = $this->createProperty(PropertyStatus::Published);
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/favorites', ['property_id' => $property->id])->assertNoContent();
        $this->getJson('/api/v1/favorites')->assertOk()->assertJsonPath('data.0.id', $property->id);
        $this->postJson('/api/v1/properties', [])->assertForbidden();
    }

    public function test_agent_can_confirm_only_their_own_viewing_request(): void
    {
        $agentUser = User::factory()->create();
        $agentUser->assignRole('agent');
        $agent = Agent::query()->create(['user_id' => $agentUser->id, 'is_active' => true]);
        $client = User::factory()->create();
        $client->assignRole('user');
        $property = $this->createProperty(PropertyStatus::Published);
        $property->update(['agent_id' => $agent->id]);
        $request = \App\Models\ViewingRequest::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $agent->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '17:30',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($agentUser);
        $this->patchJson('/api/v1/viewing-requests/'.$request->id, ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
        $this->assertDatabaseHas('viewing_requests', ['id' => $request->id, 'status' => 'confirmed']);
    }

    public function test_an_authenticated_user_can_upload_an_avatar_and_manage_notification_preferences_and_device_token(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $this->post('/api/v1/me/avatar', [
            'avatar' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                file_get_contents(base_path('../mobile/assets/images/icon.png')),
            ),
        ])->assertOk()->assertJsonPath('data.id', $user->id);
        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        $this->patchJson('/api/v1/me/notification-preferences', [
            'message_notifications' => false,
            'viewing_notifications' => true,
            'property_updates' => false,
        ])->assertOk()->assertJsonPath('data.message_notifications', false);
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'message_notifications' => false,
            'property_updates' => false,
        ]);

        $this->postJson('/api/v1/me/devices', [
            'device_id' => 'android-device-2026-001',
            'platform' => 'android',
            'push_token' => str_repeat('f', 64),
        ])->assertCreated();
        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id, 'platform' => 'android']);
    }

    private function createProperty(PropertyStatus $status): Property
    {
        $agentUser = User::factory()->create();
        $agentUser->assignRole('agent');
        $agent = Agent::query()->create(['user_id' => $agentUser->id, 'is_active' => true]);
        $type = PropertyType::query()->firstOrCreate(['slug' => 'apartment'], ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'is_active' => true]);
        $location = PropertyLocation::query()->create(['city' => 'الرياض', 'address' => 'حي العليا', 'latitude' => 24.7136, 'longitude' => 46.6753]);

        return Property::query()->create([
            'agent_id' => $agent->id,
            'property_type_id' => $type->id,
            'property_location_id' => $location->id,
            'title' => 'شقة اختبارية',
            'slug' => 'test-property-'.uniqid(),
            'reference_code' => 'LUX-T-'.uniqid(),
            'description' => 'وصف عقار اختباري فقط.',
            'transaction_type' => TransactionType::Sale,
            'status' => $status,
            'price' => 850000,
            'currency' => 'SAR',
            'published_at' => $status === PropertyStatus::Published ? now() : null,
        ]);
    }
}
