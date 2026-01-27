<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class ExportBlogPost extends Command
{
    protected $signature = 'blog:export {slug} {--format=sql : Output format (sql or json)}';

    protected $description = 'Export a blog post for production deployment';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $format = $this->option('format');

        $post = BlogPost::where('slug', $slug)->first();

        if (! $post) {
            $this->error("Blog post with slug '{$slug}' not found.");

            return 1;
        }

        if ($format === 'json') {
            $this->outputJson($post);
        } else {
            $this->outputSql($post);
        }

        return 0;
    }

    protected function outputJson(BlogPost $post): void
    {
        $data = $post->toArray();
        unset($data['id'], $data['uuid'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function outputSql(BlogPost $post): void
    {
        $data = [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featured_image' => $post->featured_image,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'schema_json' => $post->schema_json ? json_encode($post->schema_json) : null,
            'author_id' => $post->author_id,
            'status' => $post->status,
            'published_at' => $post->published_at?->toDateTimeString(),
            'tags' => $post->tags ? json_encode($post->tags) : null,
            'faq' => $post->faq ? json_encode($post->faq) : null,
            'quick_links' => $post->quick_links ? json_encode($post->quick_links) : null,
            'view_count' => 0,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(function ($value) {
            if ($value === null) {
                return 'NULL';
            }

            return "'" . addslashes($value) . "'";
        }, array_values($data)));

        $sql = "INSERT INTO blog_posts ({$columns}) VALUES ({$values});";

        $this->line($sql);
    }
}
