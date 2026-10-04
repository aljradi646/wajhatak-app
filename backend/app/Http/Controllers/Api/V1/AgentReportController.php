<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ViewingRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Property;
use App\Models\ReportLog;
use App\Models\Setting;
use App\Models\ViewingRequest;
use App\Services\Reports\PdfReportRenderer;
use App\Services\Reports\ReportExporter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * تقارير الوكيل من داخل تطبيق الموبايل.
 *
 * كل استعلام مُقيَّد ببيانات الوكيل الحالي حصرًا (agent_id/agent user)، فلا
 * يستطيع وكيل قراءة أرقام وكيل آخر. كل توليد يُسجَّل في سجل التقارير نفسه
 * المستخدم في لوحة الإدارة.
 */
class AgentReportController extends Controller
{
    /** أنواع تقارير الوكيل المتاحة فعليًا. */
    public const TYPES = [
        'properties' => [
            'label' => 'تقرير عقاراتي',
            'description' => 'عقاراتك المسجلة مع أسعارها ومساحاتها وحالتها.',
        ],
        'viewing_requests' => [
            'label' => 'تقرير طلبات المعاينة',
            'description' => 'طلبات معاينة عقاراتك مع مواعيدها وحالتها.',
        ],
        'performance' => [
            'label' => 'تقرير الأداء',
            'description' => 'ملخص أداء محفظتك: قيمة العقارات، متوسط السعر، ونسبة إتمام المعاينات.',
        ],
    ];

    /**
     * تعريف مرشحات كل نوع (مفتاح + تسمية + قيم مصدرها الخادم).
     * التطبيق لا يخزّن أي قائمة حالات ثابتة — يقرأ خياراتها من هنا، فأي تغيير
     * في النظام يظهر في الواجهة تلقائيًا بلا تعديل التطبيق.
     */
    private const FILTER_DEFINITIONS = [
        'properties' => [
            'status' => ['label' => 'الحالة', 'options' => [
                'draft' => 'مسودة',
                'pending' => 'قيد المراجعة',
                'published' => 'منشور',
                'rejected' => 'مرفوض',
            ]],
        ],
        'viewing_requests' => [
            'status' => ['label' => 'الحالة', 'options' => [
                'pending' => 'قيد الانتظار',
                'confirmed' => 'مؤكد',
                'rejected' => 'مرفوض',
                'cancelled' => 'ملغي',
                'completed' => 'مكتمل',
            ]],
        ],
        'performance' => [],
    ];

    public function __construct(
        private readonly ReportExporter $exporter,
        private readonly PdfReportRenderer $pdf,
    ) {}

    /** GET /api/v1/agent/reports — الأنواع المتاحة + آخر التقارير المُولَّدة. */
    public function index(Request $request): JsonResponse
    {
        $agent = $this->agentFor($request);

        $types = collect(self::TYPES)->map(fn (array $meta, string $key) => [
            'key' => $key,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'formats' => ['json', 'csv', 'pdf'],
            'filters' => collect(self::FILTER_DEFINITIONS[$key] ?? [])->map(fn (array $definition, string $filterKey) => [
                'key' => $filterKey,
                'label' => $definition['label'],
                'options' => collect($definition['options'])->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])->values(),
            ])->values(),
        ])->values();

        $history = ReportLog::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('type', ReportLog::AGENT_TYPES)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (ReportLog $log) => [
                'id' => $log->id,
                'type' => $log->type,
                'label' => $log->typeLabel(),
                'format' => $log->format,
                'format_label' => $log->formatLabel(),
                'row_count' => $log->row_count,
                'created_at' => $log->created_at?->toISOString(),
                'download_url' => route('api.v1.agent.reports.download', ['reportLog' => $log->id]),
            ]);

        return response()->json([
            'data' => [
                'types' => $types,
                'history' => $history,
                'agent' => ['id' => $agent->id, 'name' => $agent->user?->name],
            ],
        ]);
    }

    /** GET /api/v1/agent/reports/{type}?format=json|csv|pdf */
    public function show(Request $request, string $type): HttpResponse|JsonResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404, 'نوع التقرير غير معروف.');

        $agent = $this->agentFor($request);
        $format = $this->normalizeFormat($request->query('format', 'json'));
        $params = $this->extractFilters($type, $request->query());

        $data = $this->buildReport($agent, $type, $params);
        $fmt = $this->formatter($data['currency']);

        $response = match ($format) {
            'pdf' => $this->pdf->render('admin.reports.pdf', ['report' => $data, 'fmt' => $fmt], $this->exporter->fileName($data['type'], 'pdf')),
            'csv' => $this->exporter->csv($data, $fmt),
            default => Response::json(['data' => $this->payload($data, $fmt)]),
        };

        $this->recordLog($agent, $type, $format, $data, $params, $request);

        return $response;
    }

    /** GET /api/v1/agent/reports/history/{reportLog}/download — إعادة توليد تقرير سابق. */
    public function download(Request $request, ReportLog $reportLog): HttpResponse|JsonResponse
    {
        $agent = $this->agentFor($request);

        // عزل كامل: لا يفتح الوكيل تقرير وكيل آخر حتى بمعرفة المعرّف.
        abort_unless($reportLog->user_id === $request->user()->id, 403, 'لا يمكنك الوصول إلى هذا التقرير.');
        abort_unless(in_array($reportLog->type, ReportLog::AGENT_TYPES, true), 404);

        $type = str_replace('agent_', '', $reportLog->type);
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $data = $this->buildReport($agent, $type, $reportLog->filters ?? []);
        $fmt = $this->formatter($data['currency']);

        return match ($this->normalizeFormat($reportLog->format)) {
            'pdf' => $this->pdf->render('admin.reports.pdf', ['report' => $data, 'fmt' => $fmt], $this->exporter->fileName($data['type'], 'pdf')),
            'csv' => $this->exporter->csv($data, $fmt),
            default => Response::json(['data' => $this->payload($data, $fmt)]),
        };
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /** الوكيل المرتبط بالمستخدم الحالي — وإلا فلا تقارير. */
    private function agentFor(Request $request): Agent
    {
        $agent = $request->user()?->agentProfile;

        abort_if($agent === null, 403, 'هذا القسم متاح لحسابات الوكلاء فقط.');

        return $agent->loadMissing('user');
    }

    private function normalizeFormat(?string $format): string
    {
        $format = is_string($format) ? strtolower($format) : 'json';

        return in_array($format, ['json', 'csv', 'pdf'], true) ? $format : 'json';
    }

    /** @return array<string, mixed> */
    private function extractFilters(string $type, array $input): array
    {
        $filters = [];

        // فقط مفاتيح المرشحات المعرَّفة لهذا النوع، وبقيم من قائمته المسموحة.
        foreach (self::FILTER_DEFINITIONS[$type] ?? [] as $key => $definition) {
            $value = $input[$key] ?? null;
            if (is_string($value) && $value !== '' && array_key_exists($value, $definition['options'])) {
                $filters[$key] = $value;
            }
        }

        foreach (['date_from', 'date_to'] as $dateKey) {
            $value = $input[$dateKey] ?? null;
            if (is_string($value) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value) === 1) {
                try {
                    Carbon::createFromFormat('Y-m-d', $value);
                    $filters[$dateKey] = $value;
                } catch (\Throwable) {
                }
            }
        }

        if (isset($filters['date_from'], $filters['date_to']) && $filters['date_from'] > $filters['date_to']) {
            [$filters['date_from'], $filters['date_to']] = [$filters['date_to'], $filters['date_from']];
        }

        return $filters;
    }

    /** @param array<string, mixed> $params */
    private function buildReport(Agent $agent, string $type, array $params): array
    {
        $data = match ($type) {
            'properties' => $this->propertiesReport($agent, $params),
            'viewing_requests' => $this->viewingRequestsReport($agent, $params),
            'performance' => $this->performanceReport($agent, $params),
            default => abort(404, 'نوع التقرير غير معروف.'),
        };

        $data['type'] = 'agent_'.$type;
        $data['site'] = $this->siteInfo();
        $data['generated_at'] = now();

        return $data;
    }

    private function propertiesReport(Agent $agent, array $params): array
    {
        $status = $params['status'] ?? null;
        $from = $params['date_from'] ?? null;
        $to = $params['date_to'] ?? null;
        if (! in_array($status, ['draft', 'pending', 'published', 'rejected'], true)) {
            $status = null;
        }

        $filters = [];
        if ($status) {
            $filters[] = ['label' => 'الحالة', 'value' => $this->propertyStatusLabel($status)];
        }
        if ($from) $filters[] = ['label' => 'من', 'value' => $from];
        if ($to) $filters[] = ['label' => 'إلى', 'value' => $to];

        $rows = Property::query()
            ->with(['type', 'location'])
            ->where('agent_id', $agent->id)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->get()
            ->map(fn (Property $p) => [
                'reference_code' => $p->reference_code ?? '—',
                'title' => $p->title,
                'type' => $p->type?->name_ar ?? '—',
                'location' => trim(($p->location?->city ?? '').' '.($p->location?->district ?? '')) ?: '—',
                'price' => (float) $p->price,
                'area' => (float) $p->area,
                'bedrooms' => $p->bedrooms,
                'status' => $p->status?->value,
                'published_at' => $p->published_at?->toDateString(),
            ])
            ->all();

        $prices = array_column($rows, 'price');
        $totalValue = array_sum($prices);
        $counts = array_count_values(array_filter(array_column($rows, 'status')));

        return [
            'heading' => 'تقرير عقاراتي',
            'description' => 'عقاراتك المسجلة في منصة وجهتك.',
            'filters' => $filters,
            'columns' => [
                ['key' => 'reference_code', 'label' => 'الكود', 'type' => 'text'],
                ['key' => 'title', 'label' => 'العقار', 'type' => 'text'],
                ['key' => 'type', 'label' => 'النوع', 'type' => 'text'],
                ['key' => 'location', 'label' => 'الموقع', 'type' => 'text'],
                ['key' => 'price', 'label' => 'السعر', 'type' => 'money'],
                ['key' => 'area', 'label' => 'المساحة (م²)', 'type' => 'number'],
                ['key' => 'bedrooms', 'label' => 'الغرف', 'type' => 'number'],
                ['key' => 'status', 'label' => 'الحالة', 'type' => 'badge',
                    'values' => ['draft' => 'مسودة', 'pending' => 'قيد المراجعة', 'published' => 'منشور', 'rejected' => 'مرفوض'],
                    'colors' => ['draft' => 'gray', 'pending' => 'amber', 'published' => 'green', 'rejected' => 'red']],
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
            'summary' => [
                ['label' => 'إجمالي العقارات', 'value' => count($rows)],
                ['label' => 'منشورة', 'value' => $counts['published'] ?? 0],
                ['label' => 'قيد المراجعة', 'value' => $counts['pending'] ?? 0],
                ['label' => 'قيمة المحفظة', 'value' => number_format($totalValue, 0).' '.$this->currency()],
            ],
        ];
    }

    private function viewingRequestsReport(Agent $agent, array $params): array
    {
        $status = $params['status'] ?? null;
        if (! in_array($status, ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'], true)) {
            $status = null;
        }

        $filters = [];
        if ($status) {
            $filters[] = ['label' => 'الحالة', 'value' => $this->requestStatusLabel($status)];
        }
        if ($from) $filters[] = ['label' => 'من', 'value' => $from];
        if ($to) $filters[] = ['label' => 'إلى', 'value' => $to];

        $rows = ViewingRequest::query()
            ->with(['property:id,title,reference_code', 'client:id,name,phone'])
            ->where('agent_id', $agent->id)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($from, fn ($q, $date) => $q->whereDate('scheduled_date', '>=', $date))
            ->when($to, fn ($q, $date) => $q->whereDate('scheduled_date', '<=', $date))
            ->latest('scheduled_date')
            ->get()
            ->map(fn (ViewingRequest $r) => [
                'reference_code' => $r->property?->reference_code ?? '—',
                'property' => $r->property?->title ?? '—',
                'client' => $r->client?->name ?? '—',
                'phone' => $r->client?->phone ?? '—',
                'date' => $r->scheduled_date?->toDateString(),
                'time' => $r->scheduled_time,
                'status' => $r->status?->value,
            ])
            ->all();

        $counts = array_count_values(array_filter(array_column($rows, 'status')));
        $total = count($rows);
        $completed = $counts['completed'] ?? 0;

        return [
            'heading' => 'تقرير طلبات المعاينة',
            'description' => 'طلبات معاينة عقاراتك من العملاء.',
            'filters' => $filters,
            'columns' => [
                ['key' => 'reference_code', 'label' => 'الكود', 'type' => 'text'],
                ['key' => 'property', 'label' => 'العقار', 'type' => 'text'],
                ['key' => 'client', 'label' => 'العميل', 'type' => 'text'],
                ['key' => 'phone', 'label' => 'الجوال', 'type' => 'text'],
                ['key' => 'date', 'label' => 'الموعد', 'type' => 'date'],
                ['key' => 'time', 'label' => 'الوقت', 'type' => 'text'],
                ['key' => 'status', 'label' => 'الحالة', 'type' => 'badge',
                    'values' => ['pending' => 'قيد الانتظار', 'confirmed' => 'مؤكد', 'rejected' => 'مرفوض', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'],
                    'colors' => ['pending' => 'amber', 'confirmed' => 'green', 'rejected' => 'red', 'cancelled' => 'gray', 'completed' => 'blue']],
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
            'summary' => [
                ['label' => 'إجمالي الطلبات', 'value' => $total],
                ['label' => 'قيد الانتظار', 'value' => $counts['pending'] ?? 0],
                ['label' => 'مؤكدة', 'value' => $counts['confirmed'] ?? 0],
                ['label' => 'مكتملة', 'value' => $completed],
                ['label' => 'نسبة الإتمام', 'value' => $total > 0 ? round($completed / $total * 100).'%' : '0%'],
            ],
        ];
    }

    private function performanceReport(Agent $agent, array $params = []): array
    {
        $from = $params['date_from'] ?? null;
        $to = $params['date_to'] ?? null;
        $filters = [];
        if ($from) $filters[] = ['label' => 'من', 'value' => $from];
        if ($to) $filters[] = ['label' => 'إلى', 'value' => $to];

        $properties = Property::query()
            ->selectRaw('property_type_id, count(*) as total, avg(price) as avg_price, sum(price) as total_value')
            ->with('type:id,name_ar')
            ->where('agent_id', $agent->id)
            ->when($from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->groupBy('property_type_id')
            ->get();

        $rows = $properties->map(fn ($row) => [
            'type' => $row->type?->name_ar ?? 'غير محدد',
            'count' => (int) $row->total,
            'avg_price' => round((float) $row->avg_price, 0),
            'total_value' => round((float) $row->total_value, 0),
        ])->values()->all();

        $requestsTotal = ViewingRequest::query()->where('agent_id', $agent->id)
            ->when($from, fn ($q, $date) => $q->whereDate('scheduled_date', '>=', $date))
            ->when($to, fn ($q, $date) => $q->whereDate('scheduled_date', '<=', $date))
            ->count();
        $requestsCompleted = ViewingRequest::query()->where('agent_id', $agent->id)
            ->when($from, fn ($q, $date) => $q->whereDate('scheduled_date', '>=', $date))
            ->when($to, fn ($q, $date) => $q->whereDate('scheduled_date', '<=', $date))
            ->where('status', ViewingRequestStatus::Completed->value)->count();

        return [
            'heading' => 'تقرير أداء الوكيل',
            'description' => 'توزيع محفظتك العقارية حسب النوع ومؤشرات الاستجابة.',
            'filters' => $filters,
            'columns' => [
                ['key' => 'type', 'label' => 'نوع العقار', 'type' => 'text'],
                ['key' => 'count', 'label' => 'عدد العقارات', 'type' => 'number'],
                ['key' => 'avg_price', 'label' => 'متوسط السعر', 'type' => 'money'],
                ['key' => 'total_value', 'label' => 'إجمالي القيمة', 'type' => 'money'],
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
            'summary' => [
                ['label' => 'أنواع العقارات', 'value' => count($rows)],
                ['label' => 'إجمالي العقارات', 'value' => array_sum(array_column($rows, 'count'))],
                ['label' => 'إجمالي القيمة', 'value' => number_format(array_sum(array_column($rows, 'total_value')), 0).' '.$this->currency()],
                ['label' => 'طلبات المعاينة', 'value' => $requestsTotal],
                ['label' => 'معاينات مكتملة', 'value' => $requestsCompleted],
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    private function payload(array $data, Closure $fmt): array
    {
        return [
            'type' => $data['type'],
            'heading' => $data['heading'],
            'description' => $data['description'],
            'generated_at' => $data['generated_at']->toIso8601String(),
            'currency' => $data['currency'],
            'site' => ['name' => $data['site']['name'], 'tagline' => $data['site']['tagline']],
            'filters' => $data['filters'],
            'columns' => $data['columns'],
            'summary' => $data['summary'],
            // rows = قيم جاهزة للعرض (مُهيّأة)، raw_rows = قيم خام للبرمجة
            // (مثل حالة العقار لاختيار لون الشارة في الواجهة).
            'rows' => array_map(fn ($row) => array_combine(
                array_column($data['columns'], 'key'),
                array_map(fn ($c) => $fmt($c, $row[$c['key']] ?? null), $data['columns'])
            ), $data['rows']),
            'raw_rows' => $data['rows'],
        ];
    }

    /** @param array<string, mixed> $data  @param array<string, mixed> $params */
    private function recordLog(Agent $agent, string $type, string $format, array $data, array $params, Request $request): void
    {
        try {
            ReportLog::create([
                'user_id' => $agent->user_id,
                'type' => 'agent_'.$type,
                'format' => $format,
                'filters' => $params,
                'row_count' => count($data['rows'] ?? []),
                'file_name' => $format === 'json' ? null : $this->exporter->fileName($data['type'], $format === 'pdf' ? 'pdf' : 'csv'),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function formatter(string $currency): Closure
    {
        return function (array $col, $value) use ($currency) {
            $value ??= '—';

            return match ($col['type']) {
                'money' => number_format((float) $value, 0).' '.$currency,
                'number' => number_format((float) $value, 0),
                'rating' => number_format((float) $value, 2),
                'date' => $value !== '—' ? Carbon::parse($value)->format('Y-m-d') : '—',
                'badge' => ($col['values'][$value] ?? $value) ?: $value,
                default => is_bool($value) ? ($value ? 'نعم' : 'لا') : (string) $value,
            };
        };
    }

    private function siteInfo(): array
    {
        return [
            'name' => Setting::get('site_name', 'وجهتك'),
            'tagline' => Setting::get('site_tagline', 'وجهتك إلى العقار المناسب.'),
            'logo' => null,
            'currency' => $this->currency(),
        ];
    }

    private function currency(): string
    {
        return Setting::get('default_currency', 'YER');
    }

    private function propertyStatusLabel(string $status): string
    {
        return [
            'draft' => 'مسودة',
            'pending' => 'قيد المراجعة',
            'published' => 'منشور',
            'rejected' => 'مرفوض',
        ][$status] ?? $status;
    }

    private function requestStatusLabel(string $status): string
    {
        return [
            'pending' => 'قيد الانتظار',
            'confirmed' => 'مؤكد',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
        ][$status] ?? $status;
    }
}
