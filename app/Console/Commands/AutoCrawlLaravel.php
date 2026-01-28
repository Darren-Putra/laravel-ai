<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Crawler\Crawler;
use App\Services\CrawlObserver;
use Psr\Http\Message\UriInterface;

class AutoCrawlLaravel extends Command
{
    protected $signature = 'laravel-ai:crawl';
    protected $description = 'Suruh AI menjelajah dokumentasi Laravel & ecosystem';

    public function handle()
    {
        $sources = [
            [
                'name' => 'Laravel 11',
                'start' => 'https://laravel.com/docs/11.x',
                'match' => '/docs/11.x',
            ],
            [
                'name' => 'Laravel 12',
                'start' => 'https://laravel.com/docs/12.x',
                'match' => '/docs/12.x',
            ],
            [
                'name' => 'Livewire',
                'start' => 'https://livewire.laravel.com/docs',
                'match' => '/docs',
            ],
        ];

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        foreach ($sources as $source) {
            $this->info("🚀 Mulai crawl: {$source['name']}");

            Crawler::create()
                ->setCrawlObserver(new CrawlObserver())
                ->setConcurrency(1)
                ->setDelayBetweenRequests(1000)
                ->setCrawlProfile(new class($source) extends \Spatie\Crawler\CrawlProfiles\CrawlProfile {
                    private array $source;

                    public function __construct(array $source)
                    {
                        $this->source = $source;
                    }

                    public function shouldCrawl(UriInterface $url): bool
                    {
                        return str_contains($url->getPath(), $this->source['match']);
                    }
                })
                ->setMaximumDepth(5)
                ->startCrawling($source['start']);

            $this->info("✅ Selesai: {$source['name']}");
        }

        $this->info("🎉 Semua dokumentasi berhasil diproses!");
    }
}
