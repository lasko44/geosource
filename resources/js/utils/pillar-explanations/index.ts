import type { PillarDetails, PillarExplanation } from './types';

import { getDefinitionsExplanations } from './definitions';
import { getStructureExplanations } from './structure';
import { getAuthorityExplanations } from './authority';
import { getMachineReadableExplanations } from './machine-readable';
import { getAnswerabilityExplanations } from './answerability';
import { getEEATExplanations } from './eeat';
import { getCitationsExplanations } from './citations';
import { getAIAccessibilityExplanations } from './ai-accessibility';
import { getFreshnessExplanations } from './freshness';
import { getReadabilityExplanations } from './readability';
import { getQuestionCoverageExplanations } from './question-coverage';
import { getMultimediaExplanations } from './multimedia';

export type { PillarExplanation, PillarDetails } from './types';

const explanationGenerators: Record<
    string,
    (details: PillarDetails, breakdown: Record<string, number>) => PillarExplanation[]
> = {
    definitions: getDefinitionsExplanations,
    structure: getStructureExplanations,
    authority: getAuthorityExplanations,
    machine_readable: getMachineReadableExplanations,
    answerability: getAnswerabilityExplanations,
    eeat: getEEATExplanations,
    citations: getCitationsExplanations,
    ai_accessibility: getAIAccessibilityExplanations,
    freshness: getFreshnessExplanations,
    readability: getReadabilityExplanations,
    question_coverage: getQuestionCoverageExplanations,
    multimedia: getMultimediaExplanations,
};

/**
 * Get explanations for a pillar's score breakdown.
 * Each pillar has its own dedicated function for better code splitting.
 */
export function getPillarExplanations(
    pillarKey: string,
    details: PillarDetails,
    breakdown: Record<string, number>
): PillarExplanation[] {
    const generator = explanationGenerators[pillarKey];

    if (generator) {
        return generator(details, breakdown);
    }

    // Fallback for unknown pillars: show raw breakdown
    return Object.entries(breakdown).map(([key, value]) => ({
        label: key.replace(/_/g, ' '),
        achieved: (value as number) > 0,
        points: `${value}`,
    }));
}
