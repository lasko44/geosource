export interface Scan {
  id: number;
  uuid: string;
  url: string;
  title: string | null;
  score: number | null;
  grade: string | null;
  status: string;
  requested_tier: string;
  created_at: string;
  completed_at: string | null;
  results?: ScanResults;
  user?: { id: number; name: string };
  discovered_competitors?: Scan[];
  parent_scan?: { uuid: string; url: string; title: string } | null;
}

export interface ScanResults {
  pillars?: Record<string, PillarResult>;
  recommendations?: Recommendation[];
  ai_suggestions?: AiSuggestion[];
  meta?: Record<string, unknown>;
}

export interface PillarResult {
  name: string;
  score: number;
  max_score: number;
  grade: string;
  details: Record<string, unknown>;
}

export interface Recommendation {
  pillar: string;
  priority: string;
  message: string;
}

export interface AiSuggestion {
  title: string;
  suggestion: string;
  priority?: string;
}

export interface ScanStatus {
  status: string;
  progress_step: string | null;
  progress_percent: number | null;
  score: number | null;
  grade: string | null;
  error_message: string | null;
}

export interface CitationQuery {
  id: number;
  uuid: string;
  query: string;
  domain: string;
  brand: string | null;
  frequency: string;
  is_active: boolean;
  scheduled_platforms: string[];
  monthly_token_budget: number | null;
  created_at: string;
  latest_check?: CitationCheck;
}

export interface CitationCheck {
  uuid: string;
  platform: string;
  status: string;
  is_cited: boolean | null;
  created_at: string;
}

export interface TokenBalance {
  current_balance: number;
  spent_this_month: number;
  transactions_this_month: number;
  total_credited: number;
  total_spent: number;
}

export interface DashboardData {
  recent_scans: Scan[];
  stats: {
    total_scans: number;
    avg_score: number;
    best_score: number;
    scans_this_week: number;
  };
  usage: Record<string, unknown>;
  current_team: { id: number; name: string; slug: string } | null;
  citation_data: Record<string, unknown> | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ApiError {
  message?: string;
  error?: string;
  errors?: Record<string, string[]>;
}
