<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * تمهيد خدمة الاستدلال المحلي في الحاوية الحالية — بدون إعادة نشر.
 *
 * يستدعي scripts/ai_inference_service.sh bootstrap بشكل متزامن:
 * يبني llama-server إن غاب (أو ينزّله جاهزًا)، يحمّل النموذج مرة واحدة،
 * ثم يشغّل الخادم وينتظر استجابته الفعلية.
 */
class AiBootstrapCommand extends Command
{
    protected $signature = 'ai:bootstrap {--wait=0 : انتظار الخادم حتى الاستجابة (ثوانٍ)}';

    protected $description = 'تأمين محرك ونموذج الاستدلال المحلي وتشغيل الخدمة فورًا (يُستخدم في الحاوية الحالية أو CI)';

    public function handle(): int
    {
        $script = base_path('scripts/ai_inference_service.sh');

        if (! is_file($script)) {
            $this->error('سكربت التمهيد غير موجود: '.$script);

            return self::FAILURE;
        }

        $this->info('بدء تمهيد خدمة الاستدلال المحلي (محرك + نموذج + تشغيل)...');
        $this->line('قد يستغرق البناء الأول للثنائي عدة دقائق — التقدم يُطبع مباشرة.');

        $process = new Process(['sh', $script, 'bootstrap'], base_path(), null, null, null);
        $process->setTimeout(null);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->newLine(2);
            $this->warn('انتهى التمهيد بحالة غير مكتملة — راجع الرسائل أعلاه.');

            return self::FAILURE;
        }

        // تحقق نهائي اختياري بفترة انتظار.
        $wait = (int) $this->option('wait');
        if ($wait > 0) {
            $this->newLine();
            $this->info("انتظار استجابة الخادم حتى {$wait} ثانية...");
            sleep(min($wait, 10));
        }

        // حالة نهائية عبر فاحص الصحة القياسي.
        $health = app(\App\Services\AI\AiProviderManager::class)->provider()->health();
        if ($health->healthy) {
            $this->newLine();
            $this->components->success("الاستدلال المحلي يعمل ✓ ({$health->latencyMs}ms) — جرّب المحادثة الآن من التطبيق أو /admin/ai/playground.");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->warn('الخادم لا يستجيب بعد: '.$health->message);
        $this->line('أعد الفحص بعد دقيقة: php artisan ai:status');

        return self::FAILURE;
    }
}
