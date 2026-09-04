<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enforces conversation uniqueness on (client_id, agent_id) instead of
 * (property_id, client_id, agent_id) and makes property_id nullable.
 *
 * Works on MySQL, MariaDB, PostgreSQL and SQLite (driver-aware index handling).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Merge duplicate conversations (same client+agent, different property).
        $duplicates = DB::table('conversations')
            ->select('client_id', 'agent_id', DB::raw('MIN(id) as keep_id'), DB::raw('GROUP_CONCAT(id) as all_ids'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('client_id', 'agent_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $keepId = (int) $dup->keep_id;
            $allIds = explode(',', (string) $dup->all_ids);
            $mergeIds = array_filter(
                $allIds,
                fn ($id) => (int) $id !== $keepId,
            );

            foreach ($mergeIds as $mergeId) {
                DB::table('messages')
                    ->where('conversation_id', (int) $mergeId)
                    ->update(['conversation_id' => $keepId]);

                $latestMsg = DB::table('messages')
                    ->where('conversation_id', $keepId)
                    ->max('created_at');

                if ($latestMsg !== null) {
                    DB::table('conversations')
                        ->where('id', $keepId)
                        ->update(['last_message_at' => $latestMsg]);
                }

                DB::table('conversations')->where('id', (int) $mergeId)->delete();
            }
        }

        // Step 2: Drop legacy unique index on (property_id, client_id, agent_id).
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $this->dropMySqlForeignKeysAndIndexes('conversations', 'property_id');
        } else {
            // sqlite / pgsql / sqlsrv: unique constraints created by the schema
            // builder are named indexes and can be dropped directly.
            $legacyIndex = $this->findIndexName('conversations', ['property_id', 'client_id', 'agent_id']);
            if ($legacyIndex !== null) {
                Schema::table('conversations', fn (Blueprint $table) => $table->dropUnique($legacyIndex));
            }
        }

        // Step 3: Make property_id nullable (keep the FK, cascade behaviour unchanged).
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable()->change();
        });

        // Step 4: Add the new unique constraint on (client_id, agent_id) only.
        if ($this->findIndexName('conversations', ['client_id', 'agent_id']) === null) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unique(['client_id', 'agent_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'agent_id']);
            $table->unsignedBigInteger('property_id')->nullable(false)->change();
            $table->unique(['property_id', 'client_id', 'agent_id']);
        });
    }

    /**
     * MySQL requires dropping the FK before dropping an index that covers its columns.
     */
    private function dropMySqlForeignKeysAndIndexes(string $table, string $column): void
    {
        $foreignKeys = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $column],
        );

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        $indexes = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Column_name IN ('property_id', 'client_id', 'agent_id') AND Non_unique = 0",
        );
        $indexNames = array_unique(array_map(fn ($idx) => $idx->Key_name, $indexes));
        foreach ($indexNames as $indexName) {
            if ($indexName === 'PRIMARY') {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }

    /**
     * Resolves the actual index/constraint name for the given columns across drivers.
     */
    private function findIndexName(string $table, array $columns): ?string
    {
        $sortColumns = collect($columns)->sort()->values()->all();
        $schema = match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::select(
                "SELECT INDEX_NAME as name, GROUP_CONCAT(COLUMN_NAME ORDER BY COLUMN_NAME) as cols
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND NON_UNIQUE = 0
                 GROUP BY INDEX_NAME",
                [$table],
            ),
            'pgsql' => DB::select(
                "SELECT i.relname as name, string_agg(a.attname, ',' ORDER BY a.attname) as cols
                 FROM pg_class t
                 JOIN pg_index ix ON ix.indrelid = t.oid
                 JOIN pg_class i ON i.indexrelid = ix.indexrelid
                 JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
                 WHERE t.relname = ? AND ix.indisunique
                 GROUP BY i.relname",
                [$table],
            ),
            'sqlite' => DB::select(
                "SELECT name, GROUP_CONCAT(origin) as origin_info FROM pragma_index_list(?) WHERE origin IN ('u','c') GROUP BY name",
                [$table],
            ),
            default => [],
        };

        foreach ($schema as $index) {
            $cols = match (DB::getDriverName()) {
                'sqlite' => null,
                default => isset($index->cols)
                    ? collect(explode(',', (string) $index->cols))
                        ->map(fn ($c) => trim($c))
                        ->sort()
                        ->values()
                        ->all()
                    : null,
            };

            if ($cols === null && DB::getDriverName() === 'sqlite') {
                $cols = collect(DB::select("SELECT name FROM pragma_index_info(?)", [$index->name]))
                    ->map(fn ($row) => $row->name)
                    ->sort()
                    ->values()
                    ->all();
            }

            if ($cols !== null && $cols === $sortColumns) {
                return (string) $index->name;
            }
        }

        return null;
    }
};
