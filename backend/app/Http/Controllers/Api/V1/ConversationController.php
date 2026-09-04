<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
                'client',
                'agent',
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

        // Conversation uniqueness is enforced on (client_id, agent_id): opening a
        // chat from any property of the same agent reuses the same conversation.
        try {
            $conversation = Conversation::query()->create([
                'client_id' => $request->user()->id,
                'agent_id' => $agentUserId,
                'property_id' => $property->id,
                'last_message_at' => now(),
            ]);
            $isNew = true;
        } catch (UniqueConstraintViolationException) {
            // Concurrent request created it first — reuse the existing row.
            $conversation = Conversation::query()
                ->where('client_id', $request->user()->id)
                ->where('agent_id', $agentUserId)
                ->firstOrFail();
            $isNew = false;
        }

        if ($isNew) {
            // WhatsApp-style contextual property card as the first message.
            $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'body' => '',
                'message_type' => 'property',
                'property_id' => $property->id,
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return response()->json(['data' => new ConversationResource($conversation->load(['property', 'client', 'agent']))], 201);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $this->ensureParticipant($request, $conversation);
        $conversation->messages()->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return MessageResource::collection($conversation->messages()->with(['sender', 'property.images', 'property.location'])->latest()->paginate(30));
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->ensureParticipant($request, $conversation);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
            'message_type' => ['sometimes', 'string', 'in:text,property'],
            'property_id' => ['required_with:message_type', 'integer', 'exists:properties,id'],
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
        $recipient->notify(new MessageReceived($message->load(['conversation', 'sender'])));

        return response()->json(['data' => new MessageResource($message->load('property'))], 201);
    }

    private function ensureParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(in_array($request->user()->id, [$conversation->client_id, $conversation->agent_id], true), 403);
    }
}
