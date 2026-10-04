<?php

namespace Tests\\Feature\\Ai;

use App\\Services\\AI\\AiIntentRouter;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

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

    public function test_response_contract_removes_properties_from_non_property_responses(): void
    {
        $contract = \\App\\Services\\AI\\AiResponseContract::normalize([
            'intent' => 'platform_information',
            'response_type' => 'text',
            'reply' => 'معلومات',
            'properties' => [['property_id' => 11]],
        ]);
        $this->assertSame([], $contract['properties']);
        $this->assertSame('text', $contract['response_type']);
    }
}
