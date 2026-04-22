<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'payload' => ['required', 'array'],
            'priority' => ['required', 'string', Rule::in(['low', 'normal', 'high', 'critical'])],
            'source' => ['nullable', 'string', 'max:100'],
            'requested_agent_role' => ['nullable', 'string', 'max:100'],
            'due_at' => ['nullable', 'date'],
            'initial_state' => ['required', 'string', Rule::in(['draft', 'queued'])],
        ];
    }
}
