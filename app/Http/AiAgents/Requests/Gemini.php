<?php

namespace App\Http\AiAgents\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;

class Gemini extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if (request()->gemini_status == Activity::ENABLE) {
            return [
                'gemini_api_key'         => ['required', 'string'],
                'gemini_status'          => ['required', 'numeric'],
            ];
        } else {
            return [
                'gemini_api_key'         => ['nullable', 'string'],
                'gemini_status'          => ['required', 'numeric'],
            ];
        }
    }
}
