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

// Di dalam method crawled()

// ...
if ($response->getStatusCode() === 200) {
    $this->output->writeln("<info>[Processing]</info> Sedang menyerap: $urlStr");

    // Update status jadi processing
    DB::table('crawl_tasks')->updateOrInsert(
        ['url' => $urlStr],
        ['status' => 'processing', 'updated_at' => now()]
    );

    // --- PERUBAHAN DISINI ---
    // Tampung hasil kerjanya (Exit Code)
    $exitCode = Artisan::call('laravel-ai:research', ['url' => $urlStr]);

    if ($exitCode === 0) {
        // HANYA jika exitCode 0 (Sukses), baru tandai completed
        DB::table('crawl_tasks')->where('url', $urlStr)->update([
            'status' => 'completed',
            'updated_at' => now()
        ]);
        $this->output->writeln("<info>[Success]</info> Selesai: $urlStr");
    } else {
        // Jika exitCode 1 (Gagal), tandai failed
        DB::table('crawl_tasks')->where('url', $urlStr)->update([
            'status' => 'failed',
            'last_error' => 'Research Command returned failure code',
            'updated_at' => now()
        ]);
        $this->output->writeln("<error>[Failed]</error> Gagal memproses konten: $urlStr");
    }
    // ------------------------
}
// ...
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