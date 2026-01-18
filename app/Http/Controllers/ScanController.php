<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\VectorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ScanController extends Controller
{
    public function __construct(
        private GeoScorer $geoScorer,
        private EnhancedGeoScorer $enhancedGeoScorer,
        private VectorStore $vectorStore,
    ) {}

    /**
     * Display the dashboard with recent scans.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $scanQuery = Scan::where('user_id', $user->id);

        $recentScans = (clone $scanQuery)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $stats = [
            'total_scans' => (clone $scanQuery)->count(),
            'avg_score' => (float) ((clone $scanQuery)->avg('score') ?? 0),
            'best_score' => (float) ((clone $scanQuery)->max('score') ?? 0),
            'scans_this_week' => (clone $scanQuery)
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
        ];

        return Inertia::render('Dashboard', [
            'recentScans' => $recentScans,
            'stats' => $stats,
        ]);
    }

    /**
     * Start a new scan.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $user = $request->user();
        $teamId = $user->currentTeam?->id ?? $user->ownedTeams()->first()?->id;
        $url = $request->input('url');

        try {
            // Fetch the webpage content
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'GeoSource Scanner/1.0',
                ])
                ->get($url);

            if (! $response->successful()) {
                return back()->withErrors(['url' => 'Failed to fetch URL. Status: '.$response->status()]);
            }

            $html = $response->body();

            // Extract page title
            $title = $this->extractTitle($html) ?? parse_url($url, PHP_URL_HOST);

            // Run GEO analysis
            $useEnhanced = config('rag.geo.use_rag_analysis', false) && ! empty(config('rag.openai.api_key'));

            if ($useEnhanced && $teamId) {
                $result = $this->enhancedGeoScorer->analyze($html, $teamId, ['url' => $url]);
            } else {
                $result = $this->geoScorer->score($html, ['url' => $url]);
            }

            // Save the scan
            $scan = Scan::create([
                'user_id' => $user->id,
                'team_id' => $teamId,
                'url' => $url,
                'title' => $title,
                'score' => $result['score'],
                'grade' => $result['grade'],
                'results' => $result,
            ]);

            // Optionally store in vector database for future comparisons
            if ($teamId && config('rag.geo.use_rag_analysis', false)) {
                try {
                    $this->vectorStore->addDocument(
                        $teamId,
                        $title,
                        $html,
                        [
                            'type' => 'scanned_page',
                            'url' => $url,
                            'scan_id' => $scan->id,
                            'geo_score' => $result['score'],
                        ],
                        chunk: true
                    );
                } catch (\Exception $e) {
                    // Log but don't fail
                    logger()->warning('Failed to store scan in vector DB: '.$e->getMessage());
                }
            }

            return redirect()->route('scans.show', $scan);
        } catch (\Exception $e) {
            return back()->withErrors(['url' => 'Error scanning URL: '.$e->getMessage()]);
        }
    }

    /**
     * Display scan results.
     */
    public function show(Scan $scan)
    {
        $this->authorize('view', $scan);

        return Inertia::render('Scans/Show', [
            'scan' => $scan,
        ]);
    }

    /**
     * List all scans.
     */
    public function list(Request $request)
    {
        $user = $request->user();

        $scans = Scan::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Scans/Index', [
            'scans' => $scans,
        ]);
    }

    /**
     * Delete a scan.
     */
    public function destroy(Scan $scan)
    {
        $this->authorize('delete', $scan);

        $scan->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Scan deleted successfully.');
    }

    /**
     * Rescan a URL.
     */
    public function rescan(Scan $scan, Request $request)
    {
        $this->authorize('update', $scan);

        // Create new request with the URL
        $request->merge(['url' => $scan->url]);

        return $this->scan($request);
    }

    /**
     * Extract title from HTML.
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            return trim(strip_tags($match[1]));
        }

        return null;
    }
}
