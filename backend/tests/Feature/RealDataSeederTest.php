<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RealDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RealDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_seeder_creates_sanaa_properties_for_the_two_agents(): void
    {
        Storage::fake('public');
        $this->app->detectEnvironment(static fn () => 'testing');
        $this->seed(RealDataSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'agent.demo@lux.local']);
        $this->assertDatabaseHas('users', ['email' => 'client.demo@lux.local']);
        $this->assertSame(2, Agent::query()->count());

        $properties = Property::query()->get();
        $this->assertCount(16, $properties);

        $agentIds = Agent::query()->pluck('id')->all();
        foreach ($properties as $property) {
            $this->assertStringStartsWith('SN-2026-', $property->reference_code);
            $this->assertContains($property->agent_id, $agentIds);
            $this->assertSame(3, $property->images()->count());
            $this->assertSame(1, $property->images()->where('is_cover', true)->count());
            $this->assertStringStartsWith('properties/real/', $property->images()->first()->path);
            $this->assertSame('صنعاء', $property->location->city);
        }

        $this->assertSame('1', Setting::get('system_initialized', '0'));
    }

    public function test_real_seeder_is_idempotent_and_preserves_existing_data(): void
    {
        Storage::fake('public');
        $this->app->detectEnvironment(static fn () => 'testing');
        $this->seed(RealDataSeeder::class);

        $kept = Property::query()->create([
            'agent_id' => Agent::query()->first()->id,
            'property_type_id' => \App\Models\PropertyType::query()->first()->id,
            'property_location_id' => \App\Models\PropertyLocation::query()->first()->id,
            'title' => 'عقار محفوظ - اختبار',
            'slug' => 'kept-test',
            'reference_code' => 'KEEP-0001',
            'description' => 'يجب ألا يُحذف هذا العقار أبداً.',
            'transaction_type' => 'sale',
            'status' => 'published',
            'price' => 1,
            'currency' => 'YER',
            'published_at' => now(),
        ]);

        $this->seed(RealDataSeeder::class);

        $this->assertDatabaseHas('properties', ['reference_code' => 'KEEP-0001']);
        $this->assertSame(16, Property::query()->where('reference_code', 'like', 'SN-2026-%')->count());
    }

    public function test_real_seeder_removes_only_demo_trial_properties(): void
    {
        Storage::fake('public');
        $this->app->detectEnvironment(static fn () => 'testing');

        $this->seed(DemoDataSeeder::class);
        $this->assertGreaterThan(0, Property::query()->where('reference_code', 'like', 'LUX-DEMO-%')->count());
        $this->assertDatabaseHas('users', ['email' => 'client.reem@lux.local']);

        $this->seed(RealDataSeeder::class);

        $this->assertSame(0, Property::withTrashed()->where('reference_code', 'like', 'LUX-DEMO-%')->count());
        $this->assertSame(0, PropertyImage::query()->where('path', 'like', 'properties/demo/%')->count());
        $this->assertGreaterThanOrEqual(16, Property::query()->where('reference_code', 'like', 'SN-2026-%')->count());
        $this->assertSame('1', Setting::get('system_initialized', '0'));

        // Non-property demo data (users) is preserved.
        $this->assertDatabaseHas('users', ['email' => 'client.reem@lux.local']);
        $this->assertDatabaseHas('users', ['email' => 'agent.abdullah@lux.local']);
    }
}