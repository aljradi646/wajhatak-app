<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\User;
use App\Models\ViewingRequest;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    private const STATUS_KEYS = [
        'draft', 'pending', 'published', 'rejected', 'archived',
        'confirmed', 'cancelled', 'completed', 'active', 'inactive',
    ];

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
        ]);
    }

    public function show(string $type)
    {
        $data = match ($type) {
            'agents' => $this->agentsReport(),
            'properties' => $this->propertiesReport(),
            'requests' => $this->requestsReport(),
            'users' => $this->usersReport(),
        };

        $data['type'] = $type;
        $data['site'] = $this->siteInfo();
        $data['generated_at'] = now();
        $data['route'] = route('admin.reports.show', array_merge(['type' => $type], request()->except(['format'])));

        $fmt = $this->formatter($data['currency'] ?? 'YER');

        return match (request('format', 'html')) {
            'pdf' => $this->renderPdf($data, $fmt),
            'excel' => $this->downloadExcel($data, $fmt),
            'csv' => $this->downloadCsv($data, $fmt),
            'json' => $this->downloadJson($data, $fmt),
            default => view('admin.reports.section', ['report' => $data, 'fmt' => $fmt]),
        };
    }

    private function agentsReport(): array
    {
        $isActive = match (request('status')) {
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
            'filtersQuery' => request()->only(['status', 'format']),
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

    private function propertiesReport(): array
    {
        $status = request('status');
        if (! in_array($status, ['draft', 'pending', 'published', 'rejected', 'archived'], true)) {
            $status = null;
        }

        $filters = [];
        $typeSlug = request('type');
        $type = $typeSlug ? PropertyType::where('slug', $typeSlug)->first() : null;
        if ($type) {
            $filters[] = ['label' => 'نوع العقار', 'value' => $type->name_ar];
        }
        if ($status) {
            $filters[] = ['label' => 'الحالة', 'value' => $this->statusLabel($status)];
        }

        $agentId = request('agent');
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

        $counts = array_count_values(array_column($rows, 'status') ?: []);
        $total = count($rows);
        $prices = array_column($rows, 'price');
        $avgPrice = $prices ? round(array_sum($prices) / count($prices), 2) : 0;

        return [
            'heading' => 'تقرير العقارات',
            'description' => 'عرض تفصيلي لجميع أنواع العقارات (فلل، شقق، أدوار،...) مع أسعارها وحالتها.',
            'filters' => $filters,
            'filtersQuery' => request()->only(['status', 'type', 'agent', 'format']),
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

    private function requestsReport(): array
    {
        $status = request('status');
        if (! in_array($status, ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'], true)) {
            $status = null;
        }

        $filters = [];
        if ($status) {
            $filters[] = ['label' => 'الحالة', 'value' => $this->statusLabel($status)];
        }

        $agentId = request('agent');
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

        $counts = array_count_values(array_column($rows, 'status') ?: []);

        return [
            'heading' => 'تقرير طلبات المعاينة',
            'description' => 'جميع طلبات معاينة العقارات المرسلة من العملاء إلى الوكلاء مع حالتها ومواعيدها.',
            'filters' => $filters,
            'filtersQuery' => request()->only(['status', 'agent', 'format']),
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

    private function usersReport(): array
    {
        $role = request('role');
        if (! in_array($role, ['admin', 'agent', 'user'], true)) {
            $role = null;
        }
        $filters = [];
        if ($role) {
            $filters[] = ['label' => 'الدور', 'value' => $this->roleLabel($role)];
        }

        $userId = request('user');
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

        $counts = array_count_values(array_column($rows, 'role') ?: []);

        return [
            'heading' => 'تقرير المستخدمين',
            'description' => 'جميع مستخدمي منصة وجهتك (مشرفون، وكلاء، عملاء) مع أدوارهم وحالة حساباتهم.',
            'filters' => $filters,
            'filtersQuery' => request()->only(['role', 'user', 'format']),
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
                'date' => $value && $value !== '—' ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '—',
                'badge' => ($col['values'][$value] ?? $value) ?: $value,
                default => is_bool($value) ? ($value ? 'نعم' : 'لا') : (string) $value,
            };
        };
    }

    private function renderPdf(array $data, Closure $fmt): HttpResponse
    {
        $html = $this->shapeArabic(view('admin.reports.pdf', ['report' => $data, 'fmt' => $fmt])->render());

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'fontDir' => public_path('fonts'),
            'fontCache' => storage_path('fonts/cache'),
            'isFontSubsettingEnabled' => false,
            'defaultFont' => 'Amiri',
        ]);

        $dompdf = $pdf->getDompdf();
        $fontMetrics = $dompdf->getFontMetrics();
        $fontMetrics->registerFont([
            'family' => 'Amiri',
            'style' => 'normal',
            'weight' => 'normal',
            'font' => public_path('fonts/Amiri-Regular.ttf'),
        ]);
        $fontMetrics->registerFont([
            'family' => 'Amiri',
            'style' => 'normal',
            'weight' => 'bold',
            'font' => public_path('fonts/Amiri-Bold.ttf'),
        ]);

        $dompdf->render();

        return $pdf->download('wajhatak-'.$data['type'].'-'.now()->format('Y-m-d').'.pdf');
    }

    private function shapeArabic(string $html): string
    {
        $arabic = new Arabic();
        $previous = error_reporting(0);

        try {
            $shaped = preg_replace_callback('/(<[^>]*>)|([^<]+)/s', function (array $m) use ($arabic) {
                if ($m[1] !== '') {
                    return $m[1];
                }

                return $arabic->utf8Glyphs($m[2], 50, false, true);
            }, $html);
        } finally {
            error_reporting($previous);
        }

        return $shaped ?? $html;
    }

    private function downloadExcel(array $data, Closure $fmt): HttpResponse
    {
        $columns = $data['columns'];
        $totalCols = count($columns);
        $merge = max($totalCols - 1, 0);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xml .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xml .= 'xmlns:x="urn:schemas-microsoft-com:office:excel">'."\n";
        $xml .= '<Styles>'
            .'<Style ss:ID="Default"><Font ss:FontName="Calibri" ss:Size="11"/><Alignment ss:Vertical="Center"/></Style>'
            .'<Style ss:ID="Title"><Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1"/><Alignment ss:Horizontal="Center"/></Style>'
            .'<Style ss:ID="Meta"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#6B7280"/><Alignment ss:Horizontal="Center"/></Style>'
            .'<Style ss:ID="Header"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>'
            .'<Interior ss:Color="#075E4A" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
            .'<Style ss:ID="Text"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
            .'<Style ss:ID="Number"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
            .'<Style ss:ID="Money"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
            .'<Style ss:ID="Summary"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>'
            .'<Interior ss:Color="#E8F5F0" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>'
            .'</Styles>'."\n";
        $xml .= '<Worksheet ss:Name="'.$data['type'].'"><Table>'."\n";
        $xml .= '<Column ss:AutoFitWidth="1"/>'."\n";

        $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Title"><Data ss:Type="String">'
            .$this->e($data['site']['name'].' — '.$data['heading']).'</Data></Cell></Row>'."\n";
        $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Meta"><Data ss:Type="String">'
            .$this->e($data['site']['tagline']).'</Data></Cell></Row>'."\n";
        if ($data['filters']) {
            foreach ($data['filters'] as $filter) {
                $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Meta"><Data ss:Type="String">'
                    .$this->e($filter['label'].': '.$filter['value']).'</Data></Cell></Row>'."\n";
            }
        }
        $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Meta"><Data ss:Type="String">'
            .$this->e('تاريخ الإنشاء: '.$data['generated_at']->format('Y-m-d H:i')).'</Data></Cell></Row>'."\n";

        $xml .= '<Row>';
        foreach ($columns as $col) {
            $style = $col['type'] === 'money' || $col['type'] === 'number' || $col['type'] === 'rating' ? 'Number' : 'Text';
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.$this->e($col['label']).'</Data></Cell>';
        }
        $xml .= '</Row>'."\n";

        foreach ($data['rows'] as $row) {
            $xml .= '<Row>';
            foreach ($columns as $col) {
                $raw = $row[$col['key']] ?? null;
                if ($col['type'] === 'money' || $col['type'] === 'number' || $col['type'] === 'rating') {
                    $value = is_numeric($raw) ? $raw : 0;
                    $xml .= '<Cell ss:StyleID="Number"><Data ss:Type="Number">'.$value.'</Data></Cell>';
                } else {
                    $xml .= '<Cell ss:StyleID="Text"><Data ss:Type="String">'.$this->e($fmt($col, $raw)).'</Data></Cell>';
                }
            }
            $xml .= '</Row>'."\n";
        }

        $xml .= '<Row/><Row/>'."\n";
        foreach ($data['summary'] as $item) {
            $xml .= '<Row><Cell ss:StyleID="Summary"><Data ss:Type="String">'.$this->e($item['label'])
                .'</Data></Cell><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Summary"><Data ss:Type="String">'
                .$this->e((string) $item['value']).'</Data></Cell></Row>'."\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        $filename = 'wajhatak-'.$data['type'].'-'.now()->format('Y-m-d').'.xls';

        return Response::make($xml, 200, $this->contentDisposition($filename, 'application/vnd.ms-excel'));
    }

    private function downloadCsv(array $data, Closure $fmt): HttpResponse
    {
        $filename = 'wajhatak-'.$data['type'].'-'.now()->format('Y-m-d').'.csv';
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [$data['site']['name'].' — '.$data['heading']]);
        foreach ($data['filters'] as $filter) {
            fputcsv($handle, [$filter['label'].': '.$filter['value']]);
        }
        fputcsv($handle, ['تاريخ الإنشاء: '.$data['generated_at']->format('Y-m-d H:i')]);
        fputcsv($handle, []);
        fputcsv($handle, array_map(fn ($c) => $c['label'], $data['columns']));
        foreach ($data['rows'] as $row) {
            fputcsv($handle, array_map(fn ($c) => $fmt($c, $row[$c['key']] ?? null), $data['columns']));
        }
        fputcsv($handle, []);
        foreach ($data['summary'] as $item) {
            fputcsv($handle, [$item['label'], $item['value']]);
        }

        rewind($handle);
        $body = stream_get_contents($handle);
        fclose($handle);

        return Response::make($body, 200, $this->contentDisposition($filename, 'text/csv; charset=UTF-8'));
    }

    private function downloadJson(array $data, Closure $fmt): HttpResponse
    {
        return Response::json([
            'platform' => $data['site']['name'],
            'report' => $data['heading'],
            'generated_at' => $data['generated_at']->toIso8601String(),
            'filters' => collect($data['filters'])->mapWithKeys(fn ($f) => [$f['label'] => $f['value']]),
            'columns' => collect($data['columns'])->map(fn ($c) => $c['label']),
            'rows' => array_map(fn ($row) => array_combine(
                array_column($data['columns'], 'key'),
                array_map(fn ($c) => $fmt($c, $row[$c['key']] ?? null), $data['columns'])
            ), $data['rows']),
            'summary' => $data['summary'],
        ], 200, $this->contentDisposition('wajhatak-'.$data['type'].'-'.now()->format('Y-m-d').'.json', 'application/json; charset=UTF-8'));
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

    private function contentDisposition(string $filename, string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];
    }

    private function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}