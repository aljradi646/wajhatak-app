<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $preference = UserNotificationPreference::query()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $this->payload($preference)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_notifications' => ['sometimes', 'boolean'],
            'viewing_notifications' => ['sometimes', 'boolean'],
            'property_updates' => ['sometimes', 'boolean'],
        ]);
        $preference = UserNotificationPreference::query()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);
        $preference->fill($data)->save();

        return response()->json(['data' => $this->payload($preference->fresh())]);
    }

    /** @return array<string, bool> */
    private function payload(UserNotificationPreference $preference): array
    {
        return [
            'message_notifications' => $preference->message_notifications,
            'viewing_notifications' => $preference->viewing_notifications,
            'property_updates' => $preference->property_updates,
        ];
    }
}
