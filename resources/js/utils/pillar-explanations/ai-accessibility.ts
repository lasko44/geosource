import type { PillarDetails, PillarExplanation } from './types';

export function getAIAccessibilityExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const robots = details.robots_txt || {};
    const metaRobots = details.meta_robots || {};

    return [
        {
            label: 'robots.txt configuration',
            achieved: (breakdown.robots_txt || 0) >= 3,
            points: `${breakdown.robots_txt || 0}/5`,
            tip: (breakdown.robots_txt || 0) < 3
                ? (robots.allows_all_ai
                    ? (robots.has_sitemap ? undefined : 'Add Sitemap reference to robots.txt.')
                    : `Blocked: ${(robots.blocked_bots || []).slice(0, 3).join(', ')}. Allow AI crawlers.`)
                : undefined,
        },
        {
            label: 'Meta robots directives',
            achieved: (breakdown.meta_robots || 0) >= 1.5,
            points: `${breakdown.meta_robots || 0}/2`,
            tip: (breakdown.meta_robots || 0) < 1.5
                ? (metaRobots.noindex
                    ? 'Remove noindex directive.'
                    : (metaRobots.nosnippet ? 'Remove nosnippet directive.' : undefined))
                : undefined,
        },
        {
            label: 'AI-specific meta tags',
            achieved: (breakdown.ai_meta_tags || 0) >= 0.75,
            points: `${breakdown.ai_meta_tags || 0}/1`,
            tip: (breakdown.ai_meta_tags || 0) < 0.75
                ? 'Consider adding AI-specific meta declarations (emerging standard).'
                : undefined,
        },
    ];
}
