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
                'description' => 'رسالة تحتوي على رمز التحقق المُرسل للمستخدم',
                'template_type' => 'verification',
                'subject' => 'رمز التحقق من وجهتك',
                'html_content' => $this->getEmailVerificationHtml(),
                'text_content' => 'مرحبا {name}،

رمز التحقق الخاص بك هو: {code}

صلاحية الرمز: {ttl} دقيقة.

إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.',
                'variables' => ['name', 'code', 'ttl'],
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'agent_approved',
                'name' => 'موافقة توثيق الوكيل',
                'description' => 'رسالة إشعار الوكيل بموافقة توثيقه',
                'template_type' => 'agent',
                'subject' => 'تم توثيق حسابك في وجهتك ✓',
                'html_content' => $this->getAgentApprovedHtml(),
                'text_content' => 'أهلا {name}،

نود إعلامك بأن حسابك كوكيل عقاري في منصة وجهتك قد تم توثيقه بنجاح.

يمكنك الآن البدء بنشر العقارات والاستفادة من جميع مميزات المنصة.

مع تحيات فريق وجهتك.',
                'variables' => ['name'],
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'agent_rejected',
                'name' => 'رفض توثيق الوكيل',
                'description' => 'رسالة إشعار الوكيل برفض توثيقه مع السبب',
                'template_type' => 'agent',
                'subject' => 'تم رفض طلب توثيق حسابك',
                'html_content' => $this->getAgentRejectedHtml(),
                'text_content' => 'أهلا {name}،

نأسف لإبلاغك بأن طلب توثيق حسابك كوكيل عقاري في منصة وجهتك قد تم رفضه.

سبب الرفض: {reason}

يمكنك تقديم طلب جديد بعد معالجة الملاحظات.

مع تحيات فريق وجهتك.',
                'variables' => ['name', 'reason'],
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'property_published',
                'name' => 'نشر العقار',
                'description' => 'رسالة إشعار بنشر العقار بنجاح',
                'template_type' => 'property',
                'subject' => 'تم نشر عقارك بنجاح ✓',
                'html_content' => $this->getPropertyPublishedHtml(),
                'text_content' => 'أهلا {name}،

تم نشر عقارك "{property}" بنجاح في منصة وجهتك.

يمكنك الآن متابعة الاستفسارات والطلبات من خلال لوحة التحكم.

مع تحيات فريق وجهتك.',
                'variables' => ['name', 'property'],
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'property_rejected',
                'name' => 'رفض نشر العقار',
                'description' => 'رسالة إشعار برفض نشر العقار مع السبب',
                'template_type' => 'property',
                'subject' => 'تم رفض نشر عقارك',
                'html_content' => $this->getPropertyRejectedHtml(),
                'text_content' => 'أهلا {name}،

نأسف لإبلاغك بأن طلب نشر عقارك "{property}" قد تم رفضه.

سبب الرفض: {reason}

يمكنك تعديل البيانات وإعادة تقديم الطلب.

مع تحيات فريق وجهتك.',
                'variables' => ['name', 'property', 'reason'],
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($templates): void {
            foreach ($templates as $definition) {
                $template = EmailTemplate::updateOrCreate(
                    ['key' => $definition['key']],
                    array_merge($definition, [
                        'status' => 'published',
                        'published_version' => 1,
                        'version' => 1,
                        'published_at' => now(),
                        'archived_at' => null,
                        'autosaved_at' => null,
                    ])
                );

                EmailTemplateVersion::updateOrCreate(
                    ['email_template_id' => $template->id, 'version' => 1],
                    [
                        'subject' => (string) $template->subject,
                        'html_content' => $template->html_content,
                        'text_content' => $template->text_content,
                        'css_styles' => $template->css_styles,
                        'variables' => $template->variables,
                        'created_by' => null,
                        'change_note' => 'قالب إنتاجي أساسي — تمت تهيئته بواسطة Seeder',
                    ],
                );
            }
        });
    }

    private function getEmailVerificationHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #075E4A, #0E8A6D); padding: 30px; text-align: center;">
            {{logo}}
            <h1 style="color: white; margin: 0; font-size: 24px;">وجهتك</h1>
        </div>
        
        <div style="padding: 30px;">
            <h2 style="color: #075E4A; margin-top: 0;">رمز التحقق</h2>
            <p>أهلا {name}،</p>
            <p>رمز التحقق الخاص بك هو:</p>
            
            <div style="background: #f0f0f0; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; border-radius: 8px; margin: 20px 0; color: #075E4A;">
                {code}
            </div>
            
            <p>صلاحية الرمز: <strong>{ttl} دقيقة</strong></p>
            <p>إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.</p>
        </div>
        
        <div style="background: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="margin: 0; color: #666; font-size: 12px;">
                تم إرسال هذه الرسالة من منصة وجهتك العقارية
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getAgentApprovedHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موافقة التوثيق</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #075E4A, #0E8A6D); padding: 30px; text-align: center;">
            {{logo}}
            <h1 style="color: white; margin: 0; font-size: 24px;">وجهتك</h1>
        </div>
        
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <span style="font-size: 48px;">✓</span>
            </div>
            <h2 style="color: #075E4A; margin-top: 0; text-align: center;">تم توثيق حسابك بنجاح!</h2>
            <p>أهلا {name}،</p>
            <p>نود إعلامك بأن حسابك كوكيل عقاري في منصة وجهتك قد تم توثيقه بنجاح.</p>
            
            <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #075E4A;">
                <h3 style="margin-top: 0; color: #075E4A;">ما يمكنك فعله الآن:</h3>
                <ul style="margin: 10px 0; padding-right: 20px;">
                    <li>نشر العقارات</li>
                    <li>إدارة الاستفسارات</li>
                    <li>متابعة طلبات المشاهدة</li>
                    <li>الاستفادة من جميع مميزات المنصة</li>
                </ul>
            </div>
            
            <p>يمكنك البدء فوراً من خلال لوحة التحكم.</p>
        </div>
        
        <div style="background: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="margin: 0; color: #666; font-size: 12px;">
                تم إرسال هذه الرسالة من منصة وجهتك العقارية
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getAgentRejectedHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفض التوثيق</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #dc2626, #ef4444); padding: 30px; text-align: center;">
            {{logo}}
            <h1 style="color: white; margin: 0; font-size: 24px;">وجهتك</h1>
        </div>
        
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <span style="font-size: 48px;">✕</span>
            </div>
            <h2 style="color: #dc2626; margin-top: 0; text-align: center;">تم رفض طلب التوثيق</h2>
            <p>أهلا {name}،</p>
            <p>نأسف لإبلاغك بأن طلب توثيق حسابك كوكيل عقاري في منصة وجهتك قد تم رفضه.</p>
            
            <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #dc2626;">
                <h3 style="margin-top: 0; color: #dc2626;">سبب الرفض:</h3>
                <p style="margin: 10px 0;">{reason}</p>
            </div>
            
            <p>يمكنك تقديم طلب جديد بعد معالجة الملاحظات المذكورة أعلاه.</p>
        </div>
        
        <div style="background: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="margin: 0; color: #666; font-size: 12px;">
                تم إرسال هذه الرسالة من منصة وجهتك العقارية
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getPropertyPublishedHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نشر العقار</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #075E4A, #0E8A6D); padding: 30px; text-align: center;">
            {{logo}}
            <h1 style="color: white; margin: 0; font-size: 24px;">وجهتك</h1>
        </div>
        
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <span style="font-size: 48px;">🏠</span>
            </div>
            <h2 style="color: #075E4A; margin-top: 0; text-align: center;">تم نشر عقارك بنجاح!</h2>
            <p>أهلا {name}،</p>
            <p>تم نشر عقارك "<strong>{property}</strong>" بنجاح في منصة وجهتك.</p>
            
            <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #075E4A;">
                <h3 style="margin-top: 0; color: #075E4A;">الخطوات التالية:</h3>
                <ul style="margin: 10px 0; padding-right: 20px;">
                    <li>راقب الاستفسارات الواردة</li>
                    <li>رد على طلبات المشاهدة</li>
                    <li>حدّث معلومات العقار عند الحاجة</li>
                </ul>
            </div>
            
            <p>يمكنك إدارة عقارك من خلال لوحة التحكم.</p>
        </div>
        
        <div style="background: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="margin: 0; color: #666; font-size: 12px;">
                تم إرسال هذه الرسالة من منصة وجهتك العقارية
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getPropertyRejectedHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفض نشر العقار</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #dc2626, #ef4444); padding: 30px; text-align: center;">
            {{logo}}
            <h1 style="color: white; margin: 0; font-size: 24px;">وجهتك</h1>
        </div>
        
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <span style="font-size: 48px;">🏠</span>
            </div>
            <h2 style="color: #dc2626; margin-top: 0; text-align: center;">تم رفض نشر العقار</h2>
            <p>أهلا {name}،</p>
            <p>نأسف لإبلاغك بأن طلب نشر عقارك "<strong>{property}</strong>" قد تم رفضه.</p>
            
            <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #dc2626;">
                <h3 style="margin-top: 0; color: #dc2626;">سبب الرفض:</h3>
                <p style="margin: 10px 0;">{reason}</p>
            </div>
            
            <p>يمكنك تعديل البيانات وإعادة تقديم الطلب من خلال لوحة التحكم.</p>
        </div>
        
        <div style="background: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="margin: 0; color: #666; font-size: 12px;">
                تم إرسال هذه الرسالة من منصة وجهتك العقارية
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
