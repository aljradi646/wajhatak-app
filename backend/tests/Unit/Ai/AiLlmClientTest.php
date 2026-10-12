<?php

namespace Tests\Unit\Ai;

use App\Services\AI\AiLlmClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiLlmClientTest extends TestCase
{
    public function test_openai_compatible_chat_completion_is_parsed(): void
    {
        config()->set('ai.llm.enabled', true);
        config()->set('ai.llm.base_url', 'http://ollama.test/v1');
        config()->set('ai.llm.model', 'wajhatak-qwen3:1.7b');

        Http::fake([
            'http://ollama.test/v1/chat/completions' => Http::response([
                'model' => 'wajhatak-qwen3:1.7b',
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'وجدت لك خيارات حقيقية من المنصة.',
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 8,
                    'total_tokens' => 18,
                ],
            ], 200),
        ]);

        $result = app(AiLlmClient::class)->chat([
            ['role' => 'user', 'content' => 'ابحث عن شقة في صنعاء'],
        ], [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_properties',
                    'description' => 'البحث عن العقارات',
                    'parameters' => ['type' => 'object', 'properties' => []],
                ],
            ],
        ]);

        $this->assertSame('assistant', $result['message']['role']);
        $this->assertSame('وجدت لك خيارات حقيقية من المنصة.', $result['message']['content']);
        $this->assertSame(18, $result['usage']['total_tokens']);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'http://ollama.test/v1/chat/completions'
                && $payload['model'] === 'wajhatak-qwen3:1.7b'
                && isset($payload['tools'])
                && $payload['stream'] === false;
        });
    }

    public function test_streaming_chat_emits_visible_deltas_and_filters_thinking_tags(): void
    {
        config()->set('ai.llm.enabled', true);
        config()->set('ai.llm.base_url', 'http://ollama.test/v1');
        config()->set('ai.llm.model', 'wajhatak-qwen3:1.7b');

        $event = static fn (array $payload): string => 'data: '.json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        )."\n\n";

        Http::fake([
            'http://ollama.test/v1/chat/completions' => Http::response(
                $event(['model' => 'wajhatak-qwen3:1.7b', 'choices' => [['delta' => ['content' => 'أهلًا <thi']]]])
                .$event(['choices' => [['delta' => ['content' => 'nk>تفكير داخلي</think>بك!']]]])
                .'data: [DONE]'."\n\n",
                200,
                ['Content-Type' => 'text/event-stream'],
            ),
        ]);

        $deltas = [];
        $result = app(AiLlmClient::class)->chatStream(
            [['role' => 'user', 'content' => 'أهلًا']],
            static function (string $delta) use (&$deltas): void {
                $deltas[] = $delta;
            },
        );

        $this->assertSame('assistant', $result['message']['role']);
        $this->assertSame('أهلًا بك!', $result['message']['content']);
        $this->assertSame('أهلًا بك!', implode('', $deltas));
        $this->assertStringNotContainsString('تفكير داخلي', implode('', $deltas));

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'http://ollama.test/v1/chat/completions'
                && $payload['model'] === 'wajhatak-qwen3:1.7b'
                && $payload['stream'] === true;
        });
    }

    public function test_health_never_exposes_api_key(): void
    {
        config()->set('ai.llm.enabled', true);
        config()->set('ai.llm.base_url', 'http://ollama.test/v1');
        config()->set('ai.llm.model', 'wajhatak-qwen3:1.7b');
        config()->set('ai.llm.api_key', 'super-secret-test-key');

        Http::fake([
            'http://ollama.test/v1/models' => Http::response([
                'data' => [['id' => 'wajhatak-qwen3:1.7b']],
            ], 200),
        ]);

        $health = app(AiLlmClient::class)->health();

        $this->assertTrue($health['configured']);
        $this->assertTrue($health['reachable']);
        $this->assertArrayNotHasKey('api_key', $health);
        $this->assertStringNotContainsString('super-secret-test-key', json_encode($health, JSON_THROW_ON_ERROR));
    }
}
