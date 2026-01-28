<?php

namespace App\Nova\Metrics;

use App\Models\BlogPost;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class TopBlogPosts extends Partition
{
    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Top Blog Posts';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $posts = BlogPost::published()
            ->orderByDesc('view_count')
            ->limit(10)
            ->get()
            ->mapWithKeys(function ($post) {
                // Convert encoding and truncate title if too long
                $title = mb_convert_encoding($post->title ?? '', 'UTF-8', 'UTF-8');
                $title = preg_replace('/[\x00-\x1F\x7F]/u', '', $title); // Remove control characters

                if (mb_strlen($title) > 40) {
                    $title = mb_substr($title, 0, 37) . '...';
                }

                return [$title => $post->view_count ?? 0];
            })
            ->toArray();

        return $this->result($posts);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'top-blog-posts';
    }
}
