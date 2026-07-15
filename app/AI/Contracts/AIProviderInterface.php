<?php

namespace App\AI\Contracts;

interface AIProviderInterface
{
    /**
     * Send a chat message with conversation history
     *
     * @param string $message
     * @param array $history Array of ['role' => 'user|assistant', 'content' => 'text']
     * @return string
     */
    public function chat(string $message, array $history): string;
}
