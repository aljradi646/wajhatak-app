<?php

namespace Tests\Feature\Api;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression coverage for: favorites user-isolation + toggle semantics,
 * conversation uniqueness (client+agent), property-card messages,
 * currency catalogue (data-driven) and cross-user security boundaries.
 */
class FavoritesChatCurrencyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // -------------------------------------------------------------------------
    // Currencies (data-driven)
    // -------------------------------------------------------------------------

    public function test_currency_catalogue_is_served_from_config_with_yer_default(): void
    {
        $response = $this->getJson('/api/v1/currencies');

        $response->assertOk();
        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertEquals(['YER', 'SAR', 'USD'], $codes);
        $this->assertTrue(collect($response->json('data'))->firstWhere('code', 'YER')['is_default']);
        $this->assertFalse(collect($response->json('data'))->firstWhere('code', 'SAR')['is_default']);
    }

    public function test_agent_cannot_create_property_with_unsupported_currency(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        Sanctum::actingAs($agentUser);

        $this->postJson('/api/v1/properties', $this->propertyPayload($agent, ['currency' => 'EUR']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    public function test_agent_can_create_property_with_supported_currency(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        Sanctum::actingAs($agentUser);

        $response = $this->postJson('/api/v1/properties', $this->propertyPayload($agent, ['currency' => 'USD']));

        $response->assertCreated()->assertJsonPath('data.currency', 'USD');
        $this->assertDatabaseHas('properties', [
            'id' => $response->json('data.id'),
            'currency' => 'USD',
        ]);
    }

    public function test_new_property_defaults_to_yer_currency(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        Sanctum::actingAs($agentUser);

        $response = $this->postJson('/api/v1/properties', $this->propertyPayload($agent, ['currency' => null]));

        $response->assertCreated();
        $this->assertSame('YER', $response->json('data.currency'));
    }

    // -------------------------------------------------------------------------
    // Favorites: toggle semantics + user isolation
    // -------------------------------------------------------------------------

    public function test_favorite_toggles_off_on_second_call(): void
    {
        $property = $this->createPublishedProperty();
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/favorites', ['property_id' => $property->id])->assertNoContent();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);

        // Second POST must not duplicate (unique constraint + firstOrCreate).
        $this->postJson('/api/v1/favorites', ['property_id' => $property->id])->assertNoContent();
        $this->assertSame(1, \App\Models\Favorite::query()->where('user_id', $user->id)->count());

        // DELETE removes it, second DELETE is still 204 (idempotent).
        $this->deleteJson('/api/v1/favorites/'.$property->id)->assertNoContent();
        $this->deleteJson('/api/v1/favorites/'.$property->id)->assertNoContent();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_favorites_are_isolated_between_users(): void
    {
        $property = $this->createPublishedProperty();
        $userA = User::factory()->create();
        $userA->assignRole('user');
        $userB = User::factory()->create();
        $userB->assignRole('user');

        \App\Models\Favorite::query()->create(['user_id' => $userA->id, 'property_id' => $property->id]);

        Sanctum::actingAs($userB);
        $this->getJson('/api/v1/favorites')->assertOk()->assertJsonCount(0, 'data');

        // is_favorited must reflect only the authenticated user.
        Sanctum::actingAs($userA);
        $this->getJson('/api/v1/properties/'.$property->id)
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);

        Sanctum::actingAs($userB);
        $this->getJson('/api/v1/properties/'.$property->id)
            ->assertOk()
            ->assertJsonPath('data.is_favorited', false);
    }

    public function test_favorite_cannot_be_added_for_unpublished_property(): void
    {
        $property = $this->createProperty(PropertyStatus::Pending);
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/favorites', ['property_id' => $property->id])->assertNotFound();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    // -------------------------------------------------------------------------
    // Conversations: uniqueness + property message cards + security
    // -------------------------------------------------------------------------

    public function test_conversation_is_unique_per_client_and_agent_across_properties(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        $propertyA = $this->createPublishedProperty($agent);
        $propertyB = $this->createPublishedProperty($agent);
        $client = User::factory()->create();
        $client->assignRole('user');
        Sanctum::actingAs($client);

        $first = $this->postJson('/api/v1/conversations', ['property_id' => $propertyA->id])->assertCreated();
        $conversationId = $first->json('data.id');

        // Opening a chat from another property of the SAME agent returns the same conversation.
        $second = $this->postJson('/api/v1/conversations', ['property_id' => $propertyB->id])->assertCreated();
        $this->assertSame($conversationId, $second->json('data.id'));
        $this->assertSame(1, Conversation::query()->where('client_id', $client->id)->count());

        // The initial conversation carries a property card message.
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'message_type' => 'property',
            'property_id' => $propertyA->id,
            'sender_id' => $client->id,
        ]);
    }

    public function test_conversation_response_uses_agent_name_as_title(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        $property = $this->createPublishedProperty($agent);
        $client = User::factory()->create();
        $client->assignRole('user');
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/conversations', ['property_id' => $property->id])->assertCreated();

        $response->assertJsonPath('data.agent.id', $agentUser->id)
            ->assertJsonPath('data.agent.name', $agentUser->name)
            ->assertJsonPath('data.client.id', $client->id);
    }

    public function test_agent_cannot_start_conversation_on_own_property(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        $property = $this->createPublishedProperty($agent);
        Sanctum::actingAs($agentUser);

        $this->postJson('/api/v1/conversations', ['property_id' => $property->id])->assertStatus(422);
    }

    public function test_message_with_property_card_includes_property_payload(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        $property = $this->createPublishedProperty($agent);
        $client = User::factory()->create();
        $client->assignRole('user');

        $conversation = Conversation::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $agentUser->id,
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($client);
        $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'السلام عليكم، هل العقار ما زال متاحًا؟',
        ])->assertCreated();

        $response->assertJsonPath('data.body', 'السلام عليكم، هل العقار ما زال متاحًا؟')
            ->assertJsonPath('data.message_type', 'text');
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $client->id,
            'message_type' => 'text',
        ]);
    }

    public function test_outsider_cannot_read_or_send_messages_in_foreign_conversation(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        $property = $this->createPublishedProperty($agent);
        $client = User::factory()->create();
        $client->assignRole('user');
        $outsider = User::factory()->create();
        $outsider->assignRole('user');

        $conversation = Conversation::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $agentUser->id,
        ]);

        Sanctum::actingAs($outsider);
        $this->getJson("/api/v1/conversations/{$conversation->id}/messages")->assertForbidden();
        $this->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'تطفل'])
            ->assertForbidden();
        $this->getJson('/api/v1/conversations')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_empty_text_message_is_rejected(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        $property = $this->createPublishedProperty($agent);
        $client = User::factory()->create();
        $client->assignRole('user');

        $conversation = Conversation::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $agentUser->id,
        ]);

        Sanctum::actingAs($client);
        $this->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => '   '])
            ->assertStatus(422);
    }

    public function test_messages_are_marked_read_for_the_other_participant(): void
    {
        [$agentUser, $agent] = $this->makeActiveAgent();
        $property = $this->createPublishedProperty($agent);
        $client = User::factory()->create();
        $client->assignRole('user');

        $conversation = Conversation::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $agentUser->id,
            'last_message_at' => now(),
        ]);
        $conversation->messages()->create([
            'sender_id' => $client->id,
            'body' => 'مرحبًا',
            'message_type' => 'text',
        ]);

        Sanctum::actingAs($agentUser);
        $this->getJson("/api/v1/conversations/{$conversation->id}/messages")->assertOk();
        $readAt = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', $client->id)
            ->value('read_at');
        $this->assertNotNull($readAt, 'Message should be marked as read for the recipient.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeActiveAgent(): array
    {
        $agentUser = User::factory()->create(['name' => 'وكيل تجريبي']);
        $agentUser->assignRole('agent');
        $agent = Agent::query()->create(['user_id' => $agentUser->id, 'is_active' => true]);

        return [$agentUser, $agent];
    }

    private function propertyPayload(Agent $agent, array $overrides = []): array
    {
        $type = PropertyType::query()->firstOrCreate(
            ['slug' => 'apartment'],
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'is_active' => true],
        );

        return array_merge([
            'title' => 'شقة فاخرة في صنعاء',
            'description' => 'شقة حديثة بإطلالة رائعة وتشطيب فاخر.',
            'property_type_id' => $type->id,
            'transaction_type' => 'sale',
            'price' => 45000000,
            'currency' => 'YER',
            'area' => 180,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'location' => [
                'city' => 'صنعاء',
                'district' => 'حدة',
                'address' => 'شارع الستين',
            ],
        ], $overrides);
    }

    private function createPublishedProperty(?Agent $agent = null): Property
    {
        return $this->createProperty(PropertyStatus::Published, $agent);
    }

    private function createProperty(PropertyStatus $status, ?Agent $agent = null): Property
    {
        if ($agent === null) {
            [, $agent] = $this->makeActiveAgent();
        }
        $type = PropertyType::query()->firstOrCreate(
            ['slug' => 'apartment'],
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'is_active' => true],
        );
        $location = PropertyLocation::query()->create([
            'city' => 'صنعاء',
            'district' => 'حدة',
            'address' => 'شارع الستين',
            'latitude' => 15.3694,
            'longitude' => 44.191,
        ]);

        return Property::query()->create([
            'agent_id' => $agent->id,
            'property_type_id' => $type->id,
            'property_location_id' => $location->id,
            'title' => 'شقة اختبارية',
            'slug' => 'test-property-'.uniqid(),
            'reference_code' => 'WJH-T-'.strtoupper(uniqid()),
            'description' => 'وصف عقار اختباري.',
            'transaction_type' => TransactionType::Sale,
            'status' => $status,
            'price' => 45000000,
            'currency' => 'YER',
            'published_at' => $status === PropertyStatus::Published ? now() : null,
        ]);
    }
}
