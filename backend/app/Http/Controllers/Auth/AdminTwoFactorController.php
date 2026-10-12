<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('admin') && $user->is_active, 404);

        if (! $user->two_factor_enabled_at || ! $user->two_factor_secret) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->session()->has('admin_2fa_verified_at')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('auth.admin-two-factor');
    }

    public function verify(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        abort_unless($user && $user->hasRole('admin') && $user->is_active, 404);
        abort_unless($user->two_factor_enabled_at && $user->two_factor_secret, 404);

        if (! $totp->verify((string) $user->two_factor_secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح أو انتهت صلاحيته.'])->onlyInput('code');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_2fa_verified_at', now()->timestamp);

        return redirect()->intended(route('admin.dashboard'));
    }
}
