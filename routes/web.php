<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Models\LaravelKnowledge;
use Illuminate\Http\Request;
use App\Http\Controllers\ChatController;

Route::get('/chat', fn() => view('chat'));
Route::get('/tanya-ai', [ChatController::class, 'ask']);



// Fungsi pembantu untuk menghitung kemiripan angka
function cosineSimilarity($vec1, $vec2) {
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;
    foreach ($vec1 as $i => $val) {
        $dotProduct += $val * ($vec2[$i] ?? 0);
        $normA += $val ** 2;
        $normB += ($vec2[$i] ?? 0) ** 2;
    }
    return $dotProduct / (sqrt($normA) * sqrt($normB));
}


Route::get('/tes-ai', function () {
    // 1. Ambil data dari .env
    $baseUrl = rtrim(env('OPENAI_BASE_URI'), '/');
    $apiKey = env('OPENAI_API_KEY');

    // 2. Kita pakai manual factory untuk bypass Ngrok
    try {
        $client = \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->withHttpHeader('ngrok-skip-browser-warning', '1') // WAJIB untuk Ngrok
            ->make();

        $result = $client->chat()->create([
            'model' => 'local-model', 
            'messages' => [
                ['role' => 'user', 'content' => 'Halo LM Studio! Jika kamu dengar ini, balas dengan: KONEKSI AMAN'],
            ],
        ]);

        return [
            'status' => 'Berhasil!',
            'pesan_dari_ai' => $result->choices[0]->message->content,
            'model_yang_dipakai' => $result->model
        ];

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'Gagal koneksi!',
            'error' => $e->getMessage(),
            'solusi' => 'Pastikan Ngrok sudah jalan dan LM Studio sudah klik START SERVER'
        ], 500);
    }
});
Route::get('/', function () {
    return view('welcome');
});
