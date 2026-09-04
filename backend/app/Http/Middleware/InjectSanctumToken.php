<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يعيد توفير رمز المصادقة لـ Sanctum حتى لو كانت الاستضافة
 * (InfinityFree/LiteSpeed) تحذف رأس Authorization من الطلب.
 *
 * يحاول الحصول على الرمز من، بالترتيب:
 *  - رأس X-Auth-Token
 *  - معامل الاستعلام ?token=
 * وإن وجد فيضعه في رأس Authorization القياسي الذي يقرؤه Sanctum.
 *
 * يجب أن يُقدَّم قبل middleware التحقق auth:sanctum. سجّلته في
 * $middleware->priority لضمان تنفيذه مبكرًا.
 */
class InjectSanctumToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // فقط إن لم يكن رأس Authorization موجودًا (فلا نتدخل إن كان سليمًا)
        if ($request->headers->has('Authorization') &&
            $request->headers->get('Authorization') !== '') {
            return $next($request);
        }

        $token = $request->header('X-Auth-Token', '');

        if ($token === '' || $token === null) {
            $queryToken = $request->query('token');
            if (is_string($queryToken) && $queryToken !== '') {
                $token = $queryToken;
            }
        }

        if (is_string($token) && $token !== '') {
            // نمنع رفع الرمز في سجل الوصول/عرضه في URL لأي مسار مصادق لاحقًا
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        return $next($request);
    }
}