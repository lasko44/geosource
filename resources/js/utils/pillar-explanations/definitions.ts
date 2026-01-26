import type { PillarDetails, PillarExplanation } from './types';

export function getDefinitionsExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    return [
        {
            label: 'Definition phrases',
            achieved: (breakdown.definition_phrases || 0) >= 6,
            points: `${breakdown.definition_phrases || 0}/8`,
            tip: (breakdown.definition_phrases || 0) < 6
                ? `${details.definitions_found?.length || 0} found. Add "X is...", "X refers to...", "X means..." phrases.`
                : undefined,
        },
        {
            label: 'Early definition',
            achieved: (breakdown.early_definition || 0) >= 6,
            points: `${breakdown.early_definition || 0}/6`,
            tip: (breakdown.early_definition || 0) < 6
                ? 'Move your primary definition to the first 20% of content.'
                : undefined,
        },
        {
            label: 'Entity in definition',
            achieved: (breakdown.entity_mention || 0) >= 6,
            points: `${breakdown.entity_mention || 0}/6`,
            tip: (breakdown.entity_mention || 0) < 6
                ? `Include "${details.entity || 'your main topic'}" in the definition sentence.`
                : undefined,
        },
    ];
}
