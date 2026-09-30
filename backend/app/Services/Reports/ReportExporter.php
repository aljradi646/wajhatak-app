<?php

namespace App\Services\Reports;

use Closure;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * تصدير التقارير إلى CSV/JSON/Excel — منطق واحد مُشترك بين لوحة الإدارة
 * وتقارير الوكيل في التطبيق، فلا تتكرر نفس الأكواد في مكانين.
 *
 * البنية المتوقعة لـ $data (نفس بنية ReportController):
 *   type, heading, site{name,tagline,currency}, filters[], columns[], rows[], summary[], generated_at
 */
class ReportExporter
{
    public const FORMATS = ['csv', 'json', 'excel'];

    /** @param array<string, mixed> $data */
    public function csv(array $data, Closure $fmt): HttpResponse
    {
        $filename = $this->fileName($data['type'], 'csv');
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

    /** @param array<string, mixed> $data */
    public function json(array $data, Closure $fmt): HttpResponse
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
        ], 200, $this->contentDisposition($this->fileName($data['type'], 'json'), 'application/json; charset=UTF-8'));
    }

    /** @param array<string, mixed> $data */
    public function excel(array $data, Closure $fmt): HttpResponse
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
            .'<Style ss:ID="Summary"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>'
            .'<Interior ss:Color="#E8F5F0" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>'
            .'</Styles>'."\n";
        $xml .= '<Worksheet ss:Name="'.$this->escape($data['type']).'"><Table>'."\n";
        $xml .= '<Column ss:AutoFitWidth="1"/>'."\n";

        $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Title"><Data ss:Type="String">'
            .$this->escape($data['site']['name'].' — '.$data['heading']).'</Data></Cell></Row>'."\n";
        $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Meta"><Data ss:Type="String">'
            .$this->escape($data['site']['tagline']).'</Data></Cell></Row>'."\n";
        foreach ($data['filters'] as $filter) {
            $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Meta"><Data ss:Type="String">'
                .$this->escape($filter['label'].': '.$filter['value']).'</Data></Cell></Row>'."\n";
        }
        $xml .= '<Row><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Meta"><Data ss:Type="String">'
            .$this->escape('تاريخ الإنشاء: '.$data['generated_at']->format('Y-m-d H:i')).'</Data></Cell></Row>'."\n";

        $xml .= '<Row>';
        foreach ($columns as $col) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.$this->escape($col['label']).'</Data></Cell>';
        }
        $xml .= '</Row>'."\n";

        foreach ($data['rows'] as $row) {
            $xml .= '<Row>';
            foreach ($columns as $col) {
                $raw = $row[$col['key']] ?? null;
                if (in_array($col['type'], ['money', 'number', 'rating'], true)) {
                    $xml .= '<Cell ss:StyleID="Number"><Data ss:Type="Number">'.(is_numeric($raw) ? $raw : 0).'</Data></Cell>';
                } else {
                    $xml .= '<Cell ss:StyleID="Text"><Data ss:Type="String">'.$this->escape($fmt($col, $raw)).'</Data></Cell>';
                }
            }
            $xml .= '</Row>'."\n";
        }

        $xml .= '<Row/><Row/>'."\n";
        foreach ($data['summary'] as $item) {
            $xml .= '<Row><Cell ss:StyleID="Summary"><Data ss:Type="String">'.$this->escape($item['label'])
                .'</Data></Cell><Cell ss:MergeAcross="'.$merge.'" ss:StyleID="Summary"><Data ss:Type="String">'
                .$this->escape((string) $item['value']).'</Data></Cell></Row>'."\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return Response::make($xml, 200, $this->contentDisposition($this->fileName($data['type'], 'xls'), 'application/vnd.ms-excel'));
    }

    public function fileName(string $type, string $extension): string
    {
        return 'wajhatak-'.$type.'-'.now()->format('Y-m-d').'.'.$extension;
    }

    private function contentDisposition(string $filename, string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
