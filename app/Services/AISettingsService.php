<?php

namespace App\Services;

use App\Models\AISetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class AISettingsService
{
    protected const CACHE_KEY = 'ai_settings';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all active AI providers ordered by priority
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveProviders()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return AISetting::active()->orderedByPriority()->get();
        });
    }

    /**
     * Get active providers as array
     *
     * @return array
     */
    public function getActiveProvidersArray(): array
    {
        return $this->getActiveProviders()->map(function ($setting) {
            return [
                'provider' => $setting->provider,
                'model' => $setting->model,
                'priority' => $setting->priority,
            ];
        })->toArray();
    }

    /**
     * Clear the settings cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Log::info('AI settings cache cleared');
    }

    /**
     * Seed default providers if none exist
     *
     * @return void
     */
    public function seedDefaultProviders(): void
    {
        if (AISetting::count() === 0) {
            AISetting::insert([
                [
                    'provider' => 'gemini',
                    'model' => 'gemini-2.0-flash',
                    'is_active' => true,
                    'priority' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'provider' => 'groq',
                    'model' => 'llama-3.3-70b-versatile',
                    'is_active' => true,
                    'priority' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'provider' => 'openrouter',
                    'model' => 'qwen/qwen3-235b',
                    'is_active' => true,
                    'priority' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'provider' => 'ollama',
                    'model' => 'llama3.2:1b',
                    'is_active' => true,
                    'priority' => 4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $this->clearCache();
            Log::info('Default AI settings seeded');
        }
    }
}
