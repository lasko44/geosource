<?php

namespace App\Http\Controllers;

use App\Models\ContentEmbedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Returns RAG-powered content suggestions based on vector similarity.
 */
class SuggestedContentController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $slug = $request->query('slug', '');
        $limit = min((int) $request->query('limit', 4), 8);

        if (! $slug) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = Cache::remember(
            "suggested_content:{$slug}:{$limit}",
            now()->addHours(24),
            fn () => $this->computeSuggestions($slug, $limit)
        );

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Compute suggestions by looking up the page's embedding and finding similar content.
     *
     * @return array<int, array<string, mixed>>
     */
    private function computeSuggestions(string $slug, int $limit): array
    {
        $page = ContentEmbedding::where('slug', $slug)->first();

        if (! $page) {
            return [];
        }

        $vector = $page->getEmbedding();

        if (! $vector) {
            return [];
        }

        return ContentEmbedding::findSimilar($vector, $slug, $limit)
            ->map(fn (ContentEmbedding $item): array => [
                'slug' => $item->slug,
                'type' => $item->type,
                'title' => $item->title,
                'url' => $item->url,
                'excerpt' => $item->excerpt,
            ])
            ->values()
            ->all();
    }
}
