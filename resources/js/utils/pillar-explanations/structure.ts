import type { PillarDetails, PillarExplanation } from './types';

export function getStructureExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const headings = details.headings || {};
    const lists = details.lists || {};
    const sections = details.sections || {};
    const hierarchy = details.hierarchy || {};

    return [
        {
            label: 'Headings structure',
            achieved: (breakdown.headings || 0) >= 4,
            points: `${breakdown.headings || 0}/6`,
            tip: (breakdown.headings || 0) < 4
                ? `H1: ${headings.h1?.count || 0}, H2: ${headings.h2?.count || 0}. Need 1 H1 and multiple H2s.`
                : undefined,
        },
        {
            label: 'List usage',
            achieved: (breakdown.lists || 0) >= 3,
            points: `${breakdown.lists || 0}/5`,
            tip: (breakdown.lists || 0) < 3
                ? `${lists.total_lists || 0} lists with ${lists.total_items || 0} items. Add more lists with 6+ items.`
                : undefined,
        },
        {
            label: 'Content sections',
            achieved: (breakdown.sections || 0) >= 2,
            points: `${breakdown.sections || 0}/4`,
            tip: (breakdown.sections || 0) < 2
                ? `${sections.semantic_sections || 0} semantic sections. Use <section>, <article>, <aside> tags.`
                : undefined,
        },
        {
            label: 'Heading hierarchy',
            achieved: (breakdown.hierarchy || 0) >= 4,
            points: `${breakdown.hierarchy || 0}/5`,
            tip: (breakdown.hierarchy || 0) < 4
                ? (hierarchy.violations?.length ? `Issue: ${hierarchy.violations[0]}` : 'Ensure proper heading nesting (H1→H2→H3).')
                : undefined,
        },
    ];
}
