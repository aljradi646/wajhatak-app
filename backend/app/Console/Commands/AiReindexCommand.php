<?php

namespace App\Console\Commands;

use App\Services\AI\AiIndexSyncService;
use Illuminate\Console\Command;

class AiReindexCommand extends Command
{
    protected $signature = 'ai:reindex {--fresh : حذف الفهرس وإعادة بنائه من الصفر}';

    protected $description = 'إعادة بناء فهرس بحث المساعد الذكي من جدول العقارات (مزامنة كاملة)';

    public function handle(AiIndexSyncService $sync): int
    {
        if ($this->option('fresh')) {
            \App\Models\AiSearchIndex::query()->delete();
            $this->info('تم تفريغ الفهرس.');
        }

        $count = $sync->reindexAll();
        $this->info("تمت مزامنة {$count} عقار مع فهرس البحث الذكي.");

        return self::SUCCESS;
    }
}
