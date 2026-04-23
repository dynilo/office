<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:text/plain,text/markdown,text/csv,application/json', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
