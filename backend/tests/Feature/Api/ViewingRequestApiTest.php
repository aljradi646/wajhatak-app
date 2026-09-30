<?php

namespace Tests\Feature\Api;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ViewingRequestApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // يهيّئ الأدوار (admin/agent/user) وبقية بيانات الأساس.
        $this->seed();
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function makeApprovedAgent(): array
    {
        $agentUser = User::factory()->create(['name' => 'وكيل معاينة']);
        $agentUser->assignRole('agent');

        $agent = Agent::query()->create([
            'user_id' => $agentUser->id,
            'is_active' => true,
            'verification_status' => 'approved',
            'verified_at' => now(),
        ]);

        return [$agentUser, $agent];
    }

    private function makeClient(): User
    {
        $client = User::factory()->create();
        $client->assignRole('user');

        return $client;
    }

    private function makePublishedProperty(?Agent $agent = null): Property
    {
        $agent ??= $this->makeApprovedAgent()[1];

        $type = PropertyType::query()->firstOrCreate(
            ['slug' => 'apartment'],
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'is_active' => true],
        );

        $location = PropertyLocation::query()->create([
            'city' => 'صنعاء',
            'district' => 'حدة',
            'address' => 'شارع الستين',
        ]);

        return Property::query()->create([
            'agent_id' => $agent->id,
            'property_type_id' => $type->id,
            'property_location_id' => $location->id,
            'title' => 'شقة للمعاينة',
            'slug' => 'viewing-'.uniqid(),
            'reference_code' => 'WJH-V-'.strtoupper(uniqid()),
            'description' => 'وصف اختباري.',
            'transaction_type' => TransactionType::Sale,
            'status' => PropertyStatus::Published,
            'price' => 45000000,
            'currency' => 'YER',
            'published_at' => now(),
        ]);
    }

    private function makeRequest(Property $property, User $client, string $status = 'pending'): ViewingRequest
    {
        return ViewingRequest::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $property->agent_id,
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '10:00',
            'status' => $status,
        ]);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    // ---------------------------------------------------------------------
    // Create / read
    // ---------------------------------------------------------------------

    public function test_client_can_create_a_viewing_request_for_a_published_property(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/viewing-requests', [
            'property_id' => $property->id,
            'scheduled_date' => now()->addDays(4)->toDateString(),
            'scheduled_time' => '11:30',
            'notes' => 'أفضل الفترة الصباحية.',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('viewing_requests', [
            'property_id' => $property->id,
            'client_id' => $client->id,
            'status' => 'pending',
        ]);
    }

    public function test_client_cannot_request_a_viewing_in_the_past(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        Sanctum::actingAs($client);

        $this->postJson('/api/v1/viewing-requests', [
            'property_id' => $property->id,
            'scheduled_date' => now()->subDay()->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_date');
    }

    public function test_a_client_cannot_read_another_clients_request(): void
    {
        $property = $this->makePublishedProperty();
        $owner = $this->makeClient();
        $other = $this->makeClient();
        $request = $this->makeRequest($property, $owner);

        Sanctum::actingAs($other);

        $this->getJson("/api/v1/viewing-requests/{$request->id}")->assertForbidden();
        $this->getJson("/api/v1/viewing-requests/{$request->id}/history")->assertForbidden();
        $this->patchJson("/api/v1/viewing-requests/{$request->id}", ['status' => 'cancelled'])->assertForbidden();
        $this->deleteJson("/api/v1/viewing-requests/{$request->id}")->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // Status transitions (state machine + roles)
    // ---------------------------------------------------------------------

    public function test_client_can_cancel_their_pending_request(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        $request = $this->makeRequest($property, $client);
        Sanctum::actingAs($client);

        $this->patchJson("/api/v1/viewing-requests/{$request->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame('cancelled', $request->refresh()->status->value);
    }

    public function test_client_cannot_confirm_their_own_request(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        $request = $this->makeRequest($property, $client);
        Sanctum::actingAs($client);

        $this->patchJson("/api/v1/viewing-requests/{$request->id}", ['status' => 'confirmed'])->assertForbidden();
        $this->assertSame('pending', $request->refresh()->status->value);
    }

    public function test_agent_can_confirm_and_complete_a_request(): void
    {
        [$agentUser, $agent] = $this->makeApprovedAgent();
        $property = $this->makePublishedProperty($agent);
        $client = $this->makeClient();
        $request = $this->makeRequest($property, $client);

        Sanctum::actingAs($agentUser);

        $this->patchJson("/api/v1/viewing-requests/{$request->id}", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->patchJson("/api/v1/viewing-requests/{$request->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_terminal_requests_cannot_be_changed_again(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        $request = $this->makeRequest($property, $client, 'rejected');
        Sanctum::actingAs($client);

        // مرفوض → لا يمكن تأكيده.
        $this->patchJson("/api/v1/viewing-requests/{$request->id}", ['status' => 'confirmed'])
            ->assertUnprocessable();

        $this->assertSame('rejected', $request->refresh()->status->value);
    }

    public function test_client_can_reschedule_while_the_request_is_open(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        $request = $this->makeRequest($property, $client);
        Sanctum::actingAs($client);

        $newDate = now()->addDays(9)->toDateString();

        $this->patchJson("/api/v1/viewing-requests/{$request->id}", [
            'scheduled_date' => $newDate,
            'scheduled_time' => '15:45',
            'notes' => 'بعد الظهر أفضل.',
        ])->assertOk();

        $request->refresh();
        $this->assertSame($newDate, $request->scheduled_date->toDateString());
        $this->assertSame('15:45', $request->scheduled_time);
        $this->assertSame('بعد الظهر أفضل.', $request->notes);
    }

    public function test_client_cannot_reschedule_a_closed_request(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        $request = $this->makeRequest($property, $client, 'completed');
        Sanctum::actingAs($client);

        $this->patchJson("/api/v1/viewing-requests/{$request->id}", [
            'scheduled_date' => now()->addDays(6)->toDateString(),
        ])->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // Delete rules
    // ---------------------------------------------------------------------

    public function test_client_can_delete_a_cancelled_request_but_not_a_confirmed_one(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();

        $confirmed = $this->makeRequest($property, $client, 'confirmed');
        $cancelled = $this->makeRequest($property, $client, 'cancelled');

        Sanctum::actingAs($client);

        $this->deleteJson("/api/v1/viewing-requests/{$confirmed->id}")->assertForbidden();
        $this->assertDatabaseHas('viewing_requests', ['id' => $confirmed->id]);

        $this->deleteJson("/api/v1/viewing-requests/{$cancelled->id}")->assertOk();
        $this->assertDatabaseMissing('viewing_requests', ['id' => $cancelled->id]);
    }

    public function test_admin_can_delete_any_request(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        $request = $this->makeRequest($property, $client, 'confirmed');

        Sanctum::actingAs($this->makeAdmin());

        $this->deleteJson("/api/v1/viewing-requests/{$request->id}")->assertOk();
        $this->assertDatabaseMissing('viewing_requests', ['id' => $request->id]);
    }

    // ---------------------------------------------------------------------
    // Change history
    // ---------------------------------------------------------------------

    public function test_history_records_creation_status_changes_and_reschedules(): void
    {
        $property = $this->makePublishedProperty();
        $client = $this->makeClient();
        Sanctum::actingAs($client);

        $created = $this->postJson('/api/v1/viewing-requests', [
            'property_id' => $property->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
        ])->assertCreated();

        $id = $created->json('data.id');

        $this->patchJson("/api/v1/viewing-requests/{$id}", [
            'scheduled_date' => now()->addDays(5)->toDateString(),
        ])->assertOk();

        $this->patchJson("/api/v1/viewing-requests/{$id}", ['status' => 'cancelled'])->assertOk();

        $history = $this->getJson("/api/v1/viewing-requests/{$id}/history")->assertOk();

        $actions = collect($history->json('data'))->pluck('action')->all();
        $this->assertContains('status_changed', $actions);
        $this->assertContains('rescheduled', $actions);
        $this->assertContains('created', $actions);

        // الحالة القديمة والجديدة محفوظتان فعليًا.
        $statusChange = collect($history->json('data'))->firstWhere('action', 'status_changed');
        $this->assertSame('pending', $statusChange['from']);
        $this->assertSame('cancelled', $statusChange['to']);

        // ولا يُسرَّب سجل تغييرات طلب آخر (هذا الطلب أُنشئ مباشرةً بلا أي تغيير).
        $otherProperty = $this->makePublishedProperty();
        $other = $this->makeRequest($otherProperty, $client);
        $this->getJson("/api/v1/viewing-requests/{$other->id}/history")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertGreaterThan(0, ActivityLog::query()->where('log_name', 'viewing_request')->count());
    }
}
