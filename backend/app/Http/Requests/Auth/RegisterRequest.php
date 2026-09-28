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
        $agentFields = $this->boolean('is_agent_full') || $this->input('account_type') === 'agent';

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'locale' => ['nullable', 'in:ar,en'],
            'account_type' => ['nullable', 'in:client,agent'],

            // ---- حقول الوكيل الكاملة (تُطلب عند حساب وكيل) ----
            'agency_name' => [$agentFields ? 'required' : 'nullable', 'string', 'max:190'],
            'job_title' => [$agentFields ? 'required' : 'nullable', 'string', 'max:120'],
            'agent_phone' => [$agentFields ? 'required' : 'nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'agent_city' => [$agentFields ? 'required' : 'nullable', 'string', 'max:120'],
            'national_id' => [$agentFields ? 'required' : 'nullable', 'string', 'max:60'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
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
            'password.min' => 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل.',
            'phone.unique' => 'رقم الجوال مسجّل بالفعل.',
            'locale.in' => 'اللغة المحددة غير مدعومة.',
            'account_type.in' => 'نوع الحساب غير صالح.',
            'license_number.unique' => 'رقم الترخيص مسجّل بالفعل.',
            'agency_name.required' => 'اسم المكتب العقاري مطلوب للوكلاء.',
            'job_title.required' => 'المسمى الوظيفي مطلوب للوكلاء.',
            'agent_phone.required' => 'رقم جوال الوكيل مطلوب للتواصل.',
            'agent_city.required' => 'مدينة العمل مطلوبة للوكلاء.',
            'national_id.required' => 'الرقم الوطني / الهوية مطلوب لتوثيق الحساب.',
            'experience_years.integer' => 'سنوات الخبرة يجب أن تكون رقمًا.',
            'experience_years.max' => 'سنوات الخبرة غير منطقية (الحد 60).',
        ];
    }
}
