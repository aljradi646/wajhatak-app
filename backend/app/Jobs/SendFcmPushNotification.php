<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notifications\FcmPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendFcmPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $userId,
        public string $notificationId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(FcmPushService $fcm): void
    {
        $user = User::query()->with('devices')->find($this->userId);
        if (! $user) {
            return;
        }

        $notification = $user->notifications()->find($this->notificationId);
        if (! $notification) {
            return;
        }

        $payload = is_array($notification->data) ? $notification->data : [];
        $payload['notification_id'] = (string) $notification->id;
        $delivered = false;

        foreach ($user->devices as $device) {
            $delivered = $fcm->send($device, $payload) || $delivered;
        }

        if ($delivered) {
            DB::table('notifications')
                ->where('id', $notification->id)
                ->whereNull('delivered_at')
                ->update(['delivered_at' => now()]);
        }
    }
}
