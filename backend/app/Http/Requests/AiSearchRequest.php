<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AiSearchRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'بيانات غير صالحة.',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_type' => ['nullable', 'in:sale,rent'],
            'property_type' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'bedrooms_min' => ['nullable', 'integer', 'between:0,20'],
            'bedrooms_max' => ['nullable', 'integer', 'between:0,20', 'gte:bedrooms_min'],
            'bathrooms_min' => ['nullable', 'integer', 'between:0,20'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999', 'gte:min_price'],
            'min_area' => ['nullable', 'numeric', 'min:0'],
            'max_area' => ['nullable', 'numeric', 'min:0', 'gte:min_area'],
            'furnished' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:price_asc,price_desc,area_desc,relevance'],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'between:1,20'],
        ];
    }
}
