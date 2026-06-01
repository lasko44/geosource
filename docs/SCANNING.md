# GEO Scanning System

Technical documentation for the website scanning system, including competitor discovery, AI analysis, and token-based pricing.

---

## Overview

The scanning system analyzes websites for Generative Engine Optimization (GEO) readiness, scoring them across multiple pillars. Scans can be triggered manually, via bulk uploads, or automatically through scheduled scans.

---

## Scan Tiers

| Tier | Pillars | Token Cost | Cooldown |
|------|---------|------------|----------|
| Basic | 5 | FREE | 5 minutes |
| Pro | 8 | 5 tokens | 1 minute |
| Full | 12 | 10 tokens | 1 minute |

Token costs are configured in `config/tokens.php` under `costs.scan_pro` and `costs.scan_full`.

---

## Scan Flow

### 1. Request Validation

**File:** `app/Http/Requests/Scans/StoreScanRequest.php`

Validates:
- URL format
- Team context (personal vs team scan)
- Cooldown status (prevents rapid rescans of same URL)
- Token balance for tier/competitor options
- Competitor limit checks

### 2. Scan Execution

**File:** `app/Services/ScanService.php`

```
executeScan(User, url, tier, team, request, isCompetitor, autoFindCompetitors, competitorScanTier)
```

Steps:
1. Lock user row for transaction safety
2. Calculate required tokens (tier + auto-find if enabled)
3. Validate quota limits
4. Deduct tokens using `TokenService::spendAmount()` for auto-find competitors
5. Create `Scan` record with status `pending`
6. Dispatch `ScanWebsiteJob`

### 3. Job Processing

**File:** `app/Jobs/ScanWebsiteJob.php`

1. Fetch webpage content (with fallback to headless browser)
2. Extract metadata (title, description, headings)
3. Run GEO scoring across enabled pillars
4. Run parallel AI analysis (RAG, suggestions, citation readiness)
5. Store results in `scans.results` JSON column
6. Calculate overall score and grade
7. If `auto_find_competitors` enabled, trigger competitor discovery

### 4. Competitor Discovery (Optional)

**File:** `app/Services/CompetitorDiscoveryService.php`

Triggered when:
- `auto_find_competitors = true`
- `requested_tier = 'full'`

---

## AI Analysis System

### Parallel API Calls

**File:** `app/Services/GEO/EnhancedGeoScorer.php`

The `runParallelAIAnalysis()` method executes three OpenAI API calls concurrently using `Http::pool()`:

1. **RAG Analysis** - Content quality assessment
2. **AI Suggestions** - Improvement recommendations
3. **Citation Readiness** - LLM citation potential score

This reduces analysis time from ~15-30 seconds (sequential) to ~5-10 seconds (parallel).

### Citation Readiness

A separate 0-100 score evaluating how likely LLMs are to cite the content:

| Factor | Description |
|--------|-------------|
| Quotability | Clear, quotable statements and definitive answers |
| Authority | Expertise signals, credentials, authoritative voice |
| Uniqueness | Original insights, unique data, novel perspectives |
| Structure | Well-organized, easy to extract information |
| Factual Density | Rich in verifiable facts and data points |

**Response format:**
```json
{
  "score": 75,
  "factors": {
    "quotability": {"score": 80, "reason": "Clear definitions provided"},
    "authority": {"score": 70, "reason": "Industry expertise demonstrated"},
    "uniqueness": {"score": 75, "reason": "Original research data"},
    "structure": {"score": 80, "reason": "Well-organized with headings"},
    "factual_density": {"score": 70, "reason": "Multiple statistics cited"}
  },
  "summary": "Content has strong citation potential due to clear structure and original data."
}
```

---

## Competitor Discovery System

### Token Pricing

The auto-find competitors feature has a two-part pricing structure:

| Component | Tokens | Refundable |
|-----------|--------|------------|
| AI Discovery Fee | 3 | No |
| Per-scan cost | (tier cost) × 5 | Yes (for unused slots) |

**Total cost formula:**
```
total = AI_DISCOVERY_FEE + (scan_tier_cost × MAX_COMPETITORS)
```

**Examples:**
- Basic tier: 3 + (0 × 5) = **3 tokens**
- Pro tier: 3 + (5 × 5) = **28 tokens**
- Full tier: 3 + (10 × 5) = **53 tokens**

### Discovery Process

**File:** `app/Services/CompetitorDiscoveryService.php`

1. **Status Update:** Parent scan marked as `competitor_discovery_status = 'discovering'`

2. **AI Analysis:** OpenAI API analyzes the scanned page to identify up to 5 competitors:
   - Uses content summary, pillars, and topics from parent scan
   - Filters out same domain, placeholder URLs, and invalid formats
   - Returns validated competitor URLs

3. **Competitor Scan Creation:**
   - Each competitor URL creates a new `Scan` with:
     - `is_competitor = true`
     - `parent_scan_id` = parent scan ID
     - `requested_tier` = selected `competitor_scan_tier`
     - `tokens_charged = false` (tokens already charged on parent)
   - Jobs dispatched with 3-second stagger to prevent rate limiting

4. **Refund Unused Slots:**
   - If fewer than 5 competitors found/created, refund scan tokens
   - AI discovery fee (3 tokens) is NOT refunded
   - Refund amount: `(5 - competitors_created) × tier_cost`

5. **Benchmark Calculation:**
   - When all competitor scans complete, `calculateCompetitorBenchmark()` is called
   - Parent scan's `results.competitor_benchmark` is updated with:
     - Position (dominant, ahead, competitive, behind, far_behind)
     - Percentile ranking
     - Average competitor score
     - Score difference

### Status Flow

```
pending → discovering → scanning → completed
                    ↘ failed
```

- `discovering`: AI is finding competitors
- `scanning`: Competitor scans in progress
- `completed`: All competitor scans finished (benchmark calculated)
- `failed`: AI discovery or all scans failed

### Competitor Benchmark

**File:** `app/Jobs/ScanWebsiteJob.php` - `calculateCompetitorBenchmark()`

When all competitor scans complete, the parent scan receives a benchmark:

```php
[
    'position' => 'ahead',           // dominant|ahead|competitive|behind|far_behind
    'percentile' => 80,              // % of competitors you outperform
    'avg_competitor_score' => 72.5,  // Average score of all competitors
    'score_difference' => 12.3,      // Your score - average
    'competitors_analyzed' => 5,
    'comparison' => "Strong position. You're 12.3 points ahead..."
]
```

---

## Database Schema

### scans table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | string | Public identifier |
| user_id | bigint | Owner |
| team_id | bigint | Team context (nullable) |
| url | string | Scanned URL |
| title | string | Page title |
| status | enum | pending, processing, completed, failed, cancelled |
| requested_tier | string | basic, pro, full |
| actual_tier | string | Tier used for scoring |
| score | decimal | Overall GEO score (0-170 depending on tier) |
| grade | string | A+, A, B+, B, C, D, F |
| results | json | Full pillar scores and analysis |
| is_competitor | boolean | Competitor scan flag |
| parent_scan_id | bigint | Parent scan for competitors |
| auto_find_competitors | boolean | AI discovery enabled |
| competitor_scan_tier | string | Tier for discovered competitors |
| competitor_discovery_status | string | Status of discovery process |
| competitors_found | int | Number of competitors created |
| tokens_charged | boolean | Whether tokens were deducted |
| tokens_amount | int | Tokens charged |
| tokens_refunded | int | Tokens refunded for unused slots |

### results JSON structure

```json
{
  "score": 85.5,
  "max_score": 94,
  "percentage": 85.5,
  "grade": "A-",
  "pillars": {...},
  "recommendations": {...},
  "summary": {...},
  "benchmark": {...},
  "competitor_benchmark": {...},
  "ai_suggestions": [...],
  "citation_readiness": {...},
  "rag_analysis": {...},
  "scored_at": "2024-01-15T10:30:00Z"
}
```

---

## Key Service Methods

### ScanService

```php
// Get token cost for a tier
getTokenCost(string $tier): int

// Get total cost for competitor discovery (fee + scans)
getCompetitorScanTokenCost(string $competitorTier): int

// Calculate refundable portion for unused competitor slots
getRefundableCompetitorTokens(string $competitorTier, int $competitorsCreated): int

// Execute a scan with all options
executeScan(User $user, string $url, string $tier, ?Team $team,
            Request $request, bool $isCompetitor = false,
            bool $autoFindCompetitors = false, ?string $competitorScanTier = null): Scan
```

### TokenService

```php
// Spend tokens for a feature (looks up cost in config)
spend(User $user, string $feature, array $metadata = []): ?TokenTransaction

// Spend a specific amount (for dynamic pricing like auto-find competitors)
spendAmount(User $user, int $amount, string $description, array $metadata = []): ?TokenTransaction

// Refund tokens
refund(User $user, int $amount, string $reason, array $metadata = []): TokenTransaction
```

### CompetitorDiscoveryService

```php
// Main entry point - discovers and scans competitors
discoverAndScanCompetitors(Scan $scan): array

// Uses OpenAI to find competitor URLs
private discoverCompetitors(string $url, string $title,
                            string $content, string $summary, array $pillars): array

// Creates competitor scan records and dispatches jobs
private createCompetitorScans(Scan $parentScan, array $competitorUrls): array

// Refunds tokens for unused competitor slots
private refundUnusedCompetitorTokens(Scan $scan, int $competitorsCreated): void
```

### EnhancedGeoScorer

```php
// Full analysis with parallel AI calls
analyze(string $content, ?int $teamId, ?int $userId, array $options, ?callable $onProgress): array

// Run RAG analysis, AI suggestions, and citation readiness in parallel
private runParallelAIAnalysis(string $content, array $baseScore, ?int $teamId, ?int $userId): array
```

---

## Constants

**ScanService:**
```php
public const MAX_COMPETITORS = 5;      // Max competitors to discover
public const AI_DISCOVERY_FEE = 3;     // Base fee for AI discovery
```

**CompetitorDiscoveryService:**
```php
private const MAX_COMPETITORS = 5;     // Matches ScanService
```

---

## Configuration

### tokens.php

```php
'costs' => [
    'scan_pro' => 5,
    'scan_full' => 10,
]
```

Note: Auto-find competitors uses `TokenService::spendAmount()` with dynamically calculated costs, not a config lookup.

### rag.php

```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
]
```

---

## Frontend Integration

### Scan Forms

Both `Dashboard.vue` and `Scans/Index.vue` include:
- Tier selection grid (Basic/Pro/Full)
- Competitor checkbox for manual competitor marking
- Auto-find competitors option (Full tier only)
- Competitor scan tier selection (when auto-find enabled)
- Token cost breakdown with refund information

### Token Display Formula

```javascript
const AI_DISCOVERY_FEE = 3;
const MAX_COMPETITORS = 5;

// Refundable scan costs
const competitorScanCosts = tierTokens * MAX_COMPETITORS;

// Total (fee + scans)
const competitorTokens = AI_DISCOVERY_FEE + competitorScanCosts;
```

### Scan Results Display

**File:** `resources/js/pages/Scans/Show.vue`

Displays:
- Overall GEO score and grade
- Pillar breakdown with scores
- AI suggestions with priority badges
- Citation Readiness card with factor breakdown
- Competitor benchmark (when competitors exist)
- AI-Discovered Competitors list (for parent scans)

---

## Error Handling

### UTF-8 Sanitization

**File:** `app/Jobs/ScanWebsiteJob.php`

Websites may contain malformed UTF-8 characters that cause JSON encoding failures. The `sanitizeUtf8()` method is applied to:
- Raw HTML after fetching
- Page title extraction
- Final results before database save

### HTTP Fallbacks

The scan job handles various HTTP errors:
- **Connection errors**: Falls back to headless browser (Browsershot)
- **cURL errors**: Falls back to headless browser
- **HTTP 409 (Conflict)**: Falls back to headless browser
- **Brotli encoding errors**: Removed 'br' from Accept-Encoding header

### QuotaExceededException

Thrown when:
- User doesn't have enough tokens
- Monthly scan limit reached

### Token Refunds

Automatic refunds occur when:
- Fewer than 5 competitors discovered
- Competitor already exists (same URL/team)
- Competitor scan creation fails

Refunds are logged with:
- `scan_id`
- `competitors_created`
- `unused_slots`
- `refund_amount`
- `ai_discovery_fee_retained`

---

## Relationships

```
Scan (parent)
├── discoveredCompetitors: hasMany(Scan, 'parent_scan_id')
└── parentScan: belongsTo(Scan, 'parent_scan_id')
```

Query examples:
```php
// Get all competitors for a scan
$scan->discoveredCompetitors;

// Get parent scan for a competitor
$competitorScan->parentScan;

// Check if scan has competitors
$scan->discoveredCompetitors()->exists();

// Get completed competitors with scores
$scan->discoveredCompetitors()
    ->where('status', 'completed')
    ->whereNotNull('score')
    ->get();
```
