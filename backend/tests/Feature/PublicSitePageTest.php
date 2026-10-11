<?php

namespace Tests\Feature;

use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicSitePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_information_hub_and_policy_pages_render_outside_the_admin_area(): void
    {
        $this->get('/pages')
            ->assertOk()
            ->assertSee('السياسات، الإرشادات والدعم')
            ->assertSee('/pages/privacy')
            ->assertSee('/pages/terms')
            ->assertDontSee('/admin/pages');

        $this->get('/pages/privacy')
            ->assertOk()
            ->assertSee('سياسة الخصوصية')
            ->assertSee('آخر تحديث')
            ->assertSee('البيانات التي قد نعالجها');

        $this->get('/pages/terms')
            ->assertOk()
            ->assertSee('الشروط والأحكام')
            ->assertSee('طبيعة المنصة');
    }

    public function test_friendly_public_urls_redirect_to_their_database_page(): void
    {
        $this->get('/privacy')->assertRedirect(route('public.pages.show', 'privacy'));
        $this->get('/terms')->assertRedirect(route('public.pages.show', 'terms'));
        $this->get('/about')->assertRedirect(route('public.pages.show', 'about'));
        $this->get('/how-to-use')->assertRedirect(route('public.pages.show', 'how-to-use'));
        $this->get('/contact')->assertRedirect(route('public.pages.show', 'contact'));
    }

    public function test_unpublished_content_is_not_exposed_on_public_web_or_api(): void
    {
        $page = SitePage::query()->where('slug', 'privacy')->firstOrFail();
        $page->update(['is_published' => false]);

        $this->get('/pages/privacy')->assertNotFound();
        $this->getJson('/api/v1/pages/privacy')->assertNotFound();
        $this->getJson('/api/v1/pages')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'privacy']);
    }
}
