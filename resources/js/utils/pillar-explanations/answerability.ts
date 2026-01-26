import type { PillarDetails, PillarExplanation } from './types';

export function getAnswerabilityExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const declarative = details.declarative || {};
    const uncertainty = details.uncertainty || {};
    const confidence = details.confidence || {};
    const snippets = details.snippets || {};
    const directness = details.directness || {};

    return [
        {
            label: 'Declarative language',
            achieved: (breakdown.declarative_language || 0) >= 4,
            points: `${breakdown.declarative_language || 0}/5`,
            tip: (breakdown.declarative_language || 0) < 4
                ? `${Math.round((declarative.declarative_ratio || 0) * 100)}% declarative. Use more "X is Y" statements.`
                : undefined,
        },
        {
            label: 'Low uncertainty',
            achieved: (breakdown.low_uncertainty || 0) >= 3,
            points: `${breakdown.low_uncertainty || 0}/4`,
            tip: (breakdown.low_uncertainty || 0) < 3
                ? `${uncertainty.hedging_count || 0} hedging words. Reduce "maybe", "perhaps", "possibly".`
                : undefined,
        },
        {
            label: 'Confidence indicators',
            achieved: (breakdown.confidence_indicators || 0) >= 3,
            points: `${breakdown.confidence_indicators || 0}/4`,
            tip: (breakdown.confidence_indicators || 0) < 3
                ? `${confidence.confidence_count || 0} found. Add phrases like "is defined as", "research shows".`
                : undefined,
        },
        {
            label: 'Quotable snippets',
            achieved: (breakdown.quotable_snippets || 0) >= 3,
            points: `${breakdown.quotable_snippets || 0}/4`,
            tip: (breakdown.quotable_snippets || 0) < 3
                ? `${snippets.count || 0} snippets. Add 50-200 char self-contained statements.`
                : undefined,
        },
        {
            label: 'Directness',
            achieved: (breakdown.directness || 0) >= 2,
            points: `${breakdown.directness || 0}/3`,
            tip: (breakdown.directness || 0) < 2
                ? (!directness.starts_with_answer
                    ? 'Start with the answer, avoid "In this article..."'
                    : 'Add more lists, steps, and bold emphasis.')
                : undefined,
        },
    ];
}
