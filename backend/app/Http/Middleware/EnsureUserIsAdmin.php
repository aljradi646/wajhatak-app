<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        // Prevent session fixation
        if (! $request->session()->has('admin_verified_at')) {
            $request->session()->put('admin_verified_at', now());
        }

        return $next($request);
    }
}
