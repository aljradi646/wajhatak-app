<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyImage;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\Region;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Idempotent showcase catalog. These 100 records and their images are examples
 * for a fresh installation and are explicitly marked as demo, never verified
 * listings. Existing user-created properties are not overwritten or deleted.
 *
 * Set WAJHATAK_SHOWCASE_AGENT_PASSWORD before seeding only when a shared
 * password is intentionally needed for a controlled demo environment. Without
 * it, demo identities receive long random passwords which are never logged.
 */
class ShowcaseCatalogSeeder extends Seeder
{
    private const SEED_MARKER = 'showcase_catalog_seed_v1';

    private const TYPES = [
        'villa' => ['name_ar' => 'فيلا', 'name_en' => 'Villa', 'base_sale' => 220000000, 'base_rent' => 420000, 'area' => 420, 'bedrooms' => 5, 'features' => ['parking', 'garden', 'security']],
        'apartment' => ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'base_sale' => 68000000, 'base_rent' => 170000, 'area' => 165, 'bedrooms' => 3, 'features' => ['elevator', 'parking', 'security']],
        'floor' => ['name_ar' => 'دور سكني', 'name_en' => 'Residential Floor', 'base_sale' => 98000000, 'base_rent' => 240000, 'area' => 240, 'bedrooms' => 4, 'features' => ['parking', 'water_tank', 'security']],
        'townhouse' => ['name_ar' => 'تاون هاوس', 'name_en' => 'Townhouse', 'base_sale' => 145000000, 'base_rent' => 300000, 'area' => 310, 'bedrooms' => 4, 'features' => ['parking', 'garden', 'water_tank']],
        'land' => ['name_ar' => 'أرض', 'name_en' => 'Land', 'base_sale' => 42000000, 'base_rent' => 85000, 'area' => 600, 'bedrooms' => null, 'features' => ['road_access', 'electricity']],
        'shop' => ['name_ar' => 'محل تجاري', 'name_en' => 'Retail Shop', 'base_sale' => 52000000, 'base_rent' => 220000, 'area' => 95, 'bedrooms' => null, 'features' => ['street_frontage', 'security', 'electricity']],
        'office' => ['name_ar' => 'مكتب إداري', 'name_en' => 'Office', 'base_sale' => 85000000, 'base_rent' => 280000, 'area' => 150, 'bedrooms' => null, 'features' => ['elevator', 'security', 'electricity']],
        'building' => ['name_ar' => 'مبنى استثماري', 'name_en' => 'Investment Building', 'base_sale' => 390000000, 'base_rent' => 850000, 'area' => 780, 'bedrooms' => null, 'features' => ['parking', 'security', 'water_tank']],
        'farm' => ['name_ar' => 'مزرعة', 'name_en' => 'Farm', 'base_sale' => 125000000, 'base_rent' => 350000, 'area' => 4200, 'bedrooms' => 2, 'features' => ['agricultural', 'water_tank', 'road_access']],
        'warehouse' => ['name_ar' => 'مستودع', 'name_en' => 'Warehouse', 'base_sale' => 115000000, 'base_rent' => 390000, 'area' => 650, 'bedrooms' => null, 'features' => ['storage', 'road_access', 'electricity']],
    ];

    /** Photographic Unsplash images organized by property category. */
    private const PHOTO_POOLS = [
        'villa' => [
            'covers' => ['1580587771525-78b9dba3b914', '1600596542815-ffad4c1539a9', '1600585154340-be6161a56a0c', '1600047509807-ba8f99d2cdde', '1613490493576-7fde63acd811'],
            'gallery' => ['1600607687939-ce8a6c25118c', '1600210492486-724fe5c67fb0', '1605348532760-6753d2c43329', '1600566753190-17f0baa2a6c3', '1523217582562-09d0def993a6'],
        ],
        'apartment' => [
            'covers' => ['1545324418-cc1a3fa10c00', '1568605114967-8130f3a36994', '1696029475205-b42ab14690ed', '1512917774080-9991f1c4c750'],
            'gallery' => ['1493809842364-78817add7ffb', '1522708323590-d24dbb6b0267', '1502672260266-1c1ef2d93688', '1484154218962-a197022b5858'],
        ],
        'floor' => [
            'covers' => ['1568605114967-8130f3a36994', '1545324418-cc1a3fa10c00', '1512917774080-9991f1c4c750'],
            'gallery' => ['1522708323590-d24dbb6b0267', '1600210492486-724fe5c67fb0', '1493809842364-78817add7ffb'],
        ],
        'townhouse' => [
            'covers' => ['1523217582562-09d0def993a6', '1600585154340-be6161a56a0c', '1600596542815-ffad4c1539a9'],
            'gallery' => ['1600607687939-ce8a6c25118c', '1605348532760-6753d2c43329', '1512917774080-9991f1c4c750'],
        ],
        'land' => [
            'covers' => ['1621928372414-30e144d51d49', '1500382017468-9049fed747ef', '1643145476612-8130f0d49e93'],
            'gallery' => ['1464226184884-fa280b87c399', '1470770841072-f978cf4d019e', '1500382017468-9049fed747ef'],
        ],
        'shop' => [
            'covers' => ['1441986300917-64674bd600d8', '1604719312566-8912e9c8a213', '1555529669-e69e7aa0ba9a'],
            'gallery' => ['1497366754035-f200968a6e72', '1497366811353-6870744d04b2', '1486406146926-c627a92ad1ab'],
        ],
        'office' => [
            'covers' => ['1497366754035-f200968a6e72', '1497366811353-6870744d04b2', '1497366216548-37526070297c'],
            'gallery' => ['1497366811353-6870744d04b2', '1497366216548-37526070297c', '1486406146926-c627a92ad1ab'],
        ],
        'building' => [
            'covers' => ['1486406146926-c627a92ad1ab', '1545324418-cc1a3fa10c00', '1600047509807-ba8f99d2cdde'],
            'gallery' => ['1497366754035-f200968a6e72', '1512917774080-9991f1c4c750', '1497366811353-6870744d04b2'],
        ],
        'farm' => [
            'covers' => ['1500382017468-9049fed747ef', '1621928372414-30e144d51d49', '1643145476612-8130f0d49e93'],
            'gallery' => ['1464226184884-fa280b87c399', '1470770841072-f978cf4d019e', '1600596542815-ffad4c1539a9'],
        ],
        'warehouse' => [
            'covers' => ['1586528116311-ad8dd3c8310d', '1486406146926-c627a92ad1ab', '1497366754035-f200968a6e72'],
            'gallery' => ['1497366216548-37526070297c', '1497366811353-6870744d04b2', '1600047509807-ba8f99d2cdde'],
        ],
    ];

    private const DISTRICTS = [
        ['name' => 'معين', 'lat' => 15.3624, 'lng' => 44.1975],
        ['name' => 'شعوب', 'lat' => 15.3699, 'lng' => 44.2090],
        ['name' => 'الثورة', 'lat' => 15.3740, 'lng' => 44.1960],
        ['name' => 'آزال', 'lat' => 15.3470, 'lng' => 44.2140],
        ['name' => 'السفارة', 'lat' => 15.3390, 'lng' => 44.2080],
        ['name' => 'الصافية', 'lat' => 15.3420, 'lng' => 44.1965],
        ['name' => 'الوحدة', 'lat' => 15.3485, 'lng' => 44.2040],
        ['name' => 'التحرير', 'lat' => 15.3560, 'lng' => 44.2070],
        ['name' => 'سبأ', 'lat' => 15.3790, 'lng' => 44.2050],
        ['name' => 'بني الحارث', 'lat' => 15.4100, 'lng' => 44.2200],
    ];

    public function run(): void
    {
        if ((string) Setting::get(self::SEED_MARKER, '0') === '1') {
            $this->command?->info('ShowcaseCatalogSeeder: already provisioned; nothing changed.');
            return;
        }

        // Use the same authoritative location hierarchy as the mobile form.
        $this->call(LocationSeeder::class);
        $this->ensureTaxonomy();
        $this->markLegacySeededRowsAsDemo();

        $password = trim((string) env('WAJHATAK_SHOWCASE_AGENT_PASSWORD', ''));
        $agents = [];
        for ($n = 1; $n <= 5; $n++) {
            $agents[] = $this->ensureShowcaseAgent($n, $password);
        }
        $this->rotateLegacyDemoPasswords($password);

        $country = Country::query()->where('code', 'YE')->firstOrFail();
        $region = Region::query()->where('country_id', $country->id)->where('name_ar', 'أمانة العاصمة')->firstOrFail();
        $city = City::query()->where('region_id', $region->id)->where('name_ar', 'صنعاء')->firstOrFail();

        $typeIds = PropertyType::query()->whereIn('slug', array_keys(self::TYPES))->pluck('id', 'slug');
        $featureIds = PropertyFeature::query()->pluck('id', 'slug');
        $codesCreated = 0;
        $slugs = array_keys(self::TYPES);

        for ($i = 1; $i <= 100; $i++) {
            $code = 'WJ-SHOWCASE-2026-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $existing = Property::withTrashed()->where('reference_code', $code)->first();
            if ($existing && ! $existing->is_demo) {
                throw new RuntimeException("Refusing to overwrite non-demo property {$code}.");
            }

            $typeSlug = $slugs[($i - 1) % count($slugs)];
            $type = self::TYPES[$typeSlug];
            $district = self::DISTRICTS[($i * 7 - 1) % count(self::DISTRICTS)];
            $area = Area::query()->where('city_id', $city->id)->where('name_ar', $district['name'])->first();
            $number = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $transaction = $this->transactionFor($typeSlug, $i);
            $transactionLabel = $transaction === 'rent' ? 'للإيجار' : 'للبيع';
            $squareMeters = max(30, (int) round($type['area'] * (0.65 + (($i * 17) % 71) / 100)));
            $price = $this->priceFor($type, $transaction, $i);
            $bedrooms = $type['bedrooms'] === null ? null : max(1, min(7, $type['bedrooms'] + (($i % 3) - 1)));
            $bathrooms = $bedrooms === null ? null : max(1, min(6, (int) ceil($bedrooms * 0.65)));
            $furnished = in_array($typeSlug, ['apartment', 'villa', 'townhouse', 'floor'], true) && $i % 5 === 0;
            $new = in_array($typeSlug, ['apartment', 'villa', 'office', 'shop'], true) && $i % 4 === 0;
            $title = $type['name_ar'].' تجريبي رقم '.$number.' '.$transactionLabel.' - حي '.$district['name'];
            $address = 'عنوان عرض تجريبي '.$number.'، حي '.$district['name'].'، صنعاء';
            $description = $this->descriptionFor($type['name_ar'], $district['name'], $transactionLabel, $squareMeters, $bedrooms, $bathrooms, $furnished);

            $location = PropertyLocation::query()->updateOrCreate(
                ['address' => $address],
                [
                    'country_id' => $country->id,
                    'region_id' => $region->id,
                    'city_id' => $city->id,
                    'area_id' => $area?->id,
                    'city' => 'صنعاء',
                    'district' => $district['name'],
                    'neighborhood' => $district['name'],
                    'latitude' => $district['lat'] + ((($i % 9) - 4) * 0.00042),
                    'longitude' => $district['lng'] + ((($i % 11) - 5) * 0.00039),
                ],
            );

            $record = Property::withTrashed()->updateOrCreate(
                ['reference_code' => $code],
                [
                    'agent_id' => $agents[($i - 1) % count($agents)]->id,
                    'property_type_id' => $typeIds[$typeSlug],
                    'property_location_id' => $location->id,
                    'title' => $title,
                    'slug' => 'wajhatak-showcase-'.$number,
                    'description' => $description,
                    'transaction_type' => $transaction,
                    'status' => 'published',
                    'price' => $price,
                    'currency' => 'YER',
                    'area' => $squareMeters,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms,
                    'parking_spaces' => $bedrooms === null ? 0 : ($i % 3),
                    'is_furnished' => $furnished,
                    'is_new' => $new,
                    'is_featured' => $i % 10 === 0,
                    'is_demo' => true,
                    'published_at' => now()->subDays($i % 60),
                    'deleted_at' => null,
                ],
            );

            $selectedFeatures = collect($type['features'])
                ->filter(fn (string $slug) => isset($featureIds[$slug]))
                ->map(fn (string $slug) => (int) $featureIds[$slug])
                ->values()
                ->all();
            $record->features()->sync($selectedFeatures);
            $this->seedImages($record, $typeSlug, $title);
            $codesCreated++;
        }

        Setting::put(self::SEED_MARKER, '1');
        $this->command?->info("ShowcaseCatalogSeeder: provisioned {$codesCreated} marked demo properties; existing user listings were preserved.");
    }

    private function ensureTaxonomy(): void
    {
        foreach (self::TYPES as $slug => $type) {
            PropertyType::query()->updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $type['name_ar'], 'name_en' => $type['name_en'], 'is_active' => true],
            );
        }

        $features = [
            'parking' => ['name_ar' => 'موقف سيارات', 'name_en' => 'Parking', 'icon' => 'parking'],
            'garden' => ['name_ar' => 'حديقة', 'name_en' => 'Garden', 'icon' => 'trees'],
            'security' => ['name_ar' => 'حراسة وأمن', 'name_en' => 'Security', 'icon' => 'shield-check'],
            'elevator' => ['name_ar' => 'مصعد', 'name_en' => 'Elevator', 'icon' => 'elevator'],
            'water_tank' => ['name_ar' => 'خزان مياه', 'name_en' => 'Water tank', 'icon' => 'droplets'],
            'electricity' => ['name_ar' => 'كهرباء', 'name_en' => 'Electricity', 'icon' => 'zap'],
            'road_access' => ['name_ar' => 'وصول إلى الطريق', 'name_en' => 'Road access', 'icon' => 'route'],
            'street_frontage' => ['name_ar' => 'واجهة على الشارع', 'name_en' => 'Street frontage', 'icon' => 'store'],
            'agricultural' => ['name_ar' => 'صالحة للزراعة', 'name_en' => 'Agricultural', 'icon' => 'sprout'],
            'storage' => ['name_ar' => 'مساحة تخزين', 'name_en' => 'Storage', 'icon' => 'warehouse'],
        ];

        foreach ($features as $slug => $feature) {
            PropertyFeature::query()->updateOrCreate(
                ['slug' => $slug],
                [...$feature, 'is_active' => true],
            );
        }
    }

    private function ensureShowcaseAgent(int $number, string $sharedPassword): Agent
    {
        $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        $email = "showcase.agent{$suffix}@example.test";
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => "وكيل تجريبي {$suffix}",
                'password' => Hash::make($sharedPassword !== '' ? $sharedPassword : Str::random(64)),
                'is_active' => true,
                'locale' => 'ar',
                'email_verified_at' => null,
                'avatar_path' => null,
            ],
        );

        if (! $user->wasRecentlyCreated && $sharedPassword !== '') {
            // Explicitly configured by the operator for a controlled showcase
            // account; never set or log a default shared password.
            $user->forceFill(['password' => Hash::make($sharedPassword)])->save();
        }

        $user->syncRoles(['agent', 'user']);

        return Agent::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'license_number' => "SHOWCASE-DEMO-{$suffix}",
                'bio' => 'ملف وكيل تجريبي لعرض وظائف وجهتك. هذه ليست هوية وسيط حقيقي ولا يحمل الحساب اعتماد توثيق.',
                'rating' => 0,
                'reviews_count' => 0,
                'is_active' => true,
                'verification_status' => 'pending',
                'verified_at' => null,
                'verified_by' => null,
                'agency_name' => 'ملفات عرض تجريبية - وجهتك',
                'job_title' => 'وكيل تجريبي',
                'city' => 'صنعاء',
                'experience_years' => 0,
                'photo_path' => 'https://ui-avatars.com/api/?name=Demo%20Agent%20'.$suffix.'&background=0E8A6D&color=fff&size=256',
                'is_demo' => true,
            ],
        );
    }

    private function rotateLegacyDemoPasswords(string $sharedPassword): void
    {
        $emails = ['agent.demo@lux.local', 'client.demo@lux.local'];
        foreach (User::query()->whereIn('email', $emails)->get() as $user) {
            if ($sharedPassword !== '') {
                $user->forceFill(['password' => Hash::make($sharedPassword)])->save();
            } elseif (Hash::check('agent123', (string) $user->password)) {
                // Remove the known weak password formerly used by the demo seeder.
                $user->forceFill(['password' => Hash::make(Str::random(64))])->save();
            }
        }
    }

    private function markLegacySeededRowsAsDemo(): void
    {
        // SN-2026-* is the reserved namespace owned by RealDataSeeder.
        Property::withTrashed()
            ->where('reference_code', 'like', 'SN-2026-%')
            ->update(['is_demo' => true]);

        Agent::query()
            ->whereIn('license_number', ['YE-AMA-1001', 'YE-AMA-1002'])
            ->update(['is_demo' => true, 'verification_status' => 'pending', 'verified_at' => null, 'verified_by' => null]);
    }

    private function transactionFor(string $type, int $number): string
    {
        if (in_array($type, ['land', 'farm'], true)) {
            return $number % 6 === 0 ? 'rent' : 'sale';
        }

        return $number % 4 === 0 ? 'rent' : 'sale';
    }

    private function priceFor(array $type, string $transaction, int $number): int
    {
        $base = $transaction === 'rent' ? $type['base_rent'] : $type['base_sale'];
        $factorHundredths = 72 + (($number * 19) % 61);
        return max(1, (int) round($base * $factorHundredths / 100));
    }

    private function descriptionFor(
        string $type,
        string $district,
        string $transaction,
        int $area,
        ?int $bedrooms,
        ?int $bathrooms,
        bool $furnished,
    ): string {
        $specs = "المساحة التقريبية {$area} متر مربع";
        if ($bedrooms !== null) $specs .= "، {$bedrooms} غرف نوم";
        if ($bathrooms !== null) $specs .= "، {$bathrooms} حمامات";
        if ($furnished) $specs .= '، مؤثث ضمن بيانات العرض';

        return "إعلان تجريبي رقمّي لاختبار البحث والتصفّح في تطبيق وجهتك، وليس عرضًا عقاريًا حقيقيًا أو موثقًا. "
            ."{$type} {$transaction} في حي {$district} بصنعاء؛ {$specs}. "
            .'السعر والموقع والوصف والصور بيانات توضيحية وليست عرضًا متاحًا للبيع أو الإيجار. '
            .'صور المبنى والصور الداخلية صور فوتوغرافية توضيحية من Unsplash ولا تمثل العقار أو موقعه الفعلي.';
    }

    private function seedImages(Property $property, string $typeSlug, string $title): void
    {
        $pool = self::PHOTO_POOLS[$typeSlug];
        $ordinal = (int) substr((string) $property->reference_code, -3);
        $cover = $pool['covers'][($ordinal - 1) % count($pool['covers'])];
        $gallery = $pool['gallery'];
        $second = $gallery[($ordinal - 1) % count($gallery)];
        $third = $gallery[($ordinal + 1) % count($gallery)];

        $photos = [$cover, $second, $third];
        foreach ($photos as $slot => $photoId) {
            $url = "https://images.unsplash.com/photo-{$photoId}?auto=format&fit=crop&w=1280&q=80";
            PropertyImage::query()->updateOrCreate(
                ['property_id' => $property->id, 'sort_order' => $slot],
                [
                    'path' => $url,
                    'alt_text' => "صورة توضيحية تجريبية: {$title} - ".($slot + 1),
                    'is_cover' => $slot === 0,
                ],
            );
        }
    }
};
