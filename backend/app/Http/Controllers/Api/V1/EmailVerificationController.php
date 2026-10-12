<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Email\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * التحقق الحقيقي من البريد الإلكتروني: إرسال رمز إلى الصندوق الفعلي
 * وتأكيده. يتطلب تسجيل الدخول (الرمز مرتبط ببريد صاحب الجلسة حصريًا).
 */
class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verifier,
    ) {}

    /** POST /me/email/verification-code — إرسال رمز جديد. */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->json(['data' => [
                'enabled' => (bool) Setting::get('email_verification_enabled', true),
                'verified' => true,
                'message' => 'بريدك موثق بالفعل.',
            ]]);
        }

        if (! (bool) Setting::get('email_verification_enabled', true)) {
            return response()->json([
                'message' => 'توثيق البريد الإلكتروني معطّل حاليًا من إعدادات المنصة.',
                'data' => ['enabled' => false, 'verified' => false],
            ], 409);
        }

        $result = $this->verifier->sendCode($user);

        return response()->json(['data' => [
            'enabled' => true,
            'verified' => false,
            'ok' => $result['ok'],
            'message' => $result['message'],
            'resend_in' => $result['resend_in'],
            'resend_available_at' => $result['resend_available_at'] ?? null,
            'server_time' => $result['server_time'] ?? now()->toIso8601String(),
        ]], $result['ok'] ? 200 : 422);
    }

    /** POST /me/email/verify — تأكيد الرمز. */
    public function verify(Request $request): JsonResponse
    {
        if (! (bool) Setting::get('email_verification_enabled', true)) {
            return response()->json([
                'message' => 'توثيق البريد الإلكتروني معطّل حاليًا من إعدادات المنصة.',
                'data' => ['enabled' => false, 'verified' => false],
            ], 409);
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'أدخل رمز التحقق.',
            'code.digits' => 'الرمز يتكون من 6 أرقام.',
        ]);

        $result = $this->verifier->confirm($request->user(), $data['code']);

        return response()->json(['data' => [
            'verified' => $result['ok'],
            'message' => $result['message'],
            'user' => $request->user()->fresh()->email_verified_at !== null,
        ]], $result['ok'] ? 200 : 422);
    }

    /** GET /me/email/status — حالة التحقق (للواجهة). */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! (bool) Setting::get('email_verification_enabled', true)) {
            return response()->json([
                'message' => 'توثيق البريد الإلكتروني معطّل حاليًا من إعدادات المنصة.',
                'data' => ['enabled' => false, 'verified' => $user->email_verified_at !== null],
            ], 409);
        }

        return response()->json(['data' => [
            'enabled' => true,
            'verified' => $user->email_verified_at !== null,
            'resend_in' => $this->verifier->resendIn($user),
            'resend_available_at' => $this->verifier->resendAvailableAt($user),
            'server_time' => now()->toIso8601String(),
        ]]);
    }
}
