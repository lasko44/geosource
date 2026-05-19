<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\ExperimentParticipant;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Manages A/B experiment variant assignment using Thompson Sampling (multi-armed bandit).
 */
class ExperimentService
{
    /**
     * Assign a variant to a visitor for a given experiment.
     *
     * Returns the existing assignment if one exists, otherwise samples
     * a new variant using Thompson Sampling.
     */
    public function assignVariant(string $experimentName, string $visitorId): ?string
    {
        $experiment = Experiment::running()->where('name', $experimentName)->first();

        if (! $experiment) {
            return null;
        }

        $existing = ExperimentParticipant::where('experiment_id', $experiment->id)
            ->where('visitor_id', $visitorId)
            ->first();

        if ($existing) {
            return $existing->variant;
        }

        $variant = $this->thompsonSample($experiment);

        ExperimentParticipant::create([
            'experiment_id' => $experiment->id,
            'visitor_id' => $visitorId,
            'variant' => $variant,
        ]);

        return $variant;
    }

    /**
     * Record a conversion for a visitor who registered.
     */
    public function recordConversion(string $visitorId, User $user): void
    {
        ExperimentParticipant::where('visitor_id', $visitorId)
            ->whereNull('converted_at')
            ->whereHas('experiment', fn ($q) => $q->running())
            ->update([
                'user_id' => $user->id,
                'converted_at' => now(),
            ]);
    }

    /**
     * Get per-variant statistics for an experiment.
     */
    public function getStats(Experiment $experiment): array
    {
        $stats = [];
        $variants = $experiment->variants;

        foreach ($variants as $variant) {
            $total = $experiment->participants()
                ->where('variant', $variant)
                ->count();

            $conversions = $experiment->participants()
                ->where('variant', $variant)
                ->whereNotNull('converted_at')
                ->count();

            $stats[$variant] = [
                'total' => $total,
                'conversions' => $conversions,
                'rate' => $total > 0 ? round(($conversions / $total) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Prior weights per variant. The primary variant (scan_input) gets a
     * stronger prior so it is shown ~70% of the time initially.
     * As real data accumulates, the priors become negligible.
     */
    private const VARIANT_PRIORS = [
        'scan_input' => ['alpha' => 1, 'beta' => 3],
        'citation_check' => ['alpha' => 3, 'beta' => 1],
    ];

    /**
     * Select a variant using Thompson Sampling (Beta distribution).
     *
     * Each variant's posterior is Beta(prior_alpha + successes, prior_beta + failures).
     * We sample from each posterior and pick the variant with the highest draw.
     */
    private function thompsonSample(Experiment $experiment): string
    {
        $variants = $experiment->variants;

        if (count($variants) < 2) {
            return Arr::first($variants);
        }

        $best = null;
        $bestSample = -1.0;

        foreach ($variants as $variant) {
            $successes = $experiment->participants()
                ->where('variant', $variant)
                ->whereNotNull('converted_at')
                ->count();

            $failures = $experiment->participants()
                ->where('variant', $variant)
                ->whereNull('converted_at')
                ->count();

            $prior = Arr::get(self::VARIANT_PRIORS, $variant, ['alpha' => 1, 'beta' => 1]);
            $sample = $this->betaSample($successes + $prior['alpha'], $failures + $prior['beta']);

            if ($sample > $bestSample) {
                $bestSample = $sample;
                $best = $variant;
            }
        }

        return $best;
    }

    /**
     * Sample from a Beta distribution using the Gamma distribution method.
     *
     * Beta(a, b) = Gamma(a) / (Gamma(a) + Gamma(b))
     */
    private function betaSample(int $alpha, int $beta): float
    {
        $x = $this->gammaSample($alpha);
        $y = $this->gammaSample($beta);

        if ($x + $y === 0.0) {
            return 0.5;
        }

        return $x / ($x + $y);
    }

    /**
     * Sample from a Gamma distribution using Marsaglia and Tsang's method.
     */
    private function gammaSample(int $shape): float
    {
        if ($shape < 1) {
            // For shape < 1, use the relation: Gamma(a) = Gamma(a+1) * U^(1/a)
            $u = mt_rand() / mt_getrandmax();

            return $this->gammaSample($shape + 1) * ($u ** (1.0 / $shape));
        }

        $d = $shape - 1.0 / 3.0;
        $c = 1.0 / sqrt(9.0 * $d);

        while (true) {
            // Generate standard normal using Box-Muller
            $u1 = mt_rand() / mt_getrandmax();
            $u2 = mt_rand() / mt_getrandmax();
            $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

            $v = (1.0 + $c * $z) ** 3;

            if ($v <= 0) {
                continue;
            }

            $u = mt_rand() / mt_getrandmax();

            // Squeeze test
            if ($u < 1.0 - 0.0331 * ($z * $z) * ($z * $z)) {
                return $d * $v;
            }

            if (log($u) < 0.5 * $z * $z + $d * (1.0 - $v + log($v))) {
                return $d * $v;
            }
        }
    }
}
