import type { PillarDetails, PillarExplanation } from './types';

export function getMachineReadableExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const schema = details.schema || {};
    const faq = details.faq || {};
    const llmsTxt = details.llms_txt || {};

    return [
        {
            label: 'Schema.org structured data',
            achieved: (breakdown.schema || 0) >= 3,
            points: `${breakdown.schema || 0}/5`,
            tip: (breakdown.schema || 0) < 3
                ? (schema.found
                    ? `Found ${schema.found} schema(s). Add JSON-LD with valuable types (Article, FAQPage).`
                    : 'Add Schema.org structured data (JSON-LD recommended).')
                : undefined,
        },
        {
            label: 'Semantic HTML',
            achieved: (breakdown.semantic_html || 0) >= 2,
            points: `${breakdown.semantic_html || 0}/3`,
            tip: (breakdown.semantic_html || 0) < 2
                ? `${details.semantic_html?.unique_elements_used || 0} semantic elements, ${details.semantic_html?.images?.alt_coverage || 0}% alt coverage. Use more semantic tags and add alt text.`
                : undefined,
        },
        {
            label: 'FAQ content',
            achieved: (breakdown.faq || 0) >= 2,
            points: `${breakdown.faq || 0}/3`,
            tip: (breakdown.faq || 0) < 2
                ? (faq.has_faq_schema ? 'Has FAQ schema.' : 'Add FAQPage schema and a dedicated FAQ section.')
                : undefined,
        },
        {
            label: 'Meta tags',
            achieved: (breakdown.meta || 0) >= 1,
            points: `${breakdown.meta || 0}/2`,
            tip: (breakdown.meta || 0) < 1
                ? 'Add title, description, and Open Graph tags.'
                : undefined,
        },
        {
            label: 'llms.txt file',
            achieved: (breakdown.llms_txt || 0) >= 1,
            points: `${breakdown.llms_txt || 0}/2`,
            tip: (breakdown.llms_txt || 0) < 1
                ? (llmsTxt.exists
                    ? `Quality: ${llmsTxt.quality_score || 0}%. Improve content.`
                    : 'Add an llms.txt file to your site root.')
                : undefined,
        },
    ];
}
