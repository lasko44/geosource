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
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
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
    ends_at: string | null;
    trial_ends_at: string | null;
    on_grace_period: boolean;
    cancelled: boolean;
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
    team_id: number | null;
    url: string;
    title: string | null;
    score: number;
    grade: string;
    results: ScanResults;
    created_at: string;
    updated_at: string;
}

export interface ScanResults {
    score: number;
    max_score: number;
    percentage: number;
    grade: string;
    pillars: Record<string, PillarResult>;
    recommendations: Record<string, Recommendation>;
    summary: ScanSummary;
    scored_at: string;
}

export interface PillarResult {
    name: string;
    score: number;
    max_score: number;
    percentage: number;
    details: Record<string, unknown>;
    breakdown?: Record<string, number>;
}

export interface Recommendation {
    pillar: string;
    current_score: string;
    priority: 'high' | 'medium' | 'low';
    actions: string[];
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
