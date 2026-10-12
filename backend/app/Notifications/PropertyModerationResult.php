<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Notifications\Notification;

class PropertyModerationResult extends Notification
{
    public function __construct(
        private readonly Property $property,
        private readonly string $decision,
        private readonly ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        // Persist the canonical in-app notification synchronously.
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->decision === 'approved';

        return [
            'kind' => 'property_moderation_result',
            'title' => $approved ? 'تم قبول عقارك' : 'تم رفض عقارك',
            'message' => $approved
                ? 'تم قبول العقار «'.$this->property->title.'» ونشره.'
                : 'تم رفض العقار «'.$this->property->title.'». السبب: '.trim((string) $this->reason),
            'property_id' => $this->property->id,
            'reference_code' => $this->property->reference_code,
            'status' => $approved ? 'published' : 'rejected',
            'reason' => $approved ? null : $this->reason,
            'actor_id' => auth()->id(),
        ];
    }
}
