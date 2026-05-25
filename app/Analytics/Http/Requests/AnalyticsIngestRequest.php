<?php

namespace App\Analytics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = config('analytics.ingest.max_batch_size', 50);

        return [
            'session_id' => 'required_without:session_uuid|string|max:64',
            'session_uuid' => 'sometimes|string|max:64',
            'visitor_id' => 'required_without:visitor_uuid|string|max:64',
            'visitor_uuid' => 'sometimes|string|max:64',
            'user_id' => 'nullable|integer',
            'events' => 'required|array|min:1|max:' . $max,
            'events.*.event_uuid' => 'required_without:events.*.id|string|max:64',
            'events.*.event_name' => 'required_without:events.*.name|string|max:128',
            'events.*.event_category' => 'nullable|string|max:64',
            'events.*.occurred_at' => 'nullable|date',
            'events.*.page_url' => 'nullable|string|max:2048',
            'events.*.properties' => 'nullable|array',
            'context' => 'nullable|array',
        ];
    }
}
