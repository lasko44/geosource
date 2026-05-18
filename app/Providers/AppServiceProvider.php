<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Observers\BlogPostObserver;
use App\Services\ExperimentService;
use App\Services\RAG\ChunkingService;
use App\Services\RAG\ContentExtractor;
use App\Services\RAG\EmbeddingService;
use App\Services\RAG\RAGService;
use App\Services\RAG\VectorStore;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Fluent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Registers application services and bootstraps configuration.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Only RAG services are registered as singletons because they are truly stateless
     * and benefit from shared caching (EmbeddingService caches API responses).
     *
     * GeoScorer, EnhancedGeoScorer, and BlogPostGeoService are NOT registered:
     * - GeoScorer/EnhancedGeoScorer have mutable state via forTier() - must be fresh per request
     * - BlogPostGeoService is stateless with no dependencies - auto-resolved by Laravel
     *
     * @see CLAUDE.md ADR-013 for singleton rationale
     */
    public function register(): void
    {
        $this->app->singleton(ExperimentService::class, fn () => new ExperimentService);
        $this->app->singleton(EmbeddingService::class, fn () => new EmbeddingService);
        $this->app->singleton(ChunkingService::class, fn () => new ChunkingService);
        $this->app->singleton(ContentExtractor::class, fn () => new ContentExtractor);

        $this->app->singleton(VectorStore::class, fn ($app) => new VectorStore(
            $app->make(EmbeddingService::class),
            $app->make(ChunkingService::class),
        ));

        $this->app->singleton(RAGService::class, fn ($app) => new RAGService(
            $app->make(VectorStore::class),
            $app->make(EmbeddingService::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        $this->configureDefaults();
        $this->configureVectorSupport();

        // Register model observers
        BlogPost::observe(BlogPostObserver::class);
    }

    /**
     * Add pgvector support to Laravel's migration schema builder.
     *
     * Enables: $table->vector('embedding', 1536) in migrations.
     * Generates: "embedding vector(1536)" in PostgreSQL.
     */
    protected function configureVectorSupport(): void
    {
        Blueprint::macro('vector', function (string $column, int $dimensions = 1536) {
            return $this->addColumn('vector', $column, compact('dimensions'));
        });

        PostgresGrammar::macro('typeVector', function (Fluent $column) {
            return "vector({$column->dimensions})";
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
