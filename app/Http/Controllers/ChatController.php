<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LaravelKnowledge;
use App\Services\AiService;
use OpenAI;

class ChatController extends Controller
{
    public function ask(Request $request, AiService $aiService)
    {
        $userQuestion = $request->query('q');

        if (!$userQuestion) {
            return response()->json([
                'jawaban' => 'Pertanyaan kosong.'
            ]);
        }

        // 1️⃣ Client LM Studio (NGROK)
        $client = OpenAI::factory()
            ->withApiKey('lm-studio') // dummy
            ->withBaseUri(env('OPENAI_BASE_URL'))
            ->withHttpHeader('ngrok-skip-browser-warning', '1')
            ->make();

        // 2️⃣ Embedding pertanyaan user (MODEL EMBEDDING)
        $embedResponse = $client->embeddings()->create([
            'model' => 'nomic-embed-text', // ✅ model embedding
            'input' => $userQuestion,
        ]);

        if (!isset($embedResponse['data'][0]['embedding'])) {
            return response()->json([
                'jawaban' => 'Gagal membuat embedding pertanyaan.'
            ], 500);
        }

        $questionVector = $embedResponse['data'][0]['embedding'];

        // 3️⃣ Filter awal (FULLTEXT)
        $filteredData = LaravelKnowledge::query()
            ->whereRaw(
                "MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)",
                [$userQuestion]
            )
            ->limit(50)
            ->get();

        // 4️⃣ Ranking cosine similarity
        $matches = $filteredData->map(function ($item) use ($questionVector, $aiService) {
                $item->similarity = $aiService->cosineSimilarity(
                    $questionVector,
                    $item->vector
                );
                return $item;
            })
            ->filter(fn ($item) => $item->similarity > 0.3)
            ->sortByDesc('similarity')
            ->take(3)
            ->values();

        // 5️⃣ Context
        $context = $matches->isEmpty()
            ? "TIDAK ADA REFERENSI RELEVAN DI DATABASE."
            : $matches->pluck('content')->implode("\n---\n");

        // 6️⃣ Chat completion (MODEL CHAT)
        $chatResponse = $client->chat()->create([
            'model' => 'your-chat-model', // contoh: llama3, mistral, qwen
            'messages' => [
                [
                    'role' => 'system',
                    'content' =>
                        "Kamu adalah Laravel AI Specialist.\n\n" .
                        "ATURAN:\n" .
                        "- Gunakan REFERENSI jika tersedia.\n" .
                        "- Jika REFERENSI kosong, kamu BOLEH menjawab menggunakan pengetahuan umum.\n" .
                        "- Jika tanpa referensi, jelaskan bahwa jawaban bukan kutipan dokumentasi.\n" .
                        "- Jangan mengarang isi dokumentasi."
                ],                
                [
                    'role' => 'user',
                    'content' =>
                        "REFERENSI:\n$context\n\nPERTANYAAN:\n$userQuestion"
                ],
            ],
        ]);

        $answer = $chatResponse['choices'][0]['message']['content']
            ?? 'Tidak ada jawaban.';

        return response()->json([
            'jawaban'        => $answer,
            'source_count'   => $matches->count(),
            'top_similarity' => $matches->first()->similarity ?? 0,
        ]);
    }
}
