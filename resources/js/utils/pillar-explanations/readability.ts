import type { PillarDetails, PillarExplanation } from './types';

export function getReadabilityExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const fk = details.flesch_kincaid || {};
    const sentenceAnalysis = details.sentence_analysis || {};
    const paragraphAnalysis = details.paragraph_analysis || {};
    const wordAnalysis = details.word_analysis || {};

    return [
        {
            label: 'Reading ease',
            achieved: (breakdown.flesch_kincaid || 0) >= 3,
            points: `${breakdown.flesch_kincaid || 0}/4`,
            tip: (breakdown.flesch_kincaid || 0) < 3
                ? `Score: ${fk.reading_ease || 0}, Level: ${fk.reading_level || 'unknown'}. Aim for 60-80 (8th-9th grade).`
                : undefined,
        },
        {
            label: 'Sentence structure',
            achieved: (breakdown.sentence_structure || 0) >= 2,
            points: `${breakdown.sentence_structure || 0}/3`,
            tip: (breakdown.sentence_structure || 0) < 2
                ? `Avg: ${sentenceAnalysis.avg_length || 0} words. Aim for 15-20 with variety.`
                : undefined,
        },
        {
            label: 'Paragraph structure',
            achieved: (breakdown.paragraph_structure || 0) >= 1.5,
            points: `${breakdown.paragraph_structure || 0}/2`,
            tip: (breakdown.paragraph_structure || 0) < 1.5
                ? `Avg: ${paragraphAnalysis.avg_length || 0} words. Aim for 50-100.`
                : undefined,
        },
        {
            label: 'Word complexity',
            achieved: (breakdown.word_complexity || 0) >= 0.75,
            points: `${breakdown.word_complexity || 0}/1`,
            tip: (breakdown.word_complexity || 0) < 0.75
                ? `${wordAnalysis.complex_ratio || 0}% complex words. Balance simplicity and expertise.`
                : undefined,
        },
    ];
}
