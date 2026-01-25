<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\BlogPostGeoService;

class BlogPostObserver
{
    public function __construct(
        private BlogPostGeoService $geoService
    ) {}

    /**
     * Handle the BlogPost "created" event.
     */
    public function created(BlogPost $blogPost): void
    {
        $this->updateGeoData($blogPost);
    }

    /**
     * Handle the BlogPost "updated" event.
     */
    public function updated(BlogPost $blogPost): void
    {
        $this->updateGeoData($blogPost);
    }

    /**
     * Handle the BlogPost "deleted" event.
     */
    public function deleted(BlogPost $blogPost): void
    {
        // Update llms.txt to remove the deleted post
        $this->geoService->updateLlmsTxt();
    }

    /**
     * Handle the BlogPost "restored" event.
     */
    public function restored(BlogPost $blogPost): void
    {
        $this->updateGeoData($blogPost);
    }

    /**
     * Update GEO data (schema.json and llms.txt) for a blog post.
     */
    private function updateGeoData(BlogPost $blogPost): void
    {
        // Generate and save schema.org JSON-LD
        $this->geoService->updatePostSchema($blogPost);

        // Update llms.txt if the post is published
        if ($blogPost->isPublished()) {
            $this->geoService->updateLlmsTxt();
        }
    }
}
