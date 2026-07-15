<?php

namespace App\AI;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\GroqProvider;
use App\AI\Providers\OpenRouterProvider;
use App\AI\Providers\OllamaProvider;
use Exception;

class AIProviderFactory
{
    protected static array $providers = [
        'gemini' => GeminiProvider::class,
        'groq' => GroqProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'ollama' => OllamaProvider::class,
    ];

    /**
     * Create an AI provider instance
     *
     * @param string $providerName
     * @param string $systemInstruction
     * @param array $config
     * @return AIProviderInterface
     */
    public static function make(string $providerName, string $systemInstruction, array $config = []): AIProviderInterface
    {
        $providerName = strtolower($providerName);

        if (!isset(static::$providers[$providerName])) {
            throw new Exception("AI provider '$providerName' is not supported.");
        }

        $providerClass = static::$providers[$providerName];

        return new $providerClass($systemInstruction, $config);
    }

    /**
     * Register a new provider
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    public static function register(string $name, string $class): void
    {
        static::$providers[strtolower($name)] = $class;
    }

    /**
     * Get all registered providers
     *
     * @return array
     */
    public static function all(): array
    {
        return static::$providers;
    }
}
