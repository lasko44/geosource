import type { PillarDetails, PillarExplanation } from './types';

export function getMultimediaExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const images = details.images || {};
    const videos = details.videos || {};
    const tables = details.tables || {};
    const visuals = details.visual_elements || {};

    return [
        {
            label: 'Image optimization',
            achieved: (breakdown.images || 0) >= 2.5,
            points: `${breakdown.images || 0}/4`,
            tip: (breakdown.images || 0) < 2.5
                ? `${images.total_images || 0} images, alt quality: ${images.alt_quality || 'none'}. Add images with descriptive alt text.`
                : undefined,
        },
        {
            label: 'Video content',
            achieved: (breakdown.videos || 0) >= 1.5,
            points: `${breakdown.videos || 0}/2`,
            tip: (breakdown.videos || 0) < 1.5
                ? (videos.has_video
                    ? 'Add VideoObject schema.'
                    : 'Consider embedding relevant videos.')
                : undefined,
        },
        {
            label: 'Tables & data',
            achieved: (breakdown.tables || 0) >= 1,
            points: `${breakdown.tables || 0}/2`,
            tip: (breakdown.tables || 0) < 1
                ? (tables.has_tables
                    ? 'Add table headers and comparison tables.'
                    : 'Add tables for comparisons or data.')
                : undefined,
        },
        {
            label: 'Visual variety',
            achieved: (breakdown.visual_elements || 0) >= 1.5,
            points: `${breakdown.visual_elements || 0}/2`,
            tip: (breakdown.visual_elements || 0) < 1.5
                ? `${visuals.visual_variety || 0} types used. Add diagrams, callouts, code blocks, icons.`
                : undefined,
        },
    ];
}
