<?php

namespace Tests\Unit\Mail;

use App\Models\EmailTemplate;
use App\Services\Mail\EmailTemplateRenderer;
use Tests\TestCase;

class EmailTemplateRendererTest extends TestCase
{
    public function test_variables_are_escaped_and_unknown_variables_reported(): void
    {
        $template = new EmailTemplate([
            'subject' => 'مرحبًا {{user.name}}',
            'html_content' => '<p>{{user.name}}</p><p>{{missing}}</p>',
            'text_content' => 'مرحبًا {{user.name}}',
            'css_styles' => ['p { font-family: Arial; }'],
        ]);

        $result = app(EmailTemplateRenderer::class)->render($template, [
            'user.name' => '<عبد الرحمن>',
        ]);

        $this->assertSame('مرحبًا <عبد الرحمن>', $result['subject']);
        $this->assertStringContainsString('&lt;عبد الرحمن&gt;', $result['html']);
        $this->assertStringNotContainsString('{{missing}}', $result['html']);
        $this->assertContains('missing', $result['unknown_variables']);
        $this->assertStringContainsString('dir="rtl"', $result['html']);
        $this->assertStringContainsString('<table', $result['html']);
    }
}
