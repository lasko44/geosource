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
        // llms.txt is now manually managed - no auto-regeneration
    }

    /**
     * Handle the BlogPost "restored" event.
     */
    public function restored(BlogPost $blogPost): void
    {
        $this->updateGeoData($blogPost);
    }

    /**
     * Update GEO data (schema.json only) for a blog post.
     * Note: llms.txt is now manually managed and not auto-regenerated.
     */
    private function updateGeoData(BlogPost $blogPost): void
    {
        // Generate and save schema.org JSON-LD
        $this->geoService->updatePostSchema($blogPost);
    }
}
