<?php

namespace Tests\Feature\Ai;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiMarketInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RealDataSeeder::class);
    }

    public function test_market_analysis_uses_live_published_listings_and_returns_no_property_cards(): void
    {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'تحليل السوق العقاري ومتوسط الأسعار',
        ])->assertOk();

        $data = $response->json('data');
        $this->assertSame('market_insights', $data['intent']);
        $this->assertSame('market_insights', $data['response_type']);
        $this->assertSame([], $data['properties']);
        $this->assertTrue($data['market_insights']['success']);
        $this->assertGreaterThan(0, $data['market_insights']['total_listings']);
        $this->assertSame('get_market_insights', $data['tool_calls'][0]['tool']);

        foreach ($data['market_insights']['groups'] as $group) {
            $this->assertGreaterThan(0, $group['listings_count']);
            $this->assertArrayHasKey('currency', $group);
            $this->assertArrayHasKey('transaction_type', $group);
        }
    }
}
