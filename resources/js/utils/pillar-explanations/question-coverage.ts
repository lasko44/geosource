import type { PillarDetails, PillarExplanation } from './types';

export function getQuestionCoverageExplanations(
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const questions = details.questions || {};
    const answersData = details.answers || {};
    const qaPatterns = details.qa_patterns || {};
    const anticipation = details.anticipation || {};

    return [
        {
            label: 'Question presence',
            achieved: (breakdown.questions || 0) >= 2,
            points: `${breakdown.questions || 0}/3`,
            tip: (breakdown.questions || 0) < 2
                ? `${questions.heading_questions?.length || 0} question headings. Add "What is...", "How to..." headings.`
                : undefined,
        },
        {
            label: 'Answer quality',
            achieved: (breakdown.answers || 0) >= 2,
            points: `${breakdown.answers || 0}/3`,
            tip: (breakdown.answers || 0) < 2
                ? `${answersData.total_answers || 0} answers found. Add direct answers after question headings.`
                : undefined,
        },
        {
            label: 'Q&A patterns',
            achieved: (breakdown.qa_patterns || 0) >= 1.5,
            points: `${breakdown.qa_patterns || 0}/2`,
            tip: (breakdown.qa_patterns || 0) < 1.5
                ? (qaPatterns.has_faq_section
                    ? (qaPatterns.has_qa_schema ? undefined : 'Add FAQPage schema.')
                    : 'Add FAQ section with question headings.')
                : undefined,
        },
        {
            label: 'Question anticipation',
            achieved: (breakdown.anticipation || 0) >= 1.5,
            points: `${breakdown.anticipation || 0}/2`,
            tip: (breakdown.anticipation || 0) < 1.5
                ? `Coverage: ${anticipation.coverage_score || 0}%. Cover what/how/why/when questions.`
                : undefined,
        },
    ];
}
