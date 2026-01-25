<?php

namespace App\Services;

use App\Models\BlogPost;
use Illuminate\Support\Facades\File;

class BlogPostGeoService
{
    /**
     * Generate schema.org JSON-LD for a blog post.
     */
    public function generateSchemaJson(BlogPost $post): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'url' => config('app.url').'/blog/'.$post->slug,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => $post->author ? [
                '@type' => 'Person',
                'name' => $post->author->name,
            ] : [
                '@type' => 'Organization',
                'name' => 'GeoSource.ai',
                'url' => config('app.url'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'GeoSource.ai',
                'url' => config('app.url'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => config('app.url').'/logo.png',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => config('app.url').'/blog/'.$post->slug,
            ],
            'articleBody' => strip_tags($post->content),
            'wordCount' => str_word_count(strip_tags($post->content)),
        ];

        // Add image if available
        if ($post->featured_image_url) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $post->featured_image_url,
                'width' => 1200,
                'height' => 630,
            ];
        }

        // Add keywords/tags if available
        if (! empty($post->tags)) {
            $schema['keywords'] = implode(', ', $post->tags);
        }

        // Add article section if we can determine it from tags
        if (! empty($post->tags)) {
            $schema['articleSection'] = $post->tags[0] ?? 'GEO';
        }

        return $schema;
    }

    /**
     * Update the blog post's schema_json field.
     */
    public function updatePostSchema(BlogPost $post): void
    {
        $schema = $this->generateSchemaJson($post);
        $post->schema_json = $schema;
        $post->saveQuietly(); // Save without triggering observers again
    }

    /**
     * Regenerate the llms.txt file with updated blog posts.
     */
    public function updateLlmsTxt(): void
    {
        $llmsPath = public_path('llms.txt');

        // Read the existing llms.txt content
        $content = File::exists($llmsPath) ? File::get($llmsPath) : '';

        // Find and remove the existing Blog Posts section
        $content = $this->removeBlogPostsSection($content);

        // Generate new blog posts section
        $blogSection = $this->generateBlogPostsSection();

        // Insert the blog section before "## Who is GeoSource.ai For?" or at the end
        $insertMarker = '## Who is GeoSource.ai For?';
        if (str_contains($content, $insertMarker)) {
            $content = str_replace(
                $insertMarker,
                $blogSection."\n".$insertMarker,
                $content
            );
        } else {
            // Append to end if marker not found
            $content = rtrim($content)."\n\n".$blogSection;
        }

        // Update the "Last Updated" date
        $content = $this->updateLastUpdatedDate($content);

        File::put($llmsPath, $content);
    }

    /**
     * Remove existing blog posts section from llms.txt content.
     */
    private function removeBlogPostsSection(string $content): string
    {
        // Match the blog posts section and remove it
        $pattern = '/## Blog Posts\n.*?(?=\n## (?!Blog Posts)|$)/s';
        return preg_replace($pattern, '', $content);
    }

    /**
     * Generate the blog posts section for llms.txt.
     */
    private function generateBlogPostsSection(): string
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->get();

        if ($posts->isEmpty()) {
            return '';
        }

        $section = "## Blog Posts\n\n";
        $section .= "Latest articles about Generative Engine Optimization (GEO) and AI search visibility.\n\n";

        foreach ($posts as $post) {
            $section .= "### {$post->title}\n";
            $section .= "- URL: ".config('app.url')."/blog/{$post->slug}\n";
            $section .= "- Description: {$post->excerpt}\n";

            if ($post->published_at) {
                $section .= "- Published: ".$post->published_at->format('Y-m-d')."\n";
            }

            if (! empty($post->tags)) {
                $section .= "- Topics: ".implode(', ', $post->tags)."\n";
            }

            $section .= "\n";
        }

        return $section;
    }

    /**
     * Update the "Last Updated" date in llms.txt.
     */
    private function updateLastUpdatedDate(string $content): string
    {
        $today = now()->format('Y-m-d');

        // Replace existing date
        $pattern = '/## Last Updated\n\n\d{4}-\d{2}-\d{2}/';
        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, "## Last Updated\n\n{$today}", $content);
        }

        // Or add it if not present
        return rtrim($content)."\n\n## Last Updated\n\n{$today}\n";
    }

    /**
     * Regenerate schema for all published blog posts.
     */
    public function regenerateAllSchemas(): int
    {
        $posts = BlogPost::published()->get();
        $count = 0;

        foreach ($posts as $post) {
            $this->updatePostSchema($post);
            $count++;
        }

        return $count;
    }
}
