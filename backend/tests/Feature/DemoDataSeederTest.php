<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Favorite;
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
        $this->assertDatabaseHas('users', ['email' => 'agent.abdullah@lux.local']);
        $this->assertDatabaseHas('users', ['email' => 'agent.sara@lux.local']);
        $this->assertDatabaseHas('users', ['email' => 'client.reem@lux.local']);
        $this->assertSame(3, Agent::query()->count());
        $this->assertSame(7, Property::query()->count());
        $this->assertSame(8, Property::withTrashed()->count());
        $this->assertSame(5, ViewingRequest::query()->count());
        $this->assertSame(3, Conversation::query()->count());
        $this->assertSame(3, Favorite::query()->count());
        $this->assertGreaterThanOrEqual(15, ActivityLog::query()->count());
        Storage::disk('public')->assertExists('properties/demo/'.Property::query()->firstOrFail()->id.'-cover.png');
        Storage::disk('public')->assertExists('branding/logo.png');
        Storage::disk('public')->assertExists('branding/logo-small.png');
    }
}
