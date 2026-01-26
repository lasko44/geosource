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
                // Truncate title if too long
                $title = strlen($post->title) > 40
                    ? substr($post->title, 0, 37) . '...'
                    : $post->title;

                return [$title => $post->view_count];
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
