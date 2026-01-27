<?php
namespace App\Services;

use Spatie\Crawler\CrawlObservers\CrawlObserver as BaseObserver;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

class CrawlObserver extends BaseObserver
{
    private $output;

    public function __construct() {
        $this->output = new ConsoleOutput();
    }

    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null
    ): void {
        $urlStr = (string) $url;

        // 1. Cek di database apakah sudah pernah 'completed'
        $task = DB::table('crawl_tasks')->where('url', $urlStr)->first();
        if ($task && $task->status === 'completed') {
            $this->output->writeln("<comment>[Skip]</comment> Sudah diproses: $urlStr");
            return;
        }

        if ($response->getStatusCode() === 200) {
            // Memberi tahu user URL mana yang sedang dihajar
            $this->output->writeln("<info>[Processing]</info> Sedang menyerap: $urlStr");

            try {
                // Tandai sedang diproses agar tidak bentrok
                DB::table('crawl_tasks')->updateOrInsert(
                    ['url' => $urlStr],
                    ['status' => 'processing', 'updated_at' => now()]
                );

                // Panggil command research (yang tadi sudah pakai transaksi DB)
                Artisan::call('laravel-ai:research', ['url' => $urlStr]);

                // Tandai selesai
                DB::table('crawl_tasks')->where('url', $urlStr)->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

                $this->output->writeln("<info>[Success]</info> Selesai: $urlStr");

            } catch (\Exception $e) {
                DB::table('crawl_tasks')->where('url', $urlStr)->update([
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                    'updated_at' => now()
                ]);
                $this->output->writeln("<error>[Error]</error> Gagal pada $urlStr: " . $e->getMessage());
            }
        }
    }

    public function crawlFailed(
        UriInterface $url,
        \GuzzleHttp\Exception\RequestException $requestException,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null
    ): void {
        $this->output->writeln("<error>[Fatal]</error> Gagal akses URL: " . (string)$url);
    }
}