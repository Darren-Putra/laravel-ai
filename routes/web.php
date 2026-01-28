<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Models\LaravelKnowledge;
use Illuminate\Http\Request;
use App\Http\Controllers\ChatController;

Route::get('/chat', fn() => view('chat'));
Route::get('/tanya-ai', [ChatController::class, 'ask']);




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
