<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles', 'permissions'));
    }

    public function update(Request $request): UserResource
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'locale' => ['sometimes', 'in:ar,en'],
        ], [
            'name.string' => 'الاسم يجب أن يكون نصًا.',
            'name.max' => 'الاسم يتجاوز الحد الأقصى (120 حرفًا).',
            'phone.string' => 'رقم الجوال يجب أن يكون نصًا.',
            'phone.max' => 'رقم الجوال يتجاوز الحد الأقصى (32 حرفًا).',
            'phone.unique' => 'رقم الجوال مسجّل بالفعل لدى حساب آخر.',
            'locale.in' => 'اللغة المحددة غير مدعومة. يُسمح فقط بالعربية أو الإنجليزية.',
        ]);
        $user->update($data);

        return new UserResource($user->fresh()->load('roles', 'permissions'));
    }

    public function uploadAvatar(Request $request): UserResource
    {
        $validated = $request->validate([
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png',
                'max:2048',
            ],
        ], [
            'avatar.required' => 'يرجى اختيار صورة للرفع.',
            'avatar.image' => 'الملف المرفق ليس صورة صالحة.',
            'avatar.mimes' => 'نوع الصورة غير مدعوم. يُسمح فقط بصيغ JPEG أو PNG.',
            'avatar.max' => 'حجم الصورة يتجاوز الحد الأقصى (2 ميجابايت). قم بتقليل الحجم والمحاولة مجددًا.',
        ]);

        $user = $request->user();
        $previousPath = $user->avatar_path;
        $path = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        $user->update(['avatar_path' => $path]);

        if ($previousPath && str_starts_with($previousPath, 'avatars/')) {
            Storage::disk('public')->delete($previousPath);
        }

        return new UserResource($user->fresh()->load('roles', 'permissions'));
    }
}
