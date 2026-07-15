<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConversationalShipmentService;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ConversationalShipmentController extends Controller
{
    protected ConversationalShipmentService $conversationalService;

    public function __construct(ConversationalShipmentService $conversationalService)
    {
        $this->conversationalService = $conversationalService;
    }

    public function showChat()
    {
        return view('chat');
    }

    /**
     * Get conversation history (optional, for initial load)
     */
    public function getConversation($id)
    {
        $conversation = Conversation::with('messages')->findOrFail($id);
        return response()->json([
            'conversation' => $conversation,
            'messages' => $conversation->messages
        ]);
    }

    /**
     * Process incoming chat message
     */
    public function processMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'nullable|exists:conversations,id'
        ]);

        $message = $request->input('message');
        $conversationId = $request->input('conversation_id');
        $conversation = $conversationId ? Conversation::find($conversationId) : null;

        Log::info('Chat request received', [
            'message' => $message,
            'conversation_id' => $conversationId,
            'resolved_conversation_id' => $conversation?->id,
        ]);

        try {
            $result = $this->conversationalService->processUserMessage($conversation, $message);

            Log::info('Chat response ready', [
                'conversation_id' => $result['conversation_id'] ?? null,
                'response' => $result['response'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'conversation_id' => $result['conversation_id']
            ]);
        } catch (\Exception $e) {
            Log::error('Chat processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => $message,
                'conversation_id' => $conversationId,
            ]);
            return response()->json([
                'success' => false,
                'response' => 'عذراً، حصل خطأ أثناء معالجة رسالتك. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }
}
