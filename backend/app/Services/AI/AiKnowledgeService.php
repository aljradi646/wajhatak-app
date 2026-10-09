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
    public const VERSION = '2026-10-09.1';

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
     * يضمن وجود نسخة قابلة للتحرير من الأسئلة المضمنة دون استبدال أي تعديل سابق.
     * يُستدعى من شاشة إدارة المعرفة فقط؛ تبقى المعرفة المضمنة متاحة إن لم تُنفذ الهجرة.
     */
    public function syncBuiltInArticles(): int
    {
        try {
            if (! Schema::hasTable('ai_knowledge_articles')) {
                return 0;
            }

            $created = 0;
            foreach (self::KNOWLEDGE_BASE as $item) {
                $article = AiKnowledgeArticle::query()->firstOrCreate(
                    ['slug' => $item['id']],
                    [
                        'topic' => $item['topic'],
                        'content' => $item['content'],
                        'keywords' => $item['keywords'],
                        'roles' => $item['roles'],
                        'target_screen' => $item['target_screen'],
                        'is_active' => true,
                        'priority' => 100,
                        'version' => 1,
                    ],
                );
                if ($article->wasRecentlyCreated) {
                    $created++;
                }
            }

            return $created;
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * المعرفة المضمنة تعمل كقيمة افتراضية، ويمكن تحريرها من لوحة التحكم.
     * المقالات المخزنة تتجاوز الافتراضي ذي slug نفسه؛ المقالات الأخرى تضاف إليه.
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
            // المرادفات العربية قد تتكرر بصيغ إملائية مختلفة (مثل ة/ه).
            // احتسب الكلمة المعيارية مرة واحدة حتى لا تفوز مادة لمجرد التكرار.
            $matchedKeywords = [];
            foreach ((array) ($item['keywords'] ?? []) as $keyword) {
                $needle = $this->normalize((string) $keyword);
                if ($needle !== '' && str_contains($normalized, $needle)) {
                    $matchedKeywords[$needle] = true;
                }
            }
            $score = count($matchedKeywords);

            if ($score > 0) {
                unset($item['slug'], $item['is_active'], $item['is_builtin']);
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
        $entries = [];
        $builtInSlugs = array_column(self::KNOWLEDGE_BASE, 'id');

        foreach (self::KNOWLEDGE_BASE as $item) {
            if (! in_array($role, $item['roles'], true)) {
                continue;
            }

            $item['version'] = $this->version();
            $item['priority'] = 100;
            $entries[$item['id']] = $item;
        }

        foreach ($this->databaseEntries() as $article) {
            $slug = (string) $article['slug'];
            $isBuiltIn = in_array($slug, $builtInSlugs, true);

            if ($isBuiltIn) {
                // وجود سجل مخصص لهذا slug يعني أن قرارات التفعيل والأدوار
                // والمحتوى في قاعدة البيانات هي المرجع، حتى إذا عُطّلت المادة.
                unset($entries[$slug]);
                if (! $article['is_active'] || ! in_array($role, $article['roles'], true)) {
                    continue;
                }

                $article['id'] = $slug;
                $entries[$slug] = $article;
                continue;
            }

            if (! $article['is_active'] || ! in_array($role, $article['roles'], true)) {
                continue;
            }

            $article['id'] = 'article-'.$article['id'];
            $entries[$article['id']] = $article;
        }

        return array_values($entries);
    }

    /**
     * تضمين المقالات المضمنة حتى لو أوقفها المدير كي لا تظهر النسخة الافتراضية
     * مجددًا؛ أما المقالات الإضافية المتوقفة فتبقى خارج البحث.
     *
     * @return list<array<string, mixed>>
     */
    private function databaseEntries(): array
    {
        try {
            if (! Schema::hasTable('ai_knowledge_articles')) {
                return [];
            }

            $builtInSlugs = array_column(self::KNOWLEDGE_BASE, 'id');

            // لا تسمح لعدد كبير من المقالات المخصصة بإقصاء تعديل سؤال مضمن
            // من الاسترجاع: حمّل التعديلات التسعة بصورة مستقلة، ثم حدّ المخصصات.
            $builtIn = AiKnowledgeArticle::query()->whereIn('slug', $builtInSlugs)->get();
            $custom = AiKnowledgeArticle::query()
                ->whereNotIn('slug', $builtInSlugs)
                ->where('is_active', true)
                ->orderBy('priority')
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();

            return $builtIn->concat($custom)
                ->map(fn (AiKnowledgeArticle $article) => [
                    'id' => (int) $article->id,
                    'slug' => (string) $article->slug,
                    'topic' => (string) $article->topic,
                    'content' => (string) $article->content,
                    'keywords' => (array) $article->keywords,
                    'target_screen' => $article->target_screen,
                    'roles' => (array) $article->roles,
                    'priority' => (int) $article->priority,
                    'version' => 'article-'.$article->id.'-v'.$article->version,
                    'is_active' => (bool) $article->is_active,
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
