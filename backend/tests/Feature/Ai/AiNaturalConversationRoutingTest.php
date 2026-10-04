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

    public function test_wellbeing_variants_are_not_blocked_by_domain_guard(): void
    {
        foreach (['كيفك', 'كيفكك', 'ايش اخبارك', 'وش الاخبار', 'كيف يومك'] as $message) {
            $response = $this->postJson('/api/v1/ai/chat', ['message' => $message]);

            $response->assertOk();
            $data = $response->json('data');

            $this->assertSame('ok', $data['status'], $message);
            $this->assertSame('small_talk', $data['intent'], $message);
            $this->assertSame([], $data['properties'], $message);
            $this->assertStringNotContainsString('لا توجد حاليًا عقارات مطابقة', (string) $data['reply'], $message);
        }
    }

    public function test_generic_conversation_never_reuses_an_old_search(): void
    {
        $first = $this->postJson('/api/v1/ai/chat', [
            'message' => 'شقة للبيع في صنعاء',
        ]);

        $first->assertOk();

        $conversationId = $first->json('data.conversation_id');
        $sessionToken = $first->json('data.session_token');

        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'خلنا نتكلم شوي',
            'conversation_id' => $conversationId,
            'session_token' => $sessionToken,
        ]);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertSame('ok', $data['status']);
        $this->assertContains($data['intent'], ['conversation', 'conversation_llm']);
        $this->assertSame([], $data['properties']);
        $this->assertStringNotContainsString('لا توجد حاليًا عقارات مطابقة', (string) $data['reply']);
    }

    public function test_repeated_greetings_are_varied_in_same_conversation(): void
    {
        $first = $this->postJson('/api/v1/ai/chat', ['message' => 'السلام عليكم']);
        $first->assertOk();

        $conversationId = $first->json('data.conversation_id');
        $sessionToken = $first->json('data.session_token');

        $second = $this->postJson('/api/v1/ai/chat', [
            'message' => 'السلام عليكم',
            'conversation_id' => $conversationId,
            'session_token' => $sessionToken,
        ]);
        $second->assertOk();

        $this->assertNotSame(
            $first->json('data.reply'),
            $second->json('data.reply'),
            'يجب ألا تتكرر نفس صياغة التحية داخل المحادثة.'
        );
    }

    public function test_weather_and_joke_are_safe_conversational_intents(): void
    {
        foreach (['كيف الجو؟', 'كيف الجو في صنعاء؟', 'قول لي نكتة'] as $message) {
            $response = $this->postJson('/api/v1/ai/chat', ['message' => $message]);

            $response->assertOk();
            $data = $response->json('data');

            $this->assertSame('ok', $data['status'], $message);
            $this->assertSame('small_talk', $data['intent'], $message);
            $this->assertSame([], $data['properties'], $message);
            $this->assertStringNotContainsString('لا توجد حاليًا عقارات مطابقة', (string) $data['reply'], $message);
        }
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
