<?php

namespace App\Console\Commands;

use App\Services\AI\AiProviderManager;
use Illuminate\Console\Command;

class AiStatusCommand extends Command
{
    protected $signature = 'ai:status';

    protected $description = 'فحص حالة خدمة الاستدلال المحلي (المحرك، النموذج، الخادم، الصحة) مع تلميحات إصلاح مباشرة';

    public function handle(AiProviderManager $providers): int
    {
        $dir = storage_path('app/ai/models');
        $bundled = '/usr/local/bin/llama-server';
        $cachedBin = $dir.'/llama-server';
        $modelFile = $dir.'/current.gguf';
        $partFile = $modelFile.'.part';
        $marker = $dir.'/current.ready';
        $serverLog = $dir.'/llama-server.log';
        $buildLog = $dir.'/build.log';
        $managerLog = storage_path('logs/ai-inference.log');

        $this->components->info('حالة خدمة الاستدلال المحلي:');

        // 1) المحرك — الثنائي.
        if (is_file($bundled)) {
            $this->line('  المحرك: مدمج في الصورة ✓ ('.$bundled.')');
        } elseif (is_file($cachedBin)) {
            $this->line('  المحرك: مبني في الحجم الدائم ✓ ('.$cachedBin.')');
        } else {
            $this->line('  المحرك: غير متوفر بعد — سيُبنى من المصدر تلقائيًا عند أول تمهيد (5-20 دقيقة مرة واحدة).');
        }

        // 2) النموذج.
        if (is_file($marker) && is_file($modelFile)) {
            $size = round((float) filesize($modelFile) / 1048576);
            $this->line("  النموذج: جاهز ✓ ({$size}MB)");
        } elseif (is_file($partFile)) {
            $part = round((float) filesize($partFile) / 1048576);
            $this->line("  النموذج: قيد التنزيل الآن... ({$part}MB من ~1900MB)");
        } elseif (is_file($modelFile)) {
            $size = round((float) filesize($modelFile) / 1048576);
            $this->line("  النموذج: تنزيل غير مكتمل ✗ ({$size}MB) — سيُعاد تلقائيًا.");
        } else {
            $this->line('  النموذج: لم يبدأ التنزيل بعد.');
        }

        // 3) سجل المدير — آخر الأحداث.
        if (is_file($managerLog)) {
            $this->line('  آخر أحداث المدير:');
            foreach (array_slice(file($managerLog) ?: [], -6) as $line) {
                $this->line('    | '.trim($line));
            }
        } else {
            $this->line('  سجل المدير: لا يوجد — الخدمة الخلفية لم تُطلق على هذه الحاوية (أعد النشر أو شغّل: php artisan ai:bootstrap).');
        }

        // 4) سجل البناء عند وجود بناء جارٍ أو فاشل.
        if (is_file($buildLog)) {
            $size = round((float) filesize($buildLog) / 1024);
            $this->line("  سجل البناء: موجود ({$size}KB) — آخر الأسطر عند الفشل فقط:");
            if (! is_file($cachedBin)) {
                foreach (array_slice(file($buildLog) ?: [], -3) as $line) {
                    $this->line('    | '.trim($line));
                }
            }
        }

        // 5) سجل الخادم عند غياب الجاهزية.
        if (is_file($serverLog) && ! is_file($marker)) {
            $this->line('  سجل llama-server (آخر 5 أسطر):');
            foreach (array_slice(file($serverLog) ?: [], -5) as $line) {
                $this->line('    | '.trim($line));
            }
        }

        // 6) الفحص الحي الفعلي.
        $health = $providers->provider()->health();
        if ($health->healthy) {
            $this->components->success("الاستدلال يعمل الآن ✓ ({$health->latencyMs}ms) — المساعد جاهز من التطبيق و/admin/ai/playground.");

            return self::SUCCESS;
        }

        $this->components->warn('الخادم لا يستجيب بعد: '.$health->message);

        // تلميح إجرائي حسب الحالة.
        if (! is_file($bundled) && ! is_file($cachedBin)) {
            $this->line('  → للإصلاح الفوري على هذه الحاوية: php artisan ai:bootstrap');
        } elseif (! is_file($marker)) {
            $this->line('  → التنزيل/التحميل جارٍ في الخلفية — أعد الفحص بعد دقائق: php artisan ai:status');
        } else {
            $this->line('  → أعد تشغيل الخدمة: php artisan ai:bootstrap  (أو أعد نشر الحاوية)');
        }

        return self::FAILURE;
    }
}
