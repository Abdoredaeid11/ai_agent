# ملخص مشروع نظام إدارة الشحنات بالذكاء الاصطناعي (Laravel AI Shipment Manager)

## نظرة عامة
هذا مشروع Laravel لإنشاء مساعد ذكي لإدارة الشحنات يدعم التحدث باللغة العربية المصرية، مع ذاكرة محادثة، واستخدام مزود AI مختلف مثل Gemini أو Groq أو Ollama أو OpenRouter.

## المكونات الرئيسية

### 1. الموديلز (Models)
- **Shipment**: تمثيل الشحنة
  - الحقول: code, status, city, branch, shipping_date, created_at, updated_at
  - الملف: [app/Models/Shipment.php](file:///d:/graphql-demo/app/Models/Shipment.php)
- **Conversation**: تمثيل المحادثة (سجل المحادثات)
  - الحقول: title, context, created_at, updated_at
  - الملف: [app/Models/Conversation.php](file:///d:/graphql-demo/app/Models/Conversation.php)
- **Message**: تمثيل رسائل المحادثة
  - الحقول: conversation_id, role (user/assistant), content, meta, created_at, updated_at
  - الملف: [app/Models/Message.php](file:///d:/graphql-demo/app/Models/Message.php)
- **AISetting**: إعدادات المزودات AI (جديد!)
  - الحقول: provider, model, is_active, priority
  - الملف: [app/Models/AISetting.php](file:///d:/graphql-demo/app/Models/AISetting.php)

### 2. بنية المزود AI الجديدة (AI Provider Architecture)
دلوقتي المشروع يستخدم نظام فعلية (Provider Pattern) منفصل لكل مزود AI!
- **app/AI/Contracts/AIProviderInterface.php**: الواجهة الأساسية لجميع المزودات (method: chat)
- **app/AI/Providers/**:
  - [GeminiProvider](file:///d:/graphql-demo/app/AI/Providers/GeminiProvider.php) - مزود Google Gemini
  - [GroqProvider](file:///d:/graphql-demo/app/AI/Providers/GroqProvider.php) - مزود Groq
  - [OpenRouterProvider](file:///d:/graphql-demo/app/AI/Providers/OpenRouterProvider.php) - مزود OpenRouter (يدعم مئات النماذج)
  - [OllamaProvider](file:///d:/graphql-demo/app/AI/Providers/OllamaProvider.php) - مزود Ollama (محلي!)
- **app/AI/AIProviderFactory.php**: مصنع إنشاء المزودات ديناميكياً!

### 3. الخدمات (Services)
- **AiQueryService.php**:
  - يتعامل مع معالجة رسائل المستخدم
  - يستخدم AIProviderFactory لتجربة المزودات بالترتيب حتى نجاح! (Fallback Mechanism!)
  - يتحقق من رد AI ما بين نص عادي أو أداة (Tool Call)
  - يحتوي على fallback بالكلمات المفتاحية عشان لو كل المزودات فشلت!
  - الملف: [app/Services/AiQueryService.php](file:///d:/graphql-demo/app/Services/AiQueryService.php)
- **ConversationalShipmentService.php**:
  - يتنظم العملية الكاملة للمحادثة والعمليات على الشحنات!
  - ينفذ الأدوات (Create/Search/Update/Delete shipment) بعد ما يقرر AI
  - يطلب من AI شرح النتيجة باللغة الطبيعية!
  - الملف: [app/Services/ConversationalShipmentService.php](file:///d:/graphql-demo/app/Services/ConversationalShipmentService.php)
- **AISettingsService.php**:
  - يتحكم في إعدادات المزودات AI
  - يخلق cache للإعدادات لمدة ساعة عشان تحسن الأداء!
  - يسوي seeding لإعدادات افتراضية لو مفيش سجلات!
  - الملف: [app/Services/AISettingsService.php](file:///d:/graphql-demo/app/Services/AISettingsService.php)
- **ShipmentSearchService.php**:
  - يتحكم في عمليات البحث عن الشحنات
  - الملف: [app/Services/ShipmentSearchService.php](file:///d:/graphql-demo/app/Services/ShipmentSearchService.php)

### 4. الكنترولر (Controller)
- **ConversationalShipmentController.php**:
  - يعرض واجهة المحادثة
  - يتعامل مع معالجة رسائل POST
  - Mreturns JSON للواجهة الأمامية
  - الملف: [app/Http/Controllers/ConversationalShipmentController.php](file:///d:/graphql-demo/app/Http/Controllers/ConversationalShipmentController.php)

### 5. الواجهة الأمامية (Frontend)
- **resources/views/chat.blade.php**: واجهة محادثة واحدة (Chat UI) بالعربية!
  - تستخدم Tailwind CSS للتصميم
  - تستخدم JavaScript الأصلي للاتصال بالـ API
  - تتبع conversation_id من كل محادثة

## الميزات الحالية
1. **ذاكرة المحادثة**: يحفظ جميع الرسائل في قاعدة البيانات، ويرسل آخر 20 رسالة للمزود AI عشان يستخدم السياق!
2. **مزودات AI متعددة**: يدعم Gemini, Groq, OpenRouter, Ollama، مع نظام Fallback عشان لو مُزود فشل بيتم تجربة التالي بالترتيب!
3. **التعامل مع الأدوات (Tool Calling)**: يمكن للمساعد:
   - إنشاء شحنة جديدة (Create Shipment)
   - البحث عن شحنات (Search Shipments)
   - تعديل شحنة موجودة (Update Shipment)
   - حذف شحنة (Delete Shipment)
4. **واجهة المستخدم بالعربية المصرية**: الشخصية ودودة، طبيعية، بتتحدث كده!
5. **إعدادات AI من قاعدة البيانات**: يمكن تغيير المزود والنموذج بدون تعديل الكود أو .env (لو عندك واجهة إدارة!)
6. **Cache لإعدادات AI**: يحفظ الإعدادات لمدة ساعة عشان يسرع الأداء!

## كيفية تشغيل المشروع
1. تأكد من إعداد composer dependencies (لو مفيش): `composer install`
2. إعداد ملف .env (تأكد من GEMINI_API_KEY أو GROQ_API_KEY أو غيرها حسب ما تريد تستخدمه!)
3. تشغيل migrations: `php artisan migrate`
4. تشغيل خادم اللارافيل: `php artisan serve`
5. افتح المتصفح على http://localhost:8000/chat

## كيفية تغيير المزود AI الأساسي
### طريقة 1: باستخدام Tinker
```bash
php artisan tinker
>>> use App\Models\AISetting;
>>> // لجعل Groq هو الأساسي (priority 1)
>>> AISetting::where('provider', 'groq')->update(['priority' => 1]);
>>> AISetting::where('provider', 'gemini')->update(['priority' => 2]);
>>> exit;
>>> php artisan cache:clear # مسح الكاش لتطبيق التغيير
```

### طريقة 2: باستخدام SQL مباشرة
```sql
UPDATE ai_settings SET priority = 1 WHERE provider = 'groq';
UPDATE ai_settings SET priority = 2 WHERE provider = 'gemini';
```

## إعدادات البيئة (.env)
تحتاج تحدد هذه المتغيرات في ملف .env حسب المزود اللي تستخدمه:
- `GEMINI_API_KEY`: مفتاح API لـ Gemini (موجود عندك بالفعل!)
- `GROQ_API_KEY`: مفتاح API لـ Groq (موجود عندك بالفعل!)
- `OPENROUTER_API_KEY`: مفتاح API لـ OpenRouter
- `OLLAMA_URL`: رابط API لـ Ollama (لو شغال محلياً، الافتراضي http://localhost:11434/api/chat)
- `OLLAMA_MODEL`: النموذج اللي عندك محمل في Ollama

## الروابط السريعة للملفات
- [نقاط entry المشروع](file:///d:/graphql-demo/routes/web.php) (جرب تشوف!)
- [الـ AI System Prompt](file:///d:/graphql-demo/app/Services/AiQueryService.php#L22-L88) (واحد جوه AiQueryService)
- [الـ AIProviderInterface](file:///d:/graphql-demo/app/AI/Contracts/AIProviderInterface.php)
- [الـ AIProviderFactory](file:///d:/graphql-demo/app/AI/AIProviderFactory.php)
- [الـ ConversationalShipmentService](file:///d:/graphql-demo/app/Services/ConversationalShipmentService.php)
