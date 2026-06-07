<?php

namespace App\Http\Requests;


use App\Enums\Ask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TokenStoreRequest extends FormRequest
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
        return [
            'token'       => ['required', 'string', 'max:512'],
            'platform'    => ['nullable', 'string', 'in:web,android,ios'],
            'device_name' => ['nullable', 'string', 'max:190'],
            'device_id'   => ['nullable', 'string', 'max:100'],
        ];
    }
}
