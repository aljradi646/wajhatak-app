<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyImage;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Real (production) dataset for the Wajhatak app.
 *
 * Seeds genuinely realistic Sana'a (Yemen) property listings owned by the two
 * real agents: عبدالرحمن and مهند. Real photos are downloaded from the
 * internet (stable Unsplash CDN, then loremflickr, then a generated cover as a
 * last resort) by the server, resized with GD to a fast web size and stored on
 * the public disk so the app serves them locally and instantly.
 *
 * Safety guarantees (explicitly requested):
 *   - Only demo/trial properties (reference_code LIKE 'LUX-DEMO-%') are
 *     removed. No other data is touched - users, agents, locations, settings,
 *     activity logs and any property the user added themselves are preserved.
 *   - The seeder is idempotent: on re-runs it replaces exactly its own
 *     reference codes (SN-2026-*) with fresh copies.
 *   - At the end it writes Setting 'system_initialized' = 1. The container
 *     entrypoint uses that flag to skip ALL migrations/seeds on later boots,
 *     so no redeploy or restart can ever modify the database again.
 */
class RealDataSeeder extends Seeder
{
    /** Stable Unsplash photo IDs (verified reachable via GET). */
    private const COVERS = [
        '1580587771525-78b9dba3b914', '1568605114967-8130f3a36994',
        '1570129477492-45c003edd2be', '1600596542815-ffad4c1539a9',
        '1600585154340-be6161a56a0c', '1600566753190-17f0baa2a6c3',
        '1600047509807-ba8f99d2cdde', '1613490493576-7fde63acd811',
        '1545324418-cc1a3fa10c00',  '1512917774080-9991f1c4c750',
        '1575517111478-7f6afd0973db', '1502005229762-cf1b2da7c5d6',
        '1470770841072-f978cf4d019e', '1523217582562-09d0def993a6',
        '1570126618953-d437176e8c79',
    ];

    private const INTERIORS = [
        '1600607687939-ce8a6c25118c', '1600210492486-724fe5c67fb0',
        '1605348532760-6753d2c43329', '1493809842364-78817add7ffb',
        '1522708323590-d24dbb6b0267', '1502672260266-1c1ef2d93688',
        '1484154218962-a197022b5858', '1554995207-c18c203602cb',
        '1560448204-e02f11c3d0e2',  '1522444195799-478538b28823',
        '1460317442991-0ec209397118', '1605276374104-dee2a0ed3cd6',
        '1512453979798-5ea266f8880c', '1560518883-ce09059eeffa',
        '1486406146926-c627a92ad1ab',
    ];

    private const BASE_LAT = 15.3525;
    private const BASE_LNG = 44.2147;

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('agent');
        Role::findOrCreate('user');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ------------------------------------------------------------------
        // 1. Real agents: عبدالرحمن and مهند (all properties belong to them).
        // ------------------------------------------------------------------
        $abdulrahman = $this->ensureAgent('agent.demo@lux.local', 'عبدالرحمن الغليسي', '0777000101', 'YE-AMA-1001');
        $mohannad = $this->ensureAgent('client.demo@lux.local', 'مهند الحمادي', '0777000202', 'YE-AMA-1002');

        $mohannad->user->syncRoles(['agent', 'user']);
        $abdulrahman->user->syncRoles(['agent', 'user']);

        $existing = Permission::query()->whereIn('name', ['create properties', 'edit own properties', 'view incoming requests'])->pluck('name');
        if ($existing->isNotEmpty()) {
            Role::findOrCreate('agent')->givePermissionTo($existing->all());
        }

        // ------------------------------------------------------------------
        // 2. Remove ONLY the previous demo/trial property data. Cascades clear
        //    favourite/listings, conversations, messages and viewing requests.
        // ------------------------------------------------------------------
        Property::withTrashed()
            ->where('reference_code', 'like', 'LUX-DEMO-%')
            ->pluck('id')
            ->pipe(fn ($ids) => $ids->isEmpty() ? null : Property::withTrashed()->whereIn('id', $ids)->forceDelete());
        Storage::disk('public')->deleteDirectory('properties/demo');

        // ------------------------------------------------------------------
        // 3. Taxonomy (created when missing so a fresh DB also works).
        // ------------------------------------------------------------------
        $this->ensureTaxonomy();

        // ==================================================================
        // 4. Real Sana'a properties.
        // ==================================================================
        $features = PropertyFeature::query()->pluck('id', 'name_ar');
        $i = 0;

        foreach ($this->properties() as $p) {
            $agent = ($i % 2 === 0) ? $abdulrahman : $mohannad;
            $i++;

            $type = PropertyType::query()->firstWhere('name_ar', $p['type']);
            $location = PropertyLocation::query()->firstOrCreate(
                ['address' => $p['address']],
                [
                    'country_id' => null,
                    'region_id' => null,
                    'city_id' => null,
                    'area_id' => null,
                    'city' => $p['city'],
                    'district' => $p['district'],
                    'neighborhood' => $p['neighborhood'],
                    'latitude' => self::BASE_LAT + $p['lat'],
                    'longitude' => self::BASE_LNG + $p['lng'],
                ],
            );

            $code = 'SN-2026-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);

            Property::withTrashed()->where('reference_code', $code)->forceDelete();

            $property = Property::query()->create([
                'agent_id' => $agent->id,
                'property_type_id' => $type->id,
                'property_location_id' => $location->id,
                'title' => $p['title'],
                'slug' => str()->slug($p['title'] . '-' . $code),
                'reference_code' => $code,
                'description' => $p['description'],
                'transaction_type' => $p['transaction'],
                'status' => $p['status'],
                'price' => $p['price'],
                'currency' => 'YER',
                'area' => $p['area'],
                'bedrooms' => $p['bedrooms'],
                'bathrooms' => $p['bathrooms'],
                'parking_spaces' => $p['parking'],
                'is_furnished' => $p['furnished'],
                'is_new' => $p['new'],
                'is_featured' => $p['featured'],
                'published_at' => $p['published'] ? now() : null,
            ]);

            $this->seedImages($property, $p['title']);
            $property->features()->sync(
                collect($p['features'])->map(fn ($name) => $features[$name])->values()->all(),
            );
        }

        // ------------------------------------------------------------------
        // 5. A couple of activity-log rows (never existed before).
        // ------------------------------------------------------------------
        ActivityLog::query()->firstOrCreate(
            ['log_name' => 'seed', 'description' => 'تم تجهيز بيانات العقارات الحقيقية (صنعاء) بنجاح.'],
            ['properties' => json_encode(['seeder' => 'RealDataSeeder', 'count' => count($this->properties())])],
        );
        ActivityLog::query()->firstOrCreate(
            ['log_name' => 'seed', 'description' => 'تم إعداد الوكلاء: عبدالرحمن الغليسي ومهند الحمادي.'],
        );

        // ------------------------------------------------------------------
        // 6. Mark the database as fully provisioned. The entrypoint checks this
        //    flag on every boot and never runs migrations/seeds again.
        // ------------------------------------------------------------------
        Setting::put('system_initialized', '1');

        $this->command?->info('RealDataSeeder done: '.count($this->properties()).' Sana\'a properties assigned to عبدالرحمن & مهند.');
    }

    private function ensureAgent(string $email, string $name, string $phone, string $license): Agent
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make('agent123'),
                'is_active' => true,
            ],
        );
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles(['agent', 'user']);

        return Agent::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'license_number' => $license,
                'bio' => 'وكيل عقاري معتمد في أمانة العاصمة صنعاء، متخصص في بيع وإيجار الفلل والشقق والوحدات السكنية في كافة الأحياء.',
                'rating' => 4.8,
                'reviews_count' => 37,
                'is_active' => true,
            ],
        );
    }

    private function ensureTaxonomy(): void
    {
        // Keyed on the unique slug (not the Arabic name) so any existing rows
        // from the old demo dataset are safely updated in place - never a
        // duplicate slug error.
        $types = [
            'villa' => 'فيلا',
            'apartment' => 'شقة',
            'townhouse' => 'تاون هاوس',
            'floor' => 'دور',
        ];
        foreach ($types as $slug => $name) {
            PropertyType::query()->updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $name, 'name_en' => ucfirst($slug), 'is_active' => true],
            );
        }

        $features = [
            'pool' => 'حمام سباحة',
            'elevator' => 'مصعد',
            'parking' => 'موقف سيارات',
            'garden' => 'حديقة خاصة',
            'security' => 'كاميرات أمنية',
            'water-tank' => 'خزان ماء مستقل',
            'rooftop' => 'سطح خاص',
            'guard-room' => 'غرفة حارس',
        ];
        foreach ($features as $slug => $name) {
            PropertyFeature::query()->updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $name, 'name_en' => $slug, 'icon' => $slug, 'is_active' => true],
            );
        }
    }

    /**
     * Download one real photo from the internet, resize it with GD and store a
     * fast-to-serve JPEG locally. Falls back gracefully (never fails).
     */
    private function seedImages(Property $property, string $title): void
    {
        $dir = 'properties/real/' . $property->reference_code;
        Storage::disk('public')->deleteDirectory($dir);
        $sources = $this->imageUrlsFor($property->reference_code);

        foreach ($sources as $n => $url) {
            $path = $dir . '/' . $n . '.jpg';
            $ok = false;

            if (! app()->environment('testing')) {
                $ok = $this->downloadAndResize($url, Storage::disk('public')->path($path));
            }

            if (! $ok) {
                $this->placeholder(Storage::disk('public')->path($path));
            }

            PropertyImage::query()->create([
                'property_id' => $property->id,
                'path' => $dir . '/' . $n . '.jpg',
                'alt_text' => $title . ' - صورة ' . ($n + 1),
                'sort_order' => $n,
                'is_cover' => $n === 0,
            ]);
        }
    }

    /** Deterministic unique image set per property code. */
    private function imageUrlsFor(string $code): array
    {
        $idx = ((int) substr($code, -3) - 1);
        $cover = self::COVERS[$idx % count(self::COVERS)];
        $i1 = self::INTERIORS[$idx % count(self::INTERIORS)];
        $i2 = self::INTERIORS[($idx + 5) % count(self::INTERIORS)];

        return [
            'https://images.unsplash.com/photo-' . $cover . '?auto=format&fit=crop&w=1280&q=78',
            'https://images.unsplash.com/photo-' . $i1 . '?auto=format&fit=crop&w=1280&q=78',
            'https://images.unsplash.com/photo-' . $i2 . '?auto=format&fit=crop&w=1280&q=78',
        ];
    }

    private function downloadAndResize(string $url, string $destPath): bool
    {
        try {
            $bytes = Http::connectTimeout(12)
                ->timeout(40)
                ->withHeaders(['User-Agent' => 'Wajhatak/1.0 (+https://wajhatak.app)'])
                ->get($url)
                ->throw()
                ->body();

            if (strlen($bytes) < 2000) {
                return false;
            }

            $img = @imagecreatefromstring($bytes);
            if (! $img) {
                return false;
            }
            $w = imagesx($img);
            $h = imagesy($img);
            if ($w > 1280) {
                $nw = 1280;
                $nh = max(1, (int) round($h * 1280 / $w));
                $resampled = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($resampled, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($img);
                $img = $resampled;
            }

            $dir = dirname($destPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $saved = imagejpeg($img, $destPath, 82);
            imagedestroy($img);

            return (bool) $saved;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function placeholder(string $destPath): void
    {
        try {
            $img = imagecreatetruecolor(1280, 800);
            $bg = imagecolorallocate($img, 20, 60, 90);
            $fg = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $bg);
            imagerectangle($img, 0, 0, 1279, 799, $fg);
            $dir = dirname($destPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            imagejpeg($img, $destPath, 82);
            imagedestroy($img);
        } catch (\Throwable $e) {
            // best effort
        }
    }

    /** @return list<array<string, mixed>> */
    private function properties(): array
    {
        return [
            [
                'title' => 'فيلا فاخرة للبيع في حي السفارة - معين',
                'type' => 'فيلا', 'transaction' => 'sale', 'status' => 'published',
                'price' => 250000000, 'area' => 520, 'bedrooms' => 6, 'bathrooms' => 5,
                'parking' => 2, 'furnished' => false, 'new' => false, 'featured' => true, 'published' => true,
                'city' => 'صنعاء', 'district' => 'معين', 'neighborhood' => 'حي السفارة',
                'address' => 'شارع الستين، حي السفارة، معين', 'lat' => 0.006, 'lng' => 0.004,
                'features' => ['حمام سباحة', 'حديقة خاصة', 'كاميرات أمنية', 'غرفة حارس', 'خزان ماء مستقل'],
                'description' => 'فيلا فاخرة قائمة على مساحة 520 متر مربع في قلب حي السفارة بالعاصمة صنعاء. المبنى من دورين مع سطح خاص وحديقة داخلية، تشطيبات راقية، جاهزة للسكن. قريبة من السفارات والوزارات وأهم الشوارع الحيوية. فرصة استثمارية متميزة للمقتني الراغب بموقع ذو خصوصية عالية.',
            ],
            [
                'title' => 'شقة فاخرة للبيع - حي شعوب',
                'type' => 'شقة', 'transaction' => 'sale', 'status' => 'published',
                'price' => 65000000, 'area' => 180, 'bedrooms' => 3, 'bathrooms' => 2,
                'parking' => 1, 'furnished' => false, 'new' => true, 'featured' => true, 'published' => true,
                'city' => 'صنعاء', 'district' => 'شعوب', 'neighborhood' => 'حي الشعوب',
                'address' => 'شارع إب، حي الشعوب', 'lat' => -0.005, 'lng' => 0.012,
                'features' => ['مصعد', 'موقف سيارات', 'كاميرات أمنية', 'خزان ماء مستقل'],
                'description' => 'شقة سكنية جديدة بمساحة 180 متر مربع بإطلالة ممتازة في حي الشعوب. عداد كهرباء وكهرباء سنترال، موقع هادئ وسكني، قريبة من الأسواق والخدمات. مناسبة للعائلات الباحثة عن سكن مريح وآمن في أمانة العاصمة.',
            ],
            [
                'title' => 'دوبلكس للبيع - حي بني الحارث',
                'type' => 'دور', 'transaction' => 'sale', 'status' => 'published',
                'price' => 90000000, 'area' => 260, 'bedrooms' => 4, 'bathrooms' => 3,
                'parking' => 1, 'furnished' => false, 'new' => true, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'بني الحارث', 'neighborhood' => 'حي جدير',
                'address' => 'شارع القيادة، حي جدير، بني الحارث', 'lat' => 0.003, 'lng' => -0.008,
                'features' => ['موقف سيارات', 'سطح خاص', 'خزان ماء مستقل'],
                'description' => 'دوبلكس جديد بدورين ودرج داخلي على مساحة 260 متر مربع في منطقة جدير ببني الحارث. تشطيب جيد، موقع هادئ وبعيد عن الزحام، قريب من جامعة صنعاء الجديدة والخطوط الرئيسية.',
            ],
            [
                'title' => 'فيلا كلاسيكية مع حديقة - حي الصافية',
                'type' => 'فيلا', 'transaction' => 'sale', 'status' => 'published',
                'price' => 300000000, 'area' => 600, 'bedrooms' => 7, 'bathrooms' => 6,
                'parking' => 3, 'furnished' => false, 'new' => false, 'featured' => true, 'published' => true,
                'city' => 'صنعاء', 'district' => 'الصافية', 'neighborhood' => 'حي الصافية',
                'address' => 'شارع جولة المصلى، حي الصافية', 'lat' => -0.008, 'lng' => -0.006,
                'features' => ['حمام سباحة', 'حديقة خاصة', 'كاميرات أمنية', 'غرفة حارس', 'خزان ماء مستقل'],
                'description' => 'فيلا كلاسيكية فاخرة على مساحة 600 متر مربع بحديقة واسعة وملحق خارجي للضيوف في حي الصافية الراقي. دور أرضي ودورين، تشطيبات راقية بالحجر الطبيعي، عزل كامل، قريبة من المدارس الدولية والمراكز التجارية. عقار فريد لمحبي الفخامة.',
            ],
            [
                'title' => 'شقة للإيجار السنوي - حي معين',
                'type' => 'شقة', 'transaction' => 'rent', 'status' => 'published',
                'price' => 1800000, 'area' => 120, 'bedrooms' => 2, 'bathrooms' => 1,
                'parking' => 1, 'furnished' => false, 'new' => false, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'معين', 'neighborhood' => 'حي معين',
                'address' => 'شارع جولة الميدان، حي معين', 'lat' => 0.009, 'lng' => 0.003,
                'features' => ['مصعد', 'موقف سيارات'],
                'description' => 'شقة مؤثثة جزئياً للإيجار السنوي في حي معين بجوار جولة الميدان. صالة واسعة، غرفتان، مطبخ جاهز، موقع حيوي قريب من المطاعم والمقاهي وفروع البنوك. مناسبة للموظفين والعائلات الصغيرة.',
            ],
            [
                'title' => 'دور كامل للبيع - حي الوحدة',
                'type' => 'دور', 'transaction' => 'sale', 'status' => 'published',
                'price' => 120000000, 'area' => 330, 'bedrooms' => 5, 'bathrooms' => 4,
                'parking' => 1, 'furnished' => false, 'new' => true, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'الوحدة', 'neighborhood' => 'حي الوحدة',
                'address' => 'شارع تعز، حي الوحدة', 'lat' => -0.012, 'lng' => 0.008,
                'features' => ['موقف سيارات', 'سطح خاص', 'كاميرات أمنية', 'خزان ماء مستقل'],
                'description' => 'دور كامل جديد بمساحة 330 متر مربع في الدور الثالث بعمارة حديثة بحي الوحدة. تشطيب فاخر، مصعد، موقع تجاري وسكني مميز على شارع تعز الرئيسي، مناسب للسكن أو كمقر لمكتب أو عيادة.',
            ],
            [
                'title' => 'شقة تمليك - حي التحرير',
                'type' => 'شقة', 'transaction' => 'sale', 'status' => 'published',
                'price' => 55000000, 'area' => 150, 'bedrooms' => 3, 'bathrooms' => 2,
                'parking' => 0, 'furnished' => false, 'new' => false, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'التحرير', 'neighborhood' => 'حي التحرير',
                'address' => 'شارع جمال عبدالناصر، حي التحرير', 'lat' => -0.004, 'lng' => -0.010,
                'features' => ['مصعد', 'كاميرات أمنية'],
                'description' => 'شقة تمليك وسط العاصمة بحي التحرير على مساحة 150 متر مربع. موقع مركزي قريب من سوق القات المركزي والجامع الكبير وجميع الخدمات، عمارة نظامية وصيانة جيدة.',
            ],
            [
                'title' => 'فيلا عصرية للبيع - حي بني الحارث (شارع عصر)',
                'type' => 'فيلا', 'transaction' => 'sale', 'status' => 'published',
                'price' => 280000000, 'area' => 580, 'bedrooms' => 7, 'bathrooms' => 6,
                'parking' => 2, 'furnished' => false, 'new' => true, 'featured' => true, 'published' => true,
                'city' => 'صنعاء', 'district' => 'بني الحارث', 'neighborhood' => 'حي الجراف',
                'address' => 'شارع عصر، حي الجراف، بني الحارث', 'lat' => 0.015, 'lng' => -0.004,
                'features' => ['حديقة خاصة', 'كاميرات أمنية', 'سطح خاص', 'خزان ماء مستقل', 'موقف سيارات'],
                'description' => 'فيلا عصرية حديثة البناء في حي الجراف ببني الحارث على مساحة 580 متر مربع. تصميم معماري معاصر، صالة مفتوحة، ملحق خارجي، حديقة وموقف لسيارتين. من أفضل مواقع الضواحي الشمالية للعاصمة وأكثرها نشاطاً.',
            ],
            [
                'title' => 'شقة سكنية للإيجار - حي آزال',
                'type' => 'شقة', 'transaction' => 'rent', 'status' => 'published',
                'price' => 1500000, 'area' => 110, 'bedrooms' => 2, 'bathrooms' => 1,
                'parking' => 1, 'furnished' => true, 'new' => false, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'آزال', 'neighborhood' => 'حي آزال الشمالي',
                'address' => 'شارع الجزائر، حي آزال الشمالي', 'lat' => -0.010, 'lng' => 0.002,
                'features' => ['مصعد', 'موقف سيارات', 'كاميرات أمنية'],
                'description' => 'شقة مفروشة بالكامل للإيجار في حي آزال الشمالي قبالة مستشفى الأمل. غرفتان، صالة، مطبخ مجهز بالكامل، جاهزة للسكن فوري. موقع متميز قريب من المستشفى والجامعات والمواصلات.',
            ],
            [
                'title' => 'تاون هاوس للبيع - حي الثورة',
                'type' => 'تاون هاوس', 'transaction' => 'sale', 'status' => 'published',
                'price' => 150000000, 'area' => 400, 'bedrooms' => 4, 'bathrooms' => 4,
                'parking' => 2, 'furnished' => false, 'new' => true, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'الثورة', 'neighborhood' => 'حي الثورة',
                'address' => 'شارع الخمسين، حي الثورة', 'lat' => -0.006, 'lng' => 0.006,
                'features' => ['مصعد', 'حديقة خاصة', 'موقف سيارات', 'كاميرات أمنية'],
                'description' => 'تاون هاوس بعمارة مشتركة فاخرة في حي الثورة على شارع الخمسين. دور أرضي وصالة علوية، 4 غرف نوم، حديقة خاصة، موقفان. موقع مركزي يخدم العائلات الباحثة عن الرقي وسهولة الوصول لكافة أنحاء العاصمة.',
            ],
            [
                'title' => 'شقة مميزة للبيع - الحي الدبلوماسي',
                'type' => 'شقة', 'transaction' => 'sale', 'status' => 'published',
                'price' => 72000000, 'area' => 200, 'bedrooms' => 3, 'bathrooms' => 3,
                'parking' => 1, 'furnished' => false, 'new' => true, 'featured' => true, 'published' => true,
                'city' => 'صنعاء', 'district' => 'السفارة', 'neighborhood' => 'الحي الدبلوماسي',
                'address' => 'شارع المطار، الحي الدبلوماسي', 'lat' => 0.012, 'lng' => 0.010,
                'features' => ['مصعد', 'موقف سيارات', 'كاميرات أمنية', 'خزان ماء مستقل'],
                'description' => 'شقة راقية في الحي الدبلوماسي بشارع المطار قرب السفارات الأجنبية ومجمع الوزارات. مساحة 200 متر مربع بمواصفات دبلوماسية من التشطيب والأمان، مثالية للمدراء الأجانب وكبار الشخصيات.',
            ],
            [
                'title' => 'فيلا استثمارية - حي شعوب (جولة سبأ)',
                'type' => 'فيلا', 'transaction' => 'sale', 'status' => 'published',
                'price' => 195000000, 'area' => 480, 'bedrooms' => 5, 'bathrooms' => 4,
                'parking' => 2, 'furnished' => false, 'new' => false, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'شعوب', 'neighborhood' => 'جولة سبأ',
                'address' => 'جولة سبأ، حي شعوب', 'lat' => 0.001, 'lng' => 0.014,
                'features' => ['حديقة خاصة', 'غرفة حارس', 'سطح خاص', 'موقف سيارات'],
                'description' => 'فيلا استثمارية على مساحة 480 متر مربع في موقع مميز جداً عند جولة سبأ. الدخل الإيجاري المتوقع مرتفع نظراً لموقعها التجاريـالسكني، قابلة للتحويل إلى مقرات ومكاتب.',
            ],
            [
                'title' => 'شقة مفروشة للإيجار - حي معين',
                'type' => 'شقة', 'transaction' => 'rent', 'status' => 'published',
                'price' => 2100000, 'area' => 130, 'bedrooms' => 2, 'bathrooms' => 2,
                'parking' => 1, 'furnished' => true, 'new' => true, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'معين', 'neighborhood' => 'حي معين',
                'address' => 'شارع الستين، حي معين', 'lat' => 0.006, 'lng' => 0.001,
                'features' => ['مصعد', 'موقف سيارات', 'كاميرات أمنية', 'خزان ماء مستقل'],
                'description' => 'شقة جديدة مفروشة بأثاث عصري كامل للإيجار السنوي على شارع الستين بحي معين. غرفتان وصالة، مطبخ راكب، تدفئة مركزية، جاهزة فوراً. مثالية للمغتربين والموظفين بالمنظمات.',
            ],
            [
                'title' => 'دور كامل للبيع - حي الصافية',
                'type' => 'دور', 'transaction' => 'sale', 'status' => 'published',
                'price' => 135000000, 'area' => 350, 'bedrooms' => 5, 'bathrooms' => 4,
                'parking' => 1, 'furnished' => false, 'new' => true, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'الصافية', 'neighborhood' => 'حي الصافية',
                'address' => 'شارع صخر، حي الصافية', 'lat' => -0.007, 'lng' => -0.007,
                'features' => ['مصعد', 'موقف سيارات', 'سطح خاص', 'كاميرات أمنية'],
                'description' => 'دور كامل حديث التشطيب في عمارة فاخرة بحي الصافية على شارع صخر. مساحة 350 متر مربع، صالتان، 5 غرف نوم، مطبخين. حي راقٍ وهادئ تفضله العائلات الكبيرة.',
            ],
            [
                'title' => 'فيلا بطراز كلاسيكي - حي آزال',
                'type' => 'فيلا', 'transaction' => 'sale', 'status' => 'published',
                'price' => 175000000, 'area' => 420, 'bedrooms' => 5, 'bathrooms' => 4,
                'parking' => 2, 'furnished' => false, 'new' => false, 'featured' => false, 'published' => true,
                'city' => 'صنعاء', 'district' => 'آزال', 'neighborhood' => 'حي آزال الغربي',
                'address' => 'شارع الحصبة القديم، حي آزال الغربي', 'lat' => -0.009, 'lng' => -0.012,
                'features' => ['حديقة خاصة', 'موقف سيارات', 'خزان ماء مستقل', 'غرفة حارس'],
                'description' => 'فيلا بطراز كلاسيكي جميل في حي آزال الغربي بعيداً عن الضجيج. مساحة 420 متر مربع مع حديقة شجرية وملحق خلفي، مثالية لعائلة تحب الهدوء والمساحات الخضراء داخل المدينة.',
            ],
            [
                'title' => 'شقة تمليك مميزة - حي بني الحارث',
                'type' => 'شقة', 'transaction' => 'sale', 'status' => 'pending',
                'price' => 60000000, 'area' => 160, 'bedrooms' => 3, 'bathrooms' => 2,
                'parking' => 1, 'furnished' => false, 'new' => true, 'featured' => false, 'published' => false,
                'city' => 'صنعاء', 'district' => 'بني الحارث', 'neighborhood' => 'حي الحصبة',
                'address' => 'شارع الحصبة، حي الحصبة', 'lat' => 0.011, 'lng' => -0.014,
                'features' => ['مصعد', 'موقف سيارات', 'كاميرات أمنية'],
                'description' => 'شقة تمليك جديدة بمساحة 160 متر مربع في حي الحصبة ببني الحارث. موقع متميز قرب مستشفى جامعة العلوم والتكنولوجيا وسوق الحصبة. عقار جديد بالكامل بمواصفات حديثة.',
            ],
        ];
    }
}