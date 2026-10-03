<?php

namespace Tests\Feature\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiConversationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_turn_filters_are_used_in_the_next_follow_up(): void
    {
        $user=User::factory()->create();

        $first=$this->actingAs($user,'sanctum')->postJson('/api/v1/ai/chat',[
            'message'=>'أريد شقة في صنعاء',
        ])->assertOk();

        $conversationId=$first->json('data.conversation_id');

        $this->actingAs($user,'sanctum')->postJson('/api/v1/ai/chat',[
            'message'=>'تكون غرفتين',
            'conversation_id'=>$conversationId,
        ])->assertOk();

        $third=$this->actingAs($user,'sanctum')->postJson('/api/v1/ai/chat',[
            'message'=>'وأقل من 100 ألف',
            'conversation_id'=>$conversationId,
        ])->assertOk();

        $filters=$third->json('data.filters');
        $this->assertSame('صنعاء',$filters['city']??null);
        $this->assertSame('apartment',$filters['property_type']??null);
        $this->assertSame(2,$filters['bedrooms_min']??null);
        $this->assertNotNull($filters['max_price']??null);

        $latestUser=AiMessage::query()
            ->where('ai_conversation_id',$conversationId)
            ->where('role','user')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('صنعاء',$latestUser->structured_filters['city']??null);
        $this->assertSame(2,$latestUser->structured_filters['bedrooms_min']??null);
    }

    public function test_pending_action_can_be_cancelled_without_execution(): void
    {
        $user=User::factory()->create();
        $conversation=AiConversation::query()->create([
            'user_id'=>$user->id,
            'locale'=>'ar',
            'status'=>'active',
        ]);

        $conversation->context_state=[
            'pending_action'=>[
                'tool'=>'create_viewing_request',
                'arguments'=>[
                    'property_id'=>1,
                    'scheduled_date'=>now()->addDay()->toDateString(),
                ],
                'created_at'=>now()->toIso8601String(),
            ],
        ];
        $conversation->save();

        $response=$this->actingAs($user,'sanctum')->postJson('/api/v1/ai/chat',[
            'message'=>'لا',
            'conversation_id'=>$conversation->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent','action_cancelled')
            ->assertJsonPath('data.status','ok');

        $fresh=$conversation->fresh();
        $this->assertNull($fresh->context_state['pending_action']??null);
    }

    public function test_stale_pending_action_is_expired(): void
    {
        $user=User::factory()->create();
        $conversation=AiConversation::query()->create([
            'user_id'=>$user->id,
            'locale'=>'ar',
            'status'=>'active',
        ]);

        $conversation->context_state=[
            'pending_action'=>[
                'tool'=>'create_viewing_request',
                'arguments'=>['property_id'=>1],
                'created_at'=>now()->subMinutes(11)->toIso8601String(),
            ],
        ];
        $conversation->save();

        $this->actingAs($user,'sanctum')->postJson('/api/v1/ai/chat',[
            'message'=>'نعم',
            'conversation_id'=>$conversation->id,
        ])->assertOk();

        $this->assertNull($conversation->fresh()->context_state['pending_action']??null);
    }
}
