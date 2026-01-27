<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\LaravelKnowledge;

class ResearchLaravel extends Command
{
    protected $signature = 'laravel-ai:research {url}';
    protected $description = 'Ambil ilmu dari dokumentasi Laravel dan simpan ke MySQL';

    public function handle()
    {
        $url = $this->argument('url');

        // 1. Ambil data halaman
        $response = Http::get($url);
        if (!$response->successful()) return;

        $cleanText = strip_tags($response->body());
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        $chunks = str_split($cleanText, 1000);

        $newData = []; // Temporary storage
        $this->info("Sedang memproses embedding: $url");

        foreach ($chunks as $index => $chunk) {
            try {
                $client = \OpenAI::factory()
                    ->withApiKey(env('OPENAI_API_KEY'))
                    ->withBaseUri(env('OPENAI_BASE_URI'))
                    ->withHttpHeader('ngrok-skip-browser-warning', '1')
                    ->make();

                $res = $client->embeddings()->create([
                    'model' => 'local-model',
                    'input' => $chunk,
                ]);

                // Simpan ke array dulu, jangan langsung ke DB
                $newData[] = [
                    'url' => $url,
                    'title' => "Bagian " . ($index + 1),
                    'content' => $chunk,
                    'vector' => json_encode($res->embeddings[0]->embedding) // Pastikan formatnya benar
                ];
            } catch (\Exception $e) {
                $this->error("Gagal embedding di bagian $index. Batalkan semua untuk URL ini.");
                return; // Berhenti total jika salah satu chunk gagal agar data tidak parsial
            }
        }

        // 2. Jika semua chunk berhasil, baru lakukan operasi database
        if (!empty($newData)) {
            \DB::transaction(function () use ($url, $newData) {
                // Hapus data lama hanya ketika data baru sudah siap
                \App\Models\LaravelKnowledge::where('url', $url)->delete();

                // Masukkan semua data baru
                foreach ($newData as $data) {
                    \App\Models\LaravelKnowledge::create($data);
                }
            });
            $this->info("Berhasil menyerap: $url (" . count($newData) . " bagian)");
        }
    }
}
