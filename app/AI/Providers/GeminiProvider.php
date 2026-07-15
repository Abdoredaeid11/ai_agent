<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected string $systemInstruction;

    public function __construct(string $systemInstruction, array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiUrl = $config['url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        $this->model = $config['model'] ?? 'gemini-2.0-flash';
        $this->systemInstruction = $systemInstruction;
    }

    public function chat(string $message, array $history): string
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured.');
        }

        $fullUrl = $this->apiUrl . '?key=' . $this->apiKey;

        // Build contents from history (keep last 20 messages)
        $contents = [];
        foreach (array_slice($history, -20) as $histMsg) {
            $contents[] = [
                'role' => $histMsg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $histMsg['content']]]
            ];
        }
        // Add current message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $this->systemInstruction]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000
            ]
        ];

        Log::info('Sending to Gemini', ['payload' => $payload]);

        try {
            $response = Http::timeout(60)->post($fullUrl, $payload);

            Log::info('Gemini response received', ['status' => $response->status(), 'body' => $response->body()]);

            if (!$response->successful()) {
                throw new Exception('Gemini API error: ' . $response->body());
            }

            $responseBody = $response->json();

            if (empty($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Invalid response from Gemini: missing content.');
            }

            return $responseBody['candidates'][0]['content']['parts'][0]['text'];
        } catch (Exception $e) {
            Log::error('Gemini provider failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
