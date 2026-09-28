<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AiChatRequest extends FormRequest
{
    /** API دائمًا: أخطاء JSON بدل إعادة التوجيه. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'بيانات غير صالحة.',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true; // المصادقة عبر sanctum اختيارية (زائر مسموح بمعدل أقل).
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:'.(int) config('ai.limits.max_message_length', 600)],
            'conversation_id' => ['nullable', 'integer', 'exists:ai_conversations,id'],
            'session_token' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:5'],
            // إحداثيات موقع العميل الحقيقية (اختيارية) — للتعبيرات «قريب مني».
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'اكتب رسالتك أولًا.',
            'message.max' => 'الرسالة طويلة جدًا. حاول اختصارها.',
        ];
    }
}
