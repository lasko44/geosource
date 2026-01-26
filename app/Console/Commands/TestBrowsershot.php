<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

class TestBrowsershot extends Command
{
    protected $signature = 'test:browsershot {url?}';

    protected $description = 'Test Browsershot configuration';

    public function handle()
    {
        $url = $this->argument('url') ?? 'https://www.texasroadhouse.com/';

        $this->info("Testing Browsershot with URL: {$url}");
        $this->newLine();

        // Check Node/NPM paths
        $nodePath = config('browsershot.node_binary', '/usr/bin/node');
        $npmPath = config('browsershot.npm_binary', '/usr/bin/npm');

        $this->info("Node binary: {$nodePath}");
        $this->info("NPM binary: {$npmPath}");

        if (!file_exists($nodePath)) {
            $this->error("Node binary not found at {$nodePath}");
            $this->info("Run 'which node' to find the correct path");
            return 1;
        }

        if (!file_exists($npmPath)) {
            $this->error("NPM binary not found at {$npmPath}");
            $this->info("Run 'which npm' to find the correct path");
            return 1;
        }

        $this->info("Node/NPM paths OK");
        $this->newLine();

        $this->info("Attempting to fetch page with Browsershot...");

        try {
            $browsershot = Browsershot::url($url)
                ->setNodeBinary($nodePath)
                ->setNpmBinary($npmPath)
                ->noSandbox()
                ->dismissDialogs()
                ->waitUntilNetworkIdle()
                ->timeout(90)
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->windowSize(1920, 1080)
                ->addChromiumArguments([
                    'disable-blink-features' => 'AutomationControlled',
                    'disable-features' => 'IsolateOrigins,site-per-process',
                    'disable-site-isolation-trials',
                    'disable-web-security',
                    'disable-dev-shm-usage',
                    'disable-gpu',
                    'no-first-run',
                    'no-default-browser-check',
                    'disable-infobars',
                    'disable-extensions',
                    'disable-popup-blocking',
                ])
                ->setDelay(5000);

            if ($chromePath = config('browsershot.chrome_path')) {
                $this->info("Using Chrome path: {$chromePath}");
                $browsershot->setChromePath($chromePath);
            }

            $html = $browsershot->bodyHtml();

            if (empty($html)) {
                $this->error("Empty response received");
                return 1;
            }

            $this->info("Success! Received " . strlen($html) . " bytes");

            // Check for Cloudflare challenge
            if (str_contains($html, 'challenge-platform') || str_contains($html, 'cf-browser-verification')) {
                $this->warn("Cloudflare challenge page detected - may need longer delay");
            }

            // Check for title
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
                $this->info("Page title: " . trim(html_entity_decode($match[1])));
            }

            // Save HTML for inspection
            $outputPath = storage_path('app/browsershot-test.html');
            file_put_contents($outputPath, $html);
            $this->info("HTML saved to: {$outputPath}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Browsershot failed: " . $e->getMessage());
            $this->newLine();
            $this->error("Full error:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
