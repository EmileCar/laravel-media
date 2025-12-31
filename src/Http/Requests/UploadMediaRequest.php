<?php

namespace Carone\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['image', 'video', 'audio', 'document'])],
            'source' => ['required', Rule::in(['local', 'external'])],
            'file' => 'required_if:source,local|file',
            'url' => 'required_if:source,external|nullable|url|max:2048',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'directory' => 'nullable|string|max:500',
            'generate_thumbnail' => 'nullable|boolean',
        ];
    }
}
