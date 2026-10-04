<?php

namespace App\Services\Mail;

final class EmailTemplateVariableRegistry
{
    /**
     * @return array<string, array{type:string,description:string,preview:mixed}>
     */
    public static function definitions(): array
    {
        return [
            'user.name' => ['type' => 'string', 'description' => 'اسم المستخدم', 'preview' => 'أحمد محمد'],
            'user.email' => ['type' => 'email', 'description' => 'بريد المستخدم', 'preview' => 'ahmed@example.com'],
            'property.title' => ['type' => 'string', 'description' => 'عنوان العقار', 'preview' => 'شقة حديثة في صنعاء'],
            'property.price' => ['type' => 'amount', 'description' => 'سعر العقار', 'preview' => '100,000 YER'],
            'property.city' => ['type' => 'string', 'description' => 'مدينة العقار', 'preview' => 'صنعاء'],
            'property.reference_code' => ['type' => 'string', 'description' => 'الرمز المرجعي للعقار', 'preview' => 'WJ-10024'],
            'agent.name' => ['type' => 'string', 'description' => 'اسم الوكيل', 'preview' => 'محمد الجرادي'],
            'agent.phone' => ['type' => 'phone', 'description' => 'هاتف الوكيل', 'preview' => '+967700000000'],
            'app.url' => ['type' => 'url', 'description' => 'رابط المنصة', 'preview' => rtrim((string) config('app.url'), '/')],
            'app.logo_url' => ['type' => 'url', 'description' => 'رابط شعار المنصة للبريد', 'preview' => ''],
            'app.name' => ['type' => 'string', 'description' => 'اسم المنصة', 'preview' => (string) config('app.name', 'وجهتك')],
            'name' => ['type' => 'string', 'description' => 'اسم المستخدم (صيغة قديمة)', 'preview' => 'أحمد محمد'],
            'email' => ['type' => 'email', 'description' => 'البريد الإلكتروني (صيغة قديمة)', 'preview' => 'ahmed@example.com'],
            'property' => ['type' => 'string', 'description' => 'العقار (صيغة قديمة)', 'preview' => 'شقة حديثة في صنعاء'],
            'code' => ['type' => 'string', 'description' => 'رمز التحقق', 'preview' => '123456'],
            'ttl' => ['type' => 'number', 'description' => 'صلاحية الرمز بالدقائق', 'preview' => '15'],
            'reason' => ['type' => 'string', 'description' => 'سبب الرفض أو الإجراء', 'preview' => 'بيانات ناقصة'],
        ];
    }

    public static function previewValues(array $overrides = []): array
    {
        $values = [];
        foreach (self::definitions() as $key => $definition) {
            $values[$key] = array_key_exists($key, $overrides) ? $overrides[$key] : $definition['preview'];
        }

        return $values;
    }
}
