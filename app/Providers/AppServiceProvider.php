<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Observers\BlogPostObserver;
use App\Services\BlogPostGeoService;
use App\Services\GEO\EnhancedGeoScorer;
use App\Services\GEO\GeoScorer;
use App\Services\RAG\ChunkingService;
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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // GEO Services
        $this->app->singleton(GeoScorer::class, fn () => new GeoScorer);

        // RAG Services
        $this->app->singleton(EmbeddingService::class, fn () => new EmbeddingService);
        $this->app->singleton(ChunkingService::class, fn () => new ChunkingService);

        $this->app->singleton(VectorStore::class, fn ($app) => new VectorStore(
            $app->make(EmbeddingService::class),
            $app->make(ChunkingService::class),
        ));

        $this->app->singleton(RAGService::class, fn ($app) => new RAGService(
            $app->make(VectorStore::class),
            $app->make(EmbeddingService::class),
        ));

        // Enhanced GEO with RAG
        $this->app->singleton(EnhancedGeoScorer::class, fn ($app) => new EnhancedGeoScorer(
            $app->make(GeoScorer::class),
            $app->make(RAGService::class),
            $app->make(VectorStore::class),
            $app->make(EmbeddingService::class),
        ));

        // Blog Post GEO Service
        $this->app->singleton(BlogPostGeoService::class, fn () => new BlogPostGeoService);
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
