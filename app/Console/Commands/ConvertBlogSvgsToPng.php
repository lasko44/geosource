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
     * Convert SVG content to PNG using Imagick.
     */
    private function convertSvgToPng(string $svgContent): ?string
    {
        try {
            $imagick = new \Imagick();
            $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
            $imagick->readImageBlob($svgContent);
            $imagick->setImageFormat('png');
            // Resize to 1200x630 for social sharing
            $imagick->resizeImage(1200, 630, \Imagick::FILTER_LANCZOS, 1);
            $pngData = $imagick->getImageBlob();
            $imagick->destroy();

            return $pngData;
        } catch (\Exception $e) {
            $this->error("   Imagick error: {$e->getMessage()}");

            return null;
        }
    }
}
