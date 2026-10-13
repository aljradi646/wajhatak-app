<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->hasRole('admin')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'غير مصرح لك بالوصول.'], 403);
            }
            abort(403, 'غير مصرح لك بالوصول إلى لوحة التحكم.');
        }

        // TOTP-protected administrators must complete a second factor before any admin route.
        if ($user->two_factor_enabled_at) {
            $verifiedAt = (int) $request->session()->get('admin_2fa_verified_at', 0);
            if ($verifiedAt === 0 || $verifiedAt < now()->subHours(8)->timestamp) {
                $request->session()->forget('admin_2fa_verified_at');
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'يلزم إكمال التحقق الثنائي.'], 403);
                }
                return redirect()->route('admin.2fa.challenge');
            }
        }

        // Enforce a 30-minute idle timeout for the administration session.
        $lastActivity = (int) $request->session()->get('admin_last_activity', 0);
        if ($lastActivity > 0 && $lastActivity < now()->subMinutes(30)->timestamp) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'انتهت الجلسة. سجّل الدخول مجددًا.'], 401);
            }
            return redirect()->route('login')->with('error', 'انتهت الجلسة بسبب عدم النشاط. سجّل الدخول مجددًا.');
        }
        $request->session()->put('admin_last_activity', now()->timestamp);

        // Prevent session fixation
        if (! $request->session()->has('admin_verified_at')) {
            $request->session()->put('admin_verified_at', now());
        }

        return $next($request);
    }
}
