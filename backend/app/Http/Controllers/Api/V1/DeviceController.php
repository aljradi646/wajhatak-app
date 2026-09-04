<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'min:8', 'max:128'],
            'platform' => ['required', 'in:android'],
            'push_token' => ['required', 'string', 'min:16', 'max:512'],
        ]);

        $device = UserDevice::query()->updateOrCreate(
            ['push_token' => $data['push_token']],
            [
                'user_id' => $request->user()->id,
                'device_id' => $data['device_id'],
                'platform' => $data['platform'],
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['data' => ['id' => $device->id]], 201);
    }

    public function destroy(Request $request, string $deviceId): JsonResponse
    {
        UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->delete();

        return response()->json(status: 204);
    }
}
