<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use App\Services\Mail\DynamicMailService;
use App\Services\Mail\EmailTemplateVariableRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return view('admin.email-templates.index', [
            'templates' => EmailTemplate::query()->orderByDesc('is_system')->orderBy('name')->paginate(24)->withQueryString(),
            'variableRegistry' => EmailTemplateVariableRegistry::definitions(),
        ]);
    }

    public function create()
    {
        return view('admin.email-templates.create', [
            'variables' => EmailTemplateVariableRegistry::definitions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $userId = $request->user()?->id;

        $template = DB::transaction(function () use ($data, $userId): EmailTemplate {
            $data['is_system'] = false;
            $data['version'] = 1;
            $data['status'] = 'draft';
            $data['last_edited_by'] = $userId;

            $template = EmailTemplate::create($data);
            $this->createSnapshot($template, $userId, 'النسخة الأولى');

            return $template;
        });

        ActivityLog::record('email_template', "تم إنشاء قالب بريد: {$template->name}", $template);

        return redirect()->route('admin.email-templates.edit', $template)->with('status', 'تم إنشاء القالب كمسودة.');
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
            'logoUrl' => EmailSetting::current()->getLogoUrlForEmail(),
            'variables' => EmailTemplateVariableRegistry::definitions(),
            'previewValues' => EmailTemplateVariableRegistry::previewValues(),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $this->validated($request, $emailTemplate);
        $userId = $request->user()?->id;

        DB::transaction(function () use ($emailTemplate, $data, $userId, $request): void {
            $nextVersion = (int) $emailTemplate->version + 1;
            $emailTemplate->update(array_merge($data, [
                'version' => $nextVersion,
                'status' => 'draft',
                'last_edited_by' => $userId,
                'published_at' => null,
                'archived_at' => null,
            ]));
            $this->createSnapshot($emailTemplate->fresh(), $userId, (string) ($request->input('change_note') ?: 'حفظ نسخة جديدة'));
        });

        ActivityLog::record('email_template', "تم حفظ إصدار جديد من قالب: {$emailTemplate->name}", $emailTemplate, properties: ['version' => $emailTemplate->version]);

        return back()->with('status', 'تم حفظ المسودة وإنشاء إصدار جديد.');
    }

    public function autosave(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $data = $this->validated($request, $emailTemplate);
        $emailTemplate->update(array_merge($data, [
            'status' => $emailTemplate->status === 'published' ? 'draft' : $emailTemplate->status,
            'last_edited_by' => $request->user()?->id,
            'autosaved_at' => now(),
        ]));

        return response()->json([
            'ok' => true,
            'saved_at' => optional($emailTemplate->autosaved_at)->toISOString(),
            'version' => (int) $emailTemplate->version,
            'status' => $emailTemplate->status,
        ]);
    }

    public function publish(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        abort_if($emailTemplate->status === 'archived', 409, 'لا يمكن نشر قالب مؤرشف قبل استعادته.');

        DB::transaction(function () use ($emailTemplate, $request): void {
            $template = $emailTemplate->fresh();
            if (! $template->versions()->where('version', $template->version)->exists()) {
                $this->createSnapshot($template, $request->user()?->id, 'نسخة النشر');
            }

            $template->update([
                'status' => 'published',
                'is_active' => true,
                'published_version' => $template->version,
                'published_at' => now(),
                'archived_at' => null,
                'last_edited_by' => $request->user()?->id,
            ]);
        });

        ActivityLog::record('email_template', "تم نشر قالب البريد: {$emailTemplate->name}", $emailTemplate, properties: ['published_version' => $emailTemplate->version]);

        return response()->json(['ok' => true, 'status' => 'published', 'version' => (int) $emailTemplate->version]);
    }

    public function archive(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        abort_if($emailTemplate->is_system, 403, 'لا يمكن أرشفة قالب نظامي.');

        $emailTemplate->update([
            'status' => 'archived',
            'is_active' => false,
            'archived_at' => now(),
            'last_edited_by' => $request->user()?->id,
        ]);

        ActivityLog::record('email_template', "تمت أرشفة قالب البريد: {$emailTemplate->name}", $emailTemplate);

        return response()->json(['ok' => true, 'status' => 'archived']);
    }

    public function restore(Request $request, EmailTemplate $emailTemplate, EmailTemplateVersion $version)
    {
        abort_unless($version->email_template_id === $emailTemplate->id, 404);
        $userId = $request->user()?->id;

        DB::transaction(function () use ($emailTemplate, $version, $userId): void {
            $template = $emailTemplate->fresh();
            $nextVersion = (int) $template->version + 1;
            $template->update([
                'version' => $nextVersion,
                'subject' => $version->subject,
                'html_content' => $version->html_content,
                'text_content' => $version->text_content,
                'css_styles' => $version->css_styles,
                'variables' => $version->variables,
                'status' => 'draft',
                'published_at' => null,
                'archived_at' => null,
                'last_edited_by' => $userId,
            ]);
            $this->createSnapshot($template->fresh(), $userId, "استعادة الإصدار {$version->version}");
        });

        ActivityLog::record('email_template', "تمت استعادة الإصدار {$version->version} كإصدار جديد", $emailTemplate);

        return back()->with('status', 'تمت الاستعادة كمسودة جديدة.');
    }

    public function history(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.history', [
            'template' => $emailTemplate,
            'versions' => $emailTemplate->versions()->with('creator:id,name')->get(),
        ]);
    }

    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $data = $this->validated($request, $emailTemplate, false);
        $variables = $this->decodeArray($request->input('preview_variables', $request->input('variables', [])));
        $rendered = app(\App\Services\Mail\EmailTemplateRenderer::class)->renderPayload(
            (string) ($data['subject'] ?? $emailTemplate->subject),
            array_key_exists('html_content', $data) ? $data['html_content'] : $emailTemplate->html_content,
            array_key_exists('text_content', $data) ? $data['text_content'] : $emailTemplate->text_content,
            $data['css_styles'] ?? $emailTemplate->css_styles,
            $variables,
        );

        return response()->json([
            'subject' => $rendered['subject'],
            'html' => $rendered['html'],
            'text' => $rendered['text'],
            'unknown_variables' => $rendered['unknown_variables'],
        ]);
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $data = $this->validated($request, $emailTemplate, false);
        $recipient = (string) $request->input('recipient');
        $request->validate(['recipient' => ['required', 'email:rfc,dns', 'max:254']]);
        $variables = $this->decodeArray($request->input('preview_variables', []));
        $rendered = app(\App\Services\Mail\EmailTemplateRenderer::class)->renderPayload(
            (string) ($data['subject'] ?? $emailTemplate->subject),
            array_key_exists('html_content', $data) ? $data['html_content'] : $emailTemplate->html_content,
            array_key_exists('text_content', $data) ? $data['text_content'] : $emailTemplate->text_content,
            $data['css_styles'] ?? $emailTemplate->css_styles,
            $variables,
        );

        if (! $rendered['html']) {
            return response()->json(['message' => 'القالب لا يحتوي على محتوى HTML صالح للإرسال.'], 422);
        }

        try {
            app(DynamicMailService::class)->sendHtml(
                $recipient,
                '[TEST] '.$rendered['subject'],
                $rendered['html'],
                $rendered['text']
            );
        } catch (Throwable $e) {
            report($e);
            ActivityLog::record('email_template_test', "فشل إرسال اختبار قالب: {$emailTemplate->name}", $emailTemplate, properties: ['recipient' => $recipient, 'error' => class_basename($e)]);
            return response()->json(['message' => 'تعذر إرسال البريد التجريبي عبر إعدادات البريد الحالية.'], 502);
        }

        ActivityLog::record('email_template_test', "تم إرسال اختبار قالب: {$emailTemplate->name}", $emailTemplate, properties: ['recipient' => $recipient]);
        return response()->json(['ok' => true, 'message' => 'تم إرسال البريد التجريبي.']);
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        abort_if(! $emailTemplate->canBeDeleted(), 403, 'لا يمكن حذف قالب نظامي.');

        $name = $emailTemplate->name;
        $emailTemplate->delete();

        ActivityLog::record('email_template', "تم حذف قالب البريد: {$name}");

        return redirect()->route('admin.email-templates.index')->with('status', 'تم حذف القالب.');
    }

    public function duplicate(Request $request, EmailTemplate $emailTemplate)
    {
        $copy = $emailTemplate->replicate();
        $copy->key = $emailTemplate->key.'_copy_'.now()->format('YmdHisv');
        $copy->name = $emailTemplate->name.' (نسخة)';
        $copy->is_system = false;
        $copy->version = 1;
        $copy->status = 'draft';
        $copy->published_version = null;
        $copy->published_at = null;
        $copy->archived_at = null;
        $copy->last_edited_by = $request->user()?->id;
        $copy->save();
        $this->createSnapshot($copy, $request->user()?->id, 'نسخة من قالب آخر');

        ActivityLog::record('email_template', "تم نسخ قالب البريد: {$emailTemplate->name}", $copy);

        return redirect()->route('admin.email-templates.edit', $copy)->with('status', 'تم نسخ القالب كمسودة.');
    }

    private function validated(Request $request, ?EmailTemplate $template = null, bool $require = true): array
    {
        $rules = [
            'name' => [$require ? 'required' : 'sometimes', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'subject' => [$require ? 'required' : 'sometimes', 'string', 'max:190'],
            'html_content' => ['nullable', 'string', 'max:1000000'],
            'text_content' => ['nullable', 'string', 'max:500000'],
            'css_styles' => ['nullable'],
            'variables' => ['nullable'],
            'change_note' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if (! $template) {
            $rules['key'] = ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:email_templates,key'];
        } elseif (! $template->is_system) {
            $rules['key'] = ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('email_templates', 'key')->ignore($template->id)];
        }

        $data = $request->validate($rules);
        $data['css_styles'] = $this->decodeArray($data['css_styles'] ?? null);
        $data['variables'] = $this->decodeArray($data['variables'] ?? null);

        return $data;
    }

    private function decodeArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [$value];
    }

    private function createSnapshot(EmailTemplate $template, ?int $userId, string $note): EmailTemplateVersion
    {
        return EmailTemplateVersion::query()->updateOrCreate(
            [
                'email_template_id' => $template->id,
                'version' => (int) $template->version,
            ],
            [
                'subject' => (string) $template->subject,
                'html_content' => $template->html_content,
                'text_content' => $template->text_content,
                'css_styles' => $template->css_styles,
                'variables' => $template->variables,
                'created_by' => $userId,
                'change_note' => $note,
            ],
        );
    }
}
