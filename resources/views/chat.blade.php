<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مساعد الشحنات AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto py-8 max-w-3xl">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">مساعد الشحنات</h1>
        
        <div id="chat-container" class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div id="messages" class="p-6 space-y-4 h-96 overflow-y-auto">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">AI</div>
                    <div class="bg-gray-100 rounded-2xl rounded-tr-none px-4 py-3 max-w-md">
                        <p class="text-gray-800">مرحباً! كيف يمكنني مساعدتك؟ يمكنك إنشاء شحنة جديدة، البحث عن شحنة، أو تعديل شحنة موجودة.</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 p-4">
                <form id="chat-form" class="flex gap-3">
                    <input type="text" id="message-input" placeholder="اكتب رسالتك هنا..." class="flex-1 border border-gray-300 rounded-full px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-full font-medium hover:bg-blue-600 transition-colors">إرسال</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const messagesContainer = document.getElementById('messages');
        let conversationId = null;

        function addMessage(text, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex items-start gap-3 ${isUser ? 'flex-row-reverse' : ''}`;
            
            const avatar = document.createElement('div');
            avatar.className = `w-8 h-8 rounded-full flex items-center justify-center text-white font-bold ${isUser ? 'bg-green-500' : 'bg-blue-500'}`;
            avatar.textContent = isUser ? 'أنت' : 'AI';
            
            const bubble = document.createElement('div');
            bubble.className = `rounded-2xl px-4 py-3 max-w-md ${isUser ? 'bg-blue-500 text-white rounded-tl-none' : 'bg-gray-100 text-gray-800 rounded-tr-none'}`;
            bubble.innerHTML = `<p>${text}</p>`;
            
            messageDiv.appendChild(avatar);
            messageDiv.appendChild(bubble);
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;
            
            addMessage(message, true);
            messageInput.value = '';
            
            try {
                const response = await fetch('{{ route('chat') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: conversationId
                    })
                });
                
                const result = await response.json();
                conversationId = result.conversation_id;
                addMessage(result.response);
            } catch (error) {
                addMessage('عذراً، حصل خطأ. حاول مرة أخرى.');
                console.error(error);
            }
        });
    </script>
</body>
</html>
