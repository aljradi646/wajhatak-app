<?php

namespace Tests\Feature\Admin;

use App\Models\ReportLog;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TYPES = ['agents', 'properties', 'requests', 'users'];

    private function admin(): User
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()->where('email', 'admin@wajhatak.app')->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_reports_index_renders_with_real_totals(): void
    {
        $this->admin();

        $this->get('/admin/reports')
            ->assertOk()
            ->assertSee('تقرير');
    }

    public function test_every_report_type_renders_html_from_real_data(): void
    {
        $this->admin();

        foreach (self::TYPES as $type) {
            $response = $this->get("/admin/reports/{$type}");

            $response->assertOk();
            $this->assertStringContainsString('<!DOCTYPE html>', (string) $response->getContent(), "HTML report {$type} not rendered");
        }
    }

    public function test_every_report_type_exports_csv_and_json(): void
    {
        $this->admin();

        foreach (self::TYPES as $type) {
            $csv = $this->get("/admin/reports/{$type}?format=csv");
            $csv->assertOk();
            $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));
            $this->assertGreaterThan(10, strlen((string) $csv->getContent()));

            $json = $this->get("/admin/reports/{$type}?format=json");
            $json->assertOk();
            $payload = json_decode((string) $json->getContent(), true);
            $this->assertIsArray($payload, "JSON report {$type} invalid");
            $this->assertArrayHasKey('rows', $payload);
            $this->assertArrayHasKey('summary', $payload);
        }
    }

    public function test_every_report_type_exports_excel(): void
    {
        $this->admin();

        foreach (self::TYPES as $type) {
            $response = $this->get("/admin/reports/{$type}?format=excel");
            $response->assertOk();
            $this->assertStringContainsString('ms-excel', (string) $response->headers->get('content-type'));
            $this->assertStringContainsString('<?mso-application', (string) $response->getContent());
        }
    }

    public function test_every_report_type_generates_a_valid_pdf(): void
    {
        $this->admin();

        foreach (self::TYPES as $type) {
            $response = $this->get("/admin/reports/{$type}?format=pdf");

            $response->assertOk();
            $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));

            $body = $response->getContent();
            $this->assertNotEmpty($body, "PDF report {$type} is empty");
            $this->assertStringStartsWith('%PDF-', $body, "PDF report {$type} is not a valid PDF");
        }
    }

    public function test_unknown_report_type_is_rejected(): void
    {
        $this->admin();

        // نوع غير معروف لا يطابق قيد المسار ولا يصل للكنترولر؛ ولوحة الإدارة تعيد التوجيه برسالة خطأ.
        $response = $this->from('/admin/reports')->get('/admin/reports/does-not-exist');
        $response->assertRedirect('/admin/reports');
        $response->assertSessionHasErrors('error');
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        $this->seed(DemoDataSeeder::class);
        $user = User::query()->where('email', 'client.demo@lux.local')->firstOrFail();

        $this->actingAs($user)->get('/admin/reports')->assertForbidden();
        $this->actingAs($user)->get('/admin/reports/agents')->assertForbidden();
        $this->actingAs($user)->get('/admin/reports/logs')->assertForbidden();
    }

    public function test_generating_a_report_persists_a_report_log_with_owner_and_filters(): void
    {
        $admin = $this->admin();

        $this->get('/admin/reports/properties?status=published&format=pdf')->assertOk();

        $log = ReportLog::query()->latest('id')->first();
        $this->assertNotNull($log, 'Report generation was not logged');
        $this->assertSame('properties', $log->type);
        $this->assertSame('pdf', $log->format);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['status' => 'published'], $log->filters);
        $this->assertSame(0, ReportLog::query()->where('type', 'agents')->count());
        $this->assertNotNull($log->file_name);
    }

    public function test_report_log_ignores_filters_that_are_not_whitelisted(): void
    {
        $this->admin();

        $this->get('/admin/reports/agents?status=active&injected=DROP%20TABLE%20users&format=csv')->assertOk();

        $log = ReportLog::query()->latest('id')->firstOrFail();
        $this->assertSame(['status' => 'active'], $log->filters);
        // لا تُحذف بيانات المستخدمين نتيجة تمرير مرشح غير مسموح.
        $this->assertGreaterThan(0, User::query()->count());
    }

    public function test_report_log_page_lists_generated_reports(): void
    {
        $this->admin();

        $this->get('/admin/reports/users?format=excel')->assertOk();

        $this->get('/admin/reports/logs')
            ->assertOk()
            ->assertSee('سجل التقارير')
            ->assertSee('تقرير المستخدمين');
    }

    public function test_a_previous_report_can_be_regenerated_from_the_log(): void
    {
        $this->admin();

        $this->get('/admin/reports/properties?status=published&format=csv')->assertOk();
        $log = ReportLog::query()->latest('id')->firstOrFail();

        $response = $this->get(route('admin.reports.logs.download', $log));
        $response->assertOk();
        $this->assertStringContainsString('csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('wajhatak-properties', (string) $response->headers->get('content-disposition'));
    }

    public function test_regenerating_an_html_log_redirects_to_the_preview_with_same_filters(): void
    {
        $this->admin();

        $this->get('/admin/reports/requests')->assertOk();
        $log = ReportLog::query()->latest('id')->firstOrFail();

        $this->get(route('admin.reports.logs.download', $log))
            ->assertRedirect(route('admin.reports.show', ['type' => 'requests']));
    }

    public function test_unknown_report_log_returns_not_found(): void
    {
        $this->admin();

        // لوحة الإدارة تحوّل 404 إلى توجيه برسالة خطأ (سلوك مقصود في bootstrap/app.php).
        $this->from('/admin/reports/logs')
            ->get('/admin/reports/logs/99999/download')
            ->assertRedirect('/admin/reports/logs')
            ->assertSessionHasErrors('error');
    }
}
