export interface PillarExplanation {
    label: string;
    achieved: boolean;
    points: string;
    tip?: string;
}

export interface PillarDetails {
    breakdown?: Record<string, number>;
    [key: string]: any;
}

export type ExplanationGenerator = (
    details: PillarDetails,
    breakdown: Record<string, number>
) => PillarExplanation[];
