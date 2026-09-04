<?php

namespace Database\Seeders;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Enums\ViewingRequestStatus;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyImage;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Local/testing data only. This seeder is intentionally not called by DatabaseSeeder.
 * It gives the mobile client a complete, persistent API dataset for manual QA.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $explicitLocalQaRun = app()->runningInConsole() && getenv('LUX_ALLOW_DEMO_SEED') === 'true';
        if (! app()->environment(['local', 'testing']) && ! app()->runningUnitTests() && ! $explicitLocalQaRun) {
            throw new RuntimeException('DemoDataSeeder مسموح في بيئات local و testing فقط.');
        }

        $this->call(DatabaseSeeder::class);

        $adminUser = User::query()->updateOrCreate(
            ['email' => 'admin@wajhatak.app'],
            [
                'name' => 'شؤون وجهتك',
                'phone' => '0500000300',
                'locale' => 'ar',
                'is_active' => true,
                'password' => Hash::make('LuxAdmin2026!'),
            ],
        );
        $adminUser->syncRoles(['admin']);

        $agentUser = User::query()->updateOrCreate(
            ['email' => 'agent.demo@lux.local'],
            [
                'name' => 'عبدالرحمن',
                'phone' => '0500000200',
                'locale' => 'ar',
                'is_active' => true,
                'password' => Hash::make('LuxDemo2026'),
            ],
        );
        $agentUser->syncRoles(['agent']);
        $agent = Agent::query()->updateOrCreate(
            ['user_id' => $agentUser->id],
            ['license_number' => 'LUX-DEMO-AGENT-01', 'bio' => 'وكيلة عقارية مختصة بالسكن العائلي في الرياض.', 'rating' => 4.9, 'reviews_count' => 28, 'is_active' => true],
        );

        $client = User::query()->updateOrCreate(
            ['email' => 'client.demo@lux.local'],
            [
                'name' => 'مهند',
                'phone' => '0500000100',
                'locale' => 'ar',
                'is_active' => true,
                'password' => Hash::make('LuxDemo2026'),
            ],
        );
        $client->syncRoles(['user']);

        $types = collect([
            ['slug' => 'villa', 'name_ar' => 'فيلا', 'name_en' => 'Villa'],
            ['slug' => 'apartment', 'name_ar' => 'شقة', 'name_en' => 'Apartment'],
            ['slug' => 'townhouse', 'name_ar' => 'تاون هاوس', 'name_en' => 'Townhouse'],
            ['slug' => 'floor', 'name_ar' => 'دور', 'name_en' => 'Floor'],
        ])->mapWithKeys(fn (array $type) => [$type['slug'] => PropertyType::query()->updateOrCreate(['slug' => $type['slug']], [...$type, 'is_active' => true])]);

        $features = collect([
            ['slug' => 'pool', 'name_ar' => 'مسبح', 'name_en' => 'Pool', 'icon' => 'pool'],
            ['slug' => 'elevator', 'name_ar' => 'مصعد', 'name_en' => 'Elevator', 'icon' => 'elevator'],
            ['slug' => 'parking', 'name_ar' => 'موقف خاص', 'name_en' => 'Private parking', 'icon' => 'local_parking'],
            ['slug' => 'garden', 'name_ar' => 'حديقة', 'name_en' => 'Garden', 'icon' => 'yard'],
            ['slug' => 'security', 'name_ar' => 'حراسة', 'name_en' => 'Security', 'icon' => 'shield'],
        ])->mapWithKeys(fn (array $feature) => [$feature['slug'] => PropertyFeature::query()->updateOrCreate(['slug' => $feature['slug']], [...$feature, 'is_active' => true])]);

        $listings = [
            ['code' => 'LUX-DEMO-001', 'type' => 'villa', 'title' => 'فيلا معاصرة بإطلالة هادئة', 'description' => 'فيلا عائلية بتصميم حديث ومساحات مضيئة وحديقة خاصة في حي النرجس.', 'transaction' => TransactionType::Sale, 'status' => PropertyStatus::Published, 'price' => 3250000, 'area' => 420, 'beds' => 5, 'baths' => 6, 'city' => 'الرياض', 'district' => 'النرجس', 'address' => 'شارع الأمير سعود', 'latitude' => 24.8421, 'longitude' => 46.7056, 'color' => '#B58A44', 'features' => ['pool', 'garden', 'parking']],
            ['code' => 'LUX-DEMO-002', 'type' => 'apartment', 'title' => 'شقة بانورامية قرب مركز الأعمال', 'description' => 'شقة مؤثثة بعناية مع شرفة واسعة وخدمات مبنى متكاملة.', 'transaction' => TransactionType::Rent, 'status' => PropertyStatus::Published, 'price' => 98000, 'area' => 165, 'beds' => 3, 'baths' => 3, 'city' => 'الرياض', 'district' => 'الصحافة', 'address' => 'طريق الملك فهد', 'latitude' => 24.7830, 'longitude' => 46.6390, 'color' => '#2F7D62', 'features' => ['elevator', 'security']],
            ['code' => 'LUX-DEMO-003', 'type' => 'townhouse', 'title' => 'تاون هاوس بحديقة خاصة', 'description' => 'تاون هاوس عملي ضمن مجتمع سكني هادئ ومخدوم للعائلات.', 'transaction' => TransactionType::Sale, 'status' => PropertyStatus::Published, 'price' => 2450000, 'area' => 310, 'beds' => 4, 'baths' => 4, 'city' => 'الرياض', 'district' => 'حطين', 'address' => 'شارع السيل الكبير', 'latitude' => 24.7561, 'longitude' => 46.6112, 'color' => '#B34A3C', 'features' => ['garden', 'parking']],
            ['code' => 'LUX-DEMO-004', 'type' => 'floor', 'title' => 'دور علوي بمدخل مستقل', 'description' => 'دور حديث مناسب للعائلات مع مجلس مستقل ومطبخ مجهز.', 'transaction' => TransactionType::Rent, 'status' => PropertyStatus::Pending, 'price' => 72000, 'area' => 230, 'beds' => 4, 'baths' => 3, 'city' => 'الرياض', 'district' => 'العارض', 'address' => 'شارع الوادي', 'latitude' => 24.8604, 'longitude' => 46.6938, 'color' => '#68727C', 'features' => ['security', 'parking']],
        ];

        $properties = [];
        foreach ($listings as $index => $listing) {
            $location = PropertyLocation::query()->updateOrCreate(
                ['address' => $listing['address']],
                [
                    'city' => $listing['city'],
                    'district' => $listing['district'],
                    'neighborhood' => $listing['district'],
                    'latitude' => $listing['latitude'],
                    'longitude' => $listing['longitude'],
                ],
            );
            $property = Property::withTrashed()->updateOrCreate(
                ['reference_code' => $listing['code']],
                [
                    'agent_id' => $agent->id,
                    'property_type_id' => $types[$listing['type']]->id,
                    'property_location_id' => $location->id,
                    'title' => $listing['title'],
                    'slug' => strtolower($listing['code']),
                    'description' => $listing['description'],
                    'transaction_type' => $listing['transaction'],
                    'status' => $listing['status'],
                    'price' => $listing['price'],
                    'currency' => 'YER',
                    'area' => $listing['area'],
                    'bedrooms' => $listing['beds'],
                    'bathrooms' => $listing['baths'],
                    'parking_spaces' => 2,
                    'is_furnished' => $index === 1,
                    'is_new' => $index !== 1,
                    'is_featured' => $index < 2,
                    'published_at' => $listing['status'] === PropertyStatus::Published ? now()->subDays($index + 1) : null,
                    'deleted_at' => null,
                ],
            );
            $property->features()->sync(collect($listing['features'])->map(fn (string $slug) => $features[$slug]->id));
            $path = 'properties/demo/'.$property->id.'-cover.png';
            Storage::disk('public')->put($path, $this->coverPng($listing['color']));
            PropertyImage::query()->updateOrCreate(
                ['property_id' => $property->id, 'sort_order' => 0],
                ['path' => $path, 'alt_text' => $listing['title'], 'is_cover' => true],
            );
            $properties[] = $property;
        }

        $viewing = ViewingRequest::query()->updateOrCreate(
            ['property_id' => $properties[0]->id, 'client_id' => $client->id, 'agent_id' => $agent->id],
            ['scheduled_date' => now()->addDays(2)->toDateString(), 'scheduled_time' => '17:30', 'notes' => 'أفضّل معاينة الحديقة قبل الغروب.', 'status' => ViewingRequestStatus::Pending],
        );

        $conversation = Conversation::query()->updateOrCreate(
            ['property_id' => $properties[0]->id, 'client_id' => $client->id, 'agent_id' => $agentUser->id],
            ['last_message_at' => now()->subMinutes(12)],
        );
        Message::query()->firstOrCreate(
            ['conversation_id' => $conversation->id, 'sender_id' => $agentUser->id, 'body' => 'أهلًا مهند، موعد المعاينة مساء السبت متاح.'],
            ['read_at' => null],
        );

        $this->command?->info("تم إنشاء بيانات LUX المحلية. دخول المدير: admin@wajhatak.app / LuxAdmin2026!، دخول العميل: client.demo@lux.local / LuxDemo2026، ودخول الوكيل: agent.demo@lux.local / LuxDemo2026. طلب المعاينة #{$viewing->id}.");
    }

    private function coverPng(string $color): string
    {
        $width = 1200;
        $height = 720;
        $image = imagecreatetruecolor($width, $height);
        $parts = sscanf($color, '#%02x%02x%02x');
        $bg = imagecolorallocate($image, (int) $parts[0], (int) $parts[1], (int) $parts[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        $text = imagecolorallocatealpha($image, 255, 255, 255, 64);
        imagefilledpolygon($image, [150, 520, 390, 320, 550, 440, 760, 220, 1050, 520, 1050, 620, 150, 620], 7, $text);
        $panel = imagecolorallocatealpha($image, 16, 24, 31, 48);
        imagefilledrectangle($image, 180, 170, 1020, 490, $panel);

        ob_start();
        imagepng($image);
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return $data;
    }
}
