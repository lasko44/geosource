import type { PillarDetails, PillarExplanation } from './types';

export function getCitationsExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const extLinks = details.external_links || {};
    const citationsData = details.citations || {};
    const refs = details.references || {};

    return [
        {
            label: 'External links',
            achieved: (breakdown.external_links || 0) >= 3,
            points: `${breakdown.external_links || 0}/5`,
            tip: (breakdown.external_links || 0) < 3
                ? `${extLinks.authoritative_count || 0} authoritative, ${extLinks.reputable_count || 0} reputable links. Add .gov, .edu, research sources.`
                : undefined,
        },
        {
            label: 'Inline citations',
            achieved: (breakdown.citations || 0) >= 2,
            points: `${breakdown.citations || 0}/3`,
            tip: (breakdown.citations || 0) < 2
                ? `${citationsData.citation_count || 0} citations. Use "according to", "study shows", blockquotes.`
                : undefined,
        },
        {
            label: 'Statistics & data',
            achieved: (breakdown.statistics || 0) >= 1,
            points: `${breakdown.statistics || 0}/2`,
            tip: (breakdown.statistics || 0) < 1
                ? 'Include percentages, numbers with context (e.g., "5 million users").'
                : undefined,
        },
        {
            label: 'References section',
            achieved: (breakdown.references || 0) >= 1,
            points: `${breakdown.references || 0}/2`,
            tip: (breakdown.references || 0) < 1
                ? 'Add a References/Sources section with cited links.'
                : undefined,
        },
    ];
}
