<?php

namespace App\Services\AI;

/** نتيجة استدعاء حوار من مزود الاستدلال. */
class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly int $tokensUsed = 0,
        public readonly ?string $finishReason = null,
        public readonly ?array $raw = null,
    ) {}

    public static function fromOpenAiCompatible(array $body): self
    {
        $choice = $body['choices'][0] ?? [];

        return new self(
            content: (string) ($choice['message']['content'] ?? ''),
            tokensUsed: (int) (($body['usage']['total_tokens'] ?? 0)),
            finishReason: $choice['finish_reason'] ?? null,
            raw: $body,
        );
    }
}
