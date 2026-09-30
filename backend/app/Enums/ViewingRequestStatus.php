<?php

namespace App\Enums;

enum ViewingRequestStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    /**
     * مخطط الحالات المسموح — يمنع القفز إلى حالة غير منطقية
     * (مثل إتمام طلب مرفوض أو إعادة فتح طلب منتهٍ).
     */
    public function canTransitionTo(self|string|null $target): bool
    {
        $target = $target instanceof self ? $target->value : $target;

        return in_array($target, $this->allowedTargets(), true);
    }

    /** @return array<int, string> */
    public function allowedTargets(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed->value, self::Rejected->value, self::Cancelled->value],
            self::Confirmed => [self::Completed->value, self::Cancelled->value],
            self::Rejected, self::Cancelled, self::Completed => [],
        };
    }

    /** الطلب ما زال قابلًا للتعديل (لم يُعتَمد نهائيًا بعد). */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /** هل يجوز للعميل حذف الطلب في هذه الحالة؟ */
    public function isDeletable(): bool
    {
        return in_array($this, [self::Pending, self::Rejected, self::Cancelled], true);
    }
}
