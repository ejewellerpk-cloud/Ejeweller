<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopBarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'top_bar_status'     => ['required', 'string', 'in:active,inactive'],
            'top_bar_text'       => ['nullable', 'string', 'max:5000'],
            'top_bar_link'       => ['nullable', 'string', 'max:500'],
            'top_bar_bg_color'   => ['nullable', 'string', 'max:50'],
            'top_bar_text_color' => ['nullable', 'string', 'max:50'],
        ];
    }
}
