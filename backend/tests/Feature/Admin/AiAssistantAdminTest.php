<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\AiAssistantController;
use App\Models\User;
use App\Services\AI\AiSettingsService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()->where('email', 'admin@wajhatak.app')->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_ai_overview_renders_with_section_cards(): void
    {
        $this->admin();

        $this->get('/admin/ai')
            ->assertOk()
            ->assertSee('أقسام الإعدادات')
            ->assertSee('التعليمات والسلوك')
            ->assertSee('الأمان والحواجز');
    }

    public function test_every_settings_section_page_renders(): void
    {
        $this->admin();

        foreach (array_keys(AiAssistantController::SETTINGS_SECTIONS) as $section) {
            $this->get("/admin/ai/settings/{$section}")
                ->assertOk()
                ->assertSee(AiAssistantController::SETTINGS_SECTIONS[$section]['label']);
        }
    }

    public function test_unknown_settings_section_returns_not_found(): void
    {
        $this->admin();

        $this->from('/admin/ai')->get('/admin/ai/settings/does-not-exist')->assertRedirect('/admin/ai');
    }

    public function test_playground_renders_without_calling_llm_health_on_page_load(): void
    {
        $this->admin();

        Illuminate\Support\Facades\Http::fake([
            '*' => Illuminate\Support\Facades\Http::response([], 500),
        ]);

        $this->get('/admin/ai/playground')
            ->assertOk()
            ->assertSee('محادثة اختبار تفاعلية مباشرة');

        Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_monitoring_stats_and_logs_pages_render(): void
    {
        $this->admin();

        $this->get('/admin/ai/monitoring')->assertOk()->assertSee('فحص حي');
        $this->get('/admin/ai/stats')->assertOk()->assertSee('الإحصاءات');
        $this->get('/admin/ai/logs')->assertOk()->assertSee('سجل طلبات المساعد');
        $this->get('/admin/ai/playground')->assertOk();
    }

    public function test_updating_a_section_only_saves_that_section(): void
    {
        $this->admin();

        $settings = app(AiSettingsService::class);
        $originalName = $settings->get('ai_assistant_name');

        $this->post('/admin/ai/settings/general', [
            'ai_assistant_name' => 'مساعد اختبار',
            'ai_welcome_message' => 'مرحبًا بك في وجهتك.',
            'ai_default_language' => 'ar',
            // ai_enabled غير مُرسل = يجب أن يُحفظ كـ 0
        ])->assertRedirect('/admin/ai/settings/general');

        $settings->flush();

        $this->assertSame('مساعد اختبار', $settings->get('ai_assistant_name'));
        $this->assertFalse((bool) $settings->get('ai_enabled'), 'تعطيل المساعد لم يُحفظ');
        // مفاتيح الأقسام الأخرى لم تتغير.
        $this->assertSame('relevance', $settings->get('ai_sort_strategy'));
        $this->assertNotSame($originalName, $settings->get('ai_assistant_name'));
    }

    public function test_disabled_assistant_can_be_re_enabled(): void
    {
        $this->admin();

        $settings = app(AiSettingsService::class);

        $this->post('/admin/ai/settings/general', [
            'ai_enabled' => '1',
            'ai_assistant_name' => 'مساعد وجهتك',
            'ai_welcome_message' => 'أهلًا بك!',
            'ai_default_language' => 'ar',
        ])->assertRedirect('/admin/ai/settings/general');

        $settings->flush();

        $this->assertTrue($settings->enabled());
    }

    public function test_section_update_validates_field_rules(): void
    {
        $this->admin();

        $this->from('/admin/ai/settings/behavior')
            ->post('/admin/ai/settings/behavior', [
                'ai_response_style' => 'concise',
                'ai_max_results' => 99,          // خارج الحد المسموح [1,6]
                'ai_min_match_score' => 5,       // خارج [0,1]
            ])
            ->assertRedirect('/admin/ai/settings/behavior')
            ->assertSessionHasErrors(['ai_max_results', 'ai_min_match_score']);
    }

    public function test_section_update_rejects_unknown_option_values(): void
    {
        $this->admin();

        $this->from('/admin/ai/settings/search')
            ->post('/admin/ai/settings/search', [
                'ai_sort_strategy' => 'random',   // غير مدعوم
                'ai_similarity_threshold' => 0.2,
                'ai_max_candidates' => 30,
                'ai_default_search_radius_km' => 10,
            ])
            ->assertRedirect('/admin/ai/settings/search')
            ->assertSessionHasErrors('ai_sort_strategy');
    }

    public function test_non_admin_cannot_reach_the_ai_admin_pages(): void
    {
        $this->seed(DemoDataSeeder::class);
        $user = User::query()->where('email', 'client.demo@lux.local')->firstOrFail();

        $this->actingAs($user)->get('/admin/ai')->assertForbidden();
        $this->actingAs($user)->get('/admin/ai/settings/general')->assertForbidden();
        $this->actingAs($user)->get('/admin/ai/logs')->assertForbidden();
        $this->actingAs($user)->post('/admin/ai/settings/general', [])->assertForbidden();
    }
}
