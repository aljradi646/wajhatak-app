<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.url' => \App\Http\Middleware\AdminUrlGuard::class,
            'inject.sanctum.token' => \App\Http\Middleware\InjectSanctumToken::class,
        ]);

        $middleware->prepend(\App\Http\Middleware\AdminUrlGuard::class);

        // تسجيل وسيط حقن الرمز كـ«وسيط عام» يعمل على كل طلب BEFORE auth:sanctum.
        // هذا يتجاوز مشكلة كاش الـ routes القديم تمامًا: حتى لو كان كاش routes
        // موجودًا على الخادم، يبقى الوسيط يعمل لأن التعديل هنا ليس جزءًا من
        // كاش الـ routes. يستعيض عن رأس Authorization المحذوف بـ X-Auth-Token / ?token=.
        $middleware->prepend(\App\Http\Middleware\InjectSanctumToken::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
$exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            // طلبات الـ API يجب أن تستقبل 401 JSON وليس توجيهًا لصفحة الدخول —
            // وإلا فإن تطبيق Flutter لا يمكنه قراءة الاستجابة (يعالج HTML بدل JSON).
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'غير مصادق عليه. سجّل الدخول مرة أخرى.'], 401);
            }
            if ($request->is('admin/*') || $request->is('dashboard')) {
                return redirect()->route('login')->with('error', 'يجب تسجيل الدخول للوصول إلى لوحة التحكم.');
            }
            return redirect()->route('login');
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('admin/*')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'الصفحة غير موجودة.'], 404);
                }
                return back()->withErrors(['error' => 'الصفحة أو المسار المطلوب غير موجود.']);
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->is('admin/*')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'غير مصرح لك بالوصول.'], 403);
                }
                abort(403, 'غير مصرح لك بالوصول إلى هذا المورد.');
            }
        });

        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('admin/*') && $request->expectsJson()) {
                return response()->json(['message' => 'بيانات غير صالحة.', 'errors' => $e->errors()], 422);
            }
        });
    })->create();
