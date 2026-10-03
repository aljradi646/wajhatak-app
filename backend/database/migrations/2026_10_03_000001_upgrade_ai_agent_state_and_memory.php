<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('ai_conversations')) Schema::table('ai_conversations', function(Blueprint $table): void {
            if(!Schema::hasColumn('ai_conversations','title')) $table->string('title',190)->default('محادثة جديدة')->after('locale');
            if(!Schema::hasColumn('ai_conversations','context_state')) $table->json('context_state')->nullable()->after('last_message_at');
            if(!Schema::hasColumn('ai_conversations','is_pinned')) $table->boolean('is_pinned')->default(false)->after('status')->index();
        });
        if(!Schema::hasTable('ai_user_memories')) Schema::create('ai_user_memories',function(Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('memory_key',80);
            $table->string('memory_value',500); $table->decimal('confidence',4,3)->default(1.000); $table->string('source',30)->default('conversation');
            $table->timestamp('last_used_at')->nullable()->index(); $table->timestamps(); $table->unique(['user_id','memory_key']); $table->index(['user_id','updated_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('ai_user_memories');
        if(Schema::hasTable('ai_conversations')) Schema::table('ai_conversations',function(Blueprint $table): void {
            $drop=[]; foreach(['title','context_state','is_pinned'] as $c) if(Schema::hasColumn('ai_conversations',$c)) $drop[]=$c;
            if($drop!==[]) $table->dropColumn($drop);
        });
    }
};
