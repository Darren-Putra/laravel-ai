<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel AI Specialist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
</head>
<body class="bg-gray-900 text-white font-sans">
<div class="max-w-4xl mx-auto h-screen flex flex-col p-4" x-data="chatBot()">
    <div class="py-4 border-b border-gray-700 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-blue-400">Laravel AI Specialist</h1>
        <span class="text-xs bg-green-900 text-green-300 px-2 py-1 rounded">Local AI (LM Studio) Connected</span>
    </div>

    <div class="flex-1 overflow-y-auto py-4 space-y-4 custom-scrollbar" id="chat-window">
        <template x-for="msg in messages">
            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                <div :class="msg.role === 'user' ? 'bg-blue-600' : 'bg-gray-800 border border-gray-700'" class="max-w-[80%] rounded-lg p-3 shadow-sm">
                    <p class="text-sm font-semibold mb-1" x-text="msg.role === 'user' ? 'Anda' : 'AI Specialist'"></p>
                    <div class="text-sm leading-relaxed whitespace-pre-wrap" x-html="formatMessage(msg.content)"></div>
                </div>
            </div>
        </template>

        <div x-show="loading" class="flex justify-start">
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 animate-pulse">Thinking...</div>
        </div>
    </div>

    <div class="py-4 border-t border-gray-700">
        <form @submit.prevent="sendMessage" class="flex gap-2">
            <input type="text" x-model="userInput" class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tanya sesuatu tentang Laravel...">
            <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-lg font-semibold disabled:opacity-50">Kirim</button>
        </form>
    </div>
</div>

<script>
function chatBot() {
    return {
        userInput: '',
        loading: false,
        messages: [
            { role: 'assistant', content: 'Halo! Saya sudah mempelajari dokumentasi Laravel di database kamu. Ada yang bisa saya bantu?' }
        ],
        async sendMessage() {
            if (!this.userInput.trim()) return;
            
            const userMsg = this.userInput;
            this.messages.push({ role: 'user', content: userMsg });
            this.userInput = '';
            this.loading = true;

            // Scroll ke bawah
            this.$nextTick(() => { document.getElementById('chat-window').scrollTo(0, 100000); });

            try {
                const response = await fetch(`/tanya-ai?q=${encodeURIComponent(userMsg)}`);
                const data = await response.json();
                this.messages.push({ role: 'assistant', content: data.jawaban });
            } catch (error) {
                this.messages.push({ role: 'assistant', content: 'Error: Gagal terhubung ke server.' });
            } finally {
                this.loading = false;
                this.$nextTick(() => { document.getElementById('chat-window').scrollTo(0, 100000); });
            }
        },
        formatMessage(content) {
            // Convert HTML tags to Markdown
            return content.replace(/<[^>]*>/g, (tag) => {
                switch (tag.toLowerCase()) {
                    case '<b>':
                        return '**';
                    case '</b>':
                        return '**';
                    case '<i>':
                        return '*';
                    case '</i>':
                        return '*';
                    default:
                        return tag;
                }
            });
        }
    };
}
</script>
</body>
</html>