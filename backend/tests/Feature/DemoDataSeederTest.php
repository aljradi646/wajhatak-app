<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Property;
use App\Models\ViewingRequest;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_seeder_creates_a_complete_api_dataset(): void
    {
        Storage::fake('public');
        $this->app->detectEnvironment(static fn () => 'testing');
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'agent.demo@lux.local']);
        $this->assertDatabaseHas('users', ['email' => 'client.demo@lux.local']);
        $this->assertSame(1, Agent::query()->count());
        $this->assertSame(4, Property::query()->count());
        $this->assertSame(1, ViewingRequest::query()->count());
        $this->assertSame(1, Conversation::query()->count());
        Storage::disk('public')->assertExists('properties/demo/'.Property::query()->firstOrFail()->id.'-cover.png');
    }
}
