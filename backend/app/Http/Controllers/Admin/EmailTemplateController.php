<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmailTemplate;
use App\Models\EmailSetting;
use App\Models\EmailTemplateVersion;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('is_system', 'desc')->orderBy('name')->get();
        
        return view('admin.email-templates.index', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        return view('admin.email-templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:email_templates,key'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:190'],
            'html_content' => ['nullable', 'string'],
            'text_content' => ['nullable', 'string'],
            'css_styles' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_system'] = false;
        $data['version'] = 1;

        $template = EmailTemplate::create($data);

        ActivityLog::record('email_template', "تم إنشاء قالب بريد جديد: {$template->name}");

        return redirect()->route('admin.email-templates.edit', $template)
            ->with('status', 'تم إنشاء القالب بنجاح.');
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        $settings = EmailSetting::current();
        
        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
            'logoUrl' => $settings->getLogoUrlForEmail(),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:190'],
            'html_content' => ['nullable', 'string'],
            'text_content' => ['nullable', 'string'],
            'css_styles' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Prevent changing key for system templates
        if (!$emailTemplate->is_system) {
            $keyData = $request->validate(['key' => ['required', 'string', 'max:100', 'unique:email_templates,key,' . $emailTemplate->id]]);
            $data['key'] = $keyData['key'];
        }

        $data['is_active'] = $data['is_active'] ?? true;

        EmailTemplateVersion::create([
            'email_template_id'=>$emailTemplate->id,
            'version'=>(int)$emailTemplate->version,
            'subject'=>$emailTemplate->subject,
            'html_content'=>$emailTemplate->html_content,
            'text_content'=>$emailTemplate->text_content,
            'css_styles'=>$emailTemplate->css_styles,
            'variables'=>$emailTemplate->variables,
            'created_by'=>$request->user()?->id,
            'change_note'=>$request->input('change_note'),
        ]);

        $emailTemplate->update($data);
        $emailTemplate->incrementVersion();

        ActivityLog::record('email_template', "تم تحديث قالب البريد: {$emailTemplate->name}");

        return back()->with('status', 'تم تحديث القالب بنجاح.');
    }

    public function history(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.history', [
            'template'=>$emailTemplate,
            'versions'=>$emailTemplate->versions()->with('creator:id,name')->get(),
        ]);
    }

    public function restore(Request $request, EmailTemplate $emailTemplate, EmailTemplateVersion $version)
    {
        abort_unless($version->email_template_id === $emailTemplate->id,404);
        EmailTemplateVersion::create([
            'email_template_id'=>$emailTemplate->id,
            'version'=>(int)$emailTemplate->version,
            'subject'=>$emailTemplate->subject,
            'html_content'=>$emailTemplate->html_content,
            'text_content'=>$emailTemplate->text_content,
            'css_styles'=>$emailTemplate->css_styles,
            'variables'=>$emailTemplate->variables,
            'created_by'=>$request->user()?->id,
            'change_note'=>'حفظ النسخة الحالية قبل الاستعادة',
        ]);
        $emailTemplate->update([
            'subject'=>$version->subject,
            'html_content'=>$version->html_content,
            'text_content'=>$version->text_content,
            'css_styles'=>$version->css_styles,
            'variables'=>$version->variables,
        ]);
        $emailTemplate->incrementVersion();
        ActivityLog::record('email_template',"تمت استعادة إصدار {$version->version} من قالب البريد: {$emailTemplate->name}");
        return back()->with('status','تمت استعادة الإصدار كنسخة جديدة.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        if (!$emailTemplate->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف القوالب النظامية.');
        }

        $name = $emailTemplate->name;
        $emailTemplate->delete();

        ActivityLog::record('email_template', "تم حذف قالب البريد: {$name}");

        return redirect()->route('admin.email-templates.index')
            ->with('status', 'تم حذف القالب بنجاح.');
    }

    /**
     * Preview template with sample data.
     */
    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $sampleData = $request->input('variables', []);
        
        // Default sample data for common variables
        $defaults = [
            'name' => 'أحمد محمد',
            'code' => '123456',
            'ttl' => '15',
            'reason' => 'سبب الرفض',
            'property' => 'شقة في الرياض',
            'email' => 'ahmed@example.com',
        ];
        
        $variables = array_merge($defaults, $sampleData);
        
        $rendered = $emailTemplate->render($variables);
        
        return response()->json([
            'subject' => $rendered['subject'],
            'html' => $rendered['html'],
            'text' => $rendered['text'],
        ]);
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(EmailTemplate $emailTemplate)
    {
        $newTemplate = $emailTemplate->replicate();
        $newTemplate->key = $emailTemplate->key . '_copy_' . time();
        $newTemplate->name = $emailTemplate->name . ' (نسخة)';
        $newTemplate->is_system = false;
        $newTemplate->version = 1;
        $newTemplate->save();

        ActivityLog::record('email_template', "تم نسخ قالب البريد: {$emailTemplate->name}");

        return redirect()->route('admin.email-templates.edit', $newTemplate)
            ->with('status', 'تم نسخ القالب بنجاح.');
    }
}

