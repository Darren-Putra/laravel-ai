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

        // =========================
        // 1️⃣ NORMALISASI PERTANYAAN
        // =========================
        $qRaw = strtolower($userQuestion);
        $q = preg_replace('/[^a-z0-9\s]/', ' ', $qRaw);
        $q = preg_replace('/\s+/', ' ', $q);

        // =========================
        // 2️⃣ DETEKSI SOURCE (HYBRID)
        // =========================
        $sources = [];

        if (preg_match('/\blivewire\b/', $q)) {
            $sources[] = 'livewire';
        }

        if (preg_match('/\blaravel\s*v?\s*12\b/', $q)) {
            $sources[] = 'laravel-12';
        }

        if (preg_match('/\blaravel\s*v?\s*11\b/', $q)) {
            $sources[] = 'laravel-11';
        }

        // =========================
        // 3️⃣ CLIENT LM STUDIO
        // =========================
        $client = OpenAI::factory()
            ->withApiKey('lm-studio')
            ->withBaseUri(env('OPENAI_BASE_URL'))
            ->withHttpHeader('ngrok-skip-browser-warning', '1')
            ->make();

        // =========================
        // 4️⃣ EMBEDDING PERTANYAAN
        // =========================
        $embedResponse = $client->embeddings()->create([
            'model' => 'nomic-embed-text',
            'input' => $userQuestion,
        ]);

        if (!isset($embedResponse['data'][0]['embedding'])) {
            return response()->json([
                'jawaban' => 'Gagal membuat embedding pertanyaan.'
            ], 500);
        }

        $questionVector = $embedResponse['data'][0]['embedding'];

        // =========================
        // 5️⃣ QUERY DATABASE (RAG)
        // =========================
        $query = LaravelKnowledge::query();

        if (!empty($sources)) {
            $query->whereIn('source', $sources);
        }

        $filteredData = $query
            ->whereRaw(
                "MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)",
                [$userQuestion]
            )
            ->limit(50)
            ->get();

        // =========================
        // 6️⃣ COSINE SIMILARITY
        // =========================
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

        // =========================
        // 7️⃣ CONTEXT
        // =========================
        $context = $matches->isEmpty()
            ? "TIDAK ADA REFERENSI RELEVAN DI DATABASE."
            : $matches->map(function ($m) {
                return "[{$m->source}]\n{$m->content}";
            })->implode("\n---\n");

        // =========================
        // 8️⃣ CHAT COMPLETION
        // =========================
        $chatResponse = $client->chat()->create([
            'model' => 'your-chat-model', // llama3 / mistral / qwen
            'messages' => [
                [
                    'role' => 'system',
                    'content' =>
                        "Kamu adalah Laravel AI Specialist.\n\n" .
                        "ATURAN:\n" .
                        "- Gunakan REFERENSI jika tersedia.\n" .
                        "- Jika REFERENSI kosong, kamu BOLEH menjawab menggunakan pengetahuan umum.\n" .
                        "- Jika jawaban berasal dari lebih dari satu sumber (misalnya Livewire dan Laravel 12), gabungkan dengan jelas.\n" .
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
            'detected_source'=> $sources,
            'source_count'   => $matches->count(),
            'top_similarity' => $matches->first()->similarity ?? 0,
        ]);
    }
}
