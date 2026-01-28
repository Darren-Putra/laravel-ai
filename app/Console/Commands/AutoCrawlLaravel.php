<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;
use App\Services\CrawlObserver;

class AutoCrawlLaravel extends Command
{
    protected $signature = 'laravel-ai:crawl';
    protected $description = 'Suruh AI menjelajah seluruh dokumentasi Laravel';

    // File: App\Console\Commands\AutoCrawlLaravel.php

    public function handle()
    {
        $targetUrl = 'https://laravel.com/docs/11.x';
        $this->info("Robot mulai berjalan dari: $targetUrl");


        set_time_limit(0); // 0 artinya tidak ada batas waktu
        ini_set('memory_limit', '512M'); // Naikkan limit memori jika diizinkan server
        
        Crawler::create()
            ->setCrawlObserver(new CrawlObserver())
            // --- TAMBAHKAN DUA BARIS INI ---
            ->setConcurrency(1) // Jalankan satu-satu (biar database tidak pusing)
            ->setDelayBetweenRequests(0) // Jeda 1 detik
            // ------------------------------
            ->setCrawlProfile(new class extends \Spatie\Crawler\CrawlProfiles\CrawlProfile {
                public function shouldCrawl(\Psr\Http\Message\UriInterface $url): bool
                {
                    return str_contains($url->getPath(), '/docs/11.x');
                }
            })
            ->setMaximumDepth(5)
            ->startCrawling($targetUrl);

        $this->info("Semua halaman sudah diproses!");
    }
}
