<?php

namespace App\Facades;

use App\Services\RAG\RAGService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection retrieve(string $query, int $teamId, int $limit = 5, float $threshold = 0.5, array $filters = [])
 * @method static array retrieveContext(string $query, int $teamId, int $limit = 5, array $filters = [])
 * @method static array generate(string $query, int $teamId, array $options = [])
 * @method static array analyzeForGEO(string $content, int $teamId, array $options = [])
 * @method static array suggestImprovements(string $content, array $geoScore, int $teamId)
 * @method static array answerQuestion(string $question, int $teamId, array $options = [])
 * @method static array summarizeTopic(string $topic, int $teamId, int $documentLimit = 10)
 * @method static array findContentGaps(string $topic, int $teamId)
 *
 * @see \App\Services\RAG\RAGService
 */
class RAG extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RAGService::class;
    }
}
