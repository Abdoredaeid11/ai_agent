<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Supported providers: openai, ollama, gemini
    |
    */
    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'url' => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://localhost:11434/api/chat'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:1b'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'url' => env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions'),
        'model' => env('OPENROUTER_MODEL', 'qwen/qwen3-235b'),
    ],

];
