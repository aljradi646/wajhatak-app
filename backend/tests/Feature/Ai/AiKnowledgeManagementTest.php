<?php

namespace Tests\Feature\Ai;

use App\Models\AiKnowledgeArticle;
use App\Models\User;
use App\Services\AI\AiKnowledgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiKnowledgeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_store_and_publish_role_scoped_knowledge_in_database(): void
    {
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.ai.knowledge.store'), [
            'slug' => 'viewing-appointment-help',
            'topic' => 'طلب معاينة عقار',
            'content' => 'افتح تفاصيل العقار، ثم اختر طلب المعاينة وحدد الموعد المتاح وأرسل الطلب.',
            'keywords_text' => 'معاينة، طلب موعد، حجز زيارة',
            'roles' => ['client'],
            'target_screen' => 'PropertyDetailsScreen',
            'priority' => 10,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.ai.knowledge.index'));

        $article = AiKnowledgeArticle::query()->where('slug', 'viewing-appointment-help')->firstOrFail();
        $this->assertSame(1, $article->version);
        $this->assertTrue($article->is_active);
        $this->assertDatabaseHas('ai_knowledge_articles', [
            'id' => $article->id,
            'slug' => 'viewing-appointment-help',
            'is_active' => 1,
        ]);

        $knowledge = app(AiKnowledgeService::class);
        $clientResults = collect($knowledge->searchKnowledge('كيف أطلب معاينة وحجز زيارة؟', 'client'));
        $this->assertTrue($clientResults->contains('id', 'article-'.$article->id));

        $agentResults = collect($knowledge->searchKnowledge('كيف أطلب معاينة وحجز زيارة؟', 'agent'));
        $this->assertNull($agentResults->firstWhere('id', 'article-'.$article->id));
    }

    public function test_knowledge_edits_and_deactivation_increment_version_without_deleting_history(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $article = AiKnowledgeArticle::query()->create([
            'slug' => 'account-help',
            'topic' => 'مساعدة الحساب',
            'content' => 'اذهب إلى الملف الشخصي لتحديث البيانات الأساسية لحسابك.',
            'keywords' => ['الحساب', 'الملف الشخصي'],
            'roles' => ['client'],
            'is_active' => true,
            'priority' => 100,
            'version' => 1,
        ]);

        $this->put(route('admin.ai.knowledge.update', ['article' => $article->id]), [
            'slug' => 'account-help',
            'topic' => 'إدارة الحساب',
            'content' => 'افتح الملف الشخصي لمراجعة بيانات حسابك وتحديثها بالطريقة المتاحة.',
            'keywords_text' => 'الحساب، الملف الشخصي، تحديث البيانات',
            'roles' => ['client'],
            'target_screen' => 'ProfileScreen',
            'priority' => 30,
            'is_active' => '1',
        ])->assertRedirect(route('admin.ai.knowledge.index', ['edit' => $article->id]));

        $article->refresh();
        $this->assertSame(2, $article->version);
        $this->assertSame('إدارة الحساب', $article->topic);

        $this->patch(route('admin.ai.knowledge.status', ['article' => $article->id]), [
            'is_active' => '0',
        ])->assertRedirect(route('admin.ai.knowledge.index'));

        $article->refresh();
        $this->assertFalse($article->is_active);
        $this->assertSame(3, $article->version);
        $this->assertDatabaseHas('ai_knowledge_articles', ['id' => $article->id]);
        $this->assertNull(
            collect(app(AiKnowledgeService::class)->searchKnowledge('تحديث البيانات', 'client'))
                ->firstWhere('id', 'article-'.$article->id)
        );
    }

    public function test_admin_can_edit_and_disable_a_builtin_article_without_falling_back_to_stale_text(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai.knowledge.index'))
            ->assertOk();

        $article = AiKnowledgeArticle::query()->where('slug', 'viewing-request')->firstOrFail();

        $this->put(route('admin.ai.knowledge.update', ['article' => $article->id]), [
            'slug' => 'viewing-request',
            'topic' => 'إرشادات المعاينة الجديدة',
            'content' => 'تظهر الإرشادات المعدلة من قاعدة المعرفة بعد حفظها في لوحة الإدارة.',
            'keywords_text' => 'موعد خاص، معاينة مميزة',
            'roles' => ['client'],
            'target_screen' => 'PropertyDetailsScreen',
            'priority' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('admin.ai.knowledge.index', ['edit' => $article->id]));

        $results = collect(app(AiKnowledgeService::class)->searchKnowledge('أريد موعد خاص للمعاينة المميزة', 'client'));
        $edited = $results->firstWhere('id', 'viewing-request');
        $this->assertNotNull($edited);
        $this->assertSame('إرشادات المعاينة الجديدة', $edited['topic']);
        $this->assertSame('تظهر الإرشادات المعدلة من قاعدة المعرفة بعد حفظها في لوحة الإدارة.', $edited['content']);
        $this->assertCount(1, $results->where('id', 'viewing-request'));

        $this->patch(route('admin.ai.knowledge.status', ['article' => $article->id]), [
            'is_active' => '0',
        ])->assertRedirect(route('admin.ai.knowledge.index'));

        $afterDisable = collect(app(AiKnowledgeService::class)->searchKnowledge('موعد خاص معاينة مميزة', 'client'));
        $this->assertNull($afterDisable->firstWhere('id', 'viewing-request'));
        $this->assertDatabaseHas('ai_knowledge_articles', [
            'id' => $article->id,
            'slug' => 'viewing-request',
            'is_active' => 0,
            'version' => 3,
        ]);
    }

    public function test_non_admin_cannot_open_knowledge_management(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.ai.knowledge.index'))
            ->assertForbidden();
    }
}
