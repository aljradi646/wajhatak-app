<?php

namespace App\Http\Requests;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $property = $this->route('property');

        return $property instanceof Property && ($this->user()?->can('update', $property) ?? false);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:190'],
            'description' => ['sometimes', 'string', 'max:10000'],
            'property_type_id' => ['sometimes', 'integer', 'exists:property_types,id'],
            'transaction_type' => ['sometimes', 'in:sale,rent'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3', Rule::in(array_keys(config('currencies.supported', [])))],
            'area' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:99'],
            'is_furnished' => ['sometimes', 'boolean'],
            'is_new' => ['sometimes', 'boolean'],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['integer', 'exists:property_features,id'],
            'location' => ['sometimes', 'array'],
            'location.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'location.region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'location.city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'location.area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'location.city' => ['required_with:location', 'string', 'max:120'],
            'location.district' => ['nullable', 'string', 'max:120'],
            'location.neighborhood' => ['nullable', 'string', 'max:120'],
            'location.address' => ['required_with:location', 'string', 'max:500'],
            'location.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'عنوان العقار طويل جدًا.',
            'description.max' => 'وصف العقار طويل جدًا.',
            'property_type_id.integer' => 'نوع العقار غير صحيح.',
            'property_type_id.exists' => 'نوع العقار غير موجود.',
            'transaction_type.in' => 'نوع المعاملة يجب أن يكون بيع أو إيجار.',
            'price.numeric' => 'السعر يجب أن يكون رقمًا.',
            'price.min' => 'السعر يجب أن يكون أكبر من أو يساوي صفر.',
            'area.numeric' => 'المساحة يجب أن تكون رقمًا.',
            'area.min' => 'المساحة يجب أن تكون أكبر من أو تساوي صفر.',
            'bedrooms.integer' => 'عدد غرف النوم يجب أن يكون رقمًا صحيحًا.',
            'bedrooms.min' => 'عدد غرف النوم لا يمكن أن يكون سالبًا.',
            'bedrooms.max' => 'عدد غرف النوم كبير جدًا.',
            'bathrooms.integer' => 'عدد الحمامات يجب أن يكون رقمًا صحيحًا.',
            'bathrooms.min' => 'عدد الحمامات لا يمكن أن يكون سالبًا.',
            'bathrooms.max' => 'عدد الحمامات كبير جدًا.',
            'parking_spaces.integer' => 'عدد مواقف السيارات يجب أن يكون رقمًا صحيحًا.',
            'parking_spaces.min' => 'عدد مواقف السيارات لا يمكن أن يكون سالبًا.',
            'parking_spaces.max' => 'عدد مواقف السيارات كبير جدًا.',
            'is_furnished.boolean' => 'حالة التفريش يجب أن تكون صحيحة أو خاطئة.',
            'is_new.boolean' => 'حالة العقار الجديد يجب أن تكون صحيحة أو خاطئة.',
            'feature_ids.array' => 'المزايا يجب أن تكون قائمة.',
            'feature_ids.*.integer' => 'معرف الميزة غير صحيح.',
            'feature_ids.*.exists' => 'إحدى المزايا المحددة غير موجودة.',
            'location.city.required_with' => 'المدينة مطلوبة عند إرسال الموقع.',
            'location.address.required_with' => 'العنوان مطلوب عند إرسال الموقع.',
        ];
    }
}
