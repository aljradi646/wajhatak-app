<?php

namespace App\Services\AI;

use App\Models\AiSearchIndex;
use App\Models\Property;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ضمان مخطط المساعد في زمن التشغيل (Self-healing schema).
 *
 * لماذا؟ على الاستضافة قد لا تُنفَّذ الهجرات عند النشر (سكربت الدخول كان
 * يتخطاها بعد التهيئة الأولى، وسكربتات النشر قد تفشل بصمت)، فيبقى جدول أو
 * عمود ناقصًا، ويفشل **كل** طلب محادثة بخطأ عام «حدث خلل مؤقت» بلا أي إشارة
 * إلى السبب. هذه الخدمة:
 *   1. تتحقق (مرة كل بضع دقائق، مع كاش) من وجود جداول المساعد وأعمدته الحرجة.
 *   2. تُنشئ/تُضيف الناقص فقط — لا تلمس أي بيانات موجودة إطلاقًا.
 *   3. تبني فهرس البحث آليًا إن كان فارغًا وعندنا عقارات فعلية في القاعدة.
 *
 * كل ذلك داخل try/catch: إن مُنعت صلاحية DDL تفشل بهدوء وتُسجّل السبب،
 * ويستمر المساعد في العمل بالبيانات الموجودة.
 */
class AiSchemaService
{
    private const CACHE_KEY = 'ai_schema_ready_v1';
    private const CACHE_TTL = 300;
    /** سقف إعادة بناء الفهرس تلقائيًا (حماية من تنفيذ ثقيل داخل طلب). */
    private const AUTO_INDEX_MAX_PROPERTIES = 5000;

    private const TABLES = ['ai_conversations', 'ai_messages', 'ai_request_logs', 'ai_search_index'];

    public function __construct(private readonly AiIndexSyncService $indexer) {}

    /**
     * @return array{checked: bool, created: list<string>, columns: list<string>, indexed: int, errors: list<string>}
     */
    public function ensure(bool $force = false): array
    {
        $result = [
            'checked' => false,
            'created' => [],
            'columns' => [],
            'indexed' => 0,
            'errors' => [],
        ];

        // لا حالة ثابتة في الذاكرة: القرار من الكاش وحده، فلا يبقى الفحص
        // معطّلًا إن فشل مرة واحدة أو تغيّرت القاعدة.
        if (! $force) {
            try {
                if (Cache::get(self::CACHE_KEY) === 'ok') {
                    return $result;
                }
            } catch (\Throwable) {
                // كاش معطّل → نكمل الفحص الفعلي.
            }
        }

        $result['checked'] = true;

        try {
            $result['created'] = $this->ensureTables();
            $result['columns'] = $this->ensureColumns();
            $result['indexed'] = $this->ensureIndex();

            if ($result['errors'] === []) {
                try {
                    Cache::put(self::CACHE_KEY, 'ok', self::CACHE_TTL);
                } catch (\Throwable) {
                    // لا مشكلة — سنفحص مرة أخرى في الطلب القادم.
                }
            }
        } catch (\Throwable $e) {
            $result['errors'][] = class_basename($e).': '.$e->getMessage();
            Log::error('ai.schema_ensure_failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
        }

        if ($result['created'] !== [] || $result['columns'] !== [] || $result['errors'] !== []) {
            Log::warning('ai.schema_repaired', [
                'created' => $result['created'],
                'columns' => $result['columns'],
                'indexed' => $result['indexed'],
                'errors' => $result['errors'],
            ]);
        }

        return $result;
    }

    /** فحص تفصيلي بلا إصلاح — يُستخدم في `php artisan ai:doctor` ولوحة التحكم. */
    public function diagnose(): array
    {
        $report = [];
        foreach (self::TABLES as $table) {
            try {
                $report[$table] = [
                    'exists' => Schema::hasTable($table),
                    'missing_columns' => Schema::hasTable($table)
                        ? array_values(array_filter(
                            array_keys(self::COLUMN_MAP[$table] ?? []),
                            fn (string $column) => ! Schema::hasColumn($table, $column),
                        ))
                        : array_keys(self::COLUMN_MAP[$table] ?? []),
                ];
            } catch (\Throwable $e) {
                $report[$table] = ['exists' => false, 'missing_columns' => [], 'error' => $e->getMessage()];
            }
        }

        return $report;
    }

    // ------------------------------------------------------------------

    /** @return list<string> الجداول التي أُنشئت الآن. */
    private function ensureTables(): array
    {
        $created = [];

        if (! Schema::hasTable('ai_conversations')) {
            Schema::create('ai_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('session_token', 64)->nullable()->unique();
                $table->string('locale', 5)->default('ar');
                $table->string('status', 20)->default('active')->index();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });
            $created[] = 'ai_conversations';
        }

        if (! Schema::hasTable('ai_messages')) {
            Schema::create('ai_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
                $table->string('role', 12)->index();
                $table->longText('content');
                $table->json('structured_filters')->nullable();
                $table->json('property_ids')->nullable();
                $table->string('status', 20)->default('ok');
                $table->timestamps();
                $table->index(['ai_conversation_id', 'created_at']);
            });
            $created[] = 'ai_messages';
        }

        if (! Schema::hasTable('ai_request_logs')) {
            Schema::create('ai_request_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_conversation_id')->nullable()->index();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('request_id', 64)->index();
                $table->string('intent', 40)->nullable()->index();
                $table->json('structured_filters')->nullable();
                $table->json('tool_calls')->nullable();
                $table->unsignedInteger('results_count')->default(0);
                $table->string('status', 20)->default('ok')->index();
                $table->string('error_code', 60)->nullable();
                $table->unsignedInteger('latency_ms')->default(0);
                $table->unsignedInteger('search_ms')->default(0);
                $table->unsignedInteger('tokens_used')->default(0);
                $table->timestamps();
                $table->index(['created_at', 'status']);
            });
            $created[] = 'ai_request_logs';
        }

        if (! Schema::hasTable('ai_search_index')) {
            Schema::create('ai_search_index', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('transaction_type', 10)->nullable()->index();
                $table->string('status', 20)->index();
                $table->string('type_slug', 60)->nullable()->index();
                $table->string('type_name_ar', 60)->nullable();
                $table->string('city', 100)->nullable()->index();
                $table->string('district', 100)->nullable()->index();
                $table->string('neighborhood', 100)->nullable()->index();
                $table->decimal('price', 15, 2)->nullable()->index();
                $table->string('currency', 3)->nullable();
                $table->decimal('area', 10, 2)->nullable();
                $table->unsignedSmallInteger('bedrooms')->nullable()->index();
                $table->unsignedSmallInteger('bathrooms')->nullable();
                $table->boolean('is_furnished')->default(false)->index();
                $table->boolean('is_new')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->decimal('latitude', 10, 7)->nullable()->index();
                $table->decimal('longitude', 10, 7)->nullable()->index();
                $table->text('search_text')->nullable();
                $table->string('content_hash', 64)->nullable()->index();
                $table->timestamps();
            });
            $created[] = 'ai_search_index';

            $this->ensureFullTextIndex();
        }

        return $created;
    }

    /** خريطة الأعمدة الحرجة لكل جدول — تُستخدم للإصلاح والفحص. */
    private const COLUMN_MAP = [
        'ai_conversations' => ['user_id' => true, 'session_token' => true, 'locale' => true, 'status' => true, 'last_message_at' => true],
        'ai_messages' => ['ai_conversation_id' => true, 'role' => true, 'content' => true, 'structured_filters' => true, 'property_ids' => true, 'status' => true, 'response_type' => true, 'metadata' => true],
        'ai_request_logs' => ['ai_conversation_id' => true, 'user_id' => true, 'request_id' => true, 'intent' => true, 'structured_filters' => true, 'tool_calls' => true, 'results_count' => true, 'status' => true, 'response_type' => true, 'error_code' => true, 'fallback_reason' => true, 'knowledge_version' => true, 'latency_ms' => true, 'search_ms' => true, 'tokens_used' => true],
        'ai_user_memories' => ['user_id' => true, 'memory_key' => true, 'memory_value' => true, 'confidence' => true, 'source' => true, 'last_used_at' => true, 'expires_at' => true],
        'ai_search_index' => ['property_id' => true, 'title' => true, 'description' => true, 'transaction_type' => true, 'status' => true, 'type_slug' => true, 'type_name_ar' => true, 'city' => true, 'district' => true, 'neighborhood' => true, 'price' => true, 'currency' => true, 'area' => true, 'bedrooms' => true, 'bathrooms' => true, 'is_furnished' => true, 'is_new' => true, 'is_featured' => true, 'published_at' => true, 'latitude' => true, 'longitude' => true, 'search_text' => true, 'content_hash' => true],
    ];

    /** @return list<string> الأعمدة التي أُضيفت الآن. */
    private function ensureColumns(): array
    {
        $added = [];

        $definitions = [
            'ai_conversations' => [
                'user_id' => fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->index(),
                'session_token' => fn (Blueprint $t) => $t->string('session_token', 64)->nullable(),
                'locale' => fn (Blueprint $t) => $t->string('locale', 5)->default('ar'),
                'status' => fn (Blueprint $t) => $t->string('status', 20)->default('active')->index(),
                'last_message_at' => fn (Blueprint $t) => $t->timestamp('last_message_at')->nullable(),
            ],
            'ai_messages' => [
                'ai_conversation_id' => fn (Blueprint $t) => $t->unsignedBigInteger('ai_conversation_id')->index(),
                'role' => fn (Blueprint $t) => $t->string('role', 12)->default('user'),
                'content' => fn (Blueprint $t) => $t->longText('content')->nullable(),
                'structured_filters' => fn (Blueprint $t) => $t->json('structured_filters')->nullable(),
                'property_ids' => fn (Blueprint $t) => $t->json('property_ids')->nullable(),
                'status' => fn (Blueprint $t) => $t->string('status', 20)->default('ok'),
                'response_type' => fn (Blueprint $t) => $t->string('response_type', 30)->default('text')->index(),
                'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
            ],
            'ai_request_logs' => [
                'ai_conversation_id' => fn (Blueprint $t) => $t->unsignedBigInteger('ai_conversation_id')->nullable()->index(),
                'user_id' => fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->index(),
                'request_id' => fn (Blueprint $t) => $t->string('request_id', 64)->nullable(),
                'intent' => fn (Blueprint $t) => $t->string('intent', 40)->nullable(),
                'structured_filters' => fn (Blueprint $t) => $t->json('structured_filters')->nullable(),
                'tool_calls' => fn (Blueprint $t) => $t->json('tool_calls')->nullable(),
                'results_count' => fn (Blueprint $t) => $t->unsignedInteger('results_count')->default(0),
                'status' => fn (Blueprint $t) => $t->string('status', 20)->default('ok'),
                'error_code' => fn (Blueprint $t) => $t->string('error_code', 60)->nullable(),
                'latency_ms' => fn (Blueprint $t) => $t->unsignedInteger('latency_ms')->default(0),
                'search_ms' => fn (Blueprint $t) => $t->unsignedInteger('search_ms')->default(0),
                'tokens_used' => fn (Blueprint $t) => $t->unsignedInteger('tokens_used')->default(0),
                'response_type' => fn (Blueprint $t) => $t->string('response_type', 30)->nullable()->index(),
                'fallback_reason' => fn (Blueprint $t) => $t->string('fallback_reason', 120)->nullable(),
                'knowledge_version' => fn (Blueprint $t) => $t->string('knowledge_version', 40)->nullable(),
            ],
            'ai_user_memories' => [
                'memory_value' => fn (Blueprint $t) => $t->string('memory_value', 500),
                'confidence' => fn (Blueprint $t) => $t->decimal('confidence', 4, 3)->default(1.000),
                'source' => fn (Blueprint $t) => $t->string('source', 30)->default('conversation'),
                'last_used_at' => fn (Blueprint $t) => $t->timestamp('last_used_at')->nullable()->index(),
                'expires_at' => fn (Blueprint $t) => $t->timestamp('expires_at')->nullable()->index(),
            ],
            'ai_search_index' => [
                'description' => fn (Blueprint $t) => $t->text('description')->nullable(),
                'transaction_type' => fn (Blueprint $t) => $t->string('transaction_type', 10)->nullable(),
                'status' => fn (Blueprint $t) => $t->string('status', 20)->default('published'),
                'type_slug' => fn (Blueprint $t) => $t->string('type_slug', 60)->nullable(),
                'type_name_ar' => fn (Blueprint $t) => $t->string('type_name_ar', 60)->nullable(),
                'city' => fn (Blueprint $t) => $t->string('city', 100)->nullable(),
                'district' => fn (Blueprint $t) => $t->string('district', 100)->nullable(),
                'neighborhood' => fn (Blueprint $t) => $t->string('neighborhood', 100)->nullable(),
                'price' => fn (Blueprint $t) => $t->decimal('price', 15, 2)->nullable(),
                'currency' => fn (Blueprint $t) => $t->string('currency', 3)->nullable(),
                'area' => fn (Blueprint $t) => $t->decimal('area', 10, 2)->nullable(),
                'bedrooms' => fn (Blueprint $t) => $t->unsignedSmallInteger('bedrooms')->nullable(),
                'bathrooms' => fn (Blueprint $t) => $t->unsignedSmallInteger('bathrooms')->nullable(),
                'is_furnished' => fn (Blueprint $t) => $t->boolean('is_furnished')->default(false),
                'is_new' => fn (Blueprint $t) => $t->boolean('is_new')->default(false),
                'is_featured' => fn (Blueprint $t) => $t->boolean('is_featured')->default(false),
                'published_at' => fn (Blueprint $t) => $t->timestamp('published_at')->nullable(),
                'latitude' => fn (Blueprint $t) => $t->decimal('latitude', 10, 7)->nullable(),
                'longitude' => fn (Blueprint $t) => $t->decimal('longitude', 10, 7)->nullable(),
                'search_text' => fn (Blueprint $t) => $t->text('search_text')->nullable(),
                'content_hash' => fn (Blueprint $t) => $t->string('content_hash', 64)->nullable(),
            ],
        ];

        foreach ($definitions as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $definition) {
                if (Schema::hasColumn($table, $column)) {
                    continue;
                }

                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($definition) {
                        $definition($blueprint);
                    });
                    $added[] = $table.'.'.$column;
                } catch (\Throwable $e) {
                    // فشل إضافة عمود واحد لا يوقف بقية الإصلاحات.
                    Log::warning('ai.schema_column_failed', [
                        'table' => $table,
                        'column' => $column,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $added;
    }

    /** إنشاء فهرس FULLTEXT على عمود البحث (MySQL فقط، وبلا فشل قاتل). */
    private function ensureFullTextIndex(): void
    {
        try {
            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                return;
            }

            foreach (Schema::getIndexes('ai_search_index') as $index) {
                $columns = array_map('strtolower', (array) ($index['columns'] ?? []));
                if (($index['type'] ?? null) === 'fulltext' && in_array('search_text', $columns, true)) {
                    return;
                }
            }

            Schema::table('ai_search_index', function (Blueprint $table) {
                $table->fullText('search_text');
            });
        } catch (\Throwable $e) {
            // البحث يتراجع تلقائيًا إلى LIKE — لا مشكلة إن فشل الفهرس النصي.
            Log::info('ai.fulltext_unavailable', ['error' => $e->getMessage()]);
        }
    }

    /**
     * إذا كان فهرس البحث فارغًا بينما توجد عقارات فعلية في القاعدة، نبنيه
     * فورًا من جدول العقارات نفسه — حتى لا يجيب المساعد «لا توجد نتائج» وهو
     * أمام قاعدة مليئة بالعقارات.
     */
    private function ensureIndex(): int
    {
        try {
            if (! Schema::hasTable('properties') || ! Schema::hasTable('ai_search_index')) {
                return 0;
            }

            $total = Property::query()->count();
            if ($total === 0) {
                return 0;
            }

            $indexed = AiSearchIndex::query()->count();

            // حالتان تستحقّان إعادة البناء: فهرس فارغ تمامًا، أو فهرس موجود
            // بلا أي إحداثيات رغم أن للعقارات إحداثيات حقيقية (وهذا يحدث
            // عندما أُسقطت الأعمدة بصمت في نسخة سابقة فبقي البحث القريب معطلًا).
            $lostCoordinates = $indexed > 0
                && AiSearchIndex::query()->whereNotNull('latitude')->count() === 0
                && $this->propertiesWithCoordinates() > 0;

            if ($indexed > 0 && ! $lostCoordinates) {
                return 0;
            }

            if ($total > self::AUTO_INDEX_MAX_PROPERTIES) {
                Log::warning('ai.auto_index_skipped', [
                    'properties' => $total,
                    'hint' => 'شغّل: php artisan ai:reindex',
                ]);

                return 0;
            }

            return $this->indexer->reindexAll();
        } catch (\Throwable $e) {
            Log::warning('ai.auto_index_failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /** عدد العقارات التي لها إحداثيات حقيقية في جدول المواقع. */
    private function propertiesWithCoordinates(): int
    {
        try {
            if (! Schema::hasTable('property_locations') || ! Schema::hasColumn('property_locations', 'latitude')) {
                return 0;
            }

            return Property::query()
                ->whereHas('location', fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude'))
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
