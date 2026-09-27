<?php

namespace App\Console\Commands;

use App\Services\AI\AiProviderManager;
use Illuminate\Console\Command;

class AiStatusCommand extends Command
{
    protected $signature = 'ai:status';

    protected $description = 'فحص حالة خدمة الاستدلال المحلي للمساعد الذكي (العملية، النموذج، الصحة)';

    public function handle(AiProviderManager $providers): int
    {
        $modelDir = storage_path('app/ai/models');
        $modelFile = $modelDir.'/current.gguf';
        $marker = $modelDir.'/current.ready';
        $log = $modelDir.'/llama-server.log';
        $managerLog = storage_path('logs/ai-inference.log');

        $this->components->info('حالة خدمة الاستدلال المحلي:');

        // 1) الملف الثنائي.
        $binary = '/usr/local/bin/llama-server';
        $this->line('  الملف الثنائي: '.(file_exists($binary) ? 'موجود ✓' : 'غير موجود ✗ (الصورة قديمة؟ أعد البناء)'));

        // 2) النموذج.
        if (is_file($modelFile)) {
            $size = round((float) filesize($modelFile) / 1048576);
            $ready = is_file($marker);
            $this->line("  النموذج: موجود ({$size}MB) ".($ready ? '— جاهز ✓' : '— التنزيل لم يكتمل ✗'));
        } else {
            $this->line('  النموذج: غير محمّل بعد — يتم تنزيله في الخلفية عند أول تشغيل (~1.9GB).');
        }

        // 3) سجل المدير (آخر 5 أسطر).
        if (is_file($managerLog)) {
            $this->line('  سجل المدير (آخر الأسطر):');
            foreach (array_slice(file($managerLog) ?: [], -5) as $line) {
                $this->line('    | '.trim($line));
            }
        } else {
            $this->line('  سجل المدير: لا يوجد (الخدمة لم تُشغَّل على هذه الحاوية بعد).');
        }

        // 4) سجل الخادم عند وجود مشكلة.
        if (is_file($log) && ! is_file($marker)) {
            $this->warn('  سجل llama-server (آخر 5 أسطر):');
            foreach (array_slice(file($log) ?: [], -5) as $line) {
                $this->line('    | '.trim($line));
            }
        }

        // 5) الفحص الفعلي عبر OpenAI-compatible /models.
        $health = $providers->provider()->health();
        if ($health->healthy) {
            $this->components->success("الخادم يستجيب ✓ ({$health->latencyMs}ms) — {$health->message}");
            $this->info('  الاستدلال جاهز: المساعد سيعمل بنموذج محلي حقيقي.');
        } else {
            $this->components->warn('الخادم لا يستجيب بعد — '.$health->message);
            $this->line('  ملاحظة: أول تحميل للنموذج يستغرق دقائق (تنزيل + تحميل للذاكرة). أعد الفحص بعد قليل: php artisan ai:status');
        }

        return $health->healthy ? self::SUCCESS : self::FAILURE;
    }
}
