<?php

namespace App\Analytics\Enterprise\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BehaviorIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = (int) config('analytics_enterprise.behavior_ingest.max_batch_size', 100);

        return [
            'session_id' => ['required', 'uuid'],
            'visitor_id' => ['nullable', 'uuid'],
            'events' => ['required', 'array', 'max:' . $max],
            'events.*.type' => ['required', 'string', 'max:64'],
            'events.*.page_path' => ['nullable', 'string', 'max:512'],
            'events.*.viewport_w' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'events.*.viewport_h' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'events.*.device_type' => ['nullable', 'string', 'max:16'],
            'events.*.occurred_at' => ['nullable', 'date'],
            'events.*.data' => ['nullable', 'array'],
        ];
    }
}
