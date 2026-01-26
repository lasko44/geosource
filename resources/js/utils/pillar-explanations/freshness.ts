import type { PillarDetails, PillarExplanation } from './types';

export function getFreshnessExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const dates = details.dates || {};
    const temporal = details.temporal_references || {};

    return [
        {
            label: 'Date visibility',
            achieved: (breakdown.dates || 0) >= 2.5,
            points: `${breakdown.dates || 0}/4`,
            tip: (breakdown.dates || 0) < 2.5
                ? (dates.has_publish_date
                    ? (dates.has_modified_date
                        ? `Age: ${dates.age_category}. Update content.`
                        : 'Add "Last updated" date.')
                    : 'Add visible publication date.')
                : undefined,
        },
        {
            label: 'Update signals',
            achieved: (breakdown.update_signals || 0) >= 2,
            points: `${breakdown.update_signals || 0}/3`,
            tip: (breakdown.update_signals || 0) < 2
                ? 'Add "Last updated" notice, revision history, or changelog.'
                : undefined,
        },
        {
            label: 'Temporal references',
            achieved: (breakdown.temporal_references || 0) >= 1.5,
            points: `${breakdown.temporal_references || 0}/2`,
            tip: (breakdown.temporal_references || 0) < 1.5
                ? (temporal.current_year_mentioned
                    ? undefined
                    : 'Include current year references where relevant.')
                : undefined,
        },
        {
            label: 'Schema dates',
            achieved: (breakdown.schema_dates || 0) >= 0.5,
            points: `${breakdown.schema_dates || 0}/1`,
            tip: (breakdown.schema_dates || 0) < 0.5
                ? 'Add datePublished and dateModified to Schema.org.'
                : undefined,
        },
    ];
}
