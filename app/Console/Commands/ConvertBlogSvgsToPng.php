<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertBlogSvgsToPng extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:convert-svgs-to-png
                            {--dry-run : Show what would be converted without actually converting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert SVG featured images to PNG for social sharing compatibility';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Check if Imagick is available, try CLI tools as fallback
        $conversionMethod = $this->detectConversionMethod();

        if ($conversionMethod === null && ! $dryRun) {
            $this->error('❌ No SVG to PNG conversion method available.');
            $this->newLine();
            $this->line('Install one of these options:');
            $this->line('  <comment>Option 1 - PHP Imagick:</comment>');
            $this->line('    Ubuntu/Debian: sudo apt-get install php-imagick');
            $this->line('    Then restart PHP-FPM: sudo systemctl restart php8.2-fpm');
            $this->newLine();
            $this->line('  <comment>Option 2 - rsvg-convert (librsvg):</comment>');
            $this->line('    Ubuntu/Debian: sudo apt-get install librsvg2-bin');
            $this->newLine();
            $this->line('  <comment>Option 3 - ImageMagick CLI:</comment>');
            $this->line('    Ubuntu/Debian: sudo apt-get install imagemagick');
            $this->newLine();
            $this->line('Or convert locally and upload:');
            $this->line('  magick input.svg -resize 1200x630 output.png');
            $this->newLine();

            // List the files that need conversion
            $posts = BlogPost::whereNotNull('featured_image')
                ->where('featured_image', 'like', '%.svg')
                ->get();

            if ($posts->isNotEmpty()) {
                $this->info('Files that need conversion:');
                foreach ($posts as $post) {
                    $pngPath = preg_replace('/\.svg$/', '.png', $post->featured_image);
                    $this->line("  SVG: {$post->featured_image}");
                    $this->line("  PNG: {$pngPath}");
                    $this->newLine();
                }
            }

            return Command::FAILURE;
        }

        if (! $dryRun) {
            $this->info("Using conversion method: <comment>{$conversionMethod}</comment>");
            $this->newLine();
        }

        if ($dryRun) {
            $this->info('🔍 Dry run mode - no files will be modified');
            $this->newLine();
        }

        // Find all blog posts with SVG featured images
        $posts = BlogPost::whereNotNull('featured_image')
            ->where('featured_image', 'like', '%.svg')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('✅ No blog posts with SVG featured images found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$posts->count()} blog post(s) with SVG featured images:");
        $this->newLine();

        $converted = 0;
        $failed = 0;

        foreach ($posts as $post) {
            $svgPath = $post->featured_image;
            $pngPath = preg_replace('/\.svg$/', '.png', $svgPath);

            $this->line("📄 <comment>{$post->title}</comment>");
            $this->line("   SVG: {$svgPath}");
            $this->line("   PNG: {$pngPath}");

            if ($dryRun) {
                $this->line('   <info>Would convert...</info>');
                $this->newLine();
                $converted++;

                continue;
            }

            try {
                // Determine if the SVG is in public folder or storage
                if (str_starts_with($svgPath, '/images/')) {
                    // Public folder
                    $svgFullPath = public_path($svgPath);
                    $pngFullPath = public_path($pngPath);

                    if (! file_exists($svgFullPath)) {
                        $this->error("   ❌ SVG file not found: {$svgFullPath}");
                        $failed++;

                        continue;
                    }

                    // Check if PNG already exists
                    if (file_exists($pngFullPath)) {
                        $this->line('   <info>✓ PNG already exists, skipping</info>');
                        $this->newLine();

                        continue;
                    }

                    // Convert using Imagick
                    $svgContent = file_get_contents($svgFullPath);
                    $pngData = $this->convertSvgToPng($svgContent);

                    if ($pngData === null) {
                        $this->error('   ❌ Conversion failed');
                        $failed++;

                        continue;
                    }

                    file_put_contents($pngFullPath, $pngData);
                    $this->line('   <info>✓ Converted successfully</info>');
                } else {
                    // Storage disk
                    if (! Storage::disk('public')->exists($svgPath)) {
                        $this->error("   ❌ SVG file not found in storage: {$svgPath}");
                        $failed++;

                        continue;
                    }

                    // Check if PNG already exists
                    if (Storage::disk('public')->exists($pngPath)) {
                        $this->line('   <info>✓ PNG already exists, skipping</info>');
                        $this->newLine();

                        continue;
                    }

                    // Convert using Imagick
                    $svgContent = Storage::disk('public')->get($svgPath);
                    $pngData = $this->convertSvgToPng($svgContent);

                    if ($pngData === null) {
                        $this->error('   ❌ Conversion failed');
                        $failed++;

                        continue;
                    }

                    Storage::disk('public')->put($pngPath, $pngData);
                    $this->line('   <info>✓ Converted successfully</info>');
                }

                $converted++;
            } catch (\Exception $e) {
                $this->error("   ❌ Error: {$e->getMessage()}");
                $failed++;
            }

            $this->newLine();
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("📊 Would convert {$converted} image(s)");
            $this->info('Run without --dry-run to perform the conversion.');
        } else {
            $this->info("📊 Converted: {$converted} | Failed: {$failed}");
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Detect which conversion method is available.
     */
    private function detectConversionMethod(): ?string
    {
        // Check PHP Imagick extension
        if (extension_loaded('imagick')) {
            return 'imagick';
        }

        // Check for rsvg-convert CLI tool
        exec('which rsvg-convert 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            return 'rsvg-convert';
        }

        // Check for ImageMagick CLI tool
        exec('which magick 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            return 'magick';
        }

        exec('which convert 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            return 'convert';
        }

        return null;
    }

    /**
     * Convert SVG content to PNG using the best available method.
     */
    private function convertSvgToPng(string $svgContent, string $svgPath = null): ?string
    {
        $method = $this->detectConversionMethod();

        return match ($method) {
            'imagick' => $this->convertWithImagick($svgContent),
            'rsvg-convert' => $this->convertWithRsvg($svgContent, $svgPath),
            'magick', 'convert' => $this->convertWithImageMagickCli($svgContent, $svgPath, $method),
            default => null,
        };
    }

    /**
     * Convert using PHP Imagick extension.
     */
    private function convertWithImagick(string $svgContent): ?string
    {
        try {
            $imagick = new \Imagick();
            $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
            $imagick->readImageBlob($svgContent);
            $imagick->setImageFormat('png');
            $imagick->resizeImage(1200, 630, \Imagick::FILTER_LANCZOS, 1);
            $pngData = $imagick->getImageBlob();
            $imagick->destroy();

            return $pngData;
        } catch (\Exception $e) {
            $this->error("   Imagick error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Convert using rsvg-convert CLI tool.
     */
    private function convertWithRsvg(string $svgContent, ?string $svgPath): ?string
    {
        try {
            $tempSvg = tempnam(sys_get_temp_dir(), 'svg_');
            $tempPng = tempnam(sys_get_temp_dir(), 'png_');

            file_put_contents($tempSvg, $svgContent);

            $command = sprintf(
                'rsvg-convert -w 1200 -h 630 -o %s %s 2>&1',
                escapeshellarg($tempPng),
                escapeshellarg($tempSvg)
            );

            exec($command, $output, $returnCode);

            unlink($tempSvg);

            if ($returnCode !== 0) {
                $this->error('   rsvg-convert error: ' . implode("\n", $output));
                @unlink($tempPng);

                return null;
            }

            $pngData = file_get_contents($tempPng);
            unlink($tempPng);

            return $pngData;
        } catch (\Exception $e) {
            $this->error("   rsvg-convert error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Convert using ImageMagick CLI tool (magick or convert).
     */
    private function convertWithImageMagickCli(string $svgContent, ?string $svgPath, string $command): ?string
    {
        try {
            $tempSvg = tempnam(sys_get_temp_dir(), 'svg_') . '.svg';
            $tempPng = tempnam(sys_get_temp_dir(), 'png_') . '.png';

            file_put_contents($tempSvg, $svgContent);

            $cmd = sprintf(
                '%s -background none -density 150 %s -resize 1200x630! %s 2>&1',
                $command,
                escapeshellarg($tempSvg),
                escapeshellarg($tempPng)
            );

            exec($cmd, $output, $returnCode);

            @unlink($tempSvg);

            if ($returnCode !== 0 || ! file_exists($tempPng)) {
                $this->error('   ImageMagick error: ' . implode("\n", $output));
                @unlink($tempPng);

                return null;
            }

            $pngData = file_get_contents($tempPng);
            unlink($tempPng);

            return $pngData;
        } catch (\Exception $e) {
            $this->error("   ImageMagick error: {$e->getMessage()}");

            return null;
        }
    }
}
