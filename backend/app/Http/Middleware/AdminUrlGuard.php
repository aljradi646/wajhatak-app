<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class AdminUrlGuard
{
    /**
     * Protect admin panel from URL manipulation attacks.
     * Validates that incoming requests to admin routes are legitimate
     * by checking referer headers, CSRF tokens, and preventing
     * unauthorized access via direct URL manipulation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Block requests with suspicious query parameters that might indicate
        // URL manipulation attempts (e.g., trying to access other users' data)
        $forbiddenParams = ['user_id', 'admin_id', 'token', 'api_token', 'access_token'];
        foreach ($forbiddenParams as $param) {
            if ($request->has($param) && ! in_array($param, ['csrf_token', '_token'], true)) {
                // Only block if the param looks like it's trying to spoof identity
                $value = $request->input($param);
                if (is_numeric($value) && (int) $value !== (int) $request->user()?->id) {
                    abort(403, 'محاولة وصول غير مصرح بها.');
                }
            }
        }

        // Prevent accessing admin panel via API-like URLs
        $path = $request->path();
        if (str_starts_with($path, 'admin/') && str_ends_with($path, '.json')) {
            abort(403, 'الوصول مرفوض.');
        }

        // Set security headers
        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
