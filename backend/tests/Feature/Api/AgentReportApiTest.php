<?php

namespace Tests\Feature\Api;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\ReportLog;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function makeAgent(string $name = 'وكيل التقارير'): array
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('agent');

        $agent = Agent::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'verification_status' => 'approved',
            'verified_at' => now(),
        ]);

        return [$user, $agent];
    }

    private function makeProperty(Agent $agent, array $overrides = []): Property
    {
        $type = PropertyType::query()->firstOrCreate(
            ['slug' => 'apartment'],
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'is_active' => true],
        );

        $location = PropertyLocation::query()->create([
            'city' => 'صنعاء',
            'district' => 'حدة',
            'address' => 'شارع الستين',
        ]);

        return Property::query()->create(array_merge([
            'agent_id' => $agent->id,
            'property_type_id' => $type->id,
            'property_location_id' => $location->id,
            'title' => 'شقة تقرير',
            'slug' => 'agent-report-'.uniqid(),
            'reference_code' => 'WJH-AR-'.strtoupper(uniqid()),
            'description' => 'وصف اختباري.',
            'transaction_type' => TransactionType::Sale,
            'status' => PropertyStatus::Published,
            'price' => 40000000,
            'currency' => 'YER',
            'area' => 150,
            'bedrooms' => 3,
            'published_at' => now(),
        ], $overrides));
    }

    // ---------------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------------

    public function test_agent_reports_catalog_lists_real_types_and_history(): void
    {
        [$user] = $this->makeAgent();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/agent/reports')->assertOk();

        $keys = collect($response->json('data.types'))->pluck('key')->all();
        $this->assertEqualsCanonicalizing(['properties', 'viewing_requests', 'performance'], $keys);
        $response->assertJsonPath('data.agent.name', 'وكيل التقارير');
        $response->assertJsonStructure(['data' => ['types' => [['key', 'label', 'formats']], 'history']]);
    }

    public function test_non_agent_cannot_access_agent_reports(): void
    {
        $client = User::factory()->create();
        $client->assignRole('user');
        Sanctum::actingAs($client);

        $this->getJson('/api/v1/agent/reports')->assertForbidden();
        $this->getJson('/api/v1/agent/reports/properties')->assertForbidden();
    }

    public function test_properties_report_returns_only_the_current_agents_properties(): void
    {
        [$user, $agent] = $this->makeAgent();
        [, $otherAgent] = $this->makeAgent('وكيل آخر');

        $mine = $this->makeProperty($agent, ['title' => 'عقاري أنا']);
        $this->makeProperty($otherAgent, ['title' => 'عقار الوكيل الآخر']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/agent/reports/properties')->assertOk();

        $titles = collect($response->json('data.rows'))->pluck('title')->all();
        $this->assertContains('عقاري أنا', $titles);
        $this->assertNotContains('عقار الوكيل الآخر', $titles);
        $this->assertSame(1, $response->json('data.summary.0.value'));
        $this->assertNotEmpty($mine->reference_code);
    }

    public function test_properties_report_respects_the_status_filter(): void
    {
        [$user, $agent] = $this->makeAgent();
        $this->makeProperty($agent, ['status' => PropertyStatus::Published]);
        $this->makeProperty($agent, ['status' => PropertyStatus::Pending]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/agent/reports/properties?status=published')->assertOk();

        $this->assertCount(1, $response->json('data.rows'));
        // الصف الخام يحمل الحالة الفعلية، والصف المعروض يحمل التسمية العربية.
        $this->assertSame('published', $response->json('data.raw_rows.0.status'));
        $this->assertSame('منشور', $response->json('data.rows.0.status'));
    }

    public function test_viewing_requests_report_counts_and_isolates(): void
    {
        [$user, $agent] = $this->makeAgent();
        $property = $this->makeProperty($agent);
        $client = User::factory()->create();
        $client->assignRole('user');

        ViewingRequest::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $agent->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'status' => 'completed',
        ]);
        ViewingRequest::query()->create([
            'property_id' => $property->id,
            'client_id' => $client->id,
            'agent_id' => $agent->id,
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '11:00',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/agent/reports/viewing_requests')->assertOk();

        $this->assertCount(2, $response->json('data.rows'));
        $summary = collect($response->json('data.summary'))->pluck('value', 'label');
        $this->assertSame(2, $summary['إجمالي الطلبات']);
        $this->assertSame(1, $summary['مكتملة']);
        $this->assertSame('50%', $summary['نسبة الإتمام']);
    }

    public function test_performance_report_groups_by_property_type(): void
    {
        [$user, $agent] = $this->makeAgent();
        $this->makeProperty($agent, ['price' => 10000000]);
        $this->makeProperty($agent, ['price' => 30000000]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/agent/reports/performance')->assertOk();

        $this->assertCount(1, $response->json('data.rows')); // كلها نوع واحد
        $row = $response->json('data.rows.0');
        // قيم الأعمدة تأتي مُهيّأة للعرض (نفس مُهيّئ التقرير الموحّد).
        $this->assertSame('2', $row['count']);
        $this->assertSame('20,000,000 YER', $row['avg_price']);

        $summary = collect($response->json('data.summary'))->pluck('value', 'label');
        $this->assertSame(2, $summary['إجمالي العقارات']);
    }

    public function test_unknown_report_type_is_rejected(): void
    {
        [$user] = $this->makeAgent();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/agent/reports/not-a-report')->assertNotFound();
    }

    public function test_each_generation_is_recorded_in_the_report_log(): void
    {
        [$user] = $this->makeAgent();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/agent/reports/properties')->assertOk();
        $this->get('/api/v1/agent/reports/properties?format=csv')->assertOk();
        $this->get('/api/v1/agent/reports/properties?format=pdf')->assertOk();

        $logs = ReportLog::query()->where('user_id', $user->id)->where('type', 'agent_properties')->get();
        $this->assertCount(3, $logs);
        $this->assertEqualsCanonicalizing(['json', 'csv', 'pdf'], $logs->pluck('format')->all());
    }

    public function test_csv_and_pdf_exports_are_real_files(): void
    {
        [$user, $agent] = $this->makeAgent();
        $this->makeProperty($agent);
        Sanctum::actingAs($user);

        $csv = $this->get('/api/v1/agent/reports/properties?format=csv')->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));
        $this->assertStringContainsString('العقار', (string) $csv->getContent());

        $pdf = $this->get('/api/v1/agent/reports/properties?format=pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', (string) $pdf->getContent());
    }

    public function test_history_endpoint_and_redownload_are_owner_scoped(): void
    {
        [$user, $agent] = $this->makeAgent();
        [$otherUser] = $this->makeAgent('وكيل آخر');
        $this->makeProperty($agent);

        Sanctum::actingAs($user);
        $this->get('/api/v1/agent/reports/properties?format=csv')->assertOk();

        $log = ReportLog::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        // المالك يعيد التوليد بنجاح.
        $this->get(route('api.v1.agent.reports.download', $log))->assertOk();

        // وكيل آخر لا يصل إليه حتى بمعرفة المعرّف.
        Sanctum::actingAs($otherUser);
        $this->get(route('api.v1.agent.reports.download', $log))->assertForbidden();
    }
}
