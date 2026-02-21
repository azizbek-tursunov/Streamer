<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCameraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'stream_path' => 'nullable|string|max:255',
            'youtube_url' => 'nullable|string|url:rtmp,rtmps',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'faculty_id' => 'nullable|exists:faculties,id',
            'rotation' => 'integer|in:0,90,180,270',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->stream_path)) {
            $this->merge(['stream_path' => '/']);
        }
    }
}
