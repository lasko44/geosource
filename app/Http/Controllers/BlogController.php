<?php

namespace App\Http\Controllers;

use App\Http\Requests\Blog\ShareBlogPostRequest;
use App\Models\BlogPost;
use App\Models\BlogShare;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles public blog post display and share tracking.
 */
class BlogController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(): Response
    {
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->select(['id', 'uuid', 'slug', 'title', 'excerpt', 'featured_image', 'published_at', 'tags', 'view_count'])
            ->paginate(12);

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Display the specified blog post.
     */
    public function show(BlogPost $post): Response
    {
        if (! $post->isPublished()) {
            abort(404);
        }

        $post->incrementViewCount();
        $post->load('author:id,name');

        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->select(['id', 'uuid', 'slug', 'title', 'excerpt', 'published_at'])
            ->get();

        return Inertia::render('Blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    /**
     * Track a share action for a blog post.
     */
    public function store(ShareBlogPostRequest $request, BlogPost $post, BlogService $blogService): JsonResponse
    {
        $geoData = $blogService->getGeoData($request->ip());

        BlogShare::create([
            'blog_post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'platform' => $request->getPlatform(),
            'visitor_hash' => $blogService->createVisitorHash($request),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'country' => $geoData['country'] ?? null,
            'referrer' => $request->header('referer') ? substr($request->header('referer'), 0, 2048) : null,
            'created_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
