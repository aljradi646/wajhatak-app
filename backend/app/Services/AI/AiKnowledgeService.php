<?php

namespace App\Services\AI;

/**
 * معرفة المنصة المعتمدة. لا تُستخدم كبديل لبيانات العقارات التشغيلية.
 */
class AiKnowledgeService
{
    public const VERSION = '2026-10-04.1';

    private const KNOWLEDGE_BASE = [
        [
            'id' => 'search-and-browse',
            'topic' => 'البحث والتصفح',
            'keywords' => ['بحث', 'شقه', 'عقار', 'موقع', 'مدينه', 'سعر', 'فلاتر', 'فلتر'],
            'content' => 'يمكنك البحث عن العقارات من شاشة البحث، أو استخدام المساعد لطلب العقار باللغة الطبيعية. يمكن تضييق البحث بحسب المدينة والمنطقة ونوع العقار والسعر وعدد الغرف والخصائص المتاحة.',
            'target_screen' => 'SearchScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'profile',
            'topic' => 'الحساب والملف الشخصي',
            'keywords' => ['ملف', 'حساب', 'بيانات', 'اسم', 'كلمه المرور', 'كلمة السر'],
            'content' => 'توجد إعدادات الملف الشخصي لإدارة بيانات الحساب. بالنسبة لكلمة المرور، استخدم مسار استعادة/تغيير كلمة المرور في واجهة الحساب بدل مشاركة كلمة المرور مع أي جهة.',
            'target_screen' => 'ProfileScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
        [
            'id' => 'viewing',
            'topic' => 'طلب المعاينة',
            'keywords' => ['معاينه', 'حجز', 'زياره', 'موعد'],
            'content' => 'لطلب معاينة عقار، افتح صفحة العقار واستخدم إجراء طلب المعاينة إن كان متاحًا، ثم حدد الموعد وأكّد الطلب. لا يُعتبر الطلب منفذًا إلا بعد نجاح العملية من النظام.',
            'target_screen' => 'PropertyDetailsScreen',
            'roles' => ['client'],
        ],
        [
            'id' => 'agent-contact',
            'topic' => 'التواصل مع الوكيل',
            'keywords' => ['وكيل', 'مراسله', 'اتصال', 'تواصل'],
            'content' => 'بيانات التواصل المعروضة داخل التطبيق هي المرجع المعتمد. افتح صفحة العقار واستخدم خيارات التواصل المتاحة فعليًا في الواجهة.',
            'target_screen' => 'PropertyDetailsScreen',
            'roles' => ['client', 'agent', 'admin'],
        ],
    ];

    public function version(): string
    {
        return self::VERSION;
    }

    public function overview(string $role = 'client'): array
    {
        return [
            [
                'id' => 'platform-overview',
                'topic' => 'منصة وجهتك',
                'content' => 'وجهتك منصة عقارية تساعد المستخدم على استكشاف العقارات المتاحة، الاطلاع على تفاصيلها، والتعامل مع إجراءات المنصة التي يسمح بها الحساب الحالي.',
                'target_screen' => null,
                'version' => self::VERSION,
            ],
        ];
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

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, 5);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;
        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);
    }
}
