<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Date;
use Exception;

class ConversationalShipmentService
{
    protected AiQueryService $aiQueryService;
    protected ShipmentSearchService $shipmentSearchService;

    public function __construct(AiQueryService $aiQueryService, ShipmentSearchService $shipmentSearchService)
    {
        $this->aiQueryService = $aiQueryService;
        $this->shipmentSearchService = $shipmentSearchService;
    }

    /**
     * Process a user message, handling conversation and tool execution
     */
    public function processUserMessage(?Conversation $conversation, string $message): array
    {
        Log::info('Starting to process user message', [
            'conversation_id' => $conversation?->id,
            'message' => $message
        ]);

        // Create new conversation if none exists
        if (!$conversation) {
            Log::info('Creating new conversation');
            $conversation = Conversation::create([
                'title' => $this->generateTitle($message),
                'context' => []
            ]);
            Log::info('Created new conversation', ['conversation_id' => $conversation->id]);
        }

        // Prepare history for AI (exclude the current message we're about to save)
        $history = $conversation->messages()
            ->latest('id')
            ->take(30)
            ->get()
            ->reverse() // Reverse to get oldest first
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content
            ])
            ->values()
            ->toArray();

        Log::info('Prepared conversation history', ['history_count' => count($history)]);

        // Save user message
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $message
        ]);
        Log::info('Saved user message to DB');

        // Call AI to decide what to do
        $aiResult = $this->aiQueryService->processConversation($message, $history);

        Log::info('Received AI result', $aiResult);

        $responseContent = '';

        if ($aiResult['type'] === 'tool') {
            Log::info('AI returned tool call detected, executing action', ['tool_call' => $aiResult['content']]);
            // Execute tool
            $toolResult = $this->executeTool($aiResult['content']);
            Log::info('Tool execution complete', ['tool_result' => $toolResult]);

            // Save assistant response with tool call
            $responseContent = json_encode($aiResult['content'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $assistantMsg = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $responseContent,
                'meta' => ['tool_call' => $aiResult['content'], 'tool_result' => $toolResult]
            ]);

            Log::info('Saved initial assistant message with tool call', ['message_id' => $assistantMsg->id]);

            // Now call AI again to explain the result naturally
            $newHistory = array_merge($history, [
                ['role' => 'user', 'content' => $message],
                ['role' => 'assistant', 'content' => $responseContent]
            ]);

            Log::info('Calling AI to explain tool result', ['new_history_count' => count($newHistory)]);
            $responseContent = $this->explainToolResult($message, $newHistory, $aiResult['content'], $toolResult);
            Log::info('Resolved final tool explanation', ['explanation' => $responseContent]);

            // Update the assistant message with the natural explanation
            $assistantMsg->update(['content' => $responseContent]);
            Log::info('Updated assistant message with natural explanation');
        } else {
            Log::info('AI returned text response');
            // Just text response
            $responseContent = $aiResult['content'];
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $responseContent
            ]);
            Log::info('Saved text response to DB');
        }

        Log::info('Final response prepared', [
            'conversation_id' => $conversation->id,
            'final_response' => $responseContent
        ]);

        return [
            'conversation_id' => $conversation->id,
            'response' => $responseContent
        ];
    }

    /**
     * Execute the tool action (create, search, update, delete)
     */
    protected function executeTool(array $toolCall): array
    {
        $action = $toolCall['action'];
        $data = $toolCall['data'] ?? [];

        Log::info('Executing tool', [
            'action' => $action,
            'data' => $data,
        ]);

        try {
            switch ($action) {
                case 'search':
                    return $this->handleSearch($data);
                case 'create':
                    return $this->handleCreate($data);
                case 'update':
                    return $this->handleUpdate($data);
                case 'delete':
                    return $this->handleDelete($data);
                default:
                    throw new Exception('Unknown action');
            }
        } catch (Exception $e) {
            Log::error('Tool execution failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    protected function handleSearch(array $data): array
    {
        Log::info('Handling search action', ['filters' => $data]);
        $results = $this->shipmentSearchService->search($data);
        $response = [
            'success' => true,
            'count' => count($results),
            'results' => $results
        ];
        Log::info('Search action result', $response);

        return $response;
    }

    protected function handleCreate(array $data): array
    {
        Log::info('Handling create action', ['data' => $data]);
        $this->validateRequiredFields($data, ['code', 'city', 'branch']);

        $shipment = Shipment::create([
            'code' => $data['code'],
            'status' => $data['status'] ?? 'pending',
            'city' => $data['city'],
            'branch' => $data['branch'],
            'shipping_date' => $data['shipping_date'] ?? Date::now()->format('Y-m-d')
        ]);

        $response = [
            'success' => true,
            'shipment' => $shipment->toArray()
        ];

        Log::info('Create action result', $response);

        return $response;
    }

    protected function handleUpdate(array $data): array
    {
        Log::info('Handling update action', ['data' => $data]);
        $this->validateRequiredFields($data, ['code']);

        $shipment = Shipment::where('code', $data['code'])->first();
        if (!$shipment) {
            return ['success' => false, 'error' => 'الشحنة غير موجودة'];
        }

        if (isset($data['status'])) $shipment->status = $data['status'];
        if (isset($data['city'])) $shipment->city = $data['city'];
        if (isset($data['branch'])) $shipment->branch = $data['branch'];
        if (isset($data['shipping_date'])) $shipment->shipping_date = $data['shipping_date'];

        $shipment->save();

        $response = [
            'success' => true,
            'shipment' => $shipment->toArray()
        ];

        Log::info('Update action result', $response);

        return $response;
    }

    protected function handleDelete(array $data): array
    {
        Log::info('Handling delete action', ['data' => $data]);
        $this->validateRequiredFields($data, ['code']);

        $shipment = Shipment::where('code', $data['code'])->first();
        if (!$shipment) {
            return ['success' => false, 'error' => 'الشحنة غير موجودة'];
        }

        $shipment->delete();

        $response = [
            'success' => true,
            'deleted_code' => $data['code'],
        ];
        Log::info('Delete action result', $response);

        return $response;
    }

    protected function explainToolResult(string $message, array $history, array $toolCall, array $toolResult): string
    {
        $fallbackExplanation = $this->formatToolResultResponse($toolCall, $toolResult);
        $explainPrompt = "نتيجة العملية:\n"
            . json_encode($toolResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nاشرح النتيجة للمستخدم بلغة مصرية طبيعية، وبدون JSON.";

        try {
            $explainResult = $this->aiQueryService->processConversation($explainPrompt, $history);

            Log::info('Explain tool result response', $explainResult);

            if (($explainResult['source'] ?? null) !== AiQueryService::SOURCE_AI) {
                Log::warning('AI explanation fell back, using local formatter instead', [
                    'message' => $message,
                    'tool_call' => $toolCall,
                    'tool_result' => $toolResult,
                ]);

                return $fallbackExplanation;
            }

            if (($explainResult['type'] ?? null) !== 'text' || empty($explainResult['content'])) {
                Log::warning('AI explanation was not usable, using local formatter instead', [
                    'message' => $message,
                    'tool_call' => $toolCall,
                    'tool_result' => $toolResult,
                    'explain_result' => $explainResult,
                ]);

                return $fallbackExplanation;
            }

            return $explainResult['content'];
        } catch (Exception $e) {
            Log::error('Failed while explaining tool result, using local formatter', [
                'message' => $message,
                'tool_call' => $toolCall,
                'tool_result' => $toolResult,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $fallbackExplanation;
        }
    }

    protected function formatToolResultResponse(array $toolCall, array $toolResult): string
    {
        $action = $toolCall['action'] ?? null;

        if (!empty($toolResult['error'])) {
            return 'حصلت مشكلة أثناء تنفيذ الطلب: ' . $toolResult['error'];
        }

        return match ($action) {
            'create' => $this->formatCreateResponse($toolResult),
            'update' => $this->formatUpdateResponse($toolResult),
            'delete' => $this->formatDeleteResponse($toolResult),
            'search' => $this->formatSearchResponse($toolResult),
            default => 'تم تنفيذ العملية بنجاح.',
        };
    }

    protected function formatCreateResponse(array $toolResult): string
    {
        if (($toolResult['success'] ?? false) !== true || empty($toolResult['shipment'])) {
            return 'ماقدرتش أنشئ الشحنة حالياً.';
        }

        $shipment = $toolResult['shipment'];

        return sprintf(
            'تم إنشاء الشحنة رقم %s بنجاح والمتجهة إلى %s من %s.',
            $shipment['code'] ?? '-',
            $shipment['city'] ?? '-',
            $shipment['branch'] ?? '-'
        );
    }

    protected function formatUpdateResponse(array $toolResult): string
    {
        if (($toolResult['success'] ?? false) !== true || empty($toolResult['shipment'])) {
            return $toolResult['error'] ?? 'ماقدرتش أعدل الشحنة حالياً.';
        }

        $shipment = $toolResult['shipment'];

        return sprintf(
            'تم تحديث الشحنة رقم %s بنجاح. حالتها الحالية %s، ووجهتها %s من %s.',
            $shipment['code'] ?? '-',
            $shipment['status'] ?? '-',
            $shipment['city'] ?? '-',
            $shipment['branch'] ?? '-'
        );
    }

    protected function formatDeleteResponse(array $toolResult): string
    {
        if (($toolResult['success'] ?? false) !== true) {
            return $toolResult['error'] ?? 'ماقدرتش أحذف الشحنة حالياً.';
        }

        return 'تم حذف الشحنة رقم ' . ($toolResult['deleted_code'] ?? '-') . ' بنجاح.';
    }

    protected function formatSearchResponse(array $toolResult): string
    {
        if (($toolResult['success'] ?? false) !== true) {
            return $toolResult['error'] ?? 'ماقدرتش أبحث عن الشحنات حالياً.';
        }

        $count = (int) ($toolResult['count'] ?? 0);
        $results = $toolResult['results'] ?? [];

        if ($count === 0 || empty($results)) {
            return 'ملقتش أي شحنات مطابقة للبيانات اللي اترسلت.';
        }

        if ($count === 1) {
            $shipment = $results[0];

            return sprintf(
                'لقيت الشحنة رقم %s. حالتها %s، ومتجهة إلى %s من %s.',
                $shipment['code'] ?? '-',
                $shipment['status'] ?? '-',
                $shipment['city'] ?? '-',
                $shipment['branch'] ?? '-'
            );
        }

        return 'لقيت ' . $count . ' شحنات مطابقة لطلبك.';
    }

    protected function validateRequiredFields(array $data, array $requiredFields): void
    {
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            throw new Exception('Missing required fields: ' . implode(', ', $missingFields));
        }
    }

    /**
     * Generate a simple title for new conversations
     */
    protected function generateTitle(string $message): string
    {
        return mb_substr($message, 0, 30) . (mb_strlen($message) > 30 ? '...' : '');
    }
}
