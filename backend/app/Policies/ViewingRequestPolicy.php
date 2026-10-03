<?php

namespace App\Policies;

use App\Enums\ViewingRequestStatus;
use App\Models\User;
use App\Models\ViewingRequest;

/**
 * صلاحيات طلبات المعاينة — الحماية هنا في الخادم وليست في الواجهة فقط.
 * كل من: العميل صاحب الطلب، الوكيل صاحب العقار، والمشرف.
 */
class ViewingRequestPolicy
{
    /** المشرفون يديرون كل الطلبات. */
    private function isStaff(User $user): bool
    {
        return $user->hasRole('admin');
    }

    private function isClientOwner(User $user, ViewingRequest $request): bool
    {
        return $user->id === $request->client_id;
    }

    private function isAgentOwner(User $user, ViewingRequest $request): bool
    {
        return $user->agentProfile?->id !== null && $user->agentProfile->id === $request->agent_id;
    }

    public function view(User $user, ViewingRequest $request): bool
    {
        return $this->isStaff($user) || $this->isClientOwner($user, $request) || $this->isAgentOwner($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /** تغيير موعد/ملاحظات الطلب — متاح للأطراف ما دام الطلب مفتوحًا. */
    public function reschedule(User $user, ViewingRequest $request): bool
    {
        if (! $this->view($user, $request)) {
            return false;
        }

        return $this->isStaff($user) || $request->status->isOpen();
    }

    /**
     * تغيير حالة الطلب وفق الحالة الحالية ودور المستخدم:
     * - العميل: إلغاء فقط.
     * - الوكيل: تأكيد / رفض / إتمام.
     * - المشرف: أي انتقال مسموح في مخطط الحالات.
     */
    public function updateStatus(User $user, ViewingRequest $request, ?string $target = null): bool
    {
        if ($this->isStaff($user)) {
            return $target === null || $request->status->canTransitionTo($target);
        }

        if (! $request->status->canTransitionTo($target)) {
            return false;
        }

        if ($this->isClientOwner($user, $request)) {
            return $target === ViewingRequestStatus::Cancelled->value;
        }

        if ($this->isAgentOwner($user, $request)) {
            return in_array($target, [
                ViewingRequestStatus::Confirmed->value,
                ViewingRequestStatus::Rejected->value,
                ViewingRequestStatus::Completed->value,
            ], true);
        }

        return false;
    }

    /** حذف الطلب: المشرف دائمًا، والعميل لطلبه غير النشط فقط. */
    public function delete(User $user, ViewingRequest $request): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        return $this->isClientOwner($user, $request) && $request->status->isDeletable();
    }

    public function viewHistory(User $user, ViewingRequest $request): bool
    {
        return $this->view($user, $request);
    }
}
