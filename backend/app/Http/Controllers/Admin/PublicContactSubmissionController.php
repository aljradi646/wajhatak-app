<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PublicContactSubmission;
use App\Services\Mail\UnifiedMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PublicContactSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:new,in_progress,replied,resolved,spam'],
            'category' => ['nullable', 'in:general,technical,property,agent,abuse,privacy,suggestion,other'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:190'],
        ]);

        $query = PublicContactSubmission::query()->latest();
        foreach (['status', 'category'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(fn ($builder) => $builder
                ->where('reference', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"));
        }

        return view('admin.contact-submissions.index', [
            'submissions' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'counts' => [
                'new' => PublicContactSubmission::where('status', 'new')->count(),
                'in_progress' => PublicContactSubmission::where('status', 'in_progress')->count(),
                'replied' => PublicContactSubmission::where('status', 'replied')->count(),
                'resolved' => PublicContactSubmission::where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function show(PublicContactSubmission $submission): View
    {
        return view('admin.contact-submissions.show', compact('submission'));
    }

    public function reply(Request $request, PublicContactSubmission $submission): RedirectResponse
    {
        $data = $request->validate([
            'admin_reply' => ['required', 'string', 'min:2', 'max:8000'],
            'status' => ['required', 'in:in_progress,replied,resolved'],
            'send_email' => ['nullable', 'boolean'],
        ]);
        $reply = trim($data['admin_reply']);
        $sendEmail = $request->boolean('send_email');

        DB::transaction(function () use ($request, $submission, $data, $reply): void {
            $submission->forceFill([
                'admin_reply' => $reply,
                'replied_by' => $request->user()->id,
                'replied_at' => now(),
                'status' => $data['status'],
                'resolved_at' => $data['status'] === 'resolved' ? now() : null,
                'email_status' => $request->boolean('send_email') ? 'pending' : 'not_sent',
            ])->save();

        });

        $mailState = 'not_requested';
        if ($sendEmail) {
            try {
                app(UnifiedMailService::class)->send(
                    $submission->email,
                    'رد فريق وجهتك على رسالتك '.$submission->reference,
                    "مرحبًا {$submission->name}،\n\nرد فريق وجهتك على رسالتك ({$submission->reference}):\n\n{$reply}\n\nشكرًا لتواصلك مع وجهتك.",
                    ['html' => '<div dir="rtl" style="font-family:Arial,sans-serif;line-height:1.9;color:#183b32"><h2>رد فريق وجهتك</h2><p>مرحبًا '.e($submission->name).'،</p><p>رد فريق وجهتك على رسالتك <strong>'.e($submission->reference).'</strong>:</p><div style="white-space:pre-wrap;padding:16px;border-radius:12px;background:#f3f8f5">'.e($reply).'</div><p>شكرًا لتواصلك مع وجهتك.</p></div>']
                );
                $mailState = 'sent';
                $submission->forceFill(['email_status' => 'sent', 'email_sent_at' => now()])->save();
            } catch (Throwable $exception) {
                report($exception);
                $mailState = 'failed';
                $submission->forceFill(['email_status' => 'failed'])->save();
            }
        }

        ActivityLog::record('public_contact', 'تحديث حالة رسالة التواصل '.$submission->reference, $submission);
        $message = $mailState === 'failed'
            ? 'حُفظ الرد، لكن تعذر إرسال البريد. تحقق من إعدادات البريد.'
            : ($mailState === 'sent' ? 'تم حفظ الرد وإرساله إلى البريد الإلكتروني.' : 'تم حفظ الرد بنجاح.');

        return redirect()->route('admin.contact-submissions.show', $submission)->with('status', $message);
    }

    public function updateStatus(Request $request, PublicContactSubmission $submission): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:new,in_progress,resolved,spam']]);
        $submission->forceFill([
            'status' => $data['status'],
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ])->save();
        ActivityLog::record('public_contact', 'تغيير حالة رسالة التواصل '.$submission->reference, $submission);

        return back()->with('status', 'تم تحديث حالة الرسالة.');
    }
}
