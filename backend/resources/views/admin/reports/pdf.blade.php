<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $report['heading'] }} — {{ $report['site']['name'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm 12mm 16mm 12mm; }
        * { box-sizing: border-box; }
        html, body {
            font-family: Amiri, serif;
            font-size: 11pt;
            color: #111827;
            margin: 0;
            padding: 0;
            line-height: 1.55;
        }
        .header {
            border-bottom: 2px solid #075E4A;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .logo { width: 56px; height: 56px; object-fit: contain; }
        .brand-name { font-size: 17pt; font-weight: bold; color: #075E4A; }
        .brand-tagline { font-size: 9.5pt; color: #4B5563; }
        .report-title { font-size: 13pt; font-weight: bold; color: #0E8A6D; text-align: left; }
        .meta { font-size: 8.5pt; color: #6B7280; margin: 2px 0; }
        .filters { margin: 6px 0; }
        .filter-chip {
            display: inline-block;
            border: 1px solid #A7F3D0;
            background: #ECFDF5;
            color: #047857;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 1px 8px;
            margin: 0 0 3px 4px;
            border-radius: 10px;
        }
        .summary { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 10px 0 14px 0; }
        .summary td {
            background: #F0FDF4;
            border: 1px solid #D1FAE5;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: center;
        }
        .summary .label { font-size: 8pt; color: #065F46; display: block; }
        .summary .value { font-size: 11pt; font-weight: bold; color: #065F46; display: block; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th, table.data td { border: 1px solid #D1D5DB; padding: 4px 7px; text-align: center; }
        table.data th { background: #075E4A; color: #FFFFFF; font-weight: bold; font-size: 9.5pt; }
        table.data td { font-size: 9.5pt; }
        table.data tr:nth-child(even) td { background: #F9FAFB; }
        .badge {
            display: inline-block;
            font-size: 8pt;
            font-weight: bold;
            padding: 0px 7px;
            border-radius: 9px;
            border: 1px solid transparent;
        }
        .b-green { background: #D1FAE5; color: #065F46; border-color: #6EE7B7; }
        .b-amber { background: #FEF3C7; color: #92400E; border-color: #FCD34D; }
        .b-red { background: #FEE2E2; color: #991B1B; border-color: #FCA5A5; }
        .b-gray { background: #F3F4F6; color: #374151; border-color: #D1D5DB; }
        .b-blue { background: #E0F2FE; color: #075985; border-color: #7DD3FC; }
        .footer { position: fixed; bottom: -11mm; left: 0; right: 0; text-align: center; font-size: 8pt; color: #9CA3AF; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 60px;">
                    @if (! empty($report['site']['logo']))
                        <img class="logo" src="{!! $report['site']['logo'] !!}" alt="الشعار">
                    @endif
                </td>
                <td>
                    <div class="brand-name">{{ $report['site']['name'] }}</div>
                    <div class="brand-tagline">{{ $report['site']['tagline'] }}</div>
                </td>
                <td style="text-align: left;">
                    <div class="report-title">{{ $report['heading'] }}</div>
                    <div class="meta">تاريخ الإنشاء: {{ $report['generated_at']->translatedFormat('d MMMM Y') }} • {{ $report['generated_at']->format('H:i') }}</div>
                </td>
            </tr>
        </table>
        @if (! empty($report['filters']))
            <div class="filters">
                @foreach ($report['filters'] as $filter)
                    <span class="filter-chip">{{ $filter['label'] }}: {{ $filter['value'] }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <table class="summary">
        <tr>
            @foreach ($report['summary'] as $item)
                <td>
                    <span class="value">{{ $item['value'] }}</span>
                    <span class="label">{{ $item['label'] }}</span>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                @foreach ($report['columns'] as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($report['columns'] as $col)
                        <td>
                            @if ($col['type'] === 'badge')
                                @php
                                    $state = $row[$col['key']] ?? null;
                                    $class = 'b-'.($col['colors'][$state] ?? 'gray');
                                @endphp
                                <span class="badge {{ $class }}">{{ $fmt($col, $state) }}</span>
                            @else
                                {{ $fmt($col, $row[$col['key']] ?? null) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    @php($colspan = max(count($report['columns']), 1))
                    <td colspan="{{ $colspan }}" style="padding: 18px; color: #6B7280;">لا توجد بيانات مطابقة لشروط التقرير.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        تم إنشاء هذا التقرير بواسطة منصة {{ $report['site']['name'] }} — صفحة {PAGE_NUM} من {PAGE_COUNT}
    </div>

</body>
</html>