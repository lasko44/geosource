<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
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
}
