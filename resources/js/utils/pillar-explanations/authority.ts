import type { PillarDetails, PillarExplanation } from './types';

export function getAuthorityExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const coherence = details.topic_coherence || {};
    const keyword = details.keyword_density || {};
    const depth = details.topic_depth || {};
    const links = details.internal_links || {};
    const similarity = details.semantic_similarity || {};

    return [
        {
            label: 'Topic coherence',
            achieved: (breakdown.topic_coherence || 0) >= 4,
            points: `${breakdown.topic_coherence || 0}/6`,
            tip: (breakdown.topic_coherence || 0) < 4
                ? `Coherence ratio: ${((coherence.coherence_ratio || 0) * 100).toFixed(1)}%. Use consistent terminology throughout.`
                : undefined,
        },
        {
            label: 'Keyword optimization',
            achieved: (breakdown.keyword_density || 0) >= 3,
            points: `${breakdown.keyword_density || 0}/5`,
            tip: (breakdown.keyword_density || 0) < 3
                ? `Density: ${keyword.density_percent || 0}%. Aim for 1-3% keyword density with good distribution.`
                : undefined,
        },
        {
            label: 'Content depth',
            achieved: (breakdown.topic_depth || 0) >= 4,
            points: `${breakdown.topic_depth || 0}/6`,
            tip: (breakdown.topic_depth || 0) < 4
                ? `${depth.word_count || 0} words, ${depth.total_indicators || 0} depth indicators. Add examples, evidence, and explanations.`
                : undefined,
        },
        {
            label: 'Internal linking',
            achieved: (breakdown.internal_links || 0) >= 2.5,
            points: `${breakdown.internal_links || 0}/4`,
            tip: (breakdown.internal_links || 0) < 2.5
                ? `${links.internal_count || 0} internal links. Add more links to related content.`
                : undefined,
        },
        {
            label: 'Semantic similarity',
            achieved: (breakdown.semantic_similarity || 0) >= 2,
            points: `${breakdown.semantic_similarity || 0}/4`,
            tip: (breakdown.semantic_similarity || 0) < 2
                ? (similarity.topic_focus
                    ? `Topic focus: ${similarity.topic_focus}. Build more related content on your site.`
                    : 'Add more related content to your site to build topical authority.')
                : undefined,
        },
    ];
}
