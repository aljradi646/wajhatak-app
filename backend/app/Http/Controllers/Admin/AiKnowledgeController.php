<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiKnowledgeArticle;
use App\Models\ActivityLog;
use App\Services\AI\AiKnowledgeService;
use App\Services\AI\AiSchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiKnowledgeController extends Controller
{
    private const ROLES = ['client', 'agent', 'admin'];

    public function __construct(
        private readonly AiKnowledgeService $knowledge,
        private readonly AiSchemaService $schema,
    ) {}

    public function index(Request $request): View
    {
        $this->schema->ensure();
        $this->knowledge->syncBuiltInArticles();

        $editing = null;
        if ($request->filled('edit')) {
            $editing = AiKnowledgeArticle::query()->findOrFail($request->integer('edit'));
        }

        return view('admin.ai.knowledge', [
            'articles' => AiKnowledgeArticle::query()
                ->orderBy('priority')
                ->orderByDesc('updated_at')
                ->paginate(20)
                ->withQueryString(),
            'editing' => $editing,
            'sections' => AiAssistantController::SETTINGS_SECTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $article = AiKnowledgeArticle::query()->create($data + ['version' => 1]);

        ActivityLog::record('ai', "تم إنشاء مادة معرفة للمساعد «{$article->topic}»");

        return redirect()
            ->route('admin.ai.knowledge.index')
            ->with('status', 'تمت إضافة مادة المعرفة وحفظها في قاعدة البيانات.');
    }

    public function update(Request $request, AiKnowledgeArticle $article): RedirectResponse
    {
        $data = $this->validatedData($request, $article);
        $data['version'] = (int) $article->version + 1;
        $article->update($data);

        ActivityLog::record('ai', "تم تحديث مادة المعرفة «{$article->topic}» إلى النسخة {$article->version}");

        return redirect()
            ->route('admin.ai.knowledge.index', ['edit' => $article->id])
            ->with('status', 'تم حفظ التعديلات. سيستخدم المساعد المحتوى المحدّث في الطلبات التالية.');
    }

    public function updateStatus(Request $request, AiKnowledgeArticle $article): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);
        $active = $request->boolean('is_active');

        if ($active !== (bool) $article->is_active) {
            $article->update([
                'is_active' => $active,
                'version' => (int) $article->version + 1,
            ]);
            ActivityLog::record('ai', ($active ? 'تم تفعيل' : 'تم إيقاف').' مادة المعرفة «'.$article->topic.'»');
        }

        return redirect()
            ->route('admin.ai.knowledge.index')
            ->with('status', $active ? 'تم تفعيل مادة المعرفة.' : 'تم إيقاف مادة المعرفة دون حذفها.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?AiKnowledgeArticle $article = null): array
    {
        $request->merge(['slug' => strtolower(trim((string) $request->input('slug', '')))]);
        $slugRules = ['required', 'string', 'max:120', 'alpha_dash'];
        $slugRules[] = $article
            ? Rule::unique('ai_knowledge_articles', 'slug')->ignore($article->id)
            : Rule::unique('ai_knowledge_articles', 'slug');

        $validated = $request->validate([
            'slug' => $slugRules,
            'topic' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'min:10', 'max:12000'],
            'keywords_text' => [
                'required', 'string', 'max:2000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $keywords = collect(preg_split('/[,،\r\n]+/u', (string) $value) ?: [])
                        ->map(fn (string $keyword) => trim($keyword))
                        ->filter(fn (string $keyword) => $keyword !== '')
                        ->unique(fn (string $keyword) => mb_strtolower($keyword));
                    return [
            'slug' => strtolower((string) $validated['slug']),
            'topic' => trim((string) $validated['topic']),
            'content' => trim((string) $validated['content']),
            'keywords' => $keywords->all(),
            'roles' => array_values(array_unique($validated['roles'])),
            'target_screen' => isset($validated['target_screen']) && trim((string) $validated['target_screen']) !== ''
                ? trim((string) $validated['target_screen'])
                : null,
            'priority' => max(0, min(10000, (int) ($validated['priority'] ?? 100))),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
