<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserPreferenceController extends Controller
{
    public function updateUi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['sometimes', Rule::in(['light', 'dark', 'system'])],
            'language' => ['sometimes', Rule::in(['ar', 'en'])],
            'sidebar_collapsed' => ['sometimes', 'boolean'],
            'density' => ['sometimes', Rule::in(['comfortable', 'compact'])],
            'table_page_size' => ['sometimes', 'integer', 'between:10,100'],
            'preferred_dashboard' => ['sometimes', 'string', 'max:100'],
            'chat' => ['sometimes', 'array'],
            'chat.*' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $preferences = is_array($user->ui_preferences) ? $user->ui_preferences : [];
        $merged = array_replace_recursive($preferences, $data);

        $user->forceFill(['ui_preferences' => $merged])->save();

        ActivityLog::record(
            'ui_preference',
            'تم تحديث تفضيلات الواجهة للمستخدم',
            $user,
            properties: ['keys' => array_keys($data)],
        );

        return response()->json(['ok' => true, 'preferences' => $merged]);
    }
}
