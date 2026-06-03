import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    target?: '_blank' | '_self';
}

export interface TeamBranding {
    enabled: boolean;
    teamName: string;
    companyName: string;
    logoUrl: string | null;
    primaryColor: string;
    secondaryColor: string;
    contactEmail: string | null;
    websiteUrl: string | null;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    hasTeams: boolean;
    canCreateTeams: boolean;
    hasCitationAccess: boolean;
    hasScheduledScansAccess: boolean;
    teamBranding: TeamBranding | null;
    [key: string]: unknown;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    timezone?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Team {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    owner_id?: number;
    owner?: User;
    members_count?: number;
    is_owner?: boolean;
    role?: string;
    created_at?: string;
    updated_at?: string;
}

export interface TeamMember {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'admin' | 'member';
    joined_at: string;
}

export interface Plan {
    name: string;
    description: string;
    price_id: string;
    price: number;
    currency: string;
    interval: 'month' | 'year';
    features: string[];
}

export interface Subscription {
    name: string;
    stripe_status: string;
    stripe_price: string | null;
    plan_name: string | null;
    plan_price: number | null;
    plan_currency: string;
    plan_interval: string;
    ends_at: string | null;
    trial_ends_at: string | null;
    on_grace_period: boolean;
    canceled: boolean;
    active: boolean;
}

export interface PaymentMethod {
    id?: string;
    brand: string;
    last_four: string;
    exp_month?: number;
    exp_year?: number;
    is_default?: boolean;
}

export interface Invoice {
    id: string;
    date: string;
    total: string;
    status: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Scan {
    id: number;
    uuid: string;
    user_id: number;
    team_id: number | null;
    scheduled_scan_id: number | null;
    url: string;
    title: string | null;
    score: number;
    grade: string;
    results: ScanResults;
    status?: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
    progress_step?: string | null;
    progress_percent?: number;
    error_message?: string | null;
    is_competitor?: boolean;
    auto_find_competitors?: boolean;
    competitor_discovery_status?: 'pending' | 'discovering' | 'scanning' | 'completed' | 'failed' | null;
    competitors_found?: number;
    parent_scan_id?: number | null;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
    };
}

export interface CitationReadinessFactor {
    score: number;
    reason?: string;
    label?: string;
    weight?: number;
    status?: string;
}

export interface CitationReadinessBenchmark {
    expected_citation_rate: string;
    comparison: string;
    study_basis: string;
}

export interface CitationReadiness {
    score: number;
    grade?: string;
    factors: Record<string, CitationReadinessFactor>;
    summary: string;
    benchmark?: CitationReadinessBenchmark;
}

export interface RecommendationReadinessFactor {
    label: string;
    score: number;
    weight: number;
    direction: 'direct' | 'inverse';
    contribution: number;
}

export interface RecommendationReadiness {
    score: number;
    grade?: string;
    factors: Record<string, RecommendationReadinessFactor>;
    summary: string;
    study_basis?: string;
}

export interface ContentTypeInsight {
    citation_context: string;
    avg_citation_rate: string;
    recommendation: string;
    top_industries: string;
}

export interface ContentType {
    primary_type: string;
    confidence: string;
    signals: Record<string, number>;
    insight?: ContentTypeInsight;
}

export interface QueryIntentItem {
    text: string;
    type: 'brand_named' | 'concept';
    predicted_likelihood: 'high' | 'medium' | 'low';
    rationale: string;
}

export interface QueryIntent {
    queries: QueryIntentItem[];
    primary_brand: string | null;
    summary: string;
}

export interface ScanResults {
    score: number;
    max_score: number;
    percentage: number;
    grade: string;
    pillars: Record<string, PillarResult>;
    recommendations: Record<string, Recommendation>;
    summary: ScanSummary;
    benchmark?: BenchmarkResult;
    competitor_benchmark?: CompetitorBenchmarkResult;
    similar_content?: SimilarContent[];
    competitor_content?: SimilarContent[];
    scan_counts?: ScanCounts;
    has_sufficient_data?: boolean;
    ai_suggestions?: AISuggestion[];
    citation_readiness?: CitationReadiness;
    citation_readiness_ai?: CitationReadiness;
    recommendation_readiness?: RecommendationReadiness;
    query_intent?: QueryIntent;
    content_type?: ContentType;
    rag_analysis?: Record<string, unknown>;
    scored_at: string;
}

export interface BenchmarkResult {
    position: string;
    percentile: number | null;
    avg_similar_score: number | null;
    score_difference?: number | null;
    comparison: string;
    scans_until_benchmark?: number;
}

export interface CompetitorBenchmarkResult {
    position: string;
    percentile: number | null;
    avg_competitor_score: number | null;
    score_difference: number | null;
    competitors_analyzed?: number;
    comparison: string;
}

export interface SimilarContent {
    id: number;
    title: string;
    similarity: number;
    is_competitor?: boolean;
}

export interface ScanCounts {
    total: number;
    own_content: number;
    competitors: number;
    min_required: number;
}

export interface AISuggestion {
    category?: string;
    priority?: 'critical' | 'high' | 'medium' | 'low';
    impact?: 'high' | 'medium' | 'low';
    suggestion: string;
    reasoning?: string;
    example?: string;
}

export interface PillarResult {
    name: string;
    score: number;
    max_score: number;
    percentage: number;
    details: Record<string, unknown>;
    breakdown?: Record<string, number>;
    tier?: 'free' | 'pro' | 'agency';
}

export interface RecommendationResource {
    title: string;
    url: string;
}

export interface Recommendation {
    pillar: string;
    current_score: string;
    priority: 'high' | 'medium' | 'low';
    actions: string[];
    tier?: 'free' | 'pro' | 'agency';
    resources?: RecommendationResource[];
}

export interface ScanSummary {
    overall: string;
    strengths: string[];
    weaknesses: string[];
    focus_area: string | null;
}

export interface DashboardStats {
    total_scans: number;
    avg_score: number;
    best_score: number;
    scans_this_week: number;
}

export interface UsageSummary {
    plan_key: string;
    plan_name: string;
    scans_used: number;
    scans_limit: number;
    scans_remaining: number;
    is_unlimited: boolean;
    can_scan: boolean;
    features: string[];
    limits: Record<string, number | boolean>;
}

export interface PlanLimits {
    scans_per_month: number;
    history_days: number;
    team_members: number;
    competitor_tracking: number;
    recommendations_shown?: number;
    api_access: boolean;
    white_label: boolean;
    scheduled_scans: boolean;
    pdf_export: boolean;
    bulk_scanning?: boolean;
}

export interface PlanWithLimits extends Plan {
    key?: string;
    limits: PlanLimits;
    popular?: boolean;
}
