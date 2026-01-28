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
        
        // 1. Ambil data
        $response = \Illuminate\Support\Facades\Http::get($url);
        if (!$response->successful()) {
            $this->error("Gagal akses URL");
            return 1; // <--- KODE ERROR
        }
    
        $cleanText = strip_tags($response->body());
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        
        // Cek: Kalau halamannya kosong/sedikit sekali (misal cuma redirect), anggap skip tapi sukses
        if (strlen($cleanText) < 100) {
            $this->warn("Konten terlalu sedikit, dilewati.");
            return 0; 
        }
    
        $chunks = str_split($cleanText, 1000);
        $newData = [];
    
        // ... (kode looping embedding kamu) ...
        foreach ($chunks as $index => $chunk) {
            try {
                // ... Logic AI ...
                // ... Simpan ke $newData[] ...
            } catch (\Exception $e) {
                $this->error("Gagal embedding: " . $e->getMessage());
                return 1; // <--- KODE ERROR: Berhenti & Lapor Gagal
            }
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
