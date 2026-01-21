<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Resources/Index');
    }

    public function whatIsGeo(): Response
    {
        return Inertia::render('Resources/WhatIsGeo');
    }

    public function geoVsSeo(): Response
    {
        return Inertia::render('Resources/GeoVsSeo');
    }

    public function howAiSearchWorks(): Response
    {
        return Inertia::render('Resources/HowAiSearchWorks');
    }

    public function howLlmsCiteSources(): Response
    {
        return Inertia::render('Resources/HowLlmsCiteSources');
    }

    public function whatIsGeoScore(): Response
    {
        return Inertia::render('Resources/WhatIsGeoScore');
    }

    public function geoContentFramework(): Response
    {
        return Inertia::render('Resources/GeoContentFramework');
    }

    public function definitions(): Response
    {
        return Inertia::render('Resources/Definitions');
    }

    public function geoScoreExplained(): Response
    {
        return Inertia::render('Resources/GeoScoreExplained');
    }

    public function geoOptimizationChecklist(): Response
    {
        return Inertia::render('Resources/GeoOptimizationChecklist');
    }

    public function aiSearchVisibilityGuide(): Response
    {
        return Inertia::render('Resources/AiSearchVisibilityGuide');
    }

    public function whyLlmsTxtMatters(): Response
    {
        return Inertia::render('Resources/WhyLlmsTxtMatters');
    }

    public function whySsrMattersForGeo(): Response
    {
        return Inertia::render('Resources/WhySsrMattersForGeo');
    }

    public function eeatAndGeo(): Response
    {
        return Inertia::render('Resources/EEATAndGeo');
    }

    public function aiCitationsAndGeo(): Response
    {
        return Inertia::render('Resources/AiCitationsAndGeo');
    }

    public function aiAccessibilityForGeo(): Response
    {
        return Inertia::render('Resources/AiAccessibilityForGeo');
    }

    public function contentFreshnessForGeo(): Response
    {
        return Inertia::render('Resources/ContentFreshnessForGeo');
    }

    public function readabilityAndGeo(): Response
    {
        return Inertia::render('Resources/ReadabilityAndGeo');
    }

    public function questionCoverageForGeo(): Response
    {
        return Inertia::render('Resources/QuestionCoverageForGeo');
    }

    public function multimediaAndGeo(): Response
    {
        return Inertia::render('Resources/MultimediaAndGeo');
    }
}
