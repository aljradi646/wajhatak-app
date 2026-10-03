<?php

namespace App\Console\Commands;

use App\Models\AiRequestLog;
use App\Models\AiSearchIndex;
use App\Models\Property;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiPropertySearchService;
use App\Services\AI\AiSchemaService;
use App\Services\AI\AiSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * طبيب المساعد — تشخيص حقيقي من داخل التطبيق نفسه.
 *
 * الاستخدام على الاستضافة (بعد النشر):
 *     php artisan ai:doctor
 *     php artisan ai:doctor --fix     # يصلح الناقص: جداول/أعمدة/فهرس
 *
 * الأمر يفحص بالترتيب: الاتصال بالقاعدة ← مخطط المساعد ← الإعدادات ←
 * الفهرس مقابل العقارات المنشورة ← بحث حقيقي ← محادثة حقيقية كاملة
 * (نفس مسار التطبيق تمامًا) — ويطبع سبب الفشل بالحرف عند أي خطوة.
 */
class AiDoctorCommand extends Command
{
    protected $signature = 'ai:doctor {--fix : إصلاح المخطط الناقص وإعادة بناء الفهرس إن كان فارغًا}';

    protected $description = 'تشخيص فوري لمشاكل المساعد الذكي (مخطط + إعدادات + بحث + محادثة حقيقية)';

    public function handle(
        AiSchemaService $schema,
        AiSettingsService $settings,
        AiPropertySearchService $search,
        AiAssistantService $assistant,
    ): int {
        $problems = 0;

        $this->components->info('فحص المساعد العقاري الذكي (وجهتك)');
        $this->newLine();

        // 1) قاعدة البيانات
        $this->line('<fg=cyan>1) الاتصال بقاعدة البيانات</>');
        try {
            DB::select('select 1');
            $this->line('   ✓ الاتصال يعمل — السائق: '.DB::getDriverName());
        } catch (Throwable $e) {
            $this->line('   ✗ فشل الاتصال: '.$e->getMessage().' — راجع متغيّرات DB_* على الاستضافة.');

            return self::FAILURE;
        }

        // 2) المخطط
        $this->newLine();
        $this->line('<fg=cyan>2) مخطط جداول المساعد</>');
        if ($this->option('fix')) {
            $result = $schema->ensure(force: true);
            $this->line('   إصلاح: أنشئ '.count($result['created']).' جدول، أضيف '.count($result['columns']).' عمود، فُهرس '.$result['indexed'].' عقار.');
            foreach ($result['errors'] as $error) {
                $this->line('   ! '.$error);
            }
        }

        foreach ($schema->diagnose() as $table => $info) {
            if (! empty($info['error'])) {
                $this->line("   ✗ {$table}: {$info['error']}");
                $problems++;

                continue;
            }
            if (empty($info['exists'])) {
                $this->line("   ✗ {$table}: الجدول غير موجود — نفّذ: php artisan ai:doctor --fix  أو  php artisan migrate --force");
                $problems++;

                continue;
            }
            if (! empty($info['missing_columns'])) {
                $this->line("   ✗ {$table}: أعمدة ناقصة (".implode(', ', $info['missing_columns']).') — نفّذ: php artisan ai:doctor --fix');
                $problems++;

                continue;
            }
            $this->line("   ✓ {$table}");
        }

        // 3) الإعدادات
        $this->newLine();
        $this->line('<fg=cyan>3) الإعدادات</>');
        $this->line('   • مفعّل: '.($settings->enabled() ? 'نعم ✓' : 'لا ✗ (فعّله من /admin/ai)'));
        $this->line('   • الاسم: '.$settings->assistantName());
        $this->line('   • أقصى نتائج: '.$settings->get('ai_max_results', 6).' | كلمات مرشحة: '.$settings->get('ai_max_candidates', 60));
        if (! $settings->enabled()) {
            $problems++;
        }

        // 4) الفهرس مقابل العقارات الحقيقية
        $this->newLine();
        $this->line('<fg=cyan>4) فهرس البحث مقابل البيانات الحقيقية</>');
        try {
            $published = Property::query()->where('status', 'published')->count();
            $total = Property::query()->count();
            $indexed = AiSearchIndex::query()->count();
            $this->line("   • عقارات في القاعدة: {$total} (منها {$published} منشورًا)");
            $this->line("   • صفوف فهرس المساعد: {$indexed}");
            if ($indexed === 0 && $total > 0) {
                $this->line('   ✗ الفهرس فارغ رغم وجود عقارات — نفّذ: php artisan ai:reindex');
                $problems++;
            } elseif ($indexed === 0) {
                $this->line('   ! لا توجد عقارات في القاعدة أصلًا — أضف عقارًا منشورًا ليجيب المساعد ببطاقات.');
            } else {
                $this->line('   ✓ الفهرس جاهز.');
            }
        } catch (Throwable $e) {
            $this->line('   ✗ تعذر قراءة الجداول: '.$e->getMessage());
            $problems++;
        }

        // 5) بحث حقيقي
        $this->newLine();
        $this->line('<fg=cyan>5) بحث حقيقي في الفهرس</>');
        try {
            $result = $search->search(['sort' => 'relevance'], 3);
            $this->line('   ✓ نجح البحث — إجمالي المطابق: '.$result['total'].'، معاد: '.count($result['items']));
            if (! empty($result['degraded'])) {
                $this->line('   ✗ البحث متدهور (جدول الفهرس غير متاح) — راجع الخطوة 2.');
                $problems++;
            }
            foreach (array_slice($result['items'], 0, 3) as $item) {
                $this->line('     - #'.$item['property_id'].' '.$item['title'].' | '.$item['city'].' | '.$item['price'].' '.($item['currency'] ?? ''));
            }
        } catch (Throwable $e) {
            $this->line('   ✗ فشل البحث: '.$e::class.': '.$e->getMessage());
            $this->line('     في الملف: '.$e->getFile().':'.$e->getLine());
            $problems++;
        }

        // 6) محادثة حقيقية كاملة (نفس مسار التطبيق)
        $this->newLine();
        $this->line('<fg=cyan>6) محادثة حقيقية كاملة (نفس مسار التطبيق)</>');
        foreach (['مرحبا', 'شقة للإيجار في صنعاء'] as $probe) {
            try {
                $response = $assistant->handleChat(null, $probe, null, 'ar');
                $status = $response['status'] ?? '?';
                $cards = count($response['properties'] ?? []);
                $icon = in_array($status, ['ok', 'blocked'], true) ? '✓' : '✗';
                if ($icon === '✗') {
                    $problems++;
                }
                $this->line("   {$icon} «{$probe}» → الحالة: {$status} | بطاقات: {$cards}");
                $this->line('     الرد: '.mb_substr(str_replace("\n", ' / ', (string) ($response['reply'] ?? '')), 0, 160));
                if (! empty($response['error'])) {
                    $this->line('     رمز الخطأ: '.$response['error']);
                }
            } catch (Throwable $e) {
                $this->line("   ✗ «{$probe}» رمى استثناء: ".$e::class.': '.$e->getMessage());
                $this->line('     في الملف: '.$e->getFile().':'.$e->getLine());
                $this->line('     أول مسار: '.collect($e->getTrace())->take(3)->map(fn ($t) => ($t['class'] ?? '').'::'.($t['function'] ?? ''))->implode(' ← '));
                $problems++;
            }
        }

        // 7) آخر الأخطاء المسجلة
        $this->newLine();
        $this->line('<fg=cyan>7) آخر أخطاء المساعد المسجلة</>');
        try {
            $errors = AiRequestLog::query()->where('status', 'error')->latest()->limit(5)->get(['created_at', 'intent', 'error_code', 'latency_ms']);
            if ($errors->isEmpty()) {
                $this->line('   ✓ لا توجد أخطاء مسجلة.');
            } else {
                foreach ($errors as $error) {
                    $this->line('   • '.$error->created_at.' | '.$error->intent.' | '.$error->error_code);
                }
            }
        } catch (Throwable $e) {
            $this->line('   ! تعذر قراءة السجل: '.$e->getMessage());
        }

        $this->newLine();
        if ($problems === 0) {
            $this->components->info('المساعد سليم تمامًا ✓ — يعمل بلا أي نموذج أو مزود خارجي.');

            return self::SUCCESS;
        }

        $this->components->error("عدد المشاكل: {$problems}. راجع التفاصيل أعلاه (والسجل: storage/logs/laravel.log).");

        return self::FAILURE;
    }
}
