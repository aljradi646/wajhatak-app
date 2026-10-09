<?php

namespace App\Services\AI;

use App\Models\AiKnowledgeArticle;
use Illuminate\Support\Facades\Schema;
use Throwable;

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

    private ?string $resolvedVersion = null;

    public function version(): string
    {
        if ($this->resolvedVersion !== null) {
            return $this->resolvedVersion;
        }

        $version = self::VERSION;
        try {
            if (Schema::hasTable('ai_knowledge_articles')) {
                $latest = AiKnowledgeArticle::query()->max('updated_at');
                if (is_string($latest) && $latest !== '') {
                    $version .= '+'.substr(hash('sha256', $latest), 0, 8);
                }
            }
        } catch (Throwable) {
            // Use the built-in version if the database table is not ready.
        }

        return $this->resolvedVersion = $version;
    }

    /**
     * المعرفة الثابتة تبقى مرجعًا افتراضيًا؛ المقالات النشطة المضافة من لوحة التحكم
     * تُحمّل من قاعدة البيانات وتُفلتر حسب الدور الحالي.
     *
     * @return list<array<string, mixed>>
     */
    public function overview(string $role = 'client'): array
    {
        $items = $this->entriesForRole($role);
        return array_values(array_map(
            fn (array $item) => [
                'id' => $item['id'],
                'topic' => $item['topic'],
                'content' => $item['content'],
                'target_screen' => $item['target_screen'] ?? null,
                'version' => $item['version'] ?? $this->version(),
            ],
            $items,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchKnowledge(string $query, string $role = 'client'): array
    {
        $normalized = $this->normalize($query);
        if ($normalized === '') {
            return [];
        }

        $results = [];
        foreach ($this->entriesForRole($role) as $item) {
            $score = 0;
            foreach ((array) ($item['keywords'] ?? []) as $keyword) {
                $needle = $this->normalize((string) $keyword);
                if ($needle !== '' && str_contains($normalized, $needle)) {
                    $score++;
                }
            }

            if ($score > 0) {
                $item['version'] = $item['version'] ?? $this->version();
                $item['score'] = $score;
                $results[] = $item;
            }
        }

        usort($results, static fn (array $a, array $b) =>
            ($b['score'] <=> $a['score']) ?: ((int) ($a['priority'] ?? 100) <=> (int) ($b['priority'] ?? 100))
        );

        return array_slice($results, 0, 5);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entriesForRole(string $role): array
    {
        $role = strtolower(trim($role)) ?: 'client';
        $builtIn = array_values(array_map(
            fn (array $item) => $item + ['version' => $this->version(), 'priority' => 100],
            array_filter(self::KNOWLEDGE_BASE, fn (array $item) => in_array($role, $item['roles'], true)),
        ));

        return array_values(array_merge($builtIn, $this->databaseEntries($role)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function databaseEntries(string $role): array
    {
        try {
            if (! Schema::hasTable('ai_knowledge_articles')) {
                return [];
            }

            return AiKnowledgeArticle::query()
                ->where('is_active', true)
                ->orderBy('priority')
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get()
                ->filter(fn (AiKnowledgeArticle $article) => in_array($role, (array) $article->roles, true))
                ->map(fn (AiKnowledgeArticle $article) => [
                    'id' => 'article-'.$article->id,
                    'topic' => $article->topic,
                    'content' => $article->content,
                    'keywords' => (array) $article->keywords,
                    'target_screen' => $article->target_screen,
                    'roles' => (array) $article->roles,
                    'priority' => (int) $article->priority,
                    'version' => 'article-'.$article->id.'-v'.$article->version,
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;

        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);
    }
}
