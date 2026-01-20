<?php

namespace App\Http\Controllers;

use App\Jobs\ScanWebsiteJob;
use App\Models\Scan;
use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\VectorStore;
use App\Services\SubscriptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScanController extends Controller
{
    public function __construct(
        private GeoScorer $geoScorer,
        private EnhancedGeoScorer $enhancedGeoScorer,
        private VectorStore $vectorStore,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Display the dashboard with recent scans.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Apply history limit based on plan
        $historyDays = $user->getLimit('history_days');
        $scanQuery = Scan::where('user_id', $user->id);

        if ($historyDays !== -1 && $historyDays !== null) {
            $scanQuery->where('created_at', '>=', now()->subDays($historyDays));
        }

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

        // Get usage summary for the subscription widget
        $usage = $user->getUsageSummary();

        // Get teams data for Agency users
        $teams = null;
        if ($this->subscriptionService->isAgencyTier($user) || $user->is_admin) {
            $teams = $user->allTeams()->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'is_owner' => $team->owner_id === $user->id,
                'members_count' => $team->members()->count(),
                'role' => $team->getUserRole($user),
            ])->values();
        }

        return Inertia::render('Dashboard', [
            'recentScans' => $recentScans,
            'stats' => $stats,
            'usage' => $usage,
            'showUpgradePrompt' => $user->shouldShowUpgradePrompt(),
            'plans' => config('billing.plans.user'),
            'teams' => $teams,
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

        // Check if user can scan
        if (! $user->canScan()) {
            $usage = $user->getUsageSummary();

            return back()->withErrors([
                'limit' => "You've reached your monthly scan limit ({$usage['scans_limit']} scans). Please upgrade your plan to continue scanning.",
            ]);
        }

        $teamId = $user->currentTeam?->id ?? $user->ownedTeams()->first()?->id;
        $url = $request->input('url');

        // Create scan record with pending status
        $scan = Scan::create([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'url' => $url,
            'title' => parse_url($url, PHP_URL_HOST),
            'status' => 'pending',
        ]);

        // Dispatch the scan job to run asynchronously
        ScanWebsiteJob::dispatch($scan);

        return redirect()->route('scans.show', $scan);
    }

    /**
     * Get scan status for polling.
     */
    public function status(Scan $scan)
    {
        $this->authorize('view', $scan);

        return response()->json([
            'status' => $scan->status,
            'error_message' => $scan->error_message,
            'score' => $scan->score,
            'grade' => $scan->grade,
        ]);
    }

    /**
     * Display scan results.
     */
    public function show(Scan $scan)
    {
        $this->authorize('view', $scan);

        $user = auth()->user();
        $scanData = $scan->toArray();

        // Filter recommendations for free tier users
        if ($user->isFreeTier()) {
            $recommendationsLimit = $user->getLimit('recommendations_shown') ?? 3;

            if (isset($scanData['results']['recommendations'])) {
                $allRecommendations = $scanData['results']['recommendations'];
                $scanData['results']['recommendations'] = array_slice($allRecommendations, 0, $recommendationsLimit);
                $scanData['results']['recommendations_limited'] = true;
                $scanData['results']['recommendations_total'] = count($allRecommendations);
            }
        }

        return Inertia::render('Scans/Show', [
            'scan' => $scanData,
            'usage' => $user->getUsageSummary(),
            'canExportCsv' => $user->hasFeature('csv_export'),
            'canExportPdf' => $user->hasFeature('pdf_export'),
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

    /**
     * Export scan results as CSV.
     */
    public function exportCsv(Scan $scan): StreamedResponse
    {
        $this->authorize('view', $scan);

        $user = auth()->user();

        if (! $user->hasFeature('csv_export')) {
            abort(403, 'CSV export is not available on your current plan.');
        }

        $filename = 'geo-scan-'.($scan->title ? str()->slug($scan->title) : $scan->uuid).'.csv';

        return response()->streamDownload(function () use ($scan) {
            $handle = fopen('php://output', 'w');

            // Header section
            fputcsv($handle, ['GEO SCAN REPORT']);
            fputcsv($handle, ['Generated by GeoSource.ai']);
            fputcsv($handle, ['Report Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            // ========== SCAN INFORMATION ==========
            fputcsv($handle, ['========== SCAN INFORMATION ==========']);
            fputcsv($handle, ['URL', $scan->url]);
            fputcsv($handle, ['Page Title', $scan->title ?? 'N/A']);
            fputcsv($handle, ['Scan Date', $scan->created_at->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['Overall Score', number_format($scan->score, 1).' / '.($scan->results['max_score'] ?? 100)]);
            fputcsv($handle, ['Grade', $scan->grade]);
            fputcsv($handle, ['Percentage', number_format($scan->results['percentage'] ?? 0, 1).'%']);
            fputcsv($handle, []);

            // ========== EXECUTIVE SUMMARY ==========
            if (isset($scan->results['summary'])) {
                fputcsv($handle, ['========== EXECUTIVE SUMMARY ==========']);
                fputcsv($handle, ['Overall Assessment', $scan->results['summary']['overall'] ?? '']);
                fputcsv($handle, ['Focus Area', $scan->results['summary']['focus_area'] ?? '']);
                fputcsv($handle, []);

                if (! empty($scan->results['summary']['strengths'])) {
                    fputcsv($handle, ['Top Strengths']);
                    foreach ($scan->results['summary']['strengths'] as $i => $strength) {
                        fputcsv($handle, [($i + 1).'.', $strength]);
                    }
                    fputcsv($handle, []);
                }

                if (! empty($scan->results['summary']['weaknesses'])) {
                    fputcsv($handle, ['Areas Needing Improvement']);
                    foreach ($scan->results['summary']['weaknesses'] as $i => $weakness) {
                        fputcsv($handle, [($i + 1).'.', $weakness]);
                    }
                    fputcsv($handle, []);
                }
            }

            // ========== SCORE BREAKDOWN ==========
            fputcsv($handle, ['========== SCORE BREAKDOWN ==========']);
            fputcsv($handle, ['Pillar', 'Score', 'Max Score', 'Percentage', 'Status']);
            if (isset($scan->results['pillars'])) {
                foreach ($scan->results['pillars'] as $key => $pillar) {
                    $pct = $pillar['percentage'] ?? 0;
                    $status = $pct >= 80 ? 'Excellent' : ($pct >= 60 ? 'Good' : ($pct >= 40 ? 'Needs Work' : 'Critical'));
                    fputcsv($handle, [
                        $pillar['name'] ?? ucfirst(str_replace('_', ' ', $key)),
                        number_format($pillar['score'] ?? 0, 1),
                        $pillar['max_score'] ?? 0,
                        number_format($pct, 1).'%',
                        $status,
                    ]);
                }
            }
            fputcsv($handle, []);

            // ========== DETAILED PILLAR ANALYSIS ==========
            if (isset($scan->results['pillars'])) {
                foreach ($scan->results['pillars'] as $key => $pillar) {
                    $name = $pillar['name'] ?? ucfirst(str_replace('_', ' ', $key));
                    fputcsv($handle, ['========== '.$name.' (Details) ==========']);
                    fputcsv($handle, ['Score', number_format($pillar['score'] ?? 0, 1).' / '.($pillar['max_score'] ?? 0)]);
                    fputcsv($handle, []);

                    $details = $pillar['details'] ?? [];

                    // Pillar-specific details
                    switch ($key) {
                        case 'definitions':
                            $this->exportDefinitionDetails($handle, $details);
                            break;
                        case 'structure':
                            $this->exportStructureDetails($handle, $details);
                            break;
                        case 'authority':
                            $this->exportAuthorityDetails($handle, $details);
                            break;
                        case 'machine_readable':
                            $this->exportMachineReadableDetails($handle, $details);
                            break;
                        case 'answerability':
                            $this->exportAnswerabilityDetails($handle, $details);
                            break;
                    }

                    // Score breakdown
                    if (isset($details['breakdown'])) {
                        fputcsv($handle, ['Score Components']);
                        foreach ($details['breakdown'] as $component => $score) {
                            fputcsv($handle, ['  '.ucfirst(str_replace('_', ' ', $component)), number_format($score, 1).' pts']);
                        }
                    }
                    fputcsv($handle, []);
                }
            }

            // ========== RECOMMENDATIONS ==========
            if (isset($scan->results['recommendations']) && ! empty($scan->results['recommendations'])) {
                fputcsv($handle, ['========== RECOMMENDATIONS ==========']);
                foreach ($scan->results['recommendations'] as $rec) {
                    fputcsv($handle, []);
                    $priority = strtoupper($rec['priority'] ?? 'medium');
                    fputcsv($handle, ["[{$priority} PRIORITY] ".($rec['pillar'] ?? 'General')]);
                    fputcsv($handle, ['Current Score', $rec['current_score'] ?? 'N/A']);
                    if (! empty($rec['actions'])) {
                        fputcsv($handle, ['Actions:']);
                        foreach ($rec['actions'] as $i => $action) {
                            fputcsv($handle, ['  '.($i + 1).'.', $action]);
                        }
                    }
                }
                fputcsv($handle, []);
            }

            // ========== TECHNICAL DATA ==========
            fputcsv($handle, ['========== TECHNICAL DATA ==========']);
            fputcsv($handle, ['Scan UUID', $scan->uuid]);
            fputcsv($handle, ['Scored At', $scan->results['scored_at'] ?? 'N/A']);
            fputcsv($handle, []);
            fputcsv($handle, ['--- End of Report ---']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export definition pillar details to CSV.
     */
    private function exportDefinitionDetails($handle, array $details): void
    {
        $entity = $details['entity'] ?? 'Not detected';
        if (is_array($entity)) {
            $entity = implode(', ', $entity);
        }

        fputcsv($handle, ['Metrics']);
        fputcsv($handle, ['  Entity/Topic Detected', $entity]);
        fputcsv($handle, ['  Has Early Definition', ($details['early_definition'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Entity in Definition', ($details['entity_in_definition'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Definitions Found', count($details['definitions_found'] ?? [])]);

        if (! empty($details['definitions_found'])) {
            fputcsv($handle, []);
            fputcsv($handle, ['Detected Definitions']);
            foreach (array_slice($details['definitions_found'], 0, 5) as $i => $def) {
                fputcsv($handle, ['  '.($i + 1).'.', $def['sentence'] ?? '']);
                fputcsv($handle, ['    Pattern', $def['pattern'] ?? 'N/A']);
                fputcsv($handle, ['    Position', ($def['position'] ?? 0).'%']);
            }
        }
        fputcsv($handle, []);
    }

    /**
     * Export structure pillar details to CSV.
     */
    private function exportStructureDetails($handle, array $details): void
    {
        // Headings - could be counts or arrays of texts
        $headings = $details['headings'] ?? [];
        $h1Count = is_array($headings['h1'] ?? 0) ? count($headings['h1']) : ($headings['h1'] ?? 0);
        $h2Count = is_array($headings['h2'] ?? 0) ? count($headings['h2']) : ($headings['h2'] ?? 0);
        $h3Count = is_array($headings['h3'] ?? 0) ? count($headings['h3']) : ($headings['h3'] ?? 0);
        $h4Count = is_array($headings['h4'] ?? 0) ? count($headings['h4']) : ($headings['h4'] ?? 0);
        $h5Count = is_array($headings['h5'] ?? 0) ? count($headings['h5']) : ($headings['h5'] ?? 0);
        $h6Count = is_array($headings['h6'] ?? 0) ? count($headings['h6']) : ($headings['h6'] ?? 0);

        fputcsv($handle, ['Heading Structure']);
        fputcsv($handle, ['  H1 Tags', $h1Count]);
        fputcsv($handle, ['  H2 Tags', $h2Count]);
        fputcsv($handle, ['  H3 Tags', $h3Count]);
        fputcsv($handle, ['  H4-H6 Tags', $h4Count + $h5Count + $h6Count]);

        // Lists
        $lists = $details['lists'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['List Usage']);
        fputcsv($handle, ['  Unordered Lists', $lists['unordered'] ?? 0]);
        fputcsv($handle, ['  Ordered Lists', $lists['ordered'] ?? 0]);
        fputcsv($handle, ['  Total List Items', $lists['total_items'] ?? 0]);

        // Sections
        $sections = $details['sections'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Content Structure']);
        fputcsv($handle, ['  Semantic Sections', $sections['semantic_sections'] ?? 0]);
        fputcsv($handle, ['  Paragraphs', $sections['paragraphs'] ?? 0]);
        fputcsv($handle, ['  Content Density', number_format($sections['content_density'] ?? 0, 2).' paragraphs/section']);

        // Hierarchy
        $hierarchy = $details['hierarchy'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Hierarchy Quality']);
        fputcsv($handle, ['  Properly Nested', ($hierarchy['properly_nested'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Single H1', ($hierarchy['single_h1'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Heading Levels Used', $hierarchy['levels_used'] ?? 0]);
        if (! empty($hierarchy['violations'])) {
            fputcsv($handle, ['  Violations', implode('; ', $hierarchy['violations'])]);
        }
        fputcsv($handle, []);
    }

    /**
     * Export authority pillar details to CSV.
     */
    private function exportAuthorityDetails($handle, array $details): void
    {
        // Topic Coherence
        $coherence = $details['topic_coherence'] ?? [];
        fputcsv($handle, ['Topic Coherence']);
        fputcsv($handle, ['  Word Count', $coherence['word_count'] ?? 0]);
        fputcsv($handle, ['  Unique Word Ratio', number_format(($coherence['unique_ratio'] ?? 0) * 100, 1).'%']);
        fputcsv($handle, ['  Coherence Score', number_format($coherence['coherence_ratio'] ?? 0, 3)]);
        if (! empty($coherence['top_terms'])) {
            $topTerms = array_slice($coherence['top_terms'], 0, 5);
            $termList = array_map(fn ($t) => $t['term'].' ('.$t['count'].')', $topTerms);
            fputcsv($handle, ['  Top Terms', implode(', ', $termList)]);
        }

        // Keyword Density
        $keyword = $details['keyword_density'] ?? [];
        $primaryKeyword = $keyword['primary_keyword'] ?? 'Not detected';
        if (is_array($primaryKeyword)) {
            $primaryKeyword = implode(', ', $primaryKeyword);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Keyword Analysis']);
        fputcsv($handle, ['  Primary Keyword', $primaryKeyword]);
        fputcsv($handle, ['  Occurrences', $keyword['occurrences'] ?? 0]);
        fputcsv($handle, ['  Density', number_format($keyword['density'] ?? 0, 2).'%']);
        fputcsv($handle, ['  Distribution', $keyword['distribution_label'] ?? 'N/A']);

        // Topic Depth
        $depth = $details['topic_depth'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Content Depth']);
        fputcsv($handle, ['  Sentence Count', $depth['sentence_count'] ?? 0]);
        fputcsv($handle, ['  Avg Sentence Length', number_format($depth['avg_sentence_length'] ?? 0, 1).' words']);
        fputcsv($handle, ['  Depth Level', $depth['depth_level'] ?? 'N/A']);
        fputcsv($handle, ['  Depth Indicators', $depth['total_indicators'] ?? 0]);

        // Links
        $links = $details['internal_links'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Link Profile']);
        fputcsv($handle, ['  Internal Links', $links['internal_count'] ?? 0]);
        fputcsv($handle, ['  External Links', $links['external_count'] ?? 0]);
        fputcsv($handle, []);
    }

    /**
     * Export machine-readable pillar details to CSV.
     */
    private function exportMachineReadableDetails($handle, array $details): void
    {
        // Schema.org
        $schema = $details['schema'] ?? [];
        fputcsv($handle, ['Schema.org Structured Data']);
        fputcsv($handle, ['  Has JSON-LD', ($schema['has_json_ld'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Schema Types Found', $schema['found_count'] ?? 0]);
        if (! empty($schema['schema_types'])) {
            fputcsv($handle, ['  Types', implode(', ', $schema['schema_types'])]);
        }
        fputcsv($handle, ['  Has Valuable Schema', ($schema['has_valuable_schema'] ?? false) ? 'Yes' : 'No']);

        // Semantic HTML
        $semantic = $details['semantic_html'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Semantic HTML']);
        fputcsv($handle, ['  Total Semantic Elements', $semantic['total_elements'] ?? 0]);
        fputcsv($handle, ['  Unique Element Types', $semantic['unique_elements'] ?? 0]);
        if (isset($semantic['image_alt_coverage'])) {
            fputcsv($handle, ['  Images with Alt Text', ($semantic['image_alt_coverage'] ?? 0).'%']);
        }

        // FAQ
        $faq = $details['faq'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['FAQ Analysis']);
        fputcsv($handle, ['  Has FAQ Schema', ($faq['has_faq_schema'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Has FAQ Section', ($faq['has_faq_section'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Questions Found', $faq['question_count'] ?? 0]);

        // Meta Tags
        $meta = $details['meta'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Meta Tags']);
        fputcsv($handle, ['  Has Title', ($meta['has_title'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Has Description', ($meta['has_description'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Has Canonical', ($meta['has_canonical'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Has Open Graph', ($meta['has_og'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Has Twitter Cards', ($meta['has_twitter'] ?? false) ? 'Yes' : 'No']);

        // llms.txt
        $llmsTxt = $details['llms_txt'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['llms.txt (AI Crawler File)']);
        fputcsv($handle, ['  File Exists', ($llmsTxt['exists'] ?? false) ? 'Yes' : 'No']);
        if ($llmsTxt['exists'] ?? false) {
            fputcsv($handle, ['  URL', $llmsTxt['url'] ?? 'N/A']);
            fputcsv($handle, ['  Content Length', ($llmsTxt['content_length'] ?? 0).' bytes']);
            fputcsv($handle, ['  Has Description', ($llmsTxt['has_description'] ?? false) ? 'Yes' : 'No']);
            fputcsv($handle, ['  Has Page Listings', ($llmsTxt['has_pages'] ?? false) ? 'Yes' : 'No']);
            fputcsv($handle, ['  Has Sitemap Reference', ($llmsTxt['has_sitemap_reference'] ?? false) ? 'Yes' : 'No']);
            fputcsv($handle, ['  Has Contact Info', ($llmsTxt['has_contact_info'] ?? false) ? 'Yes' : 'No']);
            fputcsv($handle, ['  Quality Score', ($llmsTxt['quality_score'] ?? 0).'%']);
        } else {
            fputcsv($handle, ['  Status', $llmsTxt['error'] ?? 'Not found']);
        }
        fputcsv($handle, []);
    }

    /**
     * Export answerability pillar details to CSV.
     */
    private function exportAnswerabilityDetails($handle, array $details): void
    {
        // Declarative Language
        $declarative = $details['declarative'] ?? [];
        fputcsv($handle, ['Declarative Language']);
        fputcsv($handle, ['  Total Sentences', $declarative['total_sentences'] ?? 0]);
        fputcsv($handle, ['  Declarative Sentences', $declarative['declarative_count'] ?? 0]);
        fputcsv($handle, ['  Declarative Ratio', number_format(($declarative['ratio'] ?? 0) * 100, 1).'%']);

        // Uncertainty
        $uncertainty = $details['uncertainty'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Uncertainty Analysis']);
        fputcsv($handle, ['  Hedging Words Found', $uncertainty['hedging_count'] ?? 0]);
        fputcsv($handle, ['  Hedging Density', number_format($uncertainty['hedging_density'] ?? 0, 2).'%']);
        fputcsv($handle, ['  Uncertainty Level', $uncertainty['uncertainty_level'] ?? 'N/A']);

        // Confidence
        $confidence = $details['confidence'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Confidence Indicators']);
        fputcsv($handle, ['  Confidence Phrases', $confidence['confidence_count'] ?? 0]);
        fputcsv($handle, ['  Confidence Level', $confidence['confidence_level'] ?? 'N/A']);

        // Snippets
        $snippets = $details['snippets'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Quotable Snippets']);
        fputcsv($handle, ['  Snippet Candidates', $snippets['count'] ?? 0]);
        fputcsv($handle, ['  Featured Snippet Ready', ($snippets['has_featured_snippet_candidates'] ?? false) ? 'Yes' : 'No']);

        // Directness
        $directness = $details['directness'] ?? [];
        fputcsv($handle, []);
        fputcsv($handle, ['Content Directness']);
        fputcsv($handle, ['  Starts with Answer', ($directness['starts_with_answer'] ?? false) ? 'Yes' : 'No']);
        fputcsv($handle, ['  Direct Elements', $directness['total_direct_elements'] ?? 0]);
        fputcsv($handle, ['  Directness Level', $directness['directness_level'] ?? 'N/A']);
        fputcsv($handle, []);
    }

    /**
     * Export scan results as PDF.
     */
    public function exportPdf(Scan $scan)
    {
        $this->authorize('view', $scan);

        $user = auth()->user();

        if (! $user->hasFeature('pdf_export')) {
            abort(403, 'PDF export is not available on your current plan.');
        }

        $filename = 'geo-scan-'.($scan->title ? str()->slug($scan->title) : $scan->uuid).'.pdf';

        $pdf = Pdf::loadView('exports.scan-pdf', [
            'scan' => $scan,
            'pillars' => $scan->results['pillars'] ?? [],
            'recommendations' => $scan->results['recommendations'] ?? [],
            'summary' => $scan->results['summary'] ?? [],
        ]);

        return $pdf->download($filename);
    }
}
