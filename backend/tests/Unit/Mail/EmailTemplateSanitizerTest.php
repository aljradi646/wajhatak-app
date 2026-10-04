<?php

namespace Tests\Unit\Mail;

use App\Services\Mail\EmailTemplateSanitizer;
use Tests\TestCase;

class EmailTemplateSanitizerTest extends TestCase
{
    public function test_removes_unsafe_markup_and_keeps_safe_image(): void
    {
        $html = '<div onclick="x"><script>x</script><a href="javascript:x">رابط</a><img src="https://example.com/x.png"></div>';

        $result = app(EmailTemplateSanitizer::class)->sanitize($html);

        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('https://example.com/x.png', $result);
        $this->assertStringContainsString('alt=""', $result);
    }

    public function test_removes_unsafe_css_constructs(): void
    {
        $css = '@import url("https://evil.test/x.css"); .x { behavior: url(x); background: expression(x); }';

        $result = app(EmailTemplateSanitizer::class)->sanitizeCss($css);

        $this->assertStringNotContainsString('@import', $result);
        $this->assertStringNotContainsString('behavior', $result);
        $this->assertStringNotContainsString('expression', $result);
    }
}
