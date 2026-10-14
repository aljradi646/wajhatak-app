<?php

namespace App\Http\Controllers;

use App\Models\PublicContactSubmission;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicContactSubmissionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Honeypot field catches unsophisticated bots without adding friction for real users.
        if (filled($request->input('website'))) {
            return redirect()->route('public.pages.show', 'contact')
                ->with('contact_submitted', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'category' => ['required', 'in:general,technical,property,agent,abuse,privacy,suggestion,other'],
            'subject' => ['required', 'string', 'min:4', 'max:190'],
            'message' => ['required', 'string', 'min:15', 'max:8000'],
            'consent' => ['accepted'],
        ], [
            'name.required' => 'يرجى كتابة الاسم.',
            'email.required' => 'يرجى كتابة بريد إلكتروني صالح حتى نتمكن من الرد.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'subject.required' => 'يرجى كتابة عنوان الرسالة.',
            'message.min' => 'يرجى توضيح رسالتك في 15 حرفًا على الأقل.',
            'consent.accepted' => 'يلزم الموافقة على إشعار الخصوصية لمعالجة رسالتك.',
        ]);

        $submission = PublicContactSubmission::query()->create([
            'reference' => 'WJ-C-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
            'category' => $data['category'],
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
            'status' => 'new',
        ]);

        return redirect()->route('public.pages.show', 'contact')
            ->with('contact_reference', $submission->reference)
            ->with('status', 'وصلت رسالتك إلى فريق وجهتك. احتفظ برقم المتابعة الظاهر أدناه.');
    }
}
