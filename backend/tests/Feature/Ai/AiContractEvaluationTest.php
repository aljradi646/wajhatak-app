<?php

namespace Tests\Feature\Ai;

use App\Services\AI\AiIntentRouter;
use App\Services\AI\AiToolRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiContractEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private function cases(): array
    {
        $path = base_path('tests/fixtures/ai_evaluation_cases.json');
        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data);
        return $data;
    }

    public function test_evaluation_dataset_covers_non_property_and_property_routing(): void
    {
        $router = app(AiIntentRouter::class);
        foreach ($this->cases() as $case) {
            $route = $router->route((string) $case['input']);
            $this->assertSame($case['intent'], $route['intent'], 'Intent mismatch: '.$case['id']);
        }
    }

    public function test_topic_switch_never_inherits_unrelated_property_state(): void
    {
        $router = app(AiIntentRouter::class);
        $first = $router->route('أريد شقة غرفتين في صنعاء');
        $platform = $router->route('معلومات عن منصة وجهتك', [], $first['filters'], [11, 12]);
        $this->assertSame('platform_information', $platform['intent']);
        $this->assertSame([], $platform['filters']);
        $this->assertFalse($platform['use_search_context']);
    }

    public function test_property_type_correction_overrides_previous_type_but_keeps_relevant_context(): void
    {
        $router = app(AiIntentRouter::class);
        $first = $router->route('أريد شقة للبيع في صنعاء');
        $second = $router->route('لا، أبغى فلة', [], $first['filters'], []);
        $this->assertContains($second['intent'], ['search_refinement', 'property_search']);
        $this->assertSame('villa', $second['filters']['property_type'] ?? null);
        $this->assertSame('sale', $second['filters']['transaction_type'] ?? null);
        $this->assertSame('صنعاء', $second['filters']['city'] ?? null);
    }

    public function test_explicit_area_bathroom_and_bedroom_filters_reach_live_search_unchanged(): void
    {
        // هذا اختبار لمسار القواعد وأدوات قاعدة البيانات؛ لا يعتمد على مزود نموذج خارجي.
        config()->set('ai.llm.enabled', false);
        config()->set('ai.llm.mode', 'grounded');

        $capturedArguments = null;
        $registry = \Mockery::mock(AiToolRegistry::class);
        $registry->shouldReceive('allowedToolsForIntent')
            ->once()
            ->with('property_search')
            ->andReturn(['search_properties']);
        $registry->shouldReceive('execute')
            ->once()
            ->with('search_properties', \Mockery::on(function (array $arguments) use (&$capturedArguments): bool {
                $capturedArguments = $arguments;
                return true;
            }), null)
            ->andReturn([
                'success' => true,
                'total' => 0,
                'properties' => [],
                'filters' => [],
                'result_mode' => 'exact',
                'relaxations' => [],
            ]);

        $this->app->instance(AiToolRegistry::class, $registry);

        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'أريد شقة للإيجار في صنعاء، 2-3 غرف، من 100 إلى 150 متر مربع، حمامين، هادئة، وبميزانية من 2 إلى 3 مليون',
        ]);

        $response->assertOk();
        $this->assertSame('property_search', $response->json('data.intent'));
        $this->assertIsArray($capturedArguments);
        $this->assertSame('rent', $capturedArguments['transaction_type'] ?? null);
        $this->assertSame('apartment', $capturedArguments['property_type'] ?? null);
        $this->assertSame('صنعاء', $capturedArguments['city'] ?? null);
        $this->assertSame(2, $capturedArguments['bedrooms_min'] ?? null);
        $this->assertSame(3, $capturedArguments['bedrooms_max'] ?? null);
        $this->assertSame(2, $capturedArguments['bathrooms_min'] ?? null);
        $this->assertSame(100.0, $capturedArguments['min_area'] ?? null);
        $this->assertSame(150.0, $capturedArguments['max_area'] ?? null);
        $this->assertSame(2000000.0, $capturedArguments['min_price'] ?? null);
        $this->assertSame(3000000.0, $capturedArguments['max_price'] ?? null);
        $this->assertSame(['هادئ'], $capturedArguments['keywords'] ?? null);
    }

    public function test_response_contract_removes_properties_from_non_property_responses(): void
    {
        $contract = \App\Services\AI\AiResponseContract::normalize([
            'intent' => 'platform_information',
            'response_type' => 'text',
            'reply' => 'معلومات',
            'properties' => [['property_id' => 11]],
        ]);
        $this->assertSame([], $contract['properties']);
        $this->assertSame('text', $contract['response_type']);
    }
}
