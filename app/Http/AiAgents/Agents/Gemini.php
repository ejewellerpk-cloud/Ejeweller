<?php

namespace App\Http\AiAgents\Agents;

use App\Enums\Status;
use App\Http\Requests\AiRequest;
use App\Libraries\QueryExceptionLibrary;
use App\Models\AiAgent;
use App\Services\AiAbstract;
use Exception;
use Illuminate\Support\Facades\Http;

class Gemini extends AiAbstract
{
    public string $apiKey = '';

    public function __construct()
    {
        parent::__construct();
        $this->aiAgent = AiAgent::with('gatewayOptions')->where(['slug' => 'gemini'])->first();
        if (!blank($this->aiAgent)) {
            $this->aiAgentOption = $this->aiAgent->gatewayOptions->pluck('value', 'option');
            $this->apiKey = $this->aiAgentOption['gemini_api_key'] ?? '';
        }
    }

    public function status(): bool
    {
        $aiAgent = AiAgent::where(['slug' => 'gemini', 'status' => Status::ACTIVE])->first();
        return !blank($aiAgent) && !blank($this->apiKey);
    }

    /**
     * Send request to Gemini API
     */
    private function generateContent(string $prompt): string
    {
        if (blank($this->apiKey)) {
            return trans('all.message.agent_is_not_active');
        }

        try {
            $text = '';

            // Check if it's an OpenRouter API key (starts with sk-or-)
            if (str_starts_with($this->apiKey, 'sk-or-')) {
                $url = "https://openrouter.ai/api/v1/chat/completions";
                $models = [
                    'google/gemma-4-26b-a4b-it:free',
                    'meta-llama/llama-3.3-70b-instruct:free',
                    'deepseek/deepseek-v4-flash:free',
                    'meta-llama/llama-3.2-3b-instruct:free',
                    'openrouter/free'
                ];

                $lastError = '';
                foreach ($models as $model) {
                    try {
                        $response = Http::timeout(25)->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json',
                            'HTTP-Referer' => url('/'), // Optional metadata for OpenRouter
                            'X-Title' => 'Shopperz AI'
                        ])->post($url, [
                            'model' => $model,
                            'messages' => [
                                [
                                    'role' => 'user',
                                    'content' => $prompt
                                ]
                            ]
                        ]);

                        if ($response->successful()) {
                            $data = $response->json();
                            $text = $data['choices'][0]['message']['content'] ?? '';
                            if (!blank($text)) {
                                break;
                            }
                        }

                        $lastError = $response->json()['error']['message'] ?? $response->body();
                    } catch (Exception $e) {
                        $lastError = $e->getMessage();
                    }
                }

                if (blank($text)) {
                    throw new Exception("OpenRouter API Error: " . $lastError);
                }
            } else {
                // Fallback to native Google Gemini API (gemini-2.0-flash)
                $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=" . $this->apiKey;

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json'
                ])->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                } else {
                    $errorMsg = $response->json()['error']['message'] ?? $response->body();
                    throw new Exception("Gemini API Error: " . $errorMsg);
                }
            }

            // Clean up markdown code blocks if the API returns them
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            return trim($text);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function name(AiRequest $aiRequest): array|string|\Illuminate\Contracts\Translation\Translator|null
    {
        try {
            return $this->generateContent($this->buildProductNamePrompt($aiRequest->name));
        } catch (Exception $exception) {
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function description(AiRequest $aiRequest): array|string|\Illuminate\Contracts\Translation\Translator|null
    {
        try {
            return $this->generateContent($this->buildProductDescriptionPrompt($aiRequest->name));
        } catch (Exception $exception) {
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function message(AiRequest $aiRequest): array|string|\Illuminate\Contracts\Translation\Translator|null
    {
        try {
            return $this->generateContent($aiRequest->name);
        } catch (Exception $exception) {
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function tags(AiRequest $aiRequest): array|string|\Illuminate\Contracts\Translation\Translator|null
    {
        try {
            return $this->generateContent($this->buildProductTagsPrompt($aiRequest->name));
        } catch (Exception $exception) {
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
