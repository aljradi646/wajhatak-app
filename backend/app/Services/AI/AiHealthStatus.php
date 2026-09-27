<?php

namespace App\Services\AI;

/** حالة صحة مزود الاستدلال. */
class AiHealthStatus
{
    public function __construct(
        public readonly bool $healthy,
        public readonly string $provider,
        public readonly ?string $model = null,
        public readonly ?int $latencyMs = null,
        public readonly ?string $message = null,
        public readonly array $details = [],
    ) {}

    public function toArray(): array
    {
        return [
            'healthy' => $this->healthy,
            'provider' => $this->provider,
            'model' => $this->model,
            'latency_ms' => $this->latencyMs,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
