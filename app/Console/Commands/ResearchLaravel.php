<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\LaravelKnowledge;
use OpenAI;

class ResearchLaravel extends Command
{
    protected $signature = 'laravel-ai:research {url}';
    protected $description = 'Ambil ilmu dari dokumentasi Laravel dan simpan ke MySQL';

    public function handle()
    {
        // 🔑 OpenAI-compatible client (LM Studio via ngrok)
        $client = OpenAI::factory()
        ->withApiKey('lm-studio') // dummy
        ->withBaseUri(env('OPENAI_BASE_URL')) // https://xxxx.ngrok-free.app/v1
        ->make();
    

        $url = $this->argument('url');
        $embeddedCount = 0;

        // 1. Ambil HTML
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            $this->error("Gagal akses URL");
            return 1;
        }

        // 2. Bersihkan teks
        $cleanText = strip_tags($response->body());
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);

        if (strlen($cleanText) < 100) {
            $this->error("Konten terlalu sedikit, AI tidak dipanggil.");
            return 1;
        }

        $chunks = str_split($cleanText, 1000);
        $newData = [];

        // 3. Embedding
        foreach ($chunks as $index => $chunk) {
            try {
                $response = $client->embeddings()->create([
                    'model' => 'nomic-embed-text', // HARUS model embedding
                    'input' => $chunk,
                ]);
                

                // LM Studio → response ARRAY
                if (!isset($response['data'][0]['embedding'])) {
                    $this->error('Response embedding tidak valid');
                    $this->line(json_encode($response, JSON_PRETTY_PRINT));
                    return 1;
                }

                $embedding = $response['data'][0]['embedding'];

                $newData[] = [
                    'url'     => $url,
                    'title'   => 'Laravel Docs',
                    'content' => $chunk,
                    'vector'  => $embedding,
                ];

                $embeddedCount++;
                $this->info("Embedding chunk #" . ($index + 1));

            } catch (\Throwable $e) {
                $this->error("Gagal embedding: " . $e->getMessage());
                return 1;
            }
        }

        if ($embeddedCount === 0) {
            $this->error("AI tidak menghasilkan embedding sama sekali");
            return 1;
        }

        // 4. Simpan ke DB
        \DB::transaction(function () use ($url, $newData) {
            LaravelKnowledge::where('url', $url)->delete();
            foreach ($newData as $data) {
                LaravelKnowledge::create($data);
            }
        });

        $this->info("Berhasil menyerap ($embeddedCount chunk): $url");
        return 0;
    }
}
