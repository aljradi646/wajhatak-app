<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendFcmPushNotification;
use App\Jobs\PublishRealtimeEvent;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Property;
use App\Notifications\MessageReceived;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $conversations = Conversation::query()
            ->with([
                'property',
                'client.agentProfile',
                'agent.agentProfile',
                'messages' => fn ($query) => $query->latest()->limit(1),
            ])
            ->withCount(['messages as unread_count' => fn ($query) => $query->where('sender_id', '!=', $userId)->whereNull('read_at')])
            ->where(fn (Builder $query) => $query->where('client_id', $userId)->orWhere('agent_id', $userId))
            ->orderByDesc('last_message_at')->paginate(20);

        return ConversationResource::collection($conversations);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'agent_id' => ['sometimes', 'integer', 'exists:agents,id'],
        ]);
        $property = Property::query()->with('agent')->findOrFail($data['property_id']);
        $agentUserId = $property->agent->user_id;
        abort_if($agentUserId === $request->user()->id, 422, 'لا يمكن إنشاء محادثة مع عقارك الخاص.');

        // Use the participant pair as the lookup key so different listings from the
        // same agent do not create separate threads during normal sequential use.
        $conversation = Conversation::query()
            ->where('client_id', $request->user()->id)
            ->where('agent_id', $agentUserId)
            ->first();
        $isNew = $conversation === null;

        if ($isNew) {
            try {
                $conversation = Conversation::query()->create([
                    'client_id' => $request->user()->id,
                    'agent_id' => $agentUserId,
                    'property_id' => $property->id,
                    'last_message_at' => null,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request may have created this exact conversation.
                $conversation = Conversation::query()
                    ->where('client_id', $request->user()->id)
                    ->where('agent_id', $agentUserId)
                    ->firstOrFail();
                $isNew = false;
            }
        }

        return response()->json([
            'data' => new ConversationResource($conversation->load([
                'property',
                'client.agentProfile',
                'agent.agentProfile',
            ])),
        ], $isNew ? 201 : 200);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $this->ensureParticipant($request, $conversation);
        $messagesMarkedRead = $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Opening a conversation also marks its linked message notifications as read.
        $notificationsMarkedRead = $request->user()->notifications()
            ->where('type', MessageReceived::class)
            ->whereNull('read_at')
            ->where('data->conversation_id', $conversation->id)
            ->update(['read_at' => now()]);

        if ($messagesMarkedRead > 0 || $notificationsMarkedRead > 0) {
            $this->queueRealtimeEvent((int) $request->user()->id, 'notifications.updated', [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'conversation_id' => (int) $conversation->id,
            ]);
            $otherUserId = (int) ($conversation->client_id === $request->user()->id
                ? $conversation->agent_id
                : $conversation->client_id);
            if ($messagesMarkedRead > 0) {
                $this->queueRealtimeEvent($otherUserId, 'conversation.read', [
                    'conversation_id' => (int) $conversation->id,
                    'reader_id' => (int) $request->user()->id,
                ]);
            }
        }

        return MessageResource::collection($conversation->messages()->with(['sender', 'property.images', 'property.location'])->latest()->paginate(30));
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->ensureParticipant($request, $conversation);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
            'message_type' => ['sometimes', 'string', 'in:text,property'],
            'property_id' => [
                'required_if:message_type,property',
                'prohibited_unless:message_type,property',
                'integer',
                'exists:properties,id',
            ],
        ]);

        $messageType = $data['message_type'] ?? 'text';
        $body = $data['body'] ?? ($messageType === 'property' ? '' : '');

        if ($messageType === 'text' && empty(trim($body))) {
            abort(422, 'الرسالة نصية فارغة.');
        }

        if ($messageType === 'property') {
            abort_if(!in_array($request->user()->id, [$conversation->client_id, $conversation->agent_id], true), 403);
        }

        $message = DB::transaction(function () use ($request, $conversation, $data, $messageType, $body) {
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'body' => $body,
                'message_type' => $messageType,
                'property_id' => $messageType === 'property' ? $data['property_id'] : null,
            ]);
            $conversation->update(['last_message_at' => now()]);
            return $message;
        });

        $recipient = $conversation->client_id === $request->user()->id ? $conversation->agent : $conversation->client;
        $notification = new MessageReceived($message->load(['conversation', 'sender']));
        $recipient->notify($notification);
        if ($recipient->notificationPreference?->message_notifications ?? true) {
            try {
                SendFcmPushNotification::dispatch((int) $recipient->id, (string) $notification->id);
        } catch (\Throwable $exception) {
            // A push queue outage must not turn a committed message into an HTTP failure.
                report($exception);
            }
        }

        $this->queueRealtimeEvent((int) $recipient->id, 'message.received', [
            'conversation_id' => (int) $conversation->id,
            'message_id' => (int) $message->id,
            'notification_id' => (string) $notification->id,
        ]);

        return response()->json(['data' => new MessageResource($message->load('property'))], 201);
    }

    private function queueRealtimeEvent(int $userId, string $event, array $data): void
    {
        try {
            PublishRealtimeEvent::dispatch($userId, $event, $data);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function ensureParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(in_array($request->user()->id, [$conversation->client_id, $conversation->agent_id], true), 403);
    }
}
