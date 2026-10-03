<?php

namespace Tests\Feature\Ai;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGroundedLlmTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiny_llm_summarizes_real_rule_search_result(): void
    {
        config()->set('ai.llm.enabled', true);
        config()->set('ai.llm.mode', 'grounded');
        config()->set('ai.llm.base_url', 'http://tiny-llm.test/v1');
        config()->set('ai.llm.model', 'wajhatak-smollm2-135m-instruct-q8_0');
        config()->set('ai.allow_rule_fallback', false);
        config()->set('ai.llm.max_output_tokens', 256);

        $this->seed(\Database\Seeders\RealDataSeeder::class);
        Property::query()->where('status', 'published')->firstOrFail();

        Http::fake([
            'http://tiny-llm.test/v1/chat/completions' => Http::response([
                'model' => 'wajhatak-smollm2-135m-instruct-q8_0',
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'وجدت لك عقارات حقيقية من بيانات وجهتك.',
                    ],
                ]],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'ابحث عن عقار في صنعاء',
        ]);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertSame('ok', $data['status']);
        $this->assertSame('llm_grounded', $data['intent']);
        $this->assertSame('وجدت لك عقارات حقيقية من بيانات وجهتك.', $data['reply']);
        $this->assertNotEmpty($data['properties']);

        foreach ($data['properties'] as $item) {
            $this->assertDatabaseHas('properties', [
                'id' => $item['property_id'],
                'status' => 'published',
            ]);
        }

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'http://tiny-llm.test/v1/chat/completions'
                && $payload['model'] === 'wajhatak-smollm2-135m-instruct-q8_0'
                && ! isset($payload['tools']);
        });
    }
}
