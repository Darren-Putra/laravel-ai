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
        $userQuestion = $request->query('q', 'Apa itu Laravel?');

        // 1. Inisialisasi Client AI
        $client = OpenAI::factory()
            ->withApiKey(env('OPENAI_API_KEY'))
            ->withBaseUri(env('OPENAI_BASE_URI'))
            ->withHttpHeader('ngrok-skip-browser-warning', '1')
            ->make();

        // 2. Ubah pertanyaan jadi Vector
        $qResponse = $client->embeddings()->create([
            'model' => 'local-model',
            'input' => $userQuestion,
        ]);
        $questionVector = $qResponse->embeddings[0]->embedding;

        // 3. Filter Awal dengan Full-Text Search (Kecepatan)
        $filteredData = LaravelKnowledge::query()
            ->whereRaw("MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$userQuestion])
            ->limit(50)
            ->get();

        // 4. Ranking dengan Cosine Similarity (Akurasi)
        $matches = $filteredData->map(function ($item) use ($questionVector, $aiService) {
            $item->similarity = $aiService->cosineSimilarity($questionVector, $item->vector);
            return $item;
        })
        ->filter(fn($item) => $item->similarity > 0.3) // THRESHOLD: Abaikan jika tidak nyambung
        ->sortByDesc('similarity')
        ->take(3);

        // 5. Siapkan Konteks untuk AI
        $context = $matches->isEmpty() 
            ? "TIDAK ADA REFERENSI RELEVAN DI DATABASE." 
            : $matches->pluck('content')->implode("\n---\n");

        // 6. Kirim ke Chat Model
        $finalResponse = $client->chat()->create([
            'model' => 'local-model',
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => "Kamu adalah asisten Laravel. Jawablah HANYA berdasarkan referensi yang diberikan. " .
                                 "Jika referensi bertuliskan 'TIDAK ADA REFERENSI', jawablah bahwa kamu tidak menemukan informasi tersebut di dokumentasi lokal."
                ],
                ['role' => 'user', 'content' => "REFERENSI:\n$context\n\nPERTANYAAN: $userQuestion"],
            ],
        ]);

        return response()->json([
            'jawaban' => $finalResponse->choices[0]->message->content,
            'source_count' => $matches->count(),
            'top_similarity' => $matches->first() ? $matches->first()->similarity : 0
        ]);
    }
}