<?php

namespace App\Facades;

use App\Services\GEO\GeoScorer;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array score(string $content, array $context = [])
 * @method static array scorePartial(string $content, array $pillars, array $context = [])
 * @method static array quickScore(string $content, array $context = [])
 * @method static array getScorers()
 *
 * @see \App\Services\GEO\GeoScorer
 */
class GEO extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GeoScorer::class;
    }
}
