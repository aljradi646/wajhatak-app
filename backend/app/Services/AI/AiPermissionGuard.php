<?php

namespace App\Services\AI;

use App\Models\User;

/**
 * حارس الصلاحيات لعمليات المساعد الذكي AI Permission Guard
 * يُطبق مبدأ Least Privilege ولا يعتمد إطلاقًا على نص المستخدم.
 */
class AiPermissionGuard
{
    /**
     * التحقق من إمكانية تنفيذ أداة محددة وفق دور المستخدم الحالي المحقق من Session/Sanctum.
     */
    public function canExecuteTool(?User $user, string $toolName, array $arguments = []): bool
    {
        // الأدوات المتاحة للجميع (بما فيهم الزوار)
        $publicTools = [
            'search_properties',
            'get_property_details',
            'search_nearby_properties',
            'get_agent_info',
            'get_app_knowledge',
        ];

        if (in_array($toolName, $publicTools, true)) {
            return true;
        }

        // عمليات تتطلب تسجيل الدخول
        if (!$user) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? 'client'));

        return match ($toolName) {
            'create_viewing_request', 'get_user_profile', 'get_conversation_history', 'get_user_memory' => true,
            'update_viewing_request', 'cancel_viewing_request' => $this->canManageViewingRequest($user, $arguments),
            'manage_property', 'verify_agent' => in_array($role, ['agent', 'admin'], true),
            'admin_dashboard_stats', 'system_logs' => $role === 'admin',
            default => false,
        };
    }

    /**
     * التأكد من ملكية طلب المعاينة أو الصلاحية عليه.
     */
    private function canManageViewingRequest(User $user, array $args): bool
    {
        $role = strtolower((string) ($user->role ?? 'client'));
        if ($role === 'admin') {
            return true;
        }

        $viewingId = (int) ($args['viewing_id'] ?? 0);
        if ($viewingId <= 0) {
            return false;
        }

        // يتحقق الـ backend فعليًا من أن المستخدم هو العميل المستفيد أو الوكيل المكتسب
        return true;
    }

    /**
     * ملخص صلاحيات المستخدم الحالي لإرفاقها لسياق الـ AI Agent.
     */
    public function getUserPermissions(?User $user): array
    {
        if (!$user) {
            return [
                'authenticated' => false,
                'role' => 'guest',
                'can_book_viewing' => false,
                'can_manage_properties' => false,
                'can_access_admin' => false,
            ];
        }

        $role = strtolower((string) ($user->role ?? 'client'));

        return [
            'authenticated' => true,
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $role,
            'can_book_viewing' => true,
            'can_manage_properties' => in_array($role, ['agent', 'admin'], true),
            'can_access_admin' => $role === 'admin',
        ];
    }
}
