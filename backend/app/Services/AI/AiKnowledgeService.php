<?php

namespace App\Services\AI;

/**
 * معرفة المنصة المعتمدة.
 *
 * هذه الطبقة تجيب عن استخدام التطبيق وسياساته، ولا تُستخدم كمصدر
 * لحالة العقارات أو الأسعار أو التوفر.
 */
class AiKnowledgeService
{
    public const VERSION = '2026-10-04.2';

    private const KNOWLEDGE_BASE = [
        [
            'id' => 'platform-overview',
            'topic' => 'منصة وجهتك',
            'keywords' => ['وجهتك', 'المنصه', 'المنصة', 'التطبيق', 'خدماتكم', 'الخدمات'],
            'content' => 'وجهتك منصة عقارية تساعد المستخدم على استكشاف العقارات المتاحة، فتح تفاصيلها، حفظها، والتعامل مع إجراءات المنصة المسموح بها بحسب نوع الحساب.',
            'target_screen' => null,
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'search-and-filters',
            'topic' => 'البحث والفلاتر',
            'keywords' => ['بحث', 'ابحث', 'استكشاف', 'شقه', 'عقار', 'فلاتر', 'فلتر', 'سعر', 'مدينة', 'منطقه', 'غرف'],
            'content' => 'يمكنك البحث من شاشة «استكشاف» باستخدام النص الطبيعي أو الفلاتر المتاحة، مثل المدينة والمنطقة ونوع العقار والبيع أو الإيجار والسعر وعدد الغرف والمساحة والتأثيث عندما تكون هذه الحقول متاحة للعقار.',
            'target_screen' => 'SearchScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'account-and-login',
            'topic' => 'الحساب وتسجيل الدخول',
            'keywords' => ['حساب', 'تسجيل الدخول', 'تسجيل حساب', 'دخول', 'ملف', 'الملف الشخصي'],
            'content' => 'أنشئ حسابك وسجّل الدخول من واجهة المصادقة، ثم أدِر بياناتك من «الملف الشخصي». لا تتم مشاركة كلمات المرور مع المساعد أو أي مستخدم آخر.',
            'target_screen' => 'AuthScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'favorites',
            'topic' => 'المفضلة والحفظ',
            'keywords' => ['المفضله', 'المفضلة', 'مفضلتي', 'حفظ', 'احفظ', 'القلب'],
            'content' => 'لحفظ عقار، استخدم إجراء المفضلة/القلب من بطاقة العقار أو صفحة تفاصيله. يمكنك الوصول إلى العناصر المحفوظة من قسم «المفضلة».',
            'target_screen' => 'SavedScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'property-details',
            'topic' => 'تفاصيل العقار',
            'keywords' => ['تفاصيل العقار', 'معلومات العقار', 'صفحة العقار', 'الخريطة', 'الصور'],
            'content' => 'من بطاقة العقار يمكنك فتح صفحة التفاصيل لرؤية البيانات الحالية التي ينشرها النظام، والصور والموقع والخصائص المتاحة وأدوات التواصل المدعومة.',
            'target_screen' => 'PropertyDetailsScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'agent-contact',
            'topic' => 'التواصل مع الوكيل',
            'keywords' => ['الوكيل', 'المعلن', 'تواصل', 'مراسله', 'مراسلة', 'اتصال', 'هاتف الوكيل'],
            'content' => 'للتواصل مع الوكيل استخدم وسائل التواصل العامة الظاهرة داخل تفاصيل العقار أو المحادثات المدعومة. لا يعرض المساعد بيانات خاصة غير منشورة للعامة.',
            'target_screen' => 'ChatScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'viewing-request',
            'topic' => 'طلبات المعاينة',
            'keywords' => ['معاينه', 'معاينة', 'طلب معاينه', 'موعد', 'زيارة', 'الطلبات'],
            'content' => 'لطلب معاينة عقار، افتح تفاصيل العقار واستخدم إجراء طلب المعاينة، ثم حدد الموعد وأكّد الطلب. التنفيذ الحقيقي لا يتم إلا بعد نجاح العملية في النظام.',
            'target_screen' => 'PropertyDetailsScreen',
            'roles' => ['client'],
        ],
        [
            'id' => 'agent-listings',
            'topic' => 'إضافة وإدارة العقارات للوكيل',
            'keywords' => ['اضافة عقار', 'إضافة عقار', 'عقاراتي', 'ادارة العقار', 'إدارة العقار', 'تعديل عقار'],
            'content' => 'حساب الوكيل الموثق يستطيع استخدام «عقاراتي» لإضافة العقار أو إدارة البيانات التي تسمح بها المنصة، ثم يمر العرض بحالة النشر المطبقة في النظام.',
            'target_screen' => 'AgentListingsScreen',
            'roles' => ['agent', 'admin'],
        ],
        [
            'id' => 'notifications-and-messages',
            'topic' => 'الإشعارات والرسائل',
            'keywords' => ['اشعارات', 'الإشعارات', 'الاشعارات', 'رسائل', 'الرسائل', 'محادثات'],
            'content' => 'ستجد الإشعارات والرسائل في الأقسام المخصصة داخل التطبيق. تفاصيل الرسائل والحسابات الأخرى لا تصبح متاحة للمساعد إلا ضمن الصلاحيات المطبقة.',
            'target_screen' => 'NotificationsScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
    ];

    public function version(): string
    {
        return self::VERSION;
    }

    public function overview(string $role = 'client'): array
    {
        $role = strtolower($role ?: 'client');

        return array_values(array_map(
            fn (array $item) => [
                'id' => $item['id'],
                'topic' => $item['topic'],
                'content' => $item['content'],
                'target_screen' => $item['target_screen'],
                'version' => self::VERSION,
            ],
            array_filter(self::KNOWLEDGE_BASE, fn (array $item) => in_array($role, $item['roles'], true)),
        ));
    }

    public function searchKnowledge(string $query, string $role = 'client'): array
    {
        $normalized = $this->normalize($query);
        $role = strtolower($role ?: 'client');
        $results = [];

        foreach (self::KNOWLEDGE_BASE as $item) {
            if (! in_array($role, $item['roles'], true)) {
                continue;
            }

            $score = 0;
            foreach ($item['keywords'] as $keyword) {
                if (str_contains($normalized, $this->normalize($keyword))) {
                    $score++;
                }
            }

            if ($score > 0) {
                $item['version'] = self::VERSION;
                $item['score'] = $score;
                $results[] = $item;
            }
        }

        usort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, 5);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;

        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);
    }
}
