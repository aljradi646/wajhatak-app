<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use Illuminate\Support\Facades\Schema;

/**
 * مدير حالة وسياق المحادثة AI Conversation State Service
 */
class AiConversationStateService
{
    /**
     * تحديث سياق المحادثة المنهجي الحجم واستخلاص العنوان الطبيعي تلقائيًا.
     */
    public function updateState(AiConversation $conversation, array $lastFilters = [], ?array $selectedProperty = null): void
    {
        if (!$conversation->exists) {
            return;
        }

        $hasStateCol = Schema::hasColumn('ai_conversations', 'context_state');
        $state = $hasStateCol && is_array($conversation->context_state) ? $conversation->context_state : [];

        if (!empty($lastFilters)) {
            $state['active_search'] = array_merge($state['active_search'] ?? [], $lastFilters);
        }

        if ($selectedProperty !== null) {
            $state['selected_property'] = $selectedProperty;
        }

        if ($hasStateCol) {
            $conversation->context_state = $state;
        }

        if (Schema::hasColumn('ai_conversations', 'title')) {
            if (empty($conversation->title) || $conversation->title === 'محادثة جديدة') {
                $conversation->title = $this->generateTitle($lastFilters, $selectedProperty);
            }
        }

        $conversation->save();
    }

    private function generateTitle(array $filters, ?array $selectedProperty): string
    {
        if ($selectedProperty) {
            return 'استفسار عن ' . ($selectedProperty['title'] ?? 'عقار');
        }

        $parts = [];
        if (!empty($filters['property_type_name'])) {
            $parts[] = $filters['property_type_name'];
        } elseif (!empty($filters['property_type'])) {
            $parts[] = $filters['property_type'];
        } else {
            $parts[] = 'بحث عقاري';
        }

        if (!empty($filters['city'])) {
            $parts[] = 'في ' . $filters['city'];
        }

        return implode(' ', $parts);
    }
}
