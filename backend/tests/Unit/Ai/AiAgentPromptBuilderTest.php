<?php

namespace Tests\Unit\Ai;

use App\Services\AI\AiAgentPromptBuilder;
use Tests\TestCase;

class AiAgentPromptBuilderTest extends TestCase
{
    public function test_guest_prompt_renders_without_php_interpolation_errors(): void
    {
        $prompt = app(AiAgentPromptBuilder::class)->system(
            null,
            'ar',
            ['intent' => 'property_search'],
            ['preferred_city' => 'صنعاء'],
            [['topic' => 'البحث', 'content' => 'يمكن البحث حسب المدينة والسعر.']],
        );

        $this->assertStringContainsString('الدور: guest', $prompt);
        $this->assertStringContainsString('المستخدم المسجل: لا', $prompt);
        $this->assertStringContainsString('اللغة: ar', $prompt);
        $this->assertStringContainsString('preferred_city', $prompt);
    }
}
