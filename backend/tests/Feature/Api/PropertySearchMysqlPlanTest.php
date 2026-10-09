<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertySearchMysqlPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_mysql_uses_composite_city_district_index_for_combined_location_search(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The query-plan assertion requires the MySQL 8 CI service.');
        }

        $now = now()->toDateTimeString();
        $batch = [];

        // Enough deterministic rows for the optimizer to compare the composite index
        // with the single-column indexes present on city and district.
        for ($i = 0; $i < 5000; $i++) {
            $cityNumber = $i % 100;
            $districtNumber = intdiv($i, 100) % 100;
            $batch[] = [
                'city' => 'perf-city-'.str_pad((string) $cityNumber, 3, '0', STR_PAD_LEFT),
                'district' => 'perf-district-'.str_pad((string) $districtNumber, 3, '0', STR_PAD_LEFT),
                'neighborhood' => 'perf-neighborhood-'.str_pad((string) ($i % 25), 3, '0', STR_PAD_LEFT),
                'address' => 'Performance fixture '.$i,
                'latitude' => null,
                'longitude' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) === 500) {
                DB::table('property_locations')->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('property_locations')->insert($batch);
        }

        DB::select('ANALYZE TABLE property_locations');

        $city = 'perf-city-037';
        $district = 'perf-district-042';
        $this->assertSame(
            1,
            DB::table('property_locations')
                ->where('city', $city)
                ->where('district', $district)
                ->count(),
            'Fixture data must give the combined predicates a selective match.',
        );

        $plan = DB::select(
            'EXPLAIN SELECT id FROM property_locations WHERE city = ? AND district = ?',
            [$city, $district],
        );

        $this->assertNotEmpty($plan);
        $this->assertSame(
            'property_locations_city_district_search_idx',
            $plan[0]->key ?? null,
            'MySQL should select the composite index when filtering city and district together.',
        );
    }
}
