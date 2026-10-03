<?php

namespace Tests\Feature\Ai;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiLlmAgentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_llm_tool_call_executes_real_property_search_and_returns_grounded_result(): void
    {
        config()->set('ai.llm.enabled', true);
        config()->set('ai.llm.mode', 'agent');
        config()->set('ai.llm.base_url', 'http://ollama.test/v1');
        config()->set('ai.llm.model', 'wajhatak-qwen3:1.7b');
        config()->set('ai.allow_rule_fallback', false);
        config()->set('ai.llm.max_tool_rounds', 3);

        $this->seed(\Database\Seeders\RealDataSeeder::class);
        $property = Property::query()->where('status', 'published')->firstOrFail();

        Http::fakeSequence()
            ->push([
                'model' => 'wajhatak-qwen3:1.7b',
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_search_1',
                            'type' => 'function',
                            'function' => [
                                'name' => 'search_properties',
                                'arguments' => json_encode([
                                    'city' => $property->location?->city,
                                    'property_type' => $property->type?->slug,
                                    'max_price' => (float) $property->price + 1,
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ]],
                    ],
                ]],
            ], 200)
            ->push([
                'model' => 'wajhatak-qwen3:1.7b',
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'وجدت لك عقارات مطابقة من بيانات وجهتك الحقيقية.',
                    ],
                ]],
            ], 200);

        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'ابحث عن عقار مناسب',
        ]);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertSame('ok', $data['status']);
        $this->assertSame('llm_agent', $data['intent']);
        $this->assertSame('وجدت لك عقارات مطابقة من بيانات وجهتك الحقيقية.', $data['reply']);
        $this->assertNotEmpty($data['properties']);

        foreach ($data['properties'] as $item) {
            $this->assertDatabaseHas('properties', [
                'id' => $item['property_id'],
                'status' => 'published',
            ]);
        }

        Http::assertSentCount(2);
    }
}
