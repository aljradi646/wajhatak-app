<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification code (رمز 6 أرقام عبر SMTP من لوحة التحكم).
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // رمز حقيقي عبر SMTP المُعد من اللوحة بدل رابط Laravel الافتراضي
        // (الذي كان سيفشل لأن MAIL_MAILER=log افتراضيًا ولا يوجد مُرسل مضبوط).
        try {
            app(\App\Services\Email\EmailVerificationService::class)->sendCode($request->user());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'تعذر إرسال رمز التحقق — تحقق من إعدادات البريد في لوحة التحكم.');
        }

        return back()->with('status', 'تم إرسال رمز التحقق إلى بريدك.');
    }
}
