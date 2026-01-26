import type { PillarDetails, PillarExplanation } from './types';

export function getEEATExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const author = details.author || {};
    const trust = details.trust_signals || {};

    return [
        {
            label: 'Author signals',
            achieved: (breakdown.author || 0) >= 3,
            points: `${breakdown.author || 0}/5`,
            tip: (breakdown.author || 0) < 3
                ? (author.has_author
                    ? 'Add author bio, image, and link to profile.'
                    : 'Add author attribution with name and bio.')
                : undefined,
        },
        {
            label: 'Trust signals',
            achieved: (breakdown.trust_signals || 0) >= 2,
            points: `${breakdown.trust_signals || 0}/4`,
            tip: (breakdown.trust_signals || 0) < 2
                ? `${trust.trust_indicators_count || 0} indicators. Add reviews, testimonials, or certifications.`
                : undefined,
        },
        {
            label: 'Contact information',
            achieved: (breakdown.contact || 0) >= 2,
            points: `${breakdown.contact || 0}/3`,
            tip: (breakdown.contact || 0) < 2
                ? 'Add contact page link, email/phone, and social links.'
                : undefined,
        },
        {
            label: 'Credentials',
            achieved: (breakdown.credentials || 0) >= 2,
            points: `${breakdown.credentials || 0}/3`,
            tip: (breakdown.credentials || 0) < 2
                ? 'Highlight expertise, years of experience, and qualifications.'
                : undefined,
        },
    ];
}
