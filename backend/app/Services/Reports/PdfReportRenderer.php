<?php

namespace App\Services\Reports;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * توليد PDF عربي حقيقي (تشكيل الحروف + خط Amiri) — مُشترك بين تقارير
 * لوحة الإدارة وتقارير الوكيل في التطبيق.
 *
 * سبب وجوده: تكرار إعداد dompdf وتسجيل الخط في أكثر من مكان كان يعرّض
 * التوليد للكسر عند تغيير نسخة dompdf (تغيّرت توقيعات registerFont).
 */
class PdfReportRenderer
{
    private const FONT_FAMILY = 'Amiri';

    /**
     * @param  string  $view  مسار Blade التي تعرض $report (بنية التقارير الموحدة)
     * @param  array<string, mixed>  $data  ['report' => ..., 'fmt' => Closure]
     */
    public function render(string $view, array $data, string $filename): HttpResponse
    {
        $fontCache = storage_path('fonts/cache');
        if (! is_dir($fontCache)) {
            @mkdir($fontCache, 0775, true);
        }

        $html = $this->shapeArabic(view($view, $data)->render());

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'fontDir' => public_path('fonts'),
            'fontCache' => $fontCache,
            'isFontSubsettingEnabled' => false,
            'defaultFont' => self::FONT_FAMILY,
        ]);

        $dompdf = $pdf->getDompdf();
        $fontMetrics = $dompdf->getFontMetrics();

        // توقيع dompdf 3: registerFont(array $style, string $remoteFile, $context = null)
        $fontMetrics->registerFont(
            ['family' => self::FONT_FAMILY, 'style' => 'normal', 'weight' => 'normal', 'subset' => false],
            public_path('fonts/Amiri-Regular.ttf'),
        );
        $fontMetrics->registerFont(
            ['family' => self::FONT_FAMILY, 'style' => 'normal', 'weight' => 'bold', 'subset' => false],
            public_path('fonts/Amiri-Bold.ttf'),
        );

        $dompdf->render();

        return $pdf->download($filename);
    }

    /** تشكيل الحروف العربية مع الحفاظ على وسوم HTML كما هي. */
    private function shapeArabic(string $html): string
    {
        $arabic = new Arabic;
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
}
