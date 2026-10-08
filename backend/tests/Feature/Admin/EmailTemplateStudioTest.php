<?php

namespace Tests\Feature\Admin;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateStudioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin'));
        return $admin;
    }

    private function template(bool $published = false): EmailTemplate
    {
        $template = EmailTemplate::create([
            'key' => 'tests.template',
            'name' => 'قالب اختبار',
            'description' => 'اختبار',
            'subject' => 'مرحبًا {{user.name}}',
            'html_content' => '<p>{{user.name}}</p>',
            'text_content' => 'مرحبًا {{user.name}}',
            'css_styles' => ['p{font-family:Arial}'],
            'variables' => ['user.name'],
            'is_active' => $published,
            'is_system' => false,
            'version' => 1,
            'status' => $published ? 'published' : 'draft',
            'published_version' => $published ? 1 : null,
            'published_at' => $published ? now() : null,
        ]);

        EmailTemplateVersion::create([
            'email_template_id' => $template->id,
            'version' => 1,
            'subject' => $template->subject,
            'html_content' => $template->html_content,
            'text_content' => $template->text_content,
            'css_styles' => $template->css_styles,
            'variables' => $template->variables,
            'change_note' => 'initial',
        ]);

        return $template;
    }

    public function test_autosave_after_publish_creates_a_new_draft_version(): void
    {
        $template = $this->template(true);

        $response = $this->actingAs($this->admin())->postJson(
            route('admin.email-templates.autosave', $template),
            [
                'name' => $template->name,
                'subject' => 'موضوع جديد',
                'html_content' => '<p>نسخة مسودة</p>',
                'text_content' => 'نسخة مسودة',
                'css_styles' => ['p{color:red}'],
                'variables' => ['user.name'],
            ],
        );

        $response->assertOk()->assertJsonPath('version', 2);

        $live = $template->fresh();
        $this->assertSame('draft', $live->status);
        $this->assertSame(1, $live->published_version);
        $this->assertDatabaseHas('email_template_versions', [
            'email_template_id' => $template->id,
            'version' => 1,
            'subject' => $template->subject,
        ]);
        $this->assertDatabaseHas('email_template_versions', [
            'email_template_id' => $template->id,
            'version' => 2,
            'subject' => 'موضوع جديد',
        ]);
    }

    public function test_preview_reports_unknown_variables(): void
    {
        $template = $this->template();

        $response = $this->actingAs($this->admin())->postJson(
            route('admin.email-templates.preview', $template),
            [
                'name' => $template->name,
                'subject' => 'مرحبًا {{user.name}}',
                'html_content' => '<p>{{user.name}} {{missing}}</p>',
                'text_content' => '',
                'css_styles' => ['p{font-weight:bold}'],
                'preview_variables' => ['user.name' => 'نص معاينة'],
            ],
        );

        $response->assertOk()
            ->assertJsonPath('unknown_variables.0', 'missing');

        $this->assertStringContainsString('نص معاينة', $response->json('html'));
    }

    public function test_publish_marks_current_version_as_live(): void
    {
        $template = $this->template();
        $template->update(['version' => 2, 'html_content' => '<p>v2</p>']);
        EmailTemplateVersion::create([
            'email_template_id' => $template->id,
            'version' => 2,
            'subject' => $template->subject,
            'html_content' => '<p>v2</p>',
            'text_content' => 'v2',
            'css_styles' => [],
            'variables' => [],
            'change_note' => 'v2',
        ]);

        $response = $this->actingAs($this->admin())->postJson(
            route('admin.email-templates.publish', $template)
        );

        $response->assertOk()->assertJsonPath('version', 2);
        $fresh = $template->fresh();
        $this->assertSame('published', $fresh->status);
        $this->assertSame(2, $fresh->published_version);
    }
    public function test_history_route_is_not_captured_by_resource_show_route(): void
    {
        $template = $this->template();

        $response = $this->actingAs($this->admin())->get(
            route('admin.email-templates.history', $template)
        );

        $response->assertOk();
        $response->assertViewIs('admin.email-templates.history');
    }


    public function test_new_template_draft_preview_is_server_rendered(): void
    {
        $response = $this->actingAs($this->admin())->postJson(
            route('admin.email-templates.preview-draft'),
            [
                'name' => 'قالب جديد',
                'subject' => 'مرحبًا {{user.name}}',
                'html_content' => '<table><tr><td>{{user.name}}</td></tr></table>',
                'text_content' => 'مرحبًا {{user.name}}',
                'css_styles' => ['td{padding:20px}'],
                'variables' => ['user.name'],
                'preview_variables' => ['user.name' => 'عبدالرحمن'],
            ],
        );

        $response->assertOk()->assertJsonPath('unknown_variables', []);
        $this->assertStringContainsString('عبدالرحمن', $response->json('html'));
    }

    public function test_editor_can_upload_an_image_to_the_email_asset_library(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post(
            route('admin.email-templates.assets'),
            ['file' => UploadedFile::fake()->image('property-cover.jpg', 800, 600)],
            ['Accept' => 'application/json'],
        );

        $response->assertCreated();
        $path = basename(parse_url($response->json('data.0.src'), PHP_URL_PATH));
        Storage::disk('public')->assertExists('email-assets/'.$path);
    }

}
