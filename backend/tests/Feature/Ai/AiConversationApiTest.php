<?php

namespace Tests\Feature\Ai;

use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_ai_conversations_with_consistent_contract(): void
    {
        $user=User::factory()->create();

        $created=$this->actingAs($user,'sanctum')
            ->postJson('/api/v1/ai/conversations',['locale'=>'ar'])
            ->assertCreated();

        $id=$created->json('data.id');
        $this->assertIsInt($id);

        $this->actingAs($user,'sanctum')
            ->getJson('/api/v1/ai/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.id',$id)
            ->assertJsonPath('data.0.title','محادثة جديدة')
            ->assertJsonPath('data.0.is_pinned',false)
            ->assertJsonPath('data.0.messages_count',0);
    }

    public function test_user_can_pin_and_load_ai_conversation_messages_from_mobile_route(): void
    {
        $user=User::factory()->create();
        $conversation=AiConversation::query()->create([
            'user_id'=>$user->id,
            'locale'=>'ar',
            'status'=>'active',
            'title'=>'بحث عقاري في صنعاء',
        ]);

        $conversation->messages()->create([
            'role'=>'user',
            'content'=>'شقة في صنعاء',
            'status'=>'ok',
        ]);

        $this->actingAs($user,'sanctum')
            ->patchJson('/api/v1/ai/conversations/'.$conversation->id.'/pin',['pinned'=>true])
            ->assertOk()
            ->assertJsonPath('data.is_pinned',true);

        $this->actingAs($user,'sanctum')
            ->getJson('/api/v1/ai/conversations/'.$conversation->id.'/messages')
            ->assertOk()
            ->assertJsonPath('data.id',$conversation->id)
            ->assertJsonCount(1,'data.messages');
    }
}
