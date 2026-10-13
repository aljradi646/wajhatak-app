<?php

namespace App\Mail;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PropertyModerationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Property $property,
        public string $decision,
        public ?string $reason = null,
    ) {
    }

    public function build(): static
    {
        $approved = $this->decision === 'approved';
        $title = $approved ? 'تم قبول عقارك في وجهتك' : 'تم رفض عقارك في وجهتك';
        $propertyTitle = e($this->property->title);
        $reference = e($this->property->reference_code);
        $reason = e(trim((string) $this->reason));

        $html = '<div dir="rtl" style="font-family:Arial,sans-serif;line-height:1.8">'
            .'<h2>'.e($title).'</h2>'
            .'<p>العقار: <strong>'.$propertyTitle.'</strong></p>'
            .'<p>المرجع: <strong>'.$reference.'</strong></p>'
            .($approved
                ? '<p>تم اعتماد عقارك ونشره على منصة وجهتك.</p>'
                : '<p>تعذر اعتماد العقار في مراجعته الحالية.</p><p><strong>سبب الرفض:</strong> '.$reason.'</p>')
            .'<p>يمكنك فتح تطبيق وجهتك ومراجعة تفاصيل العقار.</p>'
            .'</div>';

        return $this->subject($title)->html($html);
    }
}
