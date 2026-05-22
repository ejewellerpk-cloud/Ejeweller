<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SendFbCapiEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventName;
    protected $eventData;
    protected $userData;
    protected $customData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($eventName, $eventData, $userData, $customData)
    {
        $this->eventName = $eventName;
        $this->eventData = $eventData;
        $this->userData = $userData;
        $this->customData = $customData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $settings = Setting::whereIn('key', ['site_facebook_pixel_id', 'site_facebook_capi_token', 'site_facebook_capi_status'])->pluck('value', 'key');
        
        $pixelId = $settings['site_facebook_pixel_id'] ?? null;
        $token = $settings['site_facebook_capi_token'] ?? null;
        $status = $settings['site_facebook_capi_status'] ?? \App\Enums\Activity::DISABLE;

        if ($status != \App\Enums\Activity::ENABLE || empty($pixelId) || empty($token)) {
            return;
        }

        $payload = [
            'data' => [
                [
                    'event_name' => $this->eventName,
                    'event_time' => time(),
                    'action_source' => 'website',
                    'user_data' => $this->userData,
                    'custom_data' => $this->customData,
                ]
            ]
        ];

        if (isset($this->eventData['event_id'])) {
            $payload['data'][0]['event_id'] = $this->eventData['event_id'];
        }
        if (isset($this->eventData['event_source_url'])) {
            $payload['data'][0]['event_source_url'] = $this->eventData['event_source_url'];
        }

        try {
            $response = Http::post("https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$token}", $payload);
            
            if (!$response->successful()) {
                Log::error('FB CAPI Error', ['response' => $response->json()]);
            }
        } catch (\Exception $e) {
            Log::error('FB CAPI Exception', ['message' => $e->getMessage()]);
        }
    }
}
