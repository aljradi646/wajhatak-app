<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'email_verification',
                'name' => 'رمز التحقق من البريد',
                'description' => 'رسالة التحقق من البريد الإلكتروني برمز صالح لمدة محددة.',
                'template_type' => 'verification',
                'subject' => 'رمز التحقق من {{app.name}}',
                'html_content' => $this->getEmailVerificationHtml(),
                'text_content' => "مرحبًا {{user.name}}\n\nرمز التحقق الخاص بك هو: {{code}}\nصلاحية الرمز: {{ttl}} دقيقة.\n\nإذا لم تطلب هذا الرمز، تجاهل الرسالة.",
                'variables' => ['user.name', 'code', 'ttl', 'app.name', 'app.logo_url'],
            ],
            [
                'key' => 'agent_approved',
                'name' => 'قبول توثيق الوكيل',
                'description' => 'إشعار الوكيل باعتماد حسابه واستعداده لإدارة العقارات.',
                'template_type' => 'agent',
                'subject' => 'تم توثيق حسابك كوكيل في {{app.name}}',
                'html_content' => $this->getAgentApprovedHtml(),
                'text_content' => "مرحبًا {{user.name}}\n\nتم اعتماد حسابك كوكيل عقاري في {{app.name}}.\nيمكنك الآن البدء بإدارة العقارات وطلبات المشاهدة.",
                'variables' => ['user.name', 'agent.name', 'app.url', 'app.name', 'app.logo_url'],
            ],
            [
                'key' => 'agent_rejected',
                'name' => 'رفض توثيق الوكيل',
                'description' => 'إشعار الوكيل برفض طلب التوثيق مع سبب واضح وخطوة تالية.',
                'template_type' => 'agent',
                'subject' => 'تحديث طلب توثيق حسابك — {{app.name}}',
                'html_content' => $this->getAgentRejectedHtml(),
                'text_content' => "مرحبًا {{user.name}}\n\nلم تتم الموافقة على طلب توثيق حسابك.\nالسبب: {{reason}}\n\nيمكنك معالجة الملاحظات ثم إعادة التقديم.",
                'variables' => ['user.name', 'reason', 'app.url', 'app.name', 'app.logo_url'],
            ],
            [
                'key' => 'property_published',
                'name' => 'تم نشر العقار',
                'description' => 'إشعار المالك بأن العقار أصبح منشورًا ومتاحًا داخل المنصة.',
                'template_type' => 'property',
                'subject' => 'تم نشر عقارك بنجاح — {{property.title}}',
                'html_content' => $this->getPropertyPublishedHtml(),
                'text_content' => "مرحبًا {{user.name}}\n\nتم نشر العقار {{property.title}} بنجاح.\nالمدينة: {{property.city}}\nالسعر: {{property.price}}\nالمرجع: {{property.reference_code}}",
                'variables' => ['user.name', 'property.title', 'property.city', 'property.price', 'property.reference_code', 'app.url', 'app.name', 'app.logo_url'],
            ],
            [
                'key' => 'property_rejected',
                'name' => 'رفض نشر العقار',
                'description' => 'إشعار المالك بسبب رفض نشر العقار مع توجيه واضح لإعادة التقديم.',
                'template_type' => 'property',
                'subject' => 'مطلوب تعديل عقارك — {{property.title}}',
                'html_content' => $this->getPropertyRejectedHtml(),
                'text_content' => "مرحبًا {{user.name}}\n\nتعذر نشر العقار {{property.title}}.\nالسبب: {{reason}}\n\nعدّل البيانات وأعد التقديم.",
                'variables' => ['user.name', 'property.title', 'reason', 'app.url', 'app.name', 'app.logo_url'],
            ],
        ];

        DB::transaction(function () use ($templates): void {
            foreach ($templates as $definition) {
                $template = EmailTemplate::query()->firstOrNew(['key' => $definition['key']]);
                $isNew = ! $template->exists;

                $needsLegacyUpgrade = $template->exists
                    && $template->is_system
                    && (int) $template->version === 1
                    && (int) ($template->published_version ?? 1) === 1
                    && (
                        str_contains((string) $template->html_content, '{name}')
                        || str_contains((string) $template->html_content, '{{logo}}')
                    );

                if ($isNew || $needsLegacyUpgrade) {
                    $template->fill(array_merge($definition, [
                        'is_system' => true,
                        'is_active' => true,
                        'version' => 1,
                        'status' => 'published',
                        'published_version' => 1,
                        'published_at' => now(),
                        'archived_at' => null,
                        'autosaved_at' => null,
                    ]));
                    $template->save();
                }

                $snapshotData = [
                    'subject' => (string) $template->subject,
                    'html_content' => $template->html_content,
                    'text_content' => $template->text_content,
                    'css_styles' => $template->css_styles,
                    'variables' => $template->variables,
                    'created_by' => null,
                    'change_note' => $needsLegacyUpgrade
                        ? 'تحديث القوالب النظامية القديمة إلى قوالب الإنتاج الحديثة'
                        : ($isNew ? 'قالب إنتاجي أساسي' : 'تهيئة نسخة القالب الحالية'),
                ];

                if ($needsLegacyUpgrade) {
                    EmailTemplateVersion::query()->updateOrCreate(
                        [
                            'email_template_id' => $template->id,
                            'version' => (int) $template->version,
                        ],
                        $snapshotData,
                    );
                } else {
                    EmailTemplateVersion::query()->firstOrCreate(
                        [
                            'email_template_id' => $template->id,
                            'version' => (int) $template->version,
                        ],
                        $snapshotData,
                    );
                }
            }
        });
    }

    private function getEmailVerificationHtml(): string
    {
        return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#f4f7f6;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;border-collapse:collapse;background:#ffffff;border-radius:20px;overflow:hidden;">
<tr><td style="padding:34px 30px;text-align:center;background:#075e4a;">
<img src="{{app.logo_url}}" alt="{{app.name}}" width="120" style="display:block;width:120px;max-width:120px;height:auto;margin:0 auto 14px;">
<h1 style="margin:0;color:#ffffff;font:700 25px/1.35 Arial,sans-serif;">{{app.name}}</h1>
<p style="margin:8px 0 0;color:#dceee9;font:400 14px/1.7 Arial,sans-serif;">تأكيد بريدك الإلكتروني</p>
</td></tr>
<tr><td style="padding:34px 30px;text-align:right;font:400 16px/1.8 Arial,sans-serif;color:#263631;">
<h2 style="margin:0 0 10px;color:#075e4a;font:700 22px/1.5 Arial,sans-serif;">مرحبًا {{user.name}}</h2>
<p style="margin:0 0 18px;">أدخل الرمز التالي في التطبيق لإكمال التحقق من بريدك الإلكتروني.</p>
<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:18px auto 22px;">
<tr><td style="padding:18px 28px;border:1px solid #dce8e3;border-radius:14px;background:#f5faf8;color:#075e4a;font:700 30px/1 Arial,sans-serif;letter-spacing:7px;text-align:center;">{{code}}</td></tr>
</table>
<p style="margin:0;color:#63736d;">صلاحية الرمز: <strong>{{ttl}} دقيقة</strong>.</p>
<p style="margin:14px 0 0;font-size:13px;color:#7b8783;">إذا لم تطلب هذا الرمز، تجاهل هذه الرسالة حفاظًا على أمان حسابك.</p>
</td></tr>
<tr><td style="padding:18px 30px;text-align:center;background:#f7faf9;color:#7b8783;font:400 12px/1.6 Arial,sans-serif;">رسالة آلية من {{app.name}}</td></tr>
</table></td></tr></table>
HTML;
    }

    private function getAgentApprovedHtml(): string
    {
        return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#f4f7f6;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;border-collapse:collapse;background:#ffffff;border-radius:20px;overflow:hidden;">
<tr><td style="padding:34px 30px;text-align:center;background:#075e4a;">
<img src="{{app.logo_url}}" alt="{{app.name}}" width="120" style="display:block;width:120px;max-width:120px;height:auto;margin:0 auto 14px;">
<h1 style="margin:0;color:#ffffff;font:700 25px/1.35 Arial,sans-serif;">تم توثيق حساب الوكيل</h1>
</td></tr>
<tr><td style="padding:34px 30px;text-align:right;font:400 16px/1.8 Arial,sans-serif;color:#263631;">
<div style="display:inline-block;padding:7px 12px;border-radius:999px;background:#eaf7f1;color:#087253;font:700 12px Arial,sans-serif;">تم الاعتماد</div>
<h2 style="margin:14px 0 10px;color:#075e4a;font:700 22px/1.5 Arial,sans-serif;">مرحبًا {{user.name}}</h2>
<p style="margin:0 0 14px;">تم اعتماد حسابك كوكيل عقاري في {{app.name}}، وأصبح بإمكانك إدارة العقارات وطلبات المشاهدة.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:20px 0;background:#f7faf9;">
<tr><td style="padding:16px;border-right:4px solid #0e8a6d;">
<strong style="color:#075e4a;">الخطوات التالية</strong>
<ul style="margin:8px 0 0;padding:0 20px 0 0;"><li>إكمال بيانات الوكيل</li><li>إضافة العقارات الحقيقية</li><li>متابعة طلبات المشاهدة</li></ul>
</td></tr></table>
<a href="{{app.url}}" style="display:inline-block;padding:12px 20px;border-radius:10px;background:#075e4a;color:#ffffff;text-decoration:none;font-weight:700;">فتح منصة {{app.name}}</a>
</td></tr>
<tr><td style="padding:18px 30px;text-align:center;background:#f7faf9;color:#7b8783;font:400 12px/1.6 Arial,sans-serif;">{{app.name}} — إشعار اعتماد آلي</td></tr>
</table></td></tr></table>
HTML;
    }

    private function getAgentRejectedHtml(): string
    {
        return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#f6f7f7;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;border-collapse:collapse;background:#ffffff;border-radius:20px;overflow:hidden;">
<tr><td style="padding:34px 30px;text-align:center;background:#8b1e1e;">
<img src="{{app.logo_url}}" alt="{{app.name}}" width="120" style="display:block;width:120px;max-width:120px;height:auto;margin:0 auto 14px;">
<h1 style="margin:0;color:#ffffff;font:700 25px/1.35 Arial,sans-serif;">تحديث طلب التوثيق</h1>
</td></tr>
<tr><td style="padding:34px 30px;text-align:right;font:400 16px/1.8 Arial,sans-serif;color:#263631;">
<h2 style="margin:0 0 10px;color:#8b1e1e;font:700 22px/1.5 Arial,sans-serif;">مرحبًا {{user.name}}</h2>
<p style="margin:0 0 16px;">تعذر اعتماد طلب توثيق حسابك كوكيل في {{app.name}}.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#fff5f5;">
<tr><td style="padding:18px;border-right:4px solid #dc2626;">
<strong style="color:#991b1b;">سبب الرفض</strong>
<p style="margin:8px 0 0;">{{reason}}</p>
</td></tr></table>
<p style="margin:18px 0 0;">عالج الملاحظات ثم أعد تقديم الطلب من حسابك.</p>
<a href="{{app.url}}" style="display:inline-block;margin-top:12px;padding:12px 20px;border-radius:10px;background:#8b1e1e;color:#ffffff;text-decoration:none;font-weight:700;">العودة إلى المنصة</a>
</td></tr>
<tr><td style="padding:18px 30px;text-align:center;background:#fafafa;color:#7b8783;font:400 12px/1.6 Arial,sans-serif;">إشعار آلي من {{app.name}}</td></tr>
</table></td></tr></table>
HTML;
    }

    private function getPropertyPublishedHtml(): string
    {
        return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#f4f7f6;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;border-collapse:collapse;background:#ffffff;border-radius:20px;overflow:hidden;">
<tr><td style="padding:30px;text-align:right;background:#075e4a;">
<h1 style="margin:0;color:#ffffff;font:700 24px/1.4 Arial,sans-serif;">تم نشر عقارك</h1>
<p style="margin:6px 0 0;color:#dceee9;font:400 14px/1.7 Arial,sans-serif;">{{app.name}}</p>
</td></tr>
<tr><td style="padding:32px 30px;text-align:right;font:400 16px/1.8 Arial,sans-serif;color:#263631;">
<h2 style="margin:0 0 10px;color:#075e4a;font:700 22px/1.5 Arial,sans-serif;">مرحبًا {{user.name}}</h2>
<p style="margin:0 0 20px;">تم نشر العقار التالي وأصبح جاهزًا للعرض داخل المنصة.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;border:1px solid #dce8e3;border-radius:14px;">
<tr><td style="padding:16px;"><strong style="color:#075e4a;">العقار</strong><br>{{property.title}}</td></tr>
<tr><td style="padding:16px;border-top:1px solid #edf2f0;"><strong style="color:#55645f;">المدينة</strong><br>{{property.city}}</td></tr>
<tr><td style="padding:16px;border-top:1px solid #edf2f0;"><strong style="color:#55645f;">السعر</strong><br>{{property.price}}</td></tr>
<tr><td style="padding:16px;border-top:1px solid #edf2f0;"><strong style="color:#55645f;">المرجع</strong><br>{{property.reference_code}}</td></tr>
</table>
<a href="{{app.url}}" style="display:inline-block;margin-top:20px;padding:12px 20px;border-radius:10px;background:#075e4a;color:#ffffff;text-decoration:none;font-weight:700;">إدارة العقار</a>
</td></tr>
<tr><td style="padding:18px 30px;text-align:center;background:#f7faf9;color:#7b8783;font:400 12px/1.6 Arial,sans-serif;">إشعار نشر من {{app.name}}</td></tr>
</table></td></tr></table>
HTML;
    }

    private function getPropertyRejectedHtml(): string
    {
        return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#f6f7f7;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;border-collapse:collapse;background:#ffffff;border-radius:20px;overflow:hidden;">
<tr><td style="padding:30px;text-align:right;background:#8b1e1e;">
<h1 style="margin:0;color:#ffffff;font:700 24px/1.4 Arial,sans-serif;">تعذر نشر العقار</h1>
<p style="margin:6px 0 0;color:#ffe2e2;font:400 14px/1.7 Arial,sans-serif;">مطلوب تعديل قبل إعادة التقديم</p>
</td></tr>
<tr><td style="padding:32px 30px;text-align:right;font:400 16px/1.8 Arial,sans-serif;color:#263631;">
<h2 style="margin:0 0 10px;color:#8b1e1e;font:700 22px/1.5 Arial,sans-serif;">مرحبًا {{user.name}}</h2>
<p style="margin:0 0 18px;">تعذر نشر العقار <strong>{{property.title}}</strong> في {{app.name}}.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#fff5f5;">
<tr><td style="padding:18px;border-right:4px solid #dc2626;">
<strong style="color:#991b1b;">السبب</strong>
<p style="margin:8px 0 0;">{{reason}}</p>
</td></tr></table>
<p style="margin:18px 0 0;">عدّل البيانات المطلوبة ثم أعد إرسال العقار للمراجعة.</p>
<a href="{{app.url}}" style="display:inline-block;margin-top:12px;padding:12px 20px;border-radius:10px;background:#8b1e1e;color:#ffffff;text-decoration:none;font-weight:700;">فتح لوحة المنصة</a>
</td></tr>
<tr><td style="padding:18px 30px;text-align:center;background:#fafafa;color:#7b8783;font:400 12px/1.6 Arial,sans-serif;">إشعار آلي من {{app.name}}</td></tr>
</table></td></tr></table>
HTML;
    }
}
