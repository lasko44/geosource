<?php

namespace Tests\Unit\Services\GEO;

use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\ContentExtractor;
use App\Services\RAG\EmbeddingService;
use App\Services\RAG\RAGService;
use App\Services\RAG\VectorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnhancedGeoScorerTest extends TestCase
{
    use RefreshDatabase;

    private EnhancedGeoScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock dependencies
        $baseScorer = $this->createMock(GeoScorer::class);
        $baseScorer->method('forTier')->willReturnSelf();
        $baseScorer->method('score')->willReturn([
            'score' => 70.5,
            'max_score' => 94,
            'percentage' => 75,
            'grade' => 'B+',
            'pillars' => [
                'content_quality' => ['score' => 80, 'percentage' => 80],
                'structure' => ['score' => 70, 'percentage' => 70],
            ],
            'recommendations' => [],
            'summary' => [],
        ]);

        $ragService = $this->createMock(RAGService::class);

        $vectorStore = $this->createMock(VectorStore::class);
        $vectorStore->method('searchByVector')->willReturn(collect());

        $embeddingService = $this->createMock(EmbeddingService::class);
        $embeddingService->method('embed')->willReturn(array_fill(0, 1536, 0.1));

        $contentExtractor = $this->createMock(ContentExtractor::class);
        $contentExtractor->method('extractWithMetadata')->willReturn([
            'content' => 'Extracted content here',
            'title' => 'Test Title',
        ]);

        $this->scorer = new EnhancedGeoScorer(
            $baseScorer,
            $ragService,
            $vectorStore,
            $embeddingService,
            $contentExtractor
        );
    }

    // ==========================================
    // Parallel AI Analysis Tests
    // ==========================================

    public function test_parallel_ai_analysis_makes_concurrent_api_calls(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                // RAG analysis response
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'clarity_score' => 8,
                                    'structure_score' => 7,
                                    'answerability_score' => 9,
                                    'strengths' => ['Clear definitions', 'Good structure'],
                                    'weaknesses' => ['Could use more examples'],
                                    'quotable_snippets' => ['GEO is important for visibility'],
                                    'overall_assessment' => 'Good content',
                                ]),
                            ],
                        ],
                    ],
                ])
                // AI suggestions response
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    [
                                        'category' => 'structure',
                                        'priority' => 'high',
                                        'suggestion' => 'Add more headings',
                                        'reasoning' => 'Improves scanability',
                                        'example' => 'Use H2 for main sections',
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ])
                // Citation readiness response
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'score' => 75,
                                    'factors' => [
                                        'quotability' => ['score' => 80, 'reason' => 'Clear statements'],
                                        'authority' => ['score' => 70, 'reason' => 'Good expertise signals'],
                                        'uniqueness' => ['score' => 75, 'reason' => 'Original insights'],
                                        'structure' => ['score' => 80, 'reason' => 'Well organized'],
                                        'factual_density' => ['score' => 70, 'reason' => 'Good data points'],
                                    ],
                                    'summary' => 'Content has strong citation potential.',
                                ]),
                            ],
                        ],
                    ],
                ]),
        ]);

        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => true]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        // Verify all three AI analysis results are present
        $this->assertArrayHasKey('rag_analysis', $result);
        $this->assertArrayHasKey('ai_suggestions', $result);
        $this->assertArrayHasKey('citation_readiness', $result);

        Http::assertSentCount(3);
    }

    public function test_parallel_ai_analysis_returns_null_when_api_key_missing(): void
    {
        Http::fake();
        config(['rag.openai.api_key' => '']);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        $this->assertNull($result['rag_analysis']);
        $this->assertNull($result['ai_suggestions']);
        $this->assertNull($result['citation_readiness']);

        Http::assertNothingSent();
    }

    public function test_parallel_ai_analysis_returns_null_when_rag_disabled(): void
    {
        Http::fake();
        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => false]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        $this->assertNull($result['rag_analysis']);
        $this->assertNull($result['ai_suggestions']);
        $this->assertNull($result['citation_readiness']);

        Http::assertNothingSent();
    }

    public function test_citation_readiness_parsing_handles_valid_response(): void
    {
        $citationResponse = [
            'score' => 82,
            'factors' => [
                'quotability' => ['score' => 85, 'reason' => 'Has clear quotable statements'],
                'authority' => ['score' => 78, 'reason' => 'Strong expertise signals'],
                'uniqueness' => ['score' => 80, 'reason' => 'Original research data'],
                'structure' => ['score' => 88, 'reason' => 'Well-organized with clear headings'],
                'factual_density' => ['score' => 79, 'reason' => 'Good data density'],
            ],
            'summary' => 'Strong citation potential due to clear structure.',
        ];

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($citationResponse),
                        ],
                    ],
                ],
            ]),
        ]);

        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => true]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        $this->assertNotNull($result['citation_readiness']);
        $this->assertEquals(82, $result['citation_readiness']['score']);
        $this->assertArrayHasKey('factors', $result['citation_readiness']);
        $this->assertEquals(85, $result['citation_readiness']['factors']['quotability']['score']);
    }

    public function test_citation_readiness_parsing_handles_markdown_wrapped_json(): void
    {
        $citationJson = json_encode([
            'score' => 75,
            'factors' => [
                'quotability' => ['score' => 80, 'reason' => 'Good'],
                'authority' => ['score' => 70, 'reason' => 'Fair'],
                'uniqueness' => ['score' => 75, 'reason' => 'Unique'],
                'structure' => ['score' => 80, 'reason' => 'Clear'],
                'factual_density' => ['score' => 70, 'reason' => 'Dense'],
            ],
            'summary' => 'Good potential',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```json\n{$citationJson}\n```",
                        ],
                    ],
                ],
            ]),
        ]);

        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => true]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        $this->assertNotNull($result['citation_readiness']);
        $this->assertEquals(75, $result['citation_readiness']['score']);
    }

    public function test_ai_suggestions_parsing_handles_array_response(): void
    {
        $suggestions = [
            [
                'category' => 'definitions',
                'priority' => 'critical',
                'suggestion' => 'Add clear definitions in first paragraph',
                'reasoning' => 'Helps AI understand topic',
                'example' => 'GEO is the practice of...',
            ],
            [
                'category' => 'structure',
                'priority' => 'high',
                'suggestion' => 'Use more descriptive headings',
                'reasoning' => 'Improves content navigation',
            ],
        ];

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($suggestions),
                        ],
                    ],
                ],
            ]),
        ]);

        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => true]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        $this->assertNotNull($result['ai_suggestions']);
        $this->assertCount(2, $result['ai_suggestions']);
        $this->assertEquals('definitions', $result['ai_suggestions'][0]['category']);
        $this->assertEquals('critical', $result['ai_suggestions'][0]['priority']);
    }

    public function test_ai_suggestions_filters_empty_suggestions(): void
    {
        $suggestions = [
            [
                'category' => 'structure',
                'priority' => 'high',
                'suggestion' => 'Add more headings',
                'reasoning' => 'Helps navigation',
            ],
            [
                'category' => 'examples',
                'priority' => 'medium',
                'suggestion' => '', // Empty - should be filtered
                'reasoning' => 'Unclear',
            ],
        ];

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($suggestions),
                        ],
                    ],
                ],
            ]),
        ]);

        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => true]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        $this->assertCount(1, $result['ai_suggestions']);
        $this->assertEquals('Add more headings', $result['ai_suggestions'][0]['suggestion']);
    }

    public function test_parallel_ai_analysis_handles_api_failure_gracefully(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(null, 500),
        ]);

        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => true]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        // Should still have the main score even if AI analysis fails
        $this->assertArrayHasKey('score', $result);
        $this->assertEquals(75, $result['score']);

        // AI analysis should be null or have error
        $this->assertNull($result['ai_suggestions']);
        $this->assertNull($result['citation_readiness']);
    }

    public function test_analyze_returns_complete_result_structure(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['score' => 75]),
                        ],
                    ],
                ],
            ]),
        ]);

        config(['rag.openai.api_key' => 'test-key']);
        config(['rag.geo.use_rag_analysis' => true]);

        $result = $this->scorer->analyze('<html><body>Test content</body></html>', 1, 1);

        // Verify all expected keys are present
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('max_score', $result);
        $this->assertArrayHasKey('percentage', $result);
        $this->assertArrayHasKey('grade', $result);
        $this->assertArrayHasKey('pillars', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('benchmark', $result);
        $this->assertArrayHasKey('similar_content', $result);
        $this->assertArrayHasKey('scan_counts', $result);
        $this->assertArrayHasKey('has_sufficient_data', $result);
        $this->assertArrayHasKey('rag_analysis', $result);
        $this->assertArrayHasKey('ai_suggestions', $result);
        $this->assertArrayHasKey('citation_readiness', $result);
        $this->assertArrayHasKey('embedding_generated', $result);
        $this->assertArrayHasKey('scored_at', $result);
    }

    // ==========================================
    // Grade Calculation Tests
    // ==========================================

    public function test_grade_calculation_returns_correct_grades(): void
    {
        // Using reflection to test the private calculateGrade method
        $reflection = new \ReflectionClass($this->scorer);
        $method = $reflection->getMethod('calculateGrade');
        $method->setAccessible(true);

        // Test various score ranges
        $this->assertEquals('A+', $method->invoke($this->scorer, 92, 100));
        $this->assertEquals('A', $method->invoke($this->scorer, 87, 100));
        $this->assertEquals('A-', $method->invoke($this->scorer, 82, 100));
        $this->assertEquals('B+', $method->invoke($this->scorer, 77, 100));
        $this->assertEquals('B', $method->invoke($this->scorer, 72, 100));
        $this->assertEquals('B-', $method->invoke($this->scorer, 67, 100));
        $this->assertEquals('C+', $method->invoke($this->scorer, 62, 100));
        $this->assertEquals('C', $method->invoke($this->scorer, 57, 100));
        $this->assertEquals('C-', $method->invoke($this->scorer, 52, 100));
        $this->assertEquals('D+', $method->invoke($this->scorer, 47, 100));
        $this->assertEquals('D', $method->invoke($this->scorer, 42, 100));
        $this->assertEquals('F', $method->invoke($this->scorer, 35, 100));
    }

    // ==========================================
    // Benchmark Description Tests
    // ==========================================

    public function test_benchmark_description_for_positions(): void
    {
        $reflection = new \ReflectionClass($this->scorer);
        $method = $reflection->getMethod('getBenchmarkDescription');
        $method->setAccessible(true);

        $this->assertStringContainsString('Excellent', $method->invoke($this->scorer, 'leader', 12.5));
        $this->assertStringContainsString('Good performance', $method->invoke($this->scorer, 'above_average', 7.0));
        $this->assertStringContainsString('similarly', $method->invoke($this->scorer, 'average', 2.0));
        $this->assertStringContainsString('room for improvement', $method->invoke($this->scorer, 'below_average', -8.0));
        $this->assertStringContainsString('optimization needed', $method->invoke($this->scorer, 'needs_improvement', -15.0));
    }

    public function test_competitor_benchmark_description_for_positions(): void
    {
        $reflection = new \ReflectionClass($this->scorer);
        $method = $reflection->getMethod('getCompetitorBenchmarkDescription');
        $method->setAccessible(true);

        $this->assertStringContainsString('Outstanding', $method->invoke($this->scorer, 'dominant', 20.0, 100));
        $this->assertStringContainsString('Strong position', $method->invoke($this->scorer, 'ahead', 8.0, 80));
        $this->assertStringContainsString('competitive', $method->invoke($this->scorer, 'competitive', 2.0, 60));
        $this->assertStringContainsString('behind competitors', $method->invoke($this->scorer, 'behind', -10.0, 30));
        $this->assertStringContainsString('Significant gap', $method->invoke($this->scorer, 'far_behind', -20.0, 10));
    }
}
