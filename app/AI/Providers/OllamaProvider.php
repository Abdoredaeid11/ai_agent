<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OllamaProvider implements AIProviderInterface
{
    protected string $apiUrl;
    protected string $model;
    protected string $systemInstruction;

    public function __construct(string $systemInstruction, array $config)
    {
        $this->apiUrl = $config['url'] ?? 'http://localhost:11434/api/chat';
        $this->model = $config['model'] ?? 'llama3.2:1b';
        $this->systemInstruction = $systemInstruction;
    }

    public function chat(string $message, array $history): string
    {
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
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
                'num_predict' => 1000
            ]
        ];

        Log::info('Sending to Ollama', ['payload' => $payload]);

        try {
            $response = Http::timeout(120)
                ->post($this->apiUrl, $payload);

            Log::info('Ollama response received', ['status' => $response->status(), 'body' => $response->body()]);

            if (!$response->successful()) {
                throw new Exception('Ollama API error: ' . $response->body());
            }

            $responseBody = $response->json();

            if (empty($responseBody['message']['content'])) {
                throw new Exception('Invalid response from Ollama: missing content.');
            }

            return $responseBody['message']['content'];
        } catch (Exception $e) {
            Log::error('Ollama provider failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
