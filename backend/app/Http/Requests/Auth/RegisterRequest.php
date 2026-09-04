<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'locale' => ['nullable', 'in:ar,en'],
            'account_type' => ['nullable', 'in:client,agent'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'license_number' => ['nullable', 'string', 'max:100', 'unique:agents,license_number'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال الاسم الكامل.',
            'name.max' => 'الاسم يتجاوز الحد الأقصى (120 حرفًا).',
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.email' => 'البريد الإلكتروني غير صحيح.',
            'email.unique' => 'البريد الإلكتروني مسجّل بالفعل.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
            'password.min' => 'كلمة المرور يجب أن تحتوي على 10 أحرف على الأقل.',
            'phone.unique' => 'رقم الجوال مسجّل بالفعل.',
            'locale.in' => 'اللغة المحددة غير مدعومة.',
            'account_type.in' => 'نوع الحساب غير صالح.',
            'license_number.unique' => 'رقم الترخيص مسجّل بالفعل.',
        ];
    }
}
