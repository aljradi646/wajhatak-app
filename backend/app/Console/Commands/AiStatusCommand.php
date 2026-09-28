<?php

namespace App\Console\Commands;

use App\Services\AI\AiPropertySearchService;
use App\Services\AI\AiSettingsService;
use Illuminate\Console\Command;

/**
 * فحص جاهزية المساعد — المحرك حتمي 100% داخل الخادم:
 * لا نموذج ولا خادم استدلال ولا أي ملفات محمّلة. يتحقق الأمر من
 * الإعدادات ومن الفهرس الحقيقي للعقارات مباشرة.
 */
class AiStatusCommand extends Command
{
    protected $signature = 'ai:status';

    protected $description = 'فحص جاهزية المساعد الحتمي (الإعدادات + فهرس العقارات الحقيقي)';

    public function handle(AiSettingsService $settings, AiPropertySearchService $search): int
    {
        $this->components->info('حالة المساعد العقاري الذكي (محرك حتمي داخل الخادم):');

        if (! $settings->enabled()) {
            $this->components->warn('المساعد معطّل من إعدادات لوحة التحكم (ai_enabled=0). فعّله من /admin/ai.');

            return self::FAILURE;
        }
        $this->line('  التفعيل: مفعّل ✓');
        $this->line('  الاسم: '.$settings->assistantName());

        // فحص حي حقيقي: بحث فعلي في الفهرس.
        try {
            $results = $search->search(['sort' => 'relevance'], 3);
            $total = $results['total'];

            if ($total === 0) {
                $this->components->warn('الفهرس فارغ حاليًا — أضف عقارات منشورة أو شغّل: php artisan ai:reindex');

                return self::FAILURE;
            }

            $this->line("  فهرس البحث: {$total} عقار منشور ✓ — المساعد جاهز فورًا من التطبيق و/admin/ai/playground.");
            $this->line('  لا نماذج ولا تنزيلات: حجم المحرك 0 بايت على القرص.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->components->warn('تعذر فحص الفهرس: '.class_basename($e).' — تحقق من اتصال قاعدة البيانات.');

            return self::FAILURE;
        }
    }
}
