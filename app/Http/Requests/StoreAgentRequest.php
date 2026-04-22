<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:agents,code'],
            'role' => ['required', 'string', 'max:100'],
            'capabilities' => ['required', 'array', 'min:1'],
            'capabilities.*' => ['required', 'string', 'max:100', 'distinct'],
            'model_preference' => ['nullable', 'string', 'max:191'],
            'temperature_policy' => ['nullable', 'array'],
            'temperature_policy.mode' => ['required_with:temperature_policy', 'string', Rule::in(['fixed', 'bounded'])],
            'temperature_policy.value' => ['nullable', 'numeric', 'between:0,2'],
            'temperature_policy.min' => ['nullable', 'numeric', 'between:0,2'],
            'temperature_policy.max' => ['nullable', 'numeric', 'between:0,2', 'gte:temperature_policy.min'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
