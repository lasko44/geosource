<?php

/**
 * Blog Posts Import Script
 *
 * Usage on production:
 * 1. Upload this file and blog_posts_export.json to your server
 * 2. Run: php artisan tinker < import_blog_posts.php
 *
 * Or run directly:
 * php import_blog_posts.php (from Laravel root directory)
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BlogPost;
use Illuminate\Support\Str;

$jsonFile = __DIR__.'/blog_posts_export.json';

if (!file_exists($jsonFile)) {
    echo "Error: blog_posts_export.json not found\n";
    exit(1);
}

$posts = json_decode(file_get_contents($jsonFile), true);

echo "Importing " . count($posts) . " blog posts...\n\n";

foreach ($posts as $postData) {
    // Check if post already exists by slug
    $existing = BlogPost::where('slug', $postData['slug'])->first();

    if ($existing) {
        echo "Skipping (exists): {$postData['slug']}\n";
        continue;
    }

    $post = new BlogPost();
    $post->uuid = $postData['uuid'] ?? Str::uuid();
    $post->slug = $postData['slug'];
    $post->title = $postData['title'];
    $post->excerpt = $postData['excerpt'];
    $post->content = $postData['content'];
    $post->featured_image = $postData['featured_image'];
    $post->meta_title = $postData['meta_title'];
    $post->meta_description = $postData['meta_description'];
    $post->author_id = $postData['author_id'];
    $post->status = $postData['status'];
    $post->published_at = $postData['published_at'] ? new \DateTime($postData['published_at']) : null;
    $post->tags = $postData['tags'];
    $post->view_count = $postData['view_count'] ?? 0;
    $post->save();

    echo "Imported: {$postData['title']}\n";
}

echo "\nDone!\n";
