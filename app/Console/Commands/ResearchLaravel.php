<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\LaravelKnowledge;
use OpenAI\Laravel\Facades\OpenAI;

class ResearchLaravel extends Command
{
    protected $signature = 'laravel-ai:research {url}';
    protected $description = 'Ambil ilmu dari dokumentasi Laravel dan simpan ke MySQL';

    public function handle()
    {
        $url = $this->argument('url');
        $embeddedCount = 0;

        // 1. Ambil data
        $response = \Illuminate\Support\Facades\Http::get($url);
        if (!$response->successful()) {
            $this->error("Gagal akses URL");
            return 1; // <--- KODE ERROR
        }

        $cleanText = strip_tags($response->body());
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);

        if (strlen($cleanText) < 100) {
            $this->error("Konten terlalu sedikit, AI tidak dipanggil.");
            return 1; // ✅ GAGAL
        }


        $chunks = str_split($cleanText, 1000);
        $newData = [];

        // ... (kode looping embedding kamu) ...
        foreach ($chunks as $index => $chunk) {
            try {
                $response = OpenAI::embeddings()->create([
                    'model' => 'text-embedding-3-small',
                    'input' => $chunk,
                ]);
        
                $embedding = $response->data[0]->embedding;
        
                $newData[] = [
                    'url' => $url,
                    'title' => 'Laravel Docs',
                    'content' => $chunk,
                    'vector' => $embedding,
                ];
        
                $embeddedCount++;
                $this->info("Embedding chunk #" . ($index + 1));
        
            } catch (\Exception $e) {
                $this->error("Gagal embedding: " . $e->getMessage());
                return 1;
            }
        }
        


        if ($embeddedCount === 0) {
            $this->error("AI tidak menghasilkan embedding sama sekali");
            return 1; // ❌ WAJIB GAGAL
        }
        
        // 2. Simpan ke Database
        if (!empty($newData)) {
            \DB::transaction(function () use ($url, $newData) {
                \App\Models\LaravelKnowledge::where('url', $url)->delete();
                foreach ($newData as $data) {
                    \App\Models\LaravelKnowledge::create($data);
                }
            });
            $this->info("Berhasil menyerap: $url");
            return 0; // <--- KODE SUKSES
        }

        return 1; // <--- Gagal karena tidak ada data yang siap
    }
}
