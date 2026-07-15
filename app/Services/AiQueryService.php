<?php

namespace App\Services;

use App\AI\AIProviderFactory;
use Illuminate\Support\Facades\Log;
use Exception;

class AiQueryService
{
    public const SOURCE_AI = 'ai';
    public const SOURCE_FALLBACK = 'fallback';

    protected array $allowedActions = ['search', 'create', 'update', 'delete'];
    protected string $systemInstruction;
    protected AISettingsService $settingsService;

    public function __construct(AISettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        $this->systemInstruction = <<<PROMPT
أنت مساعد ذكي لإدارة الشحنات لشركة شحن.

شخصيتك:
- تتحدث بالعربية المصرية بشكل طبيعي وودود.
- لا تكرر "أنا مساعد شحنات" في كل رد.
- لا تكرر قائمة الخدمات إلا إذا طلبها المستخدم.
- تتعامل مع المستخدم كإنسان وليس كعميل في قائمة أوامر.
- تذكر معلومات المستخدم من المحادثة السابقة.
- إذا كنت تعرف اسم المستخدم، استخدمه في الرد.
- لا ترد بصيغة روبوتية أو مختصرة.

مهامك:
1- البحث عن الشحنات.
2- إنشاء شحنة جديدة.
3- تعديل شحنة موجودة.
4- حذف شحنة.
5- الإجابة عن الأسئلة العامة.

قواعد مهمة:
1- إذا كانت الرسالة محادثة عادية فقط:
   - رد بلغة طبيعية ومصرية.
   - لا تذكر الشحنات إلا إذا كان السؤال عنها.

2- إذا أراد المستخدم عملية على الشحنات:
   - اجمع البيانات الناقصة أولاً.
   - اسأل سؤالاً أو سؤالين كحد أقصى في كل مرة.
   - لا تفترض أي بيانات غير موجودة.
   - لا ترجع JSON إلا عندما تكون جميع البيانات المطلوبة متوفرة.

3- إذا كنت جاهزاً لتنفيذ العملية:
   - ارجع JSON فقط، بدون أي نص إضافي أو Markdown.

المطلوب لكل عملية:
- إنشاء شحنة: code, city, branch
- البحث: يمكن أن يكون بدون بيانات أو مع code أو city أو status او created_at
- تعديل شحنة: code (ويمكن إضافة الحقول الأخرى للتعديل)
- حذف شحنة: code

تنسيق JSON:
{
  "action": "search|create|update|delete",
  "data": { ... }
}

مثال على الرد النصي:
مستخدم: ازيك
مساعد: تمام الحمد لله، أنا جاهز لمساعدتك.

مثال على جمع بيانات:
مستخدم: عاوز أعمل شحنة
مساعد: تمام، إيه رقم الشحنة؟

مثال على استجابة للمعلومات الشخصية:
مستخدم: أنا عبدالرحمن
مساعد: أهلاً يا عبدالرحمن، تشرفت بمعرفتك.

مثال على إرجاع JSON:
مستخدم: رقم الشحنة SH100، القاهرة، الفرع الرئيسي
مساعد:
{
  "action": "create",
  "data": {
    "code": "SH100",
    "city": "القاهرة",
    "branch": "الفرع الرئيسي"
  }
}
PROMPT;
    }

    /**
     * Process message with conversation history
     *
     * @param string $message
     * @param array $history Array of messages with 'role' and 'content'
     * @return array ['type' => 'text'|'tool', 'content' => string|array]
     */
    public function processConversation(string $message, array $history = []): array
    {
        Log::info('Starting AI processing', [
            'message' => $message,
            'history_count' => count($history)
        ]);

        // Try all active providers in priority order for graceful failover
        $activeProviders = $this->settingsService->getActiveProviders();

        foreach ($activeProviders as $setting) {
            try {
                $providerName = $setting->provider;
                $providerConfig = array_merge(
                    config("services.$providerName", []),
                    ['model' => $setting->model]
                );

                Log::info("Trying AI provider", [
                    'provider' => $providerName,
                    'model' => $setting->model
                ]);

                $provider = AIProviderFactory::make(
                    $providerName,
                    $this->systemInstruction,
                    $providerConfig
                );

                $result = $provider->chat($message, array_slice($history, -20)); // Keep last 20 msgs

                Log::info('Got raw AI response', ['raw_response' => $result]);
                $parsed = $this->parseResult($result);
                Log::info('Parsed AI result', $parsed);

                return $parsed;
            } catch (Exception $e) {
                Log::warning("Provider failed", [
                    'provider' => $setting->provider,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue to next provider in priority order
            }
        }

        // All providers failed, fall back to keyword/error handling
        Log::error('All AI providers failed');
        $fallbackResult = $this->keywordFallback($message);
        if ($fallbackResult) {
            $fallbackResult['source'] = self::SOURCE_FALLBACK;
            Log::info('Using keyword fallback', $fallbackResult);
            return $fallbackResult;
        }

        $finalFallback = [
            'type' => 'text',
            'content' => 'آسف، حالياً عندي مشكلة في إتصال بالذكاء الاصطناعي! لكن يمكنك استخدمي أوامر مباشرة زي: "عاوز أعمل شحنة"، "ابحث عن الشحنة SH123"، "عدل الشحنة SH123"، أو "احذف الشحنة SH123"!',
            'source' => self::SOURCE_FALLBACK,
        ];
        Log::info('Using final fallback', $finalFallback);
        return $finalFallback;
    }

    /**
     * Simple keyword-based fallback for common messages
     */
    protected function keywordFallback(string $message): ?array
    {
        $messageLower = mb_strtolower(trim($message));

        // Greetings
        $greetings = ['اهلا', 'أهلا', 'hello', 'hi', 'مرحبا', 'ازيك', 'إزيك', 'ازيك؟', 'إزيك؟', 'كيف الحال', 'كيفك'];
        foreach ($greetings as $greeting) {
            if (str_contains($messageLower, $greeting)) {
                return [
                    'type' => 'text',
                    'content' => 'تمام الحمد لله، أنا جاهز لمساعدتك! إزيك؟'
                ];
            }
        }

        // Thanks
        $thanks = ['شكرا', 'شكرًا', 'شكراً', 'thanks', 'thank you'];
        foreach ($thanks as $thank) {
            if (str_contains($messageLower, $thank)) {
                return [
                    'type' => 'text',
                    'content' => 'عفواً! سعيد لخدمتك!'
                ];
            }
        }

        // Goodbyes
        $goodbyes = ['سلام', 'مع السلامة', 'bye', 'goodbye'];
        foreach ($goodbyes as $goodbye) {
            if (str_contains($messageLower, $goodbye)) {
                return [
                    'type' => 'text',
                    'content' => 'مع السلامة! راح نكمل بعدين!'
                ];
            }
        }

        // Name questions
        $nameQuestions = ['اسمك', 'انت اسمك', 'إسمك', 'ما اسمك', 'ما إسمك'];
        foreach ($nameQuestions as $q) {
            if (str_contains($messageLower, $q)) {
                return [
                    'type' => 'text',
                    'content' => 'أنا مساعد الشحنات بتاعك! يمكن تناديني بأي اسم عاوزة!'
                ];
            }
        }

        // "How are you?" follow-ups
        $howAreYouResponses = ['كويس', 'كويس', 'تمام', 'حاضر', 'ماشي'];
        $howAreYouQuestions = ['عامل ايه', 'عامل إيه', 'عامل ايه', 'عامل إيه'];
        foreach ($howAreYouQuestions as $q) {
            if (str_contains($messageLower, $q)) {
                return [
                    'type' => 'text',
                    'content' => 'تمام! وأنت؟ إيه الحكاية؟ في حاجة عاوز تسألها عن الشحنات؟'
                ];
            }
        }
        foreach ($howAreYouResponses as $r) {
            if (str_contains($messageLower, $r)) {
                return [
                    'type' => 'text',
                    'content' => 'حلو كده! تمام، إيه اللي عاوز تعمله دلوقتي؟'
                ];
            }
        }

        // What's this?
        $whatQuestions = ['ايه دا', 'إيه دا', 'ده إيه', 'دا ايه'];
        foreach ($whatQuestions as $q) {
            if (str_contains($messageLower, $q)) {
                return [
                    'type' => 'text',
                    'content' => 'أنا نظام إدارة شحنات! يمكنك تسألني عاوز تخلق شحنة، تبحث عن شحنة، تعدل، أو تحذف!'
                ];
            }
        }

        // Basic shipment operation keywords
        $createKeywords = ['اعمل شحنة', 'خلق شحنة', 'أعمل شحنة', 'إنشاء شحنة', 'أنا عاوز أعمل شحنة', 'عاوز أعمل شحنة'];
        foreach ($createKeywords as $kw) {
            if (str_contains($messageLower, $kw)) {
                return [
                    'type' => 'text',
                    'content' => 'تمام! عاوز تعمل شحنة! إرسلي ليا رقم الشحنة (code)، المدينة (city)، والفرع (branch)!'
                ];
            }
        }

        $searchKeywords = ['ابحث عن', 'ابحث', 'بحث عن', 'عاوز أSearch', 'عاوز ابحث'];
        foreach ($searchKeywords as $kw) {
            if (str_contains($messageLower, $kw)) {
                return [
                    'type' => 'text',
                    'content' => 'تمام! عاوز تبحث عن إيه؟ يمكن تبحث برقم الشحنة (code)، أو بالمدينة (city)، أو بالحالة (status)!'
                ];
            }
        }

        return null;
    }

    /**
     * Parse AI result to see if it's text or tool call
     */
    protected function parseResult(string $result): array
    {
        // Try to extract JSON
        $cleaned = $this->cleanJsonResponse($result);
        $decoded = json_decode($cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['action'])) {
            // It's a tool call
            return [
                'type' => 'tool',
                'content' => $decoded,
                'source' => self::SOURCE_AI,
            ];
        }

        // It's text
        return [
            'type' => 'text',
            'content' => $result,
            'source' => self::SOURCE_AI,
        ];
    }

    protected function cleanJsonResponse(string $response): string
    {
        $firstBrace = strpos($response, '{');
        $lastBrace = strrpos($response, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            return substr($response, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        return $response;
    }
}
