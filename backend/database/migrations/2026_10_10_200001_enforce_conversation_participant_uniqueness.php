<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The conversation is unique per client/agent pair, not per property.
     *
     * Older releases could create more than one thread for a pair because the
     * database index included property_id. Merge such rows before adding the
     * correct unique index. Messages and database notifications retain their
     * references to the canonical conversation.
     */
    public function up(): void
    {
        $conversationRemap = [];

        DB::transaction(function () use (&$conversationRemap): void {
            $duplicatePairs = DB::table('conversations')
                ->select(['client_id', 'agent_id'])
                ->groupBy(['client_id', 'agent_id'])
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicatePairs as $pair) {
                $rows = DB::table('conversations')
                    ->where('client_id', $pair->client_id)
                    ->where('agent_id', $pair->agent_id)
                    ->orderByRaw('CASE WHEN last_message_at IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderByDesc('last_message_at')
                    ->orderByDesc('id')
                    ->get();

                $canonical = $rows->first();

                if ($canonical === null) {
                    continue;
                }

                $duplicateIds = $rows
                    ->skip(1)
                    ->pluck('id')
                    ->all();

                if ($duplicateIds === []) {
                    continue;
                }

                foreach ($duplicateIds as $duplicateId) {
                    $conversationRemap[(string) $duplicateId] = (int) $canonical->id;
                }

                DB::table('messages')
                    ->whereIn('conversation_id', $duplicateIds)
                    ->update(['conversation_id' => $canonical->id]);

                DB::table('conversations')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            }

            // Keep notification deep links valid after consolidating old threads.
            if ($conversationRemap !== [] && Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->select(['id', 'data'])
                    ->orderBy('id')
                    ->chunkById(500, function ($notifications) use ($conversationRemap): void {
                        foreach ($notifications as $notification) {
                            $data = json_decode((string) $notification->data, true);

                            if (! is_array($data) || ! isset($data['conversation_id'])) {
                                continue;
                            }

                            $oldId = (string) $data['conversation_id'];

                            if (! isset($conversationRemap[$oldId])) {
                                continue;
                            }

                            $data['conversation_id'] = $conversationRemap[$oldId];

                            DB::table('notifications')
                                ->where('id', $notification->id)
                                ->update([
                                    'data' => json_encode(
                                        $data,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                                    ),
                                ]);
                        }
                    }, 'id');
            }
        });

        // Some installations may already have a differently named/removed
        // legacy index. Only drop it when it actually exists.
        if (Schema::hasIndex('conversations', 'conversations_property_id_client_id_agent_id_unique')) {
            Schema::table('conversations', function (Blueprint $table): void {
                $table->dropUnique('conversations_property_id_client_id_agent_id_unique');
            });
        }

        // Also tolerate a retry after a partial DDL operation.
        if (! Schema::hasIndex('conversations', ['client_id', 'agent_id'], 'unique')) {
            Schema::table('conversations', function (Blueprint $table): void {
                $table->unique(['client_id', 'agent_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('conversations', 'conversations_client_id_agent_id_unique')) {
            Schema::table('conversations', function (Blueprint $table): void {
                $table->dropUnique('conversations_client_id_agent_id_unique');
            });
        }

        if (! Schema::hasIndex('conversations', ['property_id', 'client_id', 'agent_id'], 'unique')) {
            Schema::table('conversations', function (Blueprint $table): void {
                $table->unique(['property_id', 'client_id', 'agent_id']);
            });
        }
    }
};
