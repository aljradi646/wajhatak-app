<?php

namespace Database\Seeders;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Enums\ViewingRequestStatus;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\Message;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyImage;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Local/testing data only. This seeder is intentionally not called by DatabaseSeeder.
 * It gives the mobile client a complete, persistent API dataset for manual QA and
 * fills every admin control-panel section with realistic rows:
 * users, agents, properties (mixed statuses + a trashed one), viewing requests,
 * conversations/messages, favorites, activity logs, branding assets and settings.
 *
 * Everything is idempotent (updateOrCreate / firstOrCreate) so re-running after a
 * redeploy never duplicates rows.
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
        Setting::seedDefaults();

        // ---------------------------------------------------------------------
        // Users + agents
        // ---------------------------------------------------------------------
        $adminUser = User::query()->updateOrCreate(
            ['email' => 'admin@wajhatak.app'],
            [
                'name' => 'شؤون وجهتك',
                'phone' => '0500000300',
                'locale' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('LuxAdmin2026!'),
            ],
        );
        $adminUser->syncRoles(['admin']);

        $agentUsers = [
            0 => User::query()->updateOrCreate(
                ['email' => 'agent.demo@lux.local'],
                [
                    'name' => 'عبدالرحمن',
                    'phone' => '0500000200',
                    'locale' => 'ar',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('LuxDemo2026'),
                ],
            ),
            1 => User::query()->updateOrCreate(
                ['email' => 'agent.abdullah@lux.local'],
                [
                    'name' => 'عبدالله القحطاني',
                    'phone' => '0500000210',
                    'locale' => 'ar',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('LuxDemo2026'),
                ],
            ),
            2 => User::query()->updateOrCreate(
                ['email' => 'agent.sara@lux.local'],
                [
                    'name' => 'سارة الحمادي',
                    'phone' => '0500000220',
                    'locale' => 'ar',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('LuxDemo2026'),
                ],
            ),
        ];
        foreach ($agentUsers as $user) {
            $user->syncRoles(['agent']);
        }

        $clientUsers = [
            0 => User::query()->updateOrCreate(
                ['email' => 'client.demo@lux.local'],
                [
                    'name' => 'مهند',
                    'phone' => '0500000100',
                    'locale' => 'ar',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('LuxDemo2026'),
                ],
            ),
            1 => User::query()->updateOrCreate(
                ['email' => 'client.reem@lux.local'],
                [
                    'name' => 'ريم العتيبي',
                    'phone' => '0500000110',
                    'locale' => 'ar',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('LuxDemo2026'),
                ],
            ),
            2 => User::query()->updateOrCreate(
                ['email' => 'client.osama@lux.local'],
                [
                    'name' => 'أسامة',
                    'phone' => '0500000120',
                    'locale' => 'ar',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('LuxDemo2026'),
                ],
            ),
        ];
        foreach ($clientUsers as $user) {
            $user->syncRoles(['user']);
        }

        $agents = [
            0 => Agent::query()->updateOrCreate(
                ['user_id' => $agentUsers[0]->id],
                ['license_number' => 'LUX-DEMO-AGENT-01', 'bio' => 'وكيلة عقارية مختصة بالسكن العائلي في الرياض.', 'rating' => 4.9, 'reviews_count' => 28, 'is_active' => true],
            ),
            1 => Agent::query()->updateOrCreate(
                ['user_id' => $agentUsers[1]->id],
                ['license_number' => 'LUX-DEMO-AGENT-02', 'bio' => 'مختص بالعقار التجاري والسكني في شمال الرياض وجدة.', 'rating' => 4.7, 'reviews_count' => 41, 'is_active' => true],
            ),
            2 => Agent::query()->updateOrCreate(
                ['user_id' => $agentUsers[2]->id],
                ['license_number' => 'LUX-DEMO-AGENT-03', 'bio' => 'وسيطة عقارية بخبرة طويلة في وسط وشرق صنعاء.', 'rating' => 4.5, 'reviews_count' => 17, 'is_active' => true],
            ),
        ];

        // ---------------------------------------------------------------------
        // Taxonomy: types + features
        // ---------------------------------------------------------------------
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

        // ---------------------------------------------------------------------
        // Properties (mixed statuses + a trashed archive example). agent: index.
        // ---------------------------------------------------------------------
        $listings = [
            ['code' => 'LUX-DEMO-001', 'type' => 'villa', 'agent' => 0, 'title' => 'فيلا معاصرة بإطلالة هادئة', 'description' => 'فيلا عائلية بتصميم حديث ومساحات مضيئة وحديقة خاصة في حي النرجس.', 'transaction' => TransactionType::Sale, 'status' => PropertyStatus::Published, 'price' => 3250000, 'area' => 420, 'beds' => 5, 'baths' => 6, 'city' => 'الرياض', 'district' => 'النرجس', 'address' => 'شارع الأمير سعود', 'latitude' => 24.8421, 'longitude' => 46.7056, 'color' => '#B58A44', 'features' => ['pool', 'garden', 'parking']],
            ['code' => 'LUX-DEMO-002', 'type' => 'apartment', 'agent' => 0, 'title' => 'شقة بانورامية قرب مركز الأعمال', 'description' => 'شقة مؤثثة بعناية مع شرفة واسعة وخدمات مبنى متكاملة.', 'transaction' => TransactionType::Rent, 'status' => PropertyStatus::Published, 'price' => 98000, 'area' => 165, 'beds' => 3, 'baths' => 3, 'city' => 'الرياض', 'district' => 'الصحافة', 'address' => 'طريق الملك فهد', 'latitude' => 24.7830, 'longitude' => 46.6390, 'color' => '#2F7D62', 'features' => ['elevator', 'security']],
            ['code' => 'LUX-DEMO-003', 'type' => 'townhouse', 'agent' => 0, 'title' => 'تاون هاوس بحديقة خاصة', 'description' => 'تاون هاوس عملي ضمن مجتمع سكني هادئ ومخدوم للعائلات.', 'transaction' => TransactionType::Sale, 'status' => PropertyStatus::Published, 'price' => 2450000, 'area' => 310, 'beds' => 4, 'baths' => 4, 'city' => 'الرياض', 'district' => 'حطين', 'address' => 'شارع السيل الكبير', 'latitude' => 24.7561, 'longitude' => 46.6112, 'color' => '#B34A3C', 'features' => ['garden', 'parking']],
            ['code' => 'LUX-DEMO-004', 'type' => 'floor', 'agent' => 0, 'title' => 'دور علوي بمدخل مستقل', 'description' => 'دور حديث مناسب للعائلات مع مجلس مستقل ومطبخ مجهز.', 'transaction' => TransactionType::Rent, 'status' => PropertyStatus::Pending, 'price' => 72000, 'area' => 230, 'beds' => 4, 'baths' => 3, 'city' => 'الرياض', 'district' => 'العارض', 'address' => 'شارع الوادي', 'latitude' => 24.8604, 'longitude' => 46.6938, 'color' => '#68727C', 'features' => ['security', 'parking']],

            ['code' => 'LUX-DEMO-005', 'type' => 'apartment', 'agent' => 1, 'title' => 'شقة كاملة الأثاث في حي العليا', 'description' => 'شقة مجلسية راقية قريبة من الدوائر الحكومية وجميع الخدمات.', 'transaction' => TransactionType::Rent, 'status' => PropertyStatus::Pending, 'price' => 115000, 'area' => 190, 'beds' => 4, 'baths' => 4, 'city' => 'الرياض', 'district' => 'العليا', 'address' => 'طريق العليا العام', 'latitude' => 24.7417, 'longitude' => 46.6550, 'color' => '#3E7BA6', 'features' => ['elevator', 'parking', 'security']],
            ['code' => 'LUX-DEMO-006', 'type' => 'villa', 'agent' => 1, 'title' => 'فيلا على الواجهة البحرية في جدة', 'description' => 'فيلا مباشرة على الكورنيش بإطلالة بحرية ساحرة وتشطيبات فاخرة.', 'transaction' => TransactionType::Sale, 'status' => PropertyStatus::Draft, 'price' => 5200000, 'area' => 500, 'beds' => 6, 'baths' => 7, 'city' => 'جدة', 'district' => 'الشاطئ', 'address' => 'كورنيش جدة', 'latitude' => 21.6175, 'longitude' => 39.1125, 'color' => '#0F5E9D', 'features' => ['pool', 'garden', 'parking', 'security']],
            ['code' => 'LUX-DEMO-007', 'type' => 'floor', 'agent' => 2, 'title' => 'دور وسط صنعاء قرب الميدان', 'description' => 'دور عائلي بموقع مميز قرب الأسواق والخدمات في أمانة العاصمة.', 'transaction' => TransactionType::Rent, 'status' => PropertyStatus::Rejected, 'price' => 45000, 'area' => 210, 'beds' => 4, 'baths' => 2, 'city' => 'صنعاء', 'district' => 'التحرير', 'address' => 'جولة الميدان', 'latitude' => 15.3659, 'longitude' => 44.1967, 'color' => '#7C4A12', 'features' => ['security']],

            ['code' => 'LUX-DEMO-008', 'type' => 'townhouse', 'agent' => 1, 'title' => 'تاون هاوس المجمع السكني (أرشيف)', 'description' => 'مثال معروض في سلة المحذوفات لإظهار وظيفة الاستعادة.', 'transaction' => TransactionType::Sale, 'status' => PropertyStatus::Archived, 'price' => 1800000, 'area' => 280, 'beds' => 4, 'baths' => 4, 'city' => 'الرياض', 'district' => 'حي السفارات', 'address' => 'شارع السفارات', 'latitude' => 24.6967, 'longitude' => 46.6500, 'color' => '#5A5A5A', 'features' => ['parking', 'garden'], 'trashed' => true],
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
            $published = $listing['status'] === PropertyStatus::Published;
            $property = Property::withTrashed()->updateOrCreate(
                ['reference_code' => $listing['code']],
                [
                    'agent_id' => $agents[$listing['agent']]->id,
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
                    'is_furnished' => in_array($index, [1, 4], true),
                    'is_new' => $index > 3,
                    'is_featured' => $index < 2,
                    'published_at' => $published ? now()->subDays($index + 1) : null,
                    'deleted_at' => ! empty($listing['trashed']) ? now()->subDays(2) : null,
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

        // ---------------------------------------------------------------------
        // Viewing requests (every status + past/future dates)
        // ---------------------------------------------------------------------
        $viewingRequests = [
            ['property' => 0, 'client' => 0, 'agent' => 0, 'date' => now()->addDays(2)->toDateString(), 'time' => '17:30', 'notes' => 'أفضّل معاينة الحديقة قبل الغروب.', 'status' => ViewingRequestStatus::Pending],
            ['property' => 1, 'client' => 1, 'agent' => 0, 'date' => now()->addDays(1)->toDateString(), 'time' => '11:00', 'notes' => 'أود معرفة أجرة الصيانة السنوية.', 'status' => ViewingRequestStatus::Confirmed],
            ['property' => 2, 'client' => 0, 'agent' => 0, 'date' => now()->subDays(3)->toDateString(), 'time' => '16:00', 'notes' => 'تمت المعاينة واختيار العقار.', 'status' => ViewingRequestStatus::Completed],
            ['property' => 0, 'client' => 1, 'agent' => 0, 'date' => now()->subDays(5)->toDateString(), 'time' => '10:30', 'notes' => 'ألغى العميل الموعد.', 'status' => ViewingRequestStatus::Cancelled],
            ['property' => 4, 'client' => 2, 'agent' => 1, 'date' => now()->subDays(1)->toDateString(), 'time' => '13:00', 'notes' => 'تعذر الحضور.', 'status' => ViewingRequestStatus::Rejected],
        ];
        foreach ($viewingRequests as $vr) {
            ViewingRequest::query()->updateOrCreate(
                ['property_id' => $properties[$vr['property']]->id, 'client_id' => $clientUsers[$vr['client']]->id, 'agent_id' => $agents[$vr['agent']]->id],
                [
                    'scheduled_date' => $vr['date'],
                    'scheduled_time' => $vr['time'],
                    'notes' => $vr['notes'],
                    'status' => $vr['status'],
                ],
            );
        }

        // ---------------------------------------------------------------------
        // Conversations + messages
        // ---------------------------------------------------------------------
        $conversations = [
            ['property' => 0, 'client' => 0, 'agent_user' => 0, 'last' => now()->subMinutes(12), 'messages' => [
                [0, 'أهلًا مهند، موعد المعاينة مساء السبت متاح.', null, now()->subMinutes(12)],
            ]],
            ['property' => 4, 'client' => 2, 'agent_user' => 1, 'last' => now()->subHours(26), 'messages' => [
                [1, 'مرحبًا أسامة، الشقة متاحة للاستئجار السنوي.', null, now()->subHours(26)],
                [2, 'هل يمكن التفاوض على الإيجار قليلًا؟', null, now()->subHours(22)],
            ]],
            ['property' => 2, 'client' => 1, 'agent_user' => 0, 'last' => now()->subDays(2), 'messages' => [
                [0, 'ريم، يسعدنا متابعة طلب شراء التاون هاوس معك.', null, now()->subDays(2)],
                [1, 'شكرًا، سنكمل الإجراءات عبر الهاتف.', null, now()->subDays(2)->addHour()],
            ]],
        ];
        foreach ($conversations as $conv) {
            $conversation = Conversation::query()->updateOrCreate(
                ['property_id' => $properties[$conv['property']]->id, 'client_id' => $clientUsers[$conv['client']]->id, 'agent_id' => $agentUsers[$conv['agent_user']]->id],
                ['last_message_at' => $conv['last']],
            );
            foreach ($conv['messages'] as [$sender, $body, $readAt, $at]) {
                $message = Message::query()->firstOrCreate(
                    ['conversation_id' => $conversation->id, 'sender_id' => ($sender === 0 ? $agentUsers[$conv['agent_user']] : $clientUsers[$conv['client']])->id, 'body' => $body],
                    ['read_at' => $readAt],
                );
                if ($message->wasRecentlyCreated) {
                    $message->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
                }
            }
        }

        // ---------------------------------------------------------------------
        // Favorites
        // ---------------------------------------------------------------------
        $favorites = [[0, 1], [0, 4], [2, 4]];
        foreach ($favorites as [$userIdx, $propIdx]) {
            Favorite::query()->firstOrCreate(
                ['user_id' => $clientUsers[$userIdx]->id, 'property_id' => $properties[$propIdx]->id]
            );
        }

        // ---------------------------------------------------------------------
        // Activity logs (demo history so the control panel shows records).
        // Idempotent: keyed by (log_name, description).
        // ---------------------------------------------------------------------
        $logs = [
            ['setting', 'تم تهيئة الإعدادات الأساسية للنظام.'],
            ['user', 'تم إنشاء المستخدم «عبدالرحمن».'],
            ['user', 'تم إنشاء المستخدم «مهند».'],
            ['user', 'تم إنشاء المستخدم «ريم العتيبي».'],
            ['agent', 'تم إنشاء الوكيل «عبدالرحمن».'],
            ['agent', 'تم تحديث وكيل «عبدالله القحطاني».'],
            ['agent', 'تم إنشاء الوكيل «سارة الحمادي».'],
            ['property', 'تم إنشاء العقار «فيلا معاصرة بإطلالة هادئة» (LUX-DEMO-001).'],
            ['property', 'تم نشر العقار «فيلا معاصرة بإطلالة هادئة» (LUX-DEMO-001).'],
            ['property', 'تم إنشاء العقار «شقة بانورامية قرب مركز الأعمال» (LUX-DEMO-002).'],
            ['property', 'تم تحديث العقار «تاون هاوس بحديقة خاصة» (LUX-DEMO-003).'],
            ['property', 'تم إنشاء العقار «شقة كاملة الأثاث في حي العليا» (LUX-DEMO-005).'],
            ['property', 'تم نقل العقار «تاون هاوس المجمع السكني (أرشيف)» إلى سلة المحذوفات.'],
            ['viewing_request', 'تم تسجيل طلب معاينة لمتابعة العقار «فيلا معاصرة بإطلالة هادئة».'],
            ['viewing_request', 'تم تأكيد موعد معاينة «شقة بانورامية قرب مركز الأعمال».'],
            ['viewing_request', 'تم إكمال معاينة «تاون هاوس بحديقة خاصة».'],
            ['property_type', 'تم إنشاء نوع العقار «فيلا».'],
            ['property_feature', 'تم إنشاء الخاصية «مسبح».'],
            ['location', 'تم إنشاء الدولة «اليمن».'],
        ];
        foreach ($logs as $i => [$logName, $description]) {
            $log = ActivityLog::query()->firstOrCreate(
                ['log_name' => $logName, 'description' => $description],
                [
                    'user_id' => $adminUser->id,
                    'subject_type' => null,
                    'subject_id' => null,
                    'ip_address' => '10.0.0.1',
                    'user_agent' => 'Wajhatak/DemoSeeder',
                ],
            );
            if ($log->wasRecentlyCreated) {
                $ts = now()->subDays(intdiv($i, 6))->subMinutes(($i % 6) * 23);
                $log->forceFill(['created_at' => $ts, 'updated_at' => $ts])->save();
            }
        }

        // ---------------------------------------------------------------------
        // Branding placeholder images (sidebar logo + favicon)
        // ---------------------------------------------------------------------
        if (! Storage::disk('public')->exists('branding/logo.png')) {
            Storage::disk('public')->put('branding/logo.png', $this->brandPng(256));
        }
        if (! Storage::disk('public')->exists('branding/logo-small.png')) {
            Storage::disk('public')->put('branding/logo-small.png', $this->brandPng(96));
        }

        $this->command?->info("تم إنشاء بيانات LUX المحلية. دخول المدير: admin@wajhatak.app / LuxAdmin2026!، العملاء: client.demo@lux.local، client.reem@lux.local، client.osama@lux.local، والوكلاء: agent.demo@lux.local، agent.abdullah@lux.local، agent.sara@lux.local (كلمة المرور LuxDemo2026).");
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

    private function brandPng(int $size): string
    {
        $image = imagecreatetruecolor($size, $size);
        $emerald = imagecolorallocate($image, 14, 138, 109);
        $deep = imagecolorallocate($image, 7, 94, 74);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, $size, $size, $emerald);
        imagefilledpolygon($image, [0, 0, $size, 0, 0, $size], 3, $deep);

        $center = intdiv($size, 2);
        imagefilledellipse($image, $center, $center, intdiv($size, 2), intdiv($size, 2), $white);
        imagefilledellipse($image, $center, $center, intdiv($size, 3), intdiv($size, 3), $emerald);

        ob_start();
        imagepng($image);
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return $data;
    }
}