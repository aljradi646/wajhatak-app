<?php

namespace App\Services\AI;

/**
 * عقد الاستجابة الموحد بين orchestrator وAPI وواجهة Flutter.
 * يمنع تسرب property state إلى رسائل لا تحتوي نتائج عقارية.
 */
final class AiResponseContract
{
    public const RESPONSE_TYPES = [
        'text',
        'property_results',
        'property_detail',
        'clarification',
        'unsupported',
        'security',
        'error',
    ];

    /** @return array<string,mixed> */
    public static function normalize(array $response): array
    {
        // Legacy orchestrator paths do not always provide an explicit response_type.
        // Infer it from the authoritative status/intent/results instead of discarding
        // grounded property data.
        $rawType = $response['response_type'] ?? null;
        $type = is_string($rawType) ? $rawType : '';
        if (! in_array($type, self::RESPONSE_TYPES, true) || $type === 'text') {
            $status = (string) ($response['status'] ?? 'ok');
            $intent = (string) ($response['intent'] ?? 'ambiguous_request');
            $propertyCount = count((array) ($response['properties'] ?? []));
            $type = match (true) {
                $status === 'error' => 'error',
                in_array($intent, ['blocked', 'security'], true) => 'security',
                $intent === 'viewing_request_confirmation',
                $intent === 'confirmation_required',
                $intent === 'action_cancelled',
                $intent === 'unclear',
                $intent === 'investment_clarify' => 'clarification',
                in_array($intent, ['details', 'property_detail'], true) && $propertyCount > 0 => 'property_detail',
                $propertyCount > 0 => 'property_results',
                in_array($intent, ['out_of_scope', 'unsupported'], true) => 'unsupported',
                default => 'text',
            };
        }

        $properties = [];
        foreach ((array) ($response['properties'] ?? []) as $property) {
            if (! is_array($property)) {
                continue;
            }

            $id = (int) ($property['property_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $property['property_id'] = $id;
            $properties[$id] = $property;
        }
        $properties = array_values($properties);

        if (! in_array($type, ['property_results', 'property_detail'], true)) {
            $properties = [];
        }

        if ($type === 'property_results' && $properties === []) {
            $type = 'text';
        }

        if ($type === 'property_detail' && $properties === []) {
            $type = 'text';
        }

        $actions = [];
        foreach ((array) ($response['actions'] ?? []) as $action) {
            if (! is_array($action) || empty($action['type'])) {
                continue;
            }
            $actions[] = [
                'type' => mb_substr((string) $action['type'], 0, 40),
                'label' => mb_substr((string) ($action['label'] ?? ''), 0, 80),
                'payload' => is_array($action['payload'] ?? null) ? $action['payload'] : [],
            ];
        }

        $resultMode = (string) ($response['result_mode'] ?? 'exact');
        if (! in_array($resultMode, ['exact', 'alternatives', 'none'], true)) {
            $resultMode = 'exact';
        }

        return [
            'intent' => mb_substr((string) ($response['intent'] ?? 'ambiguous_request'), 0, 50),
            'response_type' => $type,
            'reply' => mb_substr((string) ($response['reply'] ?? ''), 0, 5000),
            'status' => (string) ($response['status'] ?? 'ok'),
            'properties' => $properties,
            'result_mode' => $resultMode,
            'filters' => is_array($response['filters'] ?? null) ? $response['filters'] : [],
            'tool_calls' => is_array($response['tool_calls'] ?? null) ? $response['tool_calls'] : [],
            'actions' => $actions,
            'citations' => is_array($response['citations'] ?? null) ? array_values($response['citations']) : [],
            'state_updates' => is_array($response['state_updates'] ?? null) ? $response['state_updates'] : [],
            'source' => is_array($response['source'] ?? null) ? $response['source'] : [],
            'failed_stage' => $response['failed_stage'] ?? null,
        ];
    }
}
