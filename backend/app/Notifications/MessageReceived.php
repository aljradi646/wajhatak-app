<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notification;

class MessageReceived extends Notification
{
    public function __construct(private readonly Message $message)
    {
        $this->id = (string) Str::uuid();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $sender = $this->message->sender;
        $body = trim((string) $this->message->body);

        return [
            'kind' => 'message_received',
            'title' => 'رسالة جديدة',
            'conversation_id' => $this->message->conversation_id,
            // A property attachment may differ from the conversation's original listing.
            'property_id' => $this->message->property_id ?? $this->message->conversation->property_id,
            'actor_id' => $sender?->id,
            'actor_name' => $sender?->name ?? 'مستخدم',
            'actor_avatar_url' => $sender?->avatar_path
                ? asset('storage/'.$sender->avatar_path)
                : null,
            'message' => 'لديك رسالة من '.($sender?->name ?? 'مستخدم')
                .($body !== '' ? ": $body" : ''),
        ];
    }
}
