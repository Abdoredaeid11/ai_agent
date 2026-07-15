<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GroqProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected string $systemInstruction;

    public function __construct(string $systemInstruction, array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiUrl = $config['url'] ?? 'https://api.groq.com/openai/v1/chat/completions';
        $this->model = $config['model'] ?? 'llama-3.3-70b-versatile';
        $this->systemInstruction = $systemInstruction;
    }

    public function chat(string $message, array $history): string
    {
        if (empty($this->apiKey)) {
            throw new Exception('Groq API key is not configured.');
        }

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $this->systemInstruction]
        ];

        // Add history (keep last 20 messages)
        foreach (array_slice($history, -20) as $histMsg) {
            $messages[] = [
                'role' => $histMsg['role'],
                'content' => $histMsg['content']
            ];
        }

        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        Log::info('Sending to Groq', ['payload' => $payload]);

        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post($this->apiUrl, $payload);

            Log::info('Groq response received', ['status' => $response->status(), 'body' => $response->body()]);

            if (!$response->successful()) {
                throw new Exception('Groq API error: ' . $response->body());
            }

            $responseBody = $response->json();

            if (empty($responseBody['choices'][0]['message']['content'])) {
                throw new Exception('Invalid response from Groq: missing content.');
            }

            return $responseBody['choices'][0]['message']['content'];
        } catch (Exception $e) {
            Log::error('Groq provider failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
