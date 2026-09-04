<?php

namespace App\Notifications;

use App\Models\ViewingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ViewingRequestUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ViewingRequest $viewingRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $agentUser = $this->viewingRequest->agent?->user;
        $propertyTitle = $this->viewingRequest->property?->title;
        $label = match ($this->viewingRequest->status->value) {
            'confirmed' => 'تم تأكيد',
            'rejected' => 'تم رفض',
            'cancelled' => 'تم إلغاء',
            'completed' => 'تم إكمال',
            default => 'تم تحديث',
        };

        return [
            'kind' => 'viewing_request_updated',
            'title' => 'تحديث طلب معاينة',
            'viewing_request_id' => $this->viewingRequest->id,
            'property_id' => $this->viewingRequest->property_id,
            'property_title' => $propertyTitle,
            'actor_id' => $agentUser?->id,
            'actor_name' => $agentUser?->name ?? 'الوكيل',
            'actor_avatar_url' => $agentUser?->avatar_path
                ? asset('storage/'.$agentUser->avatar_path)
                : null,
            'message' => $label.' طلب المعاينة الخاص بك'
                .($propertyTitle ? ' لعقار "'.$propertyTitle.'"' : '')
                .' — من '.($agentUser?->name ?? 'الوكيل'),
        ];
    }
}