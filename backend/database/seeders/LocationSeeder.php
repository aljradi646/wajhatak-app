<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * يبني التسلسل الهرمي الحقيقي للمواقع:
 * الدولة ← المحافظة ← المدينة ← الحي/المديرية
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $yemen = Country::query()->updateOrCreate(
            ['code' => 'YE'],
            [
                'name_ar' => 'اليمن',
                'name_en' => 'Yemen',
                'currency_code' => 'YER',
                'is_active' => true,
            ],
        );

        $saudi = Country::query()->updateOrCreate(
            ['code' => 'SA'],
            [
                'name_ar' => 'السعودية',
                'name_en' => 'Saudi Arabia',
                'currency_code' => 'SAR',
                'is_active' => true,
            ],
        );

        foreach ($this->yemenGovernorates() as $governorate => $cities) {
            $region = Region::query()->firstOrCreate(
                ['name_ar' => $governorate, 'country_id' => $yemen->id],
                ['name_en' => $cities['en'], 'is_active' => true],
            );

            foreach ($cities['cities'] as $city => $areas) {
                $cityModel = City::query()->firstOrCreate(
                    ['name_ar' => $city, 'region_id' => $region->id],
                    ['name_en' => $city, 'is_active' => true],
                );

                foreach ($areas as $area) {
                    Area::query()->firstOrCreate(
                        ['name_ar' => $area, 'city_id' => $cityModel->id],
                        ['name_en' => $area, 'is_active' => true],
                    );
                }
            }
        }

        foreach ($this->saudiRegions() as $region => $cities) {
            $regionModel = Region::query()->firstOrCreate(
                ['name_ar' => $region, 'country_id' => $saudi->id],
                ['name_en' => $region, 'is_active' => true],
            );

            foreach ($cities as $city => $areas) {
                $cityModel = City::query()->firstOrCreate(
                    ['name_ar' => $city, 'region_id' => $regionModel->id],
                    ['name_en' => $city, 'is_active' => true],
                );

                foreach ($areas as $area) {
                    Area::query()->firstOrCreate(
                        ['name_ar' => $area, 'city_id' => $cityModel->id],
                        ['name_en' => $area, 'is_active' => true],
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array{en: string, cities: array<string, list<string>>}>
     */
    private function yemenGovernorates(): array
    {
        return [
            'أمانة العاصمة' => [
                'en' => 'Amanat Al Asimah',
                'cities' => [
                    'صنعاء' => [
                        'شعوب', 'الثورة', 'آزال', 'السفارة', 'معين',
                        'الصافية', 'الوحدة', 'التحرير', 'سبأ', 'بني الحارث',
                    ],
                ],
            ],
            'محافظة صنعاء' => [
                'en' => 'Sanaa Governorate',
                'cities' => [
                    'صنعاء القديمة' => ['باب اليمن', 'القاع'],
                    'خولان' => ['خولان العالية'],
                    'همدان' => ['همدان'],
                    'أرحب' => ['أرحب'],
                    'بني مطر' => ['بني مطر'],
                ],
            ],
            'عدن' => [
                'en' => 'Aden',
                'cities' => [
                    'عدن' => ['كريتر', 'التواهي', 'المعلا', 'الشيخ عثمان', 'دار سعد', 'خور مكسر', 'المنصورة', 'دار الشوك'],
                ],
            ],
            'تعز' => [
                'en' => 'Taiz',
                'cities' => [
                    'تعز' => ['المظفر', 'صالة', 'القاهرة', 'حيف', 'المعلا'],
                    'التربة' => ['التربة'],
                    'المخا' => ['المخا'],
                ],
            ],
            'الحديدة' => [
                'en' => 'Hodeidah',
                'cities' => [
                    'الحديدة' => ['الحوك', 'المنصورية', 'الثورة'],
                    'باجل' => ['باجل'],
                    'الصليف' => ['الصليف'],
                ],
            ],
            'حضرموت' => [
                'en' => 'Hadhramaut',
                'cities' => [
                    'المكلا' => ['المكلا الشرقية', 'المكلا الغربية', 'فوه'],
                    'سيئون' => ['سيئون'],
                    'تريم' => ['تريم'],
                    'شبام' => ['شبام'],
                ],
            ],
            'إب' => [
                'en' => 'Ibb',
                'cities' => [
                    'إب' => ['إب القديمة', 'النهضة', 'الرصافة'],
                    'يريم' => ['يريم'],
                    'الظهار' => ['الظهار'],
                ],
            ],
            'ذمار' => [
                'en' => 'Dhamar',
                'cities' => [
                    'ذمار' => ['ذمار القديمة', 'الزاهر'],
                    'عنس' => ['عنس'],
                ],
            ],
            'لحج' => [
                'en' => 'Lahij',
                'cities' => [
                    'الحوطة' => ['الحوطة'],
                    'طور الباحة' => ['طور الباحة'],
                ],
            ],
            'أبين' => [
                'en' => 'Abyan',
                'cities' => [
                    'زنجبار' => ['زنجبار'],
                    'لودر' => ['لودر'],
                    'شقرة' => ['شقرة'],
                ],
            ],
            'شبوة' => [
                'en' => 'Shabwah',
                'cities' => [
                    'عتق' => ['عتق'],
                    'المكلا الصغرى' => ['نصاب'],
                ],
            ],
            'مأرب' => [
                'en' => 'Marib',
                'cities' => [
                    'مأرب' => ['مأرب المدينة', 'الوادي'],
                ],
            ],
            'صعدة' => [
                'en' => 'Saada',
                'cities' => [
                    'صعدة' => ['صعدة القديمة', 'الصفرة'],
                ],
            ],
            'الجوف' => [
                'en' => 'Al Jawf',
                'cities' => [
                    'الحزم' => ['الحزم'],
                ],
            ],
            'عمران' => [
                'en' => 'Amran',
                'cities' => [
                    'عمران' => ['عمران'],
                    'حجة' => ['حجة'],
                ],
            ],
            'المحويت' => [
                'en' => 'Al Mahwit',
                'cities' => [
                    'المحويت' => ['المحويت'],
                ],
            ],
            'حجة' => [
                'en' => 'Hajjah',
                'cities' => [
                    'حجة' => ['حجة المدينة', 'عبس'],
                ],
            ],
            'البيضاء' => [
                'en' => 'Al Bayda',
                'cities' => [
                    'البيضاء' => ['البيضاء'],
                ],
            ],
            'الضالع' => [
                'en' => 'Al Dhale',
                'cities' => [
                    'الضالع' => ['الضالع'],
                ],
            ],
            'المهرة' => [
                'en' => 'Al Mahrah',
                'cities' => [
                    'الغيضة' => ['الغيضة'],
                ],
            ],
            'سقطرى' => [
                'en' => 'Socotra',
                'cities' => [
                    'حديبو' => ['حديبو'],
                ],
            ],
            'ريمة' => [
                'en' => 'Raimah',
                'cities' => [
                    'الجعاشن' => ['الجعاشن'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function saudiRegions(): array
    {
        return [
            'منطقة الرياض' => [
                'الرياض' => ['العليا', 'الملقا', 'النرجس', 'حي السفارات', 'الياسمين', 'الروضة'],
                'الخرج' => ['الخرج'],
            ],
            'منطقة مكة المكرمة' => [
                'جدة' => ['الرويس', 'الشاطئ', 'العدل', 'الزهراء', 'الحمراء'],
                'مكة المكرمة' => ['العزيزية', 'الشوقية'],
                'الطائف' => ['الطائف'],
            ],
            'المنطقة الشرقية' => [
                'الدمام' => ['الفيصلية', 'الشاطئ الغربي'],
                'الخبر' => ['العقربية', 'الراكة'],
                'الظهران' => ['الظهران'],
            ],
            'منطقة عسير' => [
                'أبها' => ['أبها'],
                'خميس مشيط' => ['خميس مشيط'],
            ],
            'منطقة المدينة المنورة' => [
                'المدينة المنورة' => ['المدينة المنورة'],
            ],
        ];
    }
}
