<?php

namespace Tests\Feature\Ai;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiNaturalConversationRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_noisy_greetings_are_not_treated_as_property_searches(): void
    {
        foreach (['الوو', 'الووو', 'هلااا', 'كيفكك', 'مساء الخير'] as $message) {
            $response = $this->postJson('/api/v1/ai/chat', ['message' => $message]);

            $response->assertOk();
            $data = $response->json('data');

            $this->assertSame('ok', $data['status'], $message);
            $this->assertSame([], $data['properties'], $message);
            $this->assertSame('small_talk', $data['intent'], $message);
            $this->assertStringNotContainsString('لا توجد حاليًا عقارات مطابقة', (string) $data['reply'], $message);
            $this->assertNotEmpty($data['reply'], $message);
        }
    }

    public function test_small_talk_does_not_reuse_previous_property_filters(): void
    {
        $first = $this->postJson('/api/v1/ai/chat', [
            'message' => 'شقة للإيجار في صنعاء',
        ]);

        $first->assertOk();

        $conversationId = $first->json('data.conversation_id');
        $sessionToken = $first->json('data.session_token');

        $second = $this->postJson('/api/v1/ai/chat', [
            'message' => 'الووو كيفك؟',
            'conversation_id' => $conversationId,
            'session_token' => $sessionToken,
        ]);

        $second->assertOk();
        $data = $second->json('data');

        $this->assertSame('ok', $data['status']);
        $this->assertSame('small_talk', $data['intent']);
        $this->assertSame([], $data['properties']);
        $this->assertSame([], $data['filters']);
        $this->assertStringNotContainsString('لا توجد حاليًا عقارات مطابقة', (string) $data['reply']);
    }

    public function test_acknowledgement_is_conversational_not_a_search(): void
    {
        $response = $this->postJson('/api/v1/ai/chat', ['message' => 'تمام']);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertSame('ok', $data['status']);
        $this->assertSame('small_talk', $data['intent']);
        $this->assertSame([], $data['properties']);
        $this->assertStringContainsString('تمام', (string) $data['reply']);
    }
}
