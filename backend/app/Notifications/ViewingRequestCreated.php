<?php

namespace App\Notifications;

use App\Models\ViewingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ViewingRequestCreated extends Notification implements ShouldQueue
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
        $client = $this->viewingRequest->client;
        $propertyTitle = $this->viewingRequest->property?->title;

        return [
            'kind' => 'viewing_request_created',
            'title' => 'طلب معاينة جديد',
            'viewing_request_id' => $this->viewingRequest->id,
            'property_id' => $this->viewingRequest->property_id,
            'property_title' => $propertyTitle,
            'actor_id' => $client?->id,
            'actor_name' => $client?->name ?? 'عميل',
            'actor_avatar_url' => $client?->avatar_path
                ? asset('storage/'.$client->avatar_path)
                : null,
            'message' => 'لديك طلب معاينة من '.($client?->name ?? 'عميل')
                .($propertyTitle ? ' لعقار "'.$propertyTitle.'"' : ''),
        ];
    }
}
