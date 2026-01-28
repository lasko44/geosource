<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
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
    public function trackShare(Request $request, BlogPost $post): JsonResponse
    {
        $request->validate([
            'platform' => 'required|string|in:twitter,linkedin,facebook,copy_link',
        ]);

        // Get geolocation from IP
        $geoData = $this->getGeoData($request->ip());

        BlogShare::create([
            'blog_post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'platform' => $request->input('platform'),
            'visitor_hash' => $this->createVisitorHash($request),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'country' => $geoData['country'] ?? null,
            'referrer' => $request->header('referer') ? substr($request->header('referer'), 0, 2048) : null,
            'created_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Create a visitor hash for fingerprinting.
     */
    private function createVisitorHash(Request $request): string
    {
        $components = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language'),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Get geolocation data from IP address.
     */
    private function getGeoData(?string $ip): array
    {
        if (empty($ip) || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [];
        }

        try {
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=status,country");

            if ($response->successful() && $response->json('status') === 'success') {
                return [
                    'country' => $response->json('country'),
                ];
            }
        } catch (\Exception $e) {
            // Silently fail - geolocation is optional
        }

        return [];
    }
}
