<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentReviewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()->where('email', 'admin@wajhatak.app')->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    private function agent(string $verificationStatus = 'pending'): Agent
    {
        $agent = Agent::query()->firstOrFail();
        $agent->forceFill([
            'verification_status' => $verificationStatus,
            'is_active' => $verificationStatus === 'approved',
            'verified_at' => $verificationStatus === 'approved' ? now() : null,
        ])->save();

        return $agent->refresh();
    }

    public function test_agent_review_page_renders_all_tabs_with_real_data(): void
    {
        $this->admin();
        $agent = $this->agent();

        $response = $this->get(route('admin.agents.show', $agent));

        $response->assertOk();
        $response->assertSee('بيانات الوكيل');
        $response->assertSee('عقارات الوكيل');
        $response->assertSee('التوثيق');
        $response->assertSee('سجلات التقارير');
        $response->assertSee('المحادثات');
        $response->assertSee('طلبات المعاينة');
        $response->assertSee('السجل');
        $response->assertSee($agent->user->name);
    }

    public function test_verifications_page_links_to_the_full_review_page(): void
    {
        $this->admin();
        $agent = $this->agent('pending');

        $this->get('/admin/agents/verifications')
            ->assertOk()
            ->assertSee(route('admin.agents.show', $agent));
    }

    public function test_approving_an_agent_opens_publishing_and_is_logged(): void
    {
        $this->admin();
        $agent = $this->agent('pending');

        $this->post(route('admin.agents.approve', $agent))->assertRedirect();

        $agent->refresh();
        $this->assertSame('approved', $agent->verification_status);
        $this->assertTrue($agent->is_active);
        $this->assertNotNull($agent->verified_at);
        $this->assertTrue($agent->isApproved());

        $this->assertTrue(
            ActivityLog::query()
                ->where('subject_type', Agent::class)
                ->where('subject_id', $agent->id)
                ->where('description', 'like', '%توثيق%')
                ->exists(),
            'عملية التوثيق لم تُسجَّل في سجل الأنشطة'
        );
    }

    public function test_rejecting_an_agent_requires_and_stores_a_reason(): void
    {
        $this->admin();
        $agent = $this->agent('pending');

        // بدون سبب → خطأ تحقق ولا يتغير الحساب.
        $this->from(route('admin.agents.show', $agent))
            ->post(route('admin.agents.reject-verification', $agent), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $agent->refresh()->verification_status);

        // بسبب → يُحفظ ويُمنع النشر.
        $this->post(route('admin.agents.reject-verification', $agent), [
            'reason' => 'صورة الترخيص غير واضحة.',
        ])->assertRedirect();

        $agent->refresh();
        $this->assertSame('rejected', $agent->verification_status);
        $this->assertSame('صورة الترخيص غير واضحة.', $agent->rejection_reason);
        $this->assertFalse($agent->is_active);
        $this->assertFalse($agent->isApproved());
    }

    public function test_non_admin_cannot_view_or_act_on_agents(): void
    {
        $this->seed(DemoDataSeeder::class);
        $agent = $this->agent('pending');
        $client = User::query()->where('email', 'client.demo@lux.local')->firstOrFail();

        $this->actingAs($client)->get(route('admin.agents.show', $agent))->assertForbidden();
        $this->actingAs($client)->post(route('admin.agents.approve', $agent))->assertForbidden();
        $this->actingAs($client)->post(route('admin.agents.reject-verification', $agent), ['reason' => 'x'])->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_from_agent_review(): void
    {
        $this->seed(DemoDataSeeder::class);
        $agent = $this->agent('pending');

        $this->get(route('admin.agents.show', $agent))->assertRedirect(route('login'));
    }
}
