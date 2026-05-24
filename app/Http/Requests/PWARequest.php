<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PWARequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pwa_splash' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5048', 'dimensions:min_width=2048,min_height=2732'],
            'pwa_icon'   => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4048', 'dimensions:ratio=1/1,min_width=512,min_height=512,max_width=1024,max_height=1024']
        ];
    }
    public function messages()
    {
        return [
            'pwa_splash.dimensions' => 'Invalid image dimensions.',
            'pwa_icon.dimensions'   => 'Icon must be a square image between 512×512 and 1024×1024 pixels (recommended: exactly 512×512).',
        ];
    }
}
