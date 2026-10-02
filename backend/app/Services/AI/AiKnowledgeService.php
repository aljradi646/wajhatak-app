<?php

namespace App\Services\AI;

/**
 * قاعدة معرفة المنصة Grounded Application Knowledge Base
 */
class AiKnowledgeService
{
    private const KNOWLEDGE_BASE = [
        [
            'topic' => 'البحث والتصفح',
            'keywords' => ['بحث', 'شقة', 'عقار', 'موقع', 'مدينة', 'سعر'],
            'content' => 'يمكنك البحث عن العقارات في التطبيق باستخدام شريط البحث العادي أو المساعد الذكي. يمكنك تحديد المدينة، نوع العقار (شقة، فيلا، أرض)، السعر، وعدد الغرف.',
            'target_screen' => 'SearchScreen',
        ],
        [
            'topic' => 'تعديل البيانات الشخصية',
            'keywords' => ['تعديل', 'ملف', 'حساب', 'بيانات', 'اسم', 'كلمة السر'],
            'content' => 'لتعديل بياناتك الشخصية: انتقل إلى تبويب «الملف الشخصي» من الشريط السفلي، ثم اضغط على «تعديل الملف الشخصي» لتغيير الاسم أو رقم الهاتف أو كلمة المرور.',
            'target_screen' => 'ProfileScreen',
        ],
        [
            'topic' => 'طلب معاينة عقار',
            'keywords' => ['معاينة', 'حجز', 'زيارة', 'موعد'],
            'content' => 'لمعاينة عقار: افتح صفحة العقار واضغط زر «طلب معاينة»، حدد التاريخ والوقت المناسبين ثم أكد الطلب ليتم إرساله للوكيل المسؤول.',
            'target_screen' => 'PropertyDetailsScreen',
        ],
        [
            'topic' => 'مراسلة الوكيل',
            'keywords' => ['وكيل', 'مراسلة', 'محادثة', 'صاحب العقار', 'اتصال'],
            'content' => 'يمكنك مراسلة الوكيل مباشرة من صفحة العقار بالضغط على زر «تواصل مع الوكيل» لفتح محادثة مباشرة داخل التطبيق.',
            'target_screen' => 'ChatScreen',
        ],
    ];

    /**
     * الاستعلام الدلالي البسيط فوق معرفة التطبيق موجهة حسب دور المستخدم.
     */
    public function searchKnowledge(string $query, string $role = 'client'): array
    {
        $normalized = mb_strtolower(trim($query));
        $matches = [];

        foreach (self::KNOWLEDGE_BASE as $item) {
            foreach ($item['keywords'] as $kw) {
                if (str_contains($normalized, mb_strtolower($kw))) {
                    $matches[] = $item;
                    break;
                }
            }
        }

        return $matches !== [] ? $matches : self::KNOWLEDGE_BASE;
    }
}
