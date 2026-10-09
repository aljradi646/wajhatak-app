<?php

namespace Tests\Feature\Api;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\ViewingRequest;
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
        // حقول توثيق الوكيل المطلوبة فعليًا من RegisterRequest (تُحفظ كلها في agents).
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'وكيل الاختبار',
            'email' => 'agent@laravel.com',
            'phone' => '0500000002',
            'password' => 'SecurePass2026',
            'password_confirmation' => 'SecurePass2026',
            'account_type' => 'agent',
            'bio' => 'وكيل عقاري مختص في عقارات صنعاء.',
            'license_number' => 'LUX-AGENT-2026',
            'agency_name' => 'مكتب الوجهة العقاري',
            'job_title' => 'وسيط عقاري معتمد',
            'agent_phone' => '0511111111',
            'agent_city' => 'صنعاء',
            'national_id' => '01234567890',
        ], ['X-Device-Name' => 'phpunit']);

        $response->assertCreated()->assertJsonPath('data.user.email', 'agent@laravel.com')->assertJsonPath('data.user.roles.0', 'agent');
        $userId = User::query()->where('email', 'agent@laravel.com')->value('id');
        $this->assertDatabaseHas('agents', [
            'user_id' => $userId,
            'license_number' => 'LUX-AGENT-2026',
            // الوكيل يبدأ قيد التوثيق (pending) — بوابة النشر تمنع الإضافة قبل موافقة الإدارة.
            'agency_name' => 'مكتب الوجهة العقاري',
            'verification_status' => 'pending',
        ]);
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
        $request = ViewingRequest::query()->create([
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
            'avatar' => UploadedFile::fake()->image('avatar.png', 64, 64),
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

    public function test_property_filters_support_ranges_and_explicit_false_values(): void
    {
        $matching = $this->createProperty(
            PropertyStatus::Published,
            [
                'area' => 120,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'parking_spaces' => 1,
                'is_furnished' => false,
                'is_new' => false,
                'is_featured' => false,
            ],
            ['city' => 'صنعاء', 'district' => 'حدة', 'neighborhood' => 'السنينة'],
        );
        $this->createProperty(
            PropertyStatus::Published,
            [
                'area' => 220,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'parking_spaces' => 2,
                'is_furnished' => true,
                'is_new' => true,
                'is_featured' => true,
            ],
            ['city' => 'صنعاء', 'district' => 'حدة', 'neighborhood' => 'السنينة'],
        );

        $response = $this->getJson('/api/v1/properties?'.http_build_query([
            'city' => 'صنعاء',
            'district' => 'حدة',
            'neighborhood' => 'السنينة',
            'min_area' => 100,
            'max_area' => 150,
            'bedrooms_min' => 2,
            'bedrooms_max' => 3,
            'bathrooms_min' => 1,
            'bathrooms_max' => 2,
            'parking_spaces_min' => 1,
            'parking_spaces_max' => 1,
            'is_furnished' => 0,
            'is_new' => 0,
            'is_featured' => 0,
        ]));

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_property_search_rejects_inverted_numeric_ranges(): void
    {
        $this->getJson('/api/v1/properties?min_area=150&max_area=90')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_area']);
    }

    private function createProperty(
        PropertyStatus $status,
        array $attributes = [],
        array $locationAttributes = [],
    ): Property
    {
        $agentUser = User::factory()->create();
        $agentUser->assignRole('agent');
        $agent = Agent::query()->create(['user_id' => $agentUser->id, 'is_active' => true]);
        $type = PropertyType::query()->firstOrCreate(['slug' => 'apartment'], ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'is_active' => true]);
        $location = PropertyLocation::query()->create([
            'city' => 'الرياض',
            'address' => 'حي العليا',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            ...$locationAttributes,
        ]);

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
            ...$attributes,
        ]);
    }
}
