<?php

namespace App\Services\AI;

use App\Models\AiConversation;

class AiConversationStateService
{
    public function state(AiConversation $conversation): array
    {
        return is_array($conversation->context_state) ? $conversation->context_state : [];
    }

    public function activeSearch(AiConversation $conversation): array
    {
        $state = $this->state($conversation);
        return is_array($state['active_search'] ?? null) ? $state['active_search'] : [];
    }

    public function retrievalPropertyIds(AiConversation $conversation): array
    {
        $state = $this->state($conversation);
        $ids = is_array($state['retrieval']['property_ids'] ?? null) ? $state['retrieval']['property_ids'] : [];
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    }

    public function updateState(
        AiConversation $conversation,
        array $lastFilters = [],
        ?array $selectedProperty = null,
        ?array $retrievedPropertyIds = null,
    ): void {
        if (! $conversation->exists) {
            return;
        }

        $state = $this->state($conversation);

        if ($lastFilters !== []) {
            // lastFilters already represents the complete active search. Do not merge
            // it with an unrelated previous state.
            $state['active_search'] = $lastFilters;
        } elseif (! isset($state['active_search'])) {
            $state['active_search'] = [];
        }

        if ($retrievedPropertyIds !== null) {
            $state['retrieval'] = [
                'property_ids' => array_values(array_unique(array_filter(
                    array_map('intval', $retrievedPropertyIds),
                    fn ($id) => $id > 0,
                ))),
                'updated_at' => now()->toISOString(),
            ];
        } elseif (! isset($state['retrieval'])) {
            $state['retrieval'] = ['property_ids' => [], 'updated_at' => null];
        }

        if ($selectedProperty !== null) {
            $state['selected_property_id'] = (int) ($selectedProperty['property_id'] ?? 0) ?: null;
        }

        $conversation->context_state = $state;
        if (empty($conversation->title) || $conversation->title === 'محادثة جديدة') {
            $conversation->title = $this->generateTitle($lastFilters, $selectedProperty);
        }
        $conversation->save();
    }

    public function clearRetrievalState(AiConversation $conversation, ?int $expectedUserMessageId = null): void
    {
        if (! $conversation->exists || ($expectedUserMessageId !== null && ! $this->isCurrentTurn($conversation, $expectedUserMessageId))) {
            return;
        }

        $state = $this->state($conversation);
        $state['retrieval'] = ['property_ids' => [], 'updated_at' => now()->toISOString()];
        $state['selected_property_id'] = null;
        $conversation->context_state = $state;
        $conversation->save();
    }

    public function resetSearchState(AiConversation $conversation, ?int $expectedUserMessageId = null): void
    {
        if (! $conversation->exists || ($expectedUserMessageId !== null && ! $this->isCurrentTurn($conversation, $expectedUserMessageId))) {
            return;
        }

        $state = $this->state($conversation);
        $state['active_search'] = [];
        $state['retrieval'] = ['property_ids' => [], 'updated_at' => now()->toISOString()];
        $state['selected_property_id'] = null;
        $conversation->context_state = $state;
        $conversation->save();
    }

    public function setPendingAction(AiConversation $conversation, ?string $tool, array $arguments = [], ?int $expectedUserMessageId = null): void
    {
        if (! $conversation->exists || ($expectedUserMessageId !== null && ! $this->isCurrentTurn($conversation, $expectedUserMessageId))) {
            return;
        }

        $state = $this->state($conversation);
        $state['pending_action'] = $tool
            ? ['tool' => $tool, 'arguments' => $arguments, 'created_at' => now()->toISOString()]
            : null;
        $conversation->context_state = $state;
        $conversation->save();
    }

    public function isCurrentTurn(AiConversation $conversation, int $userMessageId): bool
    {
        return (int) $conversation->messages()
            ->where('role', \App\Enums\AiMessageRole::User->value)
            ->max('id') === $userMessageId;
    }

    public function pendingAction(AiConversation $conversation): ?array
    {
        $pending = $this->state($conversation)['pending_action'] ?? null;
        if (! is_array($pending) || ! isset($pending['tool']) || ! is_array($pending['arguments'] ?? null)) {
            return null;
        }

        if (! empty($pending['created_at'])) {
            try {
                if (now()->diffInMinutes(IlluminateSupportCarbon::parse($pending['created_at'])) > 15) {
                    $this->setPendingAction($conversation, null);
                    return null;
                }
            } catch (Throwable) {
                // Ignore malformed metadata and continue safely.
            }
        }

        return $pending;
    }

    private function generateTitle(array $filters, ?array $selectedProperty): string
    {
        if ($selectedProperty) {
            return 'استفسار عن '.($selectedProperty['title'] ?? 'عقار');
        }

        $parts = [];
        $parts[] = $filters['property_type_name'] ?? $filters['property_type'] ?? 'بحث عقاري';
        if (! empty($filters['city'])) {
            $parts[] = 'في '.$filters['city'];
        }

        return implode(' ', $parts);
    }
}
