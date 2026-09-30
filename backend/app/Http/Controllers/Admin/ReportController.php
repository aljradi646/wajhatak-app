<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\ReportLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\ViewingRequest;
use App\Services\Reports\PdfReportRenderer;
use App\Services\Reports\ReportExporter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    /**
     * مرشحات كل تقرير — تُستخدم للتحقق من المُدخلات وحفظها في سجل التقارير
     * حتى يمكن إعادة توليد نفس التقرير لاحقًا.
     */
    private const FILTER_KEYS = [
        'agents' => ['status'],
        'properties' => ['status', 'type', 'agent'],
        'requests' => ['status', 'agent'],
        'users' => ['role', 'user'],
    ];

    public function __construct(
        private readonly ReportExporter $exporter,
        private readonly PdfReportRenderer $pdf,
    ) {}

    public function index()
    {
        return view('admin.reports.index', [
            'totals' => [
                'agents' => Agent::query()->count(),
                'agents_active' => Agent::query()->where('is_active', true)->count(),
                'properties' => Property::query()->count(),
                'properties_published' => Property::query()->where('status', 'published')->count(),
                'requests' => ViewingRequest::query()->count(),
                'requests_pending' => ViewingRequest::query()->where('status', 'pending')->count(),
                'users' => User::query()->count(),
                'users_active' => User::query()->where('is_active', true)->count(),
            ],
            'propertyTypes' => PropertyType::query()->where('is_active', true)->get(['id', 'slug', 'name_ar']),
            'recentLogs' => ReportLog::query()->ownedBy(request()->user())->with('user')->latest('id')->limit(5)->get(),
        ]);
    }

    public function show(string $type)
    {
        $format = $this->normalizeFormat(request('format', 'html'));
        $data = $this->buildReport($type, $this->extractFilters($type, request()->all()));
        $fmt = $this->formatter($data['currency'] ?? 'YER');

        $response = match ($format) {
            'pdf' => $this->renderPdf($data, $fmt),
            'excel' => $this->downloadExcel($data, $fmt),
            'csv' => $this->downloadCsv($data, $fmt),
            'json' => $this->downloadJson($data, $fmt),
            default => view('admin.reports.section', ['report' => $data, 'fmt' => $fmt]),
        };

        // كل تقرير يُولَّد فعليًا يُسجَّل بمالكه ونوعه وصيغته ومرشحاته.
        $this->recordLog($type, $format, $data);

        return $response;
    }

    /** سجل التقارير: تصفح كل التقارير المُولَّدة سابقًا مع إمكانية إعادة فتحها. */
    public function logs(Request $request)
    {
        $query = ReportLog::query()->ownedBy($request->user())->with('user')->latest('id');

        $type = $request->input('type');
        if (is_string($type) && in_array($type, ReportLog::TYPES, true)) {
            $query->where('type', $type);
        }

        return view('admin.reports.logs', [
            'logs' => $query->paginate(20)->withQueryString(),
            'typeFilter' => is_string($type) ? $type : null,
            'summary' => [
                'total' => ReportLog::query()->count(),
                'byType' => ReportLog::query()->selectRaw('type, count(*) as aggregate')->groupBy('type')->pluck('aggregate', 'type'),
                'byFormat' => ReportLog::query()->selectRaw('format, count(*) as aggregate')->groupBy('format')->pluck('aggregate', 'format'),
            ],
        ]);
    }

    /**
     * إعادة توليد تقرير سابق بنفس صيغته ومرشحاته.
     * البيانات تُقرأ من قاعدة البيانات لحظة الطلب (لا نُخزّن نسخًا قديمة).
     */
    public function download(Request $request, ReportLog $reportLog)
    {
        // عزل الملكية: غير المشرف لا يفتح تقارير غيره.
        $user = $request->user();
        if ($user && ! $user->hasRole('admin') && $reportLog->user_id !== $user->id) {
            abort(403, 'لا يمكنك الوصول إلى تقرير أنشأه مستخدم آخر.');
        }

        // تقارير الوكيل لها مسارها الخاص في الـ API وتُبنى ببيانات الوكيل فقط.
        abort_unless(in_array($reportLog->type, ReportLog::ADMIN_TYPES, true), 404, 'تقرير غير معروف.');

        $filters = $reportLog->filters ?? [];
        $data = $this->buildReport($reportLog->type, $filters);
        $fmt = $this->formatter($data['currency'] ?? 'YER');

        return match ($this->normalizeFormat($reportLog->format)) {
            'pdf' => $this->renderPdf($data, $fmt),
            'excel' => $this->downloadExcel($data, $fmt),
            'csv' => $this->downloadCsv($data, $fmt),
            'json' => $this->downloadJson($data, $fmt),
            default => redirect()->route('admin.reports.show', array_merge(['type' => $reportLog->type], $filters)),
        };
    }

    /** @param  array<string, mixed>  $params */
    private function buildReport(string $type, array $params): array
    {
        $data = match ($type) {
            'agents' => $this->agentsReport($params),
            'properties' => $this->propertiesReport($params),
            'requests' => $this->requestsReport($params),
            'users' => $this->usersReport($params),
            default => abort(404, 'تقرير غير معروف.'),
        };

        $data['type'] = $type;
        $data['site'] = $this->siteInfo();
        $data['generated_at'] = now();

        return $data;
    }

    /**
     * استخراج المرشحات المسموح بها فقط من مُدخلات الطلب.
     * يمنع تمرير أي مفتاح غير مدعوم إلى الاستعلامات أو إلى سجل التقارير.
     *
     * @return array<string, mixed>
     */
    private function extractFilters(string $type, array $input): array
    {
        $filters = [];

        foreach (self::FILTER_KEYS[$type] ?? [] as $key) {
            $value = $input[$key] ?? null;
            if ((is_string($value) && $value !== '') || is_numeric($value)) {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    private function normalizeFormat(?string $format): string
    {
        $format = is_string($format) ? strtolower($format) : 'html';

        return in_array($format, ReportLog::FORMATS, true) ? $format : 'html';
    }

    private function recordLog(string $type, string $format, array $data): void
    {
        try {
            ReportLog::create([
                'user_id' => request()->user()?->id,
                'type' => $type,
                'format' => $format,
                'filters' => $data['filtersQuery'] ?? [],
                'row_count' => count($data['rows'] ?? []),
                'file_name' => $this->exportFileName($type, $format),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            // فشل التسجيل لا يجوز أن يُفشل توليد التقرير نفسه.
            report($e);
        }
    }

    private function exportFileName(string $type, string $format): ?string
    {
        $extension = match ($format) {
            'pdf' => 'pdf',
            'excel' => 'xls',
            'csv' => 'csv',
            'json' => 'json',
            default => null,
        };

        return $extension ? 'wajhatak-'.$type.'-'.now()->format('Y-m-d').'.'.$extension : null;
    }

    private function agentsReport(array $params): array
    {
        $isActive = match ($params['status'] ?? null) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $filters = [];
        if ($isActive !== null) {
            $filters[] = ['label' => 'الحالة', 'value' => $isActive ? 'نشط' : 'موقوف'];
        }

        $rows = Agent::query()
            ->with('user')
            ->withCount(['properties'])
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->get()
            ->map(fn (Agent $a) => [
                'name' => $a->user?->name ?? '—',
                'email' => $a->user?->email ?? '—',
                'phone' => $a->user?->phone ?? '—',
                'license' => $a->license_number ?? '—',
                'rating' => (float) $a->rating,
                'reviews' => (int) $a->reviews_count,
                'properties' => (int) $a->properties_count,
                'status' => $a->is_active ? 'active' : 'inactive',
            ])
            ->values()
            ->all();

        $best = collect($rows)->sortByDesc('rating')->first();
        $totalProperties = (int) collect($rows)->sum('properties');

        return [
            'heading' => 'تقرير الوكلاء',
            'description' => 'عرض تفصيلي لجميع الوكلاء المسجلين في منصة وجهتك، مع تقييماتهم وعقاراتهم.',
            'filters' => $filters,
            'filtersQuery' => $params,
            'columns' => [
                ['key' => 'name', 'label' => 'الوكيل', 'type' => 'text'],
                ['key' => 'email', 'label' => 'البريد الإلكتروني', 'type' => 'text'],
                ['key' => 'phone', 'label' => 'الهاتف', 'type' => 'text'],
                ['key' => 'license', 'label' => 'الترخيص', 'type' => 'text'],
                ['key' => 'rating', 'label' => 'التقييم', 'type' => 'rating'],
                ['key' => 'reviews', 'label' => 'التقييمات', 'type' => 'number'],
                ['key' => 'properties', 'label' => 'العقارات', 'type' => 'number'],
                ['key' => 'status', 'label' => 'الحالة', 'type' => 'badge',
                    'values' => ['active' => 'نشط', 'inactive' => 'موقوف'],
                    'colors' => ['active' => 'green', 'inactive' => 'gray']],
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
            'summary' => [
                ['label' => 'إجمالي الوكلاء', 'value' => count($rows)],
                ['label' => 'إجمالي العقارات', 'value' => $totalProperties],
                ['label' => 'الأعلى تقييماً', 'value' => $best ? ($best['name'].' ('.$best['rating'].')') : '—'],
            ],
        ];
    }

    private function propertiesReport(array $params): array
    {
        $status = $params['status'] ?? null;
        if (! in_array($status, ['draft', 'pending', 'published', 'rejected', 'archived'], true)) {
            $status = null;
        }

        $filters = [];
        $typeSlug = $params['type'] ?? null;
        $type = $typeSlug ? PropertyType::where('slug', $typeSlug)->first() : null;
        if ($type) {
            $filters[] = ['label' => 'نوع العقار', 'value' => $type->name_ar];
        }
        if ($status) {
            $filters[] = ['label' => 'الحالة', 'value' => $this->statusLabel($status)];
        }

        $agentId = $params['agent'] ?? null;
        $agent = $agentId ? Agent::with('user')->find((int) $agentId) : null;
        if ($agent) {
            $filters[] = ['label' => 'الوكيل', 'value' => $agent->user?->name ?? ('#'.$agent->id)];
        }

        $rows = Property::query()
            ->with(['agent.user', 'type'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($type, fn ($q, $t) => $q->where('property_type_id', $t->id))
            ->when($agent, fn ($q, $a) => $q->where('agent_id', $a->id))
            ->latest('published_at')
            ->limit(500)
            ->get()
            ->map(fn (Property $p) => [
                'reference_code' => $p->reference_code ?? '—',
                'title' => $p->title,
                'agent' => $p->agent?->user?->name ?? '—',
                'type' => $p->type?->name_ar ?? '—',
                'transaction' => $p->transaction_type?->value,
                'price' => (float) $p->price,
                'area' => (float) $p->area,
                'bedrooms' => $p->bedrooms,
                'status' => $p->status?->value,
                'published_at' => $p->published_at?->toDateString(),
            ])
            ->values()
            ->all();

        $counts = array_count_values(array_filter(array_column($rows, 'status') ?: []));
        $total = count($rows);
        $prices = array_column($rows, 'price');
        $avgPrice = $prices ? round(array_sum($prices) / count($prices), 2) : 0;

        return [
            'heading' => 'تقرير العقارات',
            'description' => 'عرض تفصيلي لجميع أنواع العقارات (فلل، شقق، أدوار،...) مع أسعارها وحالتها.',
            'filters' => $filters,
            'filtersQuery' => $params,
            'columns' => [
                ['key' => 'reference_code', 'label' => 'الكود', 'type' => 'text'],
                ['key' => 'title', 'label' => 'العقار', 'type' => 'text'],
                ['key' => 'agent', 'label' => 'الوكيل', 'type' => 'text'],
                ['key' => 'type', 'label' => 'النوع', 'type' => 'text'],
                ['key' => 'transaction', 'label' => 'الصفقة', 'type' => 'badge',
                    'values' => ['sale' => 'بيع', 'rent' => 'إيجار'],
                    'colors' => ['sale' => 'blue', 'rent' => 'amber']],
                ['key' => 'price', 'label' => 'السعر', 'type' => 'money', 'currency' => $this->currency()],
                ['key' => 'area', 'label' => 'المساحة (م²)', 'type' => 'number'],
                ['key' => 'bedrooms', 'label' => 'الغرف', 'type' => 'number'],
                ['key' => 'status', 'label' => 'الحالة', 'type' => 'badge',
                    'values' => ['draft' => 'مسودة', 'pending' => 'قيد المراجعة', 'published' => 'منشور', 'rejected' => 'مرفوض', 'archived' => 'مؤرشف'],
                    'colors' => ['draft' => 'gray', 'pending' => 'amber', 'published' => 'green', 'rejected' => 'red', 'archived' => 'blue']],
                ['key' => 'published_at', 'label' => 'تاريخ النشر', 'type' => 'date'],
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
            'summary' => [
                ['label' => 'إجمالي العقارات', 'value' => $total],
                ['label' => 'منشور', 'value' => $counts['published'] ?? 0],
                ['label' => 'قيد المراجعة', 'value' => $counts['pending'] ?? 0],
                ['label' => 'متوسط السعر', 'value' => number_format($avgPrice, 0).' '.$this->currency()],
            ],
        ];
    }

    private function requestsReport(array $params): array
    {
        $status = $params['status'] ?? null;
        if (! in_array($status, ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'], true)) {
            $status = null;
        }

        $filters = [];
        if ($status) {
            $filters[] = ['label' => 'الحالة', 'value' => $this->statusLabel($status)];
        }

        $agentId = $params['agent'] ?? null;
        $agent = $agentId ? Agent::with('user')->find((int) $agentId) : null;
        if ($agent) {
            $filters[] = ['label' => 'الوكيل', 'value' => $agent->user?->name ?? ('#'.$agent->id)];
        }

        $rows = ViewingRequest::query()
            ->with(['property', 'client', 'agent.user'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($agent, fn ($q, $a) => $q->where('agent_id', $a->id))
            ->latest('scheduled_date')
            ->limit(500)
            ->get()
            ->map(fn (ViewingRequest $r) => [
                'reference_code' => $r->property?->reference_code ?? '—',
                'property' => $r->property?->title ?? '—',
                'client' => $r->client?->name ?? '—',
                'agent' => $r->agent?->user?->name ?? '—',
                'date' => $r->scheduled_date?->toDateString(),
                'time' => $r->scheduled_time ?? '—',
                'status' => $r->status?->value,
            ])
            ->values()
            ->all();

        $counts = array_count_values(array_filter(array_column($rows, 'status') ?: []));

        return [
            'heading' => 'تقرير طلبات المعاينة',
            'description' => 'جميع طلبات معاينة العقارات المرسلة من العملاء إلى الوكلاء مع حالتها ومواعيدها.',
            'filters' => $filters,
            'filtersQuery' => $params,
            'columns' => [
                ['key' => 'reference_code', 'label' => 'الكود', 'type' => 'text'],
                ['key' => 'property', 'label' => 'العقار', 'type' => 'text'],
                ['key' => 'client', 'label' => 'العميل', 'type' => 'text'],
                ['key' => 'agent', 'label' => 'الوكيل', 'type' => 'text'],
                ['key' => 'date', 'label' => 'الموعد', 'type' => 'date'],
                ['key' => 'time', 'label' => 'الوقت', 'type' => 'text'],
                ['key' => 'status', 'label' => 'الحالة', 'type' => 'badge',
                    'values' => ['pending' => 'قيد الانتظار', 'confirmed' => 'مؤكد', 'rejected' => 'مرفوض', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'],
                    'colors' => ['pending' => 'amber', 'confirmed' => 'green', 'rejected' => 'red', 'cancelled' => 'gray', 'completed' => 'blue']],
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
            'summary' => [
                ['label' => 'إجمالي الطلبات', 'value' => count($rows)],
                ['label' => 'قيد الانتظار', 'value' => $counts['pending'] ?? 0],
                ['label' => 'مؤكد', 'value' => $counts['confirmed'] ?? 0],
                ['label' => 'مكتمل', 'value' => $counts['completed'] ?? 0],
                ['label' => 'ملغي', 'value' => $counts['cancelled'] ?? 0],
            ],
        ];
    }

    private function usersReport(array $params): array
    {
        $role = $params['role'] ?? null;
        if (! in_array($role, ['admin', 'agent', 'user'], true)) {
            $role = null;
        }
        $filters = [];
        if ($role) {
            $filters[] = ['label' => 'الدور', 'value' => $this->roleLabel($role)];
        }

        $userId = $params['user'] ?? null;
        $target = $userId ? User::find((int) $userId) : null;
        if ($target) {
            $filters[] = ['label' => 'المستخدم', 'value' => $target->name];
        }

        $rows = User::query()
            ->with('agentProfile')
            ->withCount('favorites')
            ->when($role, fn ($q, $r) => $q->role($r))
            ->when($target, fn ($q, $u) => $q->whereKey($u->id))
            ->latest('created_at')
            ->limit(500)
            ->get()
            ->map(fn (User $u) => [
                'name' => $u->name,
                'email' => $u->email ?? '—',
                'phone' => $u->phone ?? '—',
                'role' => $this->userRole($u),
                'favorites' => (int) $u->favorites_count,
                'status' => $u->is_active ? 'active' : 'inactive',
                'created_at' => $u->created_at?->toDateString(),
            ])
            ->values()
            ->all();

        $counts = array_count_values(array_filter(array_column($rows, 'role') ?: []));

        return [
            'heading' => 'تقرير المستخدمين',
            'description' => 'جميع مستخدمي منصة وجهتك (مشرفون، وكلاء، عملاء) مع أدوارهم وحالة حساباتهم.',
            'filters' => $filters,
            'filtersQuery' => $params,
            'columns' => [
                ['key' => 'name', 'label' => 'الاسم', 'type' => 'text'],
                ['key' => 'email', 'label' => 'البريد الإلكتروني', 'type' => 'text'],
                ['key' => 'phone', 'label' => 'الهاتف', 'type' => 'text'],
                ['key' => 'role', 'label' => 'الدور', 'type' => 'badge',
                    'values' => ['admin' => 'مشرف', 'agent' => 'وكيل', 'user' => 'عميل'],
                    'colors' => ['admin' => 'red', 'agent' => 'blue', 'user' => 'green']],
                ['key' => 'favorites', 'label' => 'المفضلة', 'type' => 'number'],
                ['key' => 'status', 'label' => 'الحالة', 'type' => 'badge',
                    'values' => ['active' => 'نشط', 'inactive' => 'موقوف'],
                    'colors' => ['active' => 'green', 'inactive' => 'gray']],
                ['key' => 'created_at', 'label' => 'تاريخ التسجيل', 'type' => 'date'],
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
            'summary' => [
                ['label' => 'إجمالي المستخدمين', 'value' => count($rows)],
                ['label' => 'مشرفون', 'value' => $counts['admin'] ?? 0],
                ['label' => 'وكلاء', 'value' => $counts['agent'] ?? 0],
                ['label' => 'عملاء', 'value' => $counts['user'] ?? 0],
            ],
        ];
    }

    private function formatter(string $currency): Closure
    {
        return function (array $col, $value) use ($currency) {
            $type = $col['type'];
            $value = $value ?? '—';

            return match ($type) {
                'money' => number_format((float) $value, 0).' '.($col['currency'] ?? $currency),
                'number' => number_format((float) $value, 0),
                'rating' => number_format((float) $value, 2),
                'date' => $value && $value !== '—' ? Carbon::parse($value)->format('Y-m-d') : '—',
                'badge' => ($col['values'][$value] ?? $value) ?: $value,
                default => is_bool($value) ? ($value ? 'نعم' : 'لا') : (string) $value,
            };
        };
    }

    private function renderPdf(array $data, Closure $fmt): HttpResponse
    {
        return $this->pdf->render(
            'admin.reports.pdf',
            ['report' => $data, 'fmt' => $fmt],
            $this->exporter->fileName($data['type'], 'pdf'),
        );
    }

    private function downloadExcel(array $data, Closure $fmt): HttpResponse
    {
        return $this->exporter->excel($data, $fmt);
    }

    private function downloadCsv(array $data, Closure $fmt): HttpResponse
    {
        return $this->exporter->csv($data, $fmt);
    }

    private function downloadJson(array $data, Closure $fmt): HttpResponse
    {
        return $this->exporter->json($data, $fmt);
    }

    private function siteInfo(): array
    {
        $logoPath = public_path('storage/branding/logo.png');
        $logoUri = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath)) : null;

        return [
            'name' => Setting::get('site_name', 'وجهتك'),
            'tagline' => Setting::get('site_tagline', 'وجهتك إلى العقار المناسب.'),
            'logo' => $logoUri,
            'currency' => $this->currency(),
        ];
    }

    private function currency(): string
    {
        return Setting::get('default_currency', 'YER');
    }

    private function statusLabel(string $status): string
    {
        return [
            'draft' => 'مسودة',
            'pending' => 'قيد المراجعة',
            'published' => 'منشور',
            'rejected' => 'مرفوض',
            'archived' => 'مؤرشف',
            'confirmed' => 'مؤكد',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            'active' => 'نشط',
            'inactive' => 'موقوف',
        ][$status] ?? $status;
    }

    private function roleLabel(string $role): string
    {
        return ['admin' => 'مشرف', 'agent' => 'وكيل', 'user' => 'عميل'][$role] ?? $role;
    }

    private function userRole(User $user): string
    {
        if ($user->hasRole('admin')) {
            return 'admin';
        }

        return $user->hasRole('agent') ? 'agent' : 'user';
    }
}
