<?php

namespace App\Jobs;

use App\Services\Realtime\RealtimePublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishRealtimeEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $userId,
        public string $event,
        public array $data = [],
    ) {
        $this->afterCommit();
    }

    public function handle(RealtimePublisher $publisher): void
    {
        $publisher->publishToUser($this->userId, $this->event, $this->data);
    }
}
