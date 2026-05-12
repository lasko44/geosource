# Project Coding Standards

This document defines the architectural decisions and coding standards for this project. All code must follow these guidelines.

---

## Table of Contents

### Core Architecture
- [ADR-001: Skinny Controllers, Fat Services](#adr-001-skinny-controllers-fat-services)
- [ADR-002: Form Requests for Validation](#adr-002-form-requests-for-validation)
- [ADR-003: Policy Classes for Authorization](#adr-003-policy-classes-for-authorization)
- [ADR-004: Eloquent ORM Only](#adr-004-eloquent-orm-only)

### Security
- [ADR-005: Return 404 Instead of 403](#adr-005-return-404-instead-of-403)

### Code Quality
- [ADR-006: Type Hints and Return Types Everywhere](#adr-006-type-hints-and-return-types-everywhere)
- [ADR-007: Class Documentation](#adr-007-class-documentation)
- [ADR-008: Array Access via Arr Helper](#adr-008-array-access-via-arr-helper)

### Controller Rules
- [ADR-009: Method Injection in Controllers](#adr-009-method-injection-in-controllers)
- [ADR-010: Invokable Controllers for Single Actions](#adr-010-invokable-controllers-for-single-actions)
- [ADR-011: No Private Methods in Controllers](#adr-011-no-private-methods-in-controllers)

### Service Rules
- [ADR-012: Services Use Constructor Injection](#adr-012-services-use-constructor-injection)
- [ADR-013: Singleton Registration for Stateless Services](#adr-013-singleton-registration-for-stateless-services)

### Project Organization
- [ADR-014: Feature-Based Directory Structure](#adr-014-feature-based-directory-structure)

### Performance
- [ADR-015: Efficient Eloquent Query Methods](#adr-015-efficient-eloquent-query-methods)
- [ADR-016: Large Dataset Processing](#adr-016-large-dataset-processing)
- [ADR-017: Queue Heavy Operations](#adr-017-queue-heavy-operations)
- [ADR-018: Database Indexing Strategy](#adr-018-database-indexing-strategy)
- [ADR-019: Eager Loading Relationships](#adr-019-eager-loading-relationships)
- [ADR-020: Caching Expensive Operations](#adr-020-caching-expensive-operations)
- [ADR-021: Production Optimization Commands](#adr-021-production-optimization-commands)
- [ADR-022: Avoiding Memory Leaks in Long-Running Processes](#adr-022-avoiding-memory-leaks-in-long-running-processes)
- [ADR-023: Select Only Required Columns](#adr-023-select-only-required-columns)
- [ADR-024: Use Database Aggregates Over Collection Methods](#adr-024-use-database-aggregates-over-collection-methods)

### Reference
- [Architecture Overview](#architecture-overview)
- [Quick Reference](#quick-reference)

---

# Architectural Decision Records (ADR)

## Core Architecture

### ADR-001: Skinny Controllers, Fat Services

### Decision
Controllers contain **zero business logic**. All logic lives in service classes.


### Rationale
1. **Testability**: Services can be unit tested without HTTP layer. Controllers need feature tests.
2. **Reusability**: Services can be called from controllers, jobs, commands, or other services.
3. **Readability**: A 50-line controller is easier to understand than a 500-line one.
4. **Single Responsibility**: Each class has one reason to change.
5. **Debugging**: When something breaks, you know where to look based on the type of issue.

### Rules

**Controller responsibilities:**
- Receive HTTP request
- Call service method(s)
- Return HTTP response

**Service responsibilities:**
- Business logic
- Database operations
- External API calls
- Complex calculations

### Example
```php
// ❌ WRONG - Logic in controller
public function store(Request $request) {
    // 50+ lines of validation, authorization, business logic...
}

// ✅ CORRECT - Delegate to service
public function store(StoreScanRequest $request, ScanService $service): RedirectResponse
{
    $scan = $service->executeScan($request->user(), $request->validated());
    return redirect()->route('scans.show', $scan);
}
```

---

### ADR-002: Form Requests for Validation

### Decision
All validation logic lives in **Form Request** classes, not controllers.

### Rationale
1. **Reusability**: Same validation can be used by multiple endpoints.
2. **Separation of concerns**: Controllers don't need to know validation rules.
3. **Authorization co-location**: `authorize()` method keeps auth logic with related validation.
4. **Cleaner controllers**: No `$request->validate([...])` blocks.
5. **Custom error messages**: Centralized in one place.
6. **Data transformation**: Helper methods like `getTier()` encapsulate input transformation.

### Rules
```php
class StoreScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Scan::class);
    }

    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'tier' => 'nullable|in:basic,pro,full',
        ];
    }

    public function getTier(): string
    {
        return $this->input('tier', 'basic');
    }
}
```

---

### ADR-003: Policy Classes for Authorization

### Decision
Use **Policy classes** for all model-based authorization, not inline checks.


### Rationale
1. **DRY**: Authorization logic written once, used everywhere.
2. **Testability**: Policies are easily unit tested.
3.**Consistency**: All authorization follows the same pattern.
4.**Discoverability**: All rules for a model are in one file.

### Rules
```php
// Controller - use $this->authorize()
public function show(Scan $scan): Response
{
    $this->authorize('view', $scan);
    // ...
}

// Policy - all authorization logic here
class ScanPolicy
{
    public function view(User $user, Scan $scan): bool
    {
        return $scan->user_id === $user->id
            || $user->allTeams()->contains('id', $scan->team_id);
    }
}
```

---

### ADR-004: Eloquent ORM Only

### Decision
All database queries must use **Eloquent ORM**. Raw `DB::` statements are prohibited.

### Rationale
1. **Consistency**: One way to access the database. No mixing of patterns.
2. **Security**: Eloquent handles parameter binding automatically, reducing SQL injection risk.
3. **Maintainability**: Eloquent queries are more readable and self-documenting.
4. **Model features**: Mass assignment protection, events, observers, and accessors/mutators only work with Eloquent.
5. **Relationships**: Eager loading, lazy loading, and relationship methods require Eloquent.
6. **Testability**: Eloquent models can be easily mocked and factories used for testing.
7. **Refactoring**: Changing table structure only requires updating the model, not hunting for raw queries.

### Prohibited
```php
DB::select('...');
DB::insert('...');
DB::statement('...');
DB::table('...');
DB::raw('...');
```

### Allowed
```php
User::where('status', 'active')->get();
Scan::create(['url' => $url]);
$user->scans()->where('status', 'pending')->get();
```

### Exceptions
Raw expressions (`selectRaw`, `whereRaw`, `orderByRaw`) are allowed for database-specific features like pgvector. Must include a comment explaining why.

---

## Security

### ADR-005: Return 404 Instead of 403

### Decision
Return **404 Not Found** instead of **403 Forbidden** when authorization fails. Log the actual 403 internally.

### Context
When a user attempts to access a resource they're not authorized to view (e.g., another user's scan), we need to decide what HTTP status code to return.

### Rationale
Returning 403 Forbidden leaks information and enables **resource enumeration attacks**:
```
GET /scans/12345 → 403  (attacker learns: scan 12345 EXISTS)
GET /scans/99999 → 404  (attacker learns: scan 99999 doesn't exist)
```

By returning 404 for both cases, attackers can't distinguish "exists but forbidden" from "doesn't exist."

### Benefits
1. **Prevents enumeration**: Attackers can't probe for valid resource IDs.
2. **Security monitoring**: Logged 403s can trigger alerts for suspicious activity.
3. **Same user experience**: Legitimate users see "not found" which is accurate from their perspective.

### Implementation
```php
// bootstrap/app.php
$exceptions->render(function (AuthorizationException $e, $request) {
    Log::warning('Authorization denied (returned as 404)', [
        'user_id' => $request->user()?->id,
        'ip' => $request->ip(),
        'url' => $request->fullUrl(),
        'method' => $request->method(),
    ]);

    throw new NotFoundHttpException('Not Found');
});
```

### Monitoring
Set up alerts for patterns like:
- Multiple 403s from same IP in short time
- 403s targeting sequential resource IDs
- 403s from authenticated users on resources they shouldn't know about

---

## Code Quality

### ADR-006: Type Hints and Return Types Everywhere

### Decision
All method parameters and return types must have explicit type hints. **Every function must declare a return type.**

### Rationale
1. **Self-documenting**: The signature tells you what the method expects and returns.
2. **IDE support**: Autocomplete, refactoring, and error detection.
3. **Runtime safety**: PHP enforces types, catching bugs early.
4. **Static analysis**: Tools like PHPStan can verify correctness.
5. **Contract clarity**: Return types make the method's contract explicit.

### Rules
```php
// ✅ CORRECT
public function executeScan(User $user, string $url, ?Team $team = null): Scan
public function sendNotification(User $user): void
public function findScan(string $uuid): ?Scan

// ❌ WRONG - Missing return type
public function executeScan(User $user, string $url)
```

---

### ADR-007: Class Documentation

### Decision
Every class must have a docblock explaining its purpose.

### Rationale
1. **Discoverability**: Developers can quickly understand what a class does without reading all its code.
2. **Onboarding**: New team members understand the codebase faster.
3. **IDE integration**: Docblocks appear in hover tooltips and autocomplete.
4. **Intentional design**: Writing a description forces you to clarify the class's single responsibility.

### Rules
```php
/**
 * Handles website scan execution and lifecycle management.
 */
class ScanService
{
    // ...
}

/**
 * Exports a scan report as a PDF document.
 */
class ExportPdfController extends Controller
{
    // ...
}
```

### Guidelines
- Keep descriptions concise (1-3 sentences)
- Focus on **what** the class does, not **how**
- For controllers, describe the action it performs
- For services, describe the domain it manages

---

### ADR-008: Array Access via Arr Helper

### Decision
Always use `Illuminate\Support\Arr::get()` for array access instead of direct bracket notation.

### Rationale
1. **Null safety**: `Arr::get($data, 'key')` returns null instead of throwing an error.
2. **Default values**: `Arr::get($data, 'key', 'default')` is cleaner than `$data['key'] ?? 'default'`.
3. **Nested access**: `Arr::get($data, 'user.profile.name')` handles missing intermediate keys.
4. **Consistency**: One pattern across the codebase.

### Rules
```php
use Illuminate\Support\Arr;

// ✅ CORRECT
$name = Arr::get($response, 'data.user.name', 'Unknown');

// ❌ WRONG
$name = $response['data']['user']['name'];
$name = $data['user']['name'] ?? 'Default';
```

---

## Controller Rules

### ADR-009: Method Injection in Controllers

### Decision
Use **method injection** in controllers, not constructor injection.

### Rationale
1. **Only inject what you need**: Each method declares its own dependencies. The `index` method might not need `TokenService`, so why inject it for every action?
2. **Explicit dependencies**: Reading a method signature tells you exactly what it needs.
3. **Testing simplicity**: Mock only what the specific method uses.
4. **Controller lifecycle**: Controllers are instantiated per-request. Constructor injection offers no performance benefit and may instantiate unused services.
5. **Laravel convention**: Taylor Otwell (Laravel creator) recommends method injection for controllers.

### Rules
```php
// ✅ CORRECT - Method injection
public function show(Scan $scan, ScanService $scanService): Response
{
    return $scanService->getScanData($scan);
}

// ❌ WRONG - Constructor injection
public function __construct(private ScanService $scanService) {}
```

---

### ADR-010: Invokable Controllers for Single Actions

### Decision
Use native Laravel **invokable controllers** for single-action endpoints rather than the `lorisleiva/laravel-actions` package.

### Context
When refactoring controllers to follow single-responsibility principle, we needed a pattern for non-CRUD actions like `ExportPdf`, `ScanCancel`, `CheckCooldown`, etc.

### Rationale
1. **Separation of concerns**: Controllers handle HTTP, services handle business logic, jobs handle async work.
2. **YAGNI**: We don't have use cases requiring the same action as both HTTP endpoint and queued job.
3. **No external dependencies**: Reduces maintenance burden and potential security vulnerabilities.
4. **Team familiarity**: Standard Laravel patterns are easier for new developers to understand.

### Rules
- Resource controllers: 7 standard actions only (index, create, store, show, edit, update, destroy)
- Non-CRUD actions: Use invokable controllers with `__invoke()`
- Naming: `{Action}{Model}Controller` (e.g., `ExportPdfController`)

### Example
```php
Route::post('/scans/{scan}/cancel', ScanCancelController::class)->name('scans.cancel');

class ScanCancelController extends Controller
{
    public function __invoke(Scan $scan, ScanService $service): RedirectResponse
    {
        $service->cancel($scan);
        return redirect()->route('scans.index');
    }
}
```

---

### ADR-011: No Private Methods in Controllers

### Decision
Controllers must not contain `private` or `protected` methods.

### Context
Private controller methods indicate business logic that should live elsewhere.

### Rationale
1. **Signals misplaced logic**: If you need a helper method, it belongs in a service.
2. **Untestable**: Private methods can't be unit tested directly.
3. **Not reusable**: Other controllers can't use private methods.
4. **Controller bloat**: Private methods are a slippery slope to 500-line controllers.

### Rules
| Logic Type | Location |
|------------|----------|
| Business logic | Service |
| Data transformation | Form Request or Service |
| Authorization | Policy |
| Query building | Service or Model scope |

---

## Service Rules

### ADR-012: Services Use Constructor Injection

### Decision
While controllers use method injection, **services use constructor injection**.

### Rationale
1. **Different lifecycle**: Services are often singletons or have consistent dependencies.
2. **All methods need same dependencies**: Unlike controllers, service methods typically share dependencies.
3. **Cleaner method signatures**: Service methods focus on business parameters, not infrastructure.

### Rules
```php
class ScanService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected TokenService $tokenService,
    ) {}

    public function executeScan(User $user, string $url): Scan
    {
        // Uses $this->subscriptionService and $this->tokenService
    }
}
```

---

### ADR-013: Singleton Registration for Stateless Services

### Decision
Register only **truly stateless** services as singletons. Services with mutable state must NOT be singletons.

### Context
The application has RAG services that benefit from singleton registration, and GEO scoring services that must NOT be singletons due to mutable state.

### Rationale for Singletons
1. **Resource Efficiency**: `EmbeddingService` creates HTTP clients for OpenAI API calls. Multiple instances waste memory and duplicate connection pools.
2. **Cache Sharing**: `EmbeddingService` caches embeddings to avoid redundant API calls. A singleton ensures all code paths share the same cache.
3. **Shared Dependencies**: Without singletons, `EmbeddingService` would be instantiated multiple times.
4. **Configuration Consistency**: Services read config values at construction. Singletons guarantee consistent configuration.

### Registered as Singletons
- `EmbeddingService` - HTTP client reuse, embedding cache
- `ChunkingService` - Stateless text processing
- `VectorStore` - Stateless database queries
- `RAGService` - Orchestrates vector search

### NOT Registered (mutable state)
- `GeoScorer` - Has `forTier()` that mutates `$scorers` array
- `EnhancedGeoScorer` - Wraps GeoScorer, same mutation issue

### Rule
If a service has methods that modify internal state, it must NOT be a singleton. State changes would leak between requests.

---

## Project Organization

### ADR-014: Feature-Based Directory Structure

### Decision
Organize controllers and requests by **feature/domain**, not by type.

### Rationale
1. **Cohesion**: Related code lives together.
2. **Discoverability**: Find all scan-related controllers in one folder.
3. **Scalability**: Adding features doesn't bloat a single directory.
4. **Bounded contexts**: Mirrors domain-driven design principles.

### Structure
```
app/Http/Controllers/
├── Scans/
│   ├── ScanController.php
│   ├── BulkScanController.php
│   └── ExportPdfController.php
├── GA4/
│   ├── GA4ConnectionController.php
│   └── GA4CallbackController.php
└── Teams/
    └── TeamController.php
```

---

## Performance

### ADR-015: Efficient Eloquent Query Methods

### Decision
Use Eloquent query builder methods directly instead of fetching data first and then processing in PHP.

### Context
Methods like `pluck()`, `count()`, `sum()`, `exists()` are available on both the Query Builder and Collection classes. Using them on the Query Builder executes optimized SQL, while using them on a Collection processes data in PHP after loading everything into memory.

### Rationale
1. **Memory efficiency**: Query builder methods only fetch what's needed from the database.
2. **Database optimization**: The database engine is optimized for these operations.
3. **Network efficiency**: Less data transferred between database and application.
4. **Scalability**: Performance difference grows dramatically with data size.

### Rules

**Use query builder methods directly:**
```php
// ✅ CORRECT - Single column fetched: SELECT name FROM users
$names = User::where('active', true)->pluck('name');

// ✅ CORRECT - Count in database: SELECT COUNT(*) FROM users
$count = User::where('active', true)->count();

// ✅ CORRECT - Existence check: SELECT EXISTS(...)
$exists = User::where('email', $email)->exists();

// ✅ CORRECT - Sum in database: SELECT SUM(amount) FROM orders
$total = Order::where('user_id', $userId)->sum('amount');
```

**Avoid fetching all data first:**
```php
// ❌ WRONG - Loads ALL rows, ALL columns into memory, then extracts names
$names = User::where('active', true)->get()->pluck('name');

// ❌ WRONG - Loads ALL rows into memory just to count them
$count = User::where('active', true)->get()->count();

// ❌ WRONG - Loads ALL rows into memory to check if any exist
$exists = User::where('email', $email)->get()->isNotEmpty();
```

### The `::query()` Method

The `::query()` method is optional when chaining. Only use it when it provides clarity:

```php
// These are identical - prefer the shorter form
User::where('active', true)->get();
User::query()->where('active', true)->get();

// ✅ Useful when building queries conditionally
$query = User::query();

if ($request->has('role')) {
    $query->where('role', $request->role);
}

if ($request->has('status')) {
    $query->where('status', $request->status);
}

return $query->get();
```

---

### ADR-016: Large Dataset Processing

### Decision
Use `cursor()`, `lazy()`, or `chunk()` for processing large datasets. Never use `get()` or `all()` on unbounded queries.

### Context
Each Eloquent model consumes ~2-5KB of RAM. Loading 10,000 rows uses 20-50MB just for model objects, before any processing.

### Rationale
1. **Memory safety**: Prevents out-of-memory errors on large tables.
2. **Predictable resource usage**: Memory consumption stays constant regardless of dataset size.
3. **Production stability**: Workers and commands won't crash from memory exhaustion.
4. **Scalability**: Code that works on 1,000 rows will work on 1,000,000 rows.

### Memory Comparison (100k rows)
| Method | Peak RAM | Use Case |
|--------|----------|----------|
| `get()` | ~500MB | Never for large sets |
| `chunk(1000)` | ~5MB | When you need to save/update |
| `lazy()` | ~2MB | Read-only iteration |
| `cursor()` | ~1MB | Read-only, absolute minimum |

### Rules

```php
// ❌ WRONG - Loads all rows into memory
$users = User::all();
foreach ($users as $user) {
    $this->process($user);
}

// ✅ CORRECT - Chunks process then free memory (use when writing to DB)
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        $user->update(['processed' => true]);
    }
});

// ✅ CORRECT - Lazy collection, single row in memory (read-only)
foreach (User::lazy() as $user) {
    $this->sendEmail($user);
}

// ✅ BEST - Database cursor, absolute minimum RAM (read-only)
foreach (User::cursor() as $user) {
    $this->export($user);
}
```

### When to Use Each
- **`chunk()`**: Need to write back to DB (avoids database locks)
- **`lazy()`**: Read-only iteration, need Collection methods
- **`cursor()`**: Read-only, maximum memory efficiency

---

### ADR-017: Queue Heavy Operations

### Decision
Any operation taking >500ms or consuming significant resources must be queued. User requests should return in <200ms.

### Context
HTTP requests should be fast. Heavy operations block web workers and degrade user experience.

### Rationale
1. **User experience**: Users perceive responses >200ms as slow; >1s feels broken.
2. **Worker availability**: Long requests block PHP-FPM workers, reducing throughput.
3. **Timeout safety**: Web servers timeout long requests (typically 30-60s).
4. **Retry capability**: Queued jobs can retry on failure; HTTP requests cannot.
5. **Resource isolation**: Queue workers can have different memory/CPU limits than web workers.

### What Must Be Queued
| Operation | Why |
|-----------|-----|
| External API calls | Network latency unpredictable |
| Email sending | SMTP can be slow |
| PDF generation | CPU + memory intensive |
| Image processing | CPU + memory intensive |
| Large data exports | Memory + time intensive |
| Webhook deliveries | Network latency |
| Search indexing | Can be slow |

### Rules

```php
// ❌ WRONG - User waits for PDF generation
public function download(Report $report)
{
    $pdf = $this->generatePdf($report); // 5-10 seconds
    return $pdf->download();
}

// ✅ CORRECT - Generate async, notify when ready
public function requestDownload(Report $report)
{
    GenerateReportJob::dispatch($report, auth()->user());

    return back()->with('status', 'Generating report. We\'ll email you when ready.');
}

// ❌ WRONG - External API in request
public function store(Request $request)
{
    $order = Order::create($request->validated());
    Http::post('payment-api.com', $order->toArray()); // 2-3 seconds
    return redirect()->route('orders.show', $order);
}

// ✅ CORRECT - Queue the API call
public function store(Request $request)
{
    $order = Order::create($request->validated());
    ProcessPaymentJob::dispatch($order);
    return redirect()->route('orders.show', $order);
}
```

### Job Best Practices
```php
class ProcessPaymentJob implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60;

    // Pass IDs, not models (smaller payload, fresh data)
    public function __construct(
        public int $orderId
    ) {}

    public function handle(): void
    {
        $order = Order::findOrFail($this->orderId);
        // Process...
    }
}
```

---

### ADR-018: Database Indexing Strategy

### Decision
Add indexes to all columns used in `WHERE`, `ORDER BY`, and `JOIN` clauses. Foreign keys must always have indexes.

### Context
Without indexes, the database performs full table scans. A query on 1M rows can take seconds without an index, milliseconds with one.

### Rationale
1. **Query speed**: Indexes turn O(n) table scans into O(log n) lookups.
2. **Database load**: Unindexed queries consume excessive CPU and I/O.
3. **Connection pooling**: Slow queries hold connections longer, exhausting pools.
4. **User experience**: Page loads depend on query performance.
5. **Scaling**: Proper indexing delays the need for read replicas or sharding.

### Rules

```php
// ✅ CORRECT - Index columns you filter by
Schema::create('scans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();     // Auto-indexed by FK
    $table->foreignId('team_id')->nullable()->constrained(); // Auto-indexed
    $table->string('status')->index();                // Filtered frequently
    $table->timestamp('created_at')->index();         // Sorted frequently

    // Composite index for common query patterns
    $table->index(['user_id', 'status']);             // WHERE user_id = ? AND status = ?
    $table->index(['status', 'created_at']);          // WHERE status = ? ORDER BY created_at
});
```

### Index Selection Guide
| Query Pattern | Index Needed |
|--------------|--------------|
| `WHERE status = ?` | `index('status')` |
| `WHERE user_id = ? AND status = ?` | `index(['user_id', 'status'])` |
| `ORDER BY created_at` | `index('created_at')` |
| `WHERE status = ? ORDER BY created_at` | `index(['status', 'created_at'])` |
| Foreign key relationships | Automatic with `constrained()` |

### Don't Over-Index
- Each index slows down `INSERT`/`UPDATE` operations
- Only index columns actually used in queries
- Use `EXPLAIN ANALYZE` to verify indexes are being used

---

### ADR-019: Eager Loading Relationships

### Decision
Always eager load relationships that will be accessed. Use `with()` to prevent N+1 queries.

### Context
Accessing a relationship in a loop without eager loading causes N+1 queries: 1 query for the parent + N queries for each child.

### Rationale
1. **Query reduction**: N+1 becomes 2 queries regardless of dataset size.
2. **Database load**: Fewer queries mean fewer connections and less parsing overhead.
3. **Latency**: Each query has network round-trip time; fewer queries = faster response.
4. **Predictability**: Performance doesn't degrade as data grows.
5. **Debuggability**: Easy to spot with Laravel Debugbar or `preventLazyLoading()`.

### Rules

```php
// ❌ WRONG - N+1 queries (1 + 100 queries for 100 posts)
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;    // Query per post
    echo $post->category->name;  // Another query per post
}

// ✅ CORRECT - 3 queries total
$posts = Post::with(['author', 'category'])->get();
foreach ($posts as $post) {
    echo $post->author->name;    // Already loaded
    echo $post->category->name;  // Already loaded
}

// ✅ CORRECT - Nested eager loading
$posts = Post::with(['author.profile', 'comments.user'])->get();

// ✅ CORRECT - Constrained eager loading
$posts = Post::with([
    'comments' => fn ($query) => $query->where('approved', true)->limit(5)
])->get();
```

### Automatic Eager Loading in Models
```php
class Post extends Model
{
    // Always load these relationships
    protected $with = ['author'];
}
```

### Detecting N+1 in Development
```php
// In AppServiceProvider::boot()
if (app()->isLocal()) {
    \Illuminate\Database\Eloquent\Model::preventLazyLoading();
}
```

---

### ADR-020: Caching Expensive Operations

### Decision
Cache results of expensive operations (database queries, API calls, computations) that don't change frequently.

### Context
Repeatedly executing expensive operations wastes resources. Cache results with appropriate TTL based on data freshness requirements.

### Rationale
1. **Response time**: Cached data returns in microseconds vs milliseconds/seconds.
2. **Database load**: Reduces repetitive queries, especially for dashboard/aggregate data.
3. **API costs**: External API calls often have rate limits and costs.
4. **Scalability**: Cache handles traffic spikes without scaling database.
5. **Consistency**: All users see the same cached result (useful for shared data).

### Rules

```php
// ❌ WRONG - Hits database every request
$stats = $this->calculateDashboardStats();

// ✅ CORRECT - Cache for 5 minutes
$stats = Cache::remember('dashboard:stats:' . $userId, 300, function () {
    return $this->calculateDashboardStats();
});

// ✅ CORRECT - Cache forever, invalidate manually
$settings = Cache::rememberForever('app:settings', fn () =>
    Setting::pluck('value', 'key')->toArray()
);

// Invalidate when settings change
Cache::forget('app:settings');
```

### Cache Key Strategies
```php
// User-specific cache
$key = "user:{$userId}:dashboard";

// Team-specific cache
$key = "team:{$teamId}:reports";

// Query-specific cache (careful with this)
$key = 'search:' . md5(serialize($filters));

// Time-based cache keys (auto-expire pattern)
$key = "stats:" . now()->format('Y-m-d-H'); // Hourly
```

### What to Cache
| Data Type | TTL | Strategy |
|-----------|-----|----------|
| App configuration | Forever | Invalidate on change |
| User preferences | 1 hour | Invalidate on change |
| Dashboard stats | 5-15 min | TTL expiry |
| API responses | Varies | Based on API cache headers |
| Computed reports | 1 hour+ | Regenerate via queue |

### What NOT to Cache
- Frequently changing data
- User-specific sensitive data (unless properly keyed)
- Data that must be real-time

---

### ADR-021: Production Optimization Commands

### Decision
Always run optimization commands in production deployments. Never run them in development.

### Context
Laravel's optimization commands compile and cache configuration, routes, and views. This eliminates file parsing overhead on every request.

### Rationale
1. **Startup time**: Eliminates file I/O and parsing on every request.
2. **Consistency**: Cached config prevents env var race conditions.
3. **Autoloader efficiency**: Optimized class maps avoid filesystem lookups.
4. **Memory**: Pre-compiled views use less memory than on-the-fly compilation.
5. **Measurable impact**: 10-30ms savings per request adds up significantly.

### Required in Production
```bash
# Cache configuration (all config files → single cached file)
php artisan config:cache

# Cache routes (route registration → single cached file)
php artisan route:cache

# Cache views (Blade compilation)
php artisan view:cache

# Cache events (event discovery → cached file)
php artisan event:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Performance Impact
| Command | Without | With |
|---------|---------|------|
| Config loading | 5-10ms | <1ms |
| Route matching | 5-20ms | <1ms |
| View compilation | First-hit penalty | Pre-compiled |

### Deployment Script
```bash
php artisan down
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan up
```

### Clearing in Development
```bash
php artisan optimize:clear  # Clears all cached data
```

### Important Notes
- After `config:cache`, `env()` only works inside config files
- After `route:cache`, closure routes won't work (use controller routes)
- Always clear cache before caching in deployment

---

### ADR-022: Avoiding Memory Leaks in Long-Running Processes

### Decision
Queue workers and scheduled commands must not accumulate memory. Use `cursor()`, unset large variables, and configure worker restarts.

### Context
Queue workers run continuously. Without proper memory management, they grow until they crash.

### Rationale
1. **Worker stability**: Prevents OOM crashes that lose in-progress jobs.
2. **Predictable behavior**: Memory stays constant regardless of jobs processed.
3. **Resource planning**: Can accurately size worker instances.
4. **Debugging**: Memory growth indicates a leak to investigate.
5. **Cost efficiency**: Smaller instance sizes when memory is well-managed.

### Rules

```php
// ❌ WRONG - Accumulates memory
class ProcessDataJob implements ShouldQueue
{
    private array $results = [];

    public function handle(): void
    {
        foreach (Data::all() as $record) {
            $this->results[] = $this->process($record); // Memory grows
        }
    }
}

// ✅ CORRECT - Process and release
class ProcessDataJob implements ShouldQueue
{
    public function handle(): void
    {
        foreach (Data::cursor() as $record) {
            $result = $this->process($record);
            $this->save($result);
            // $record and $result released each iteration
        }
    }
}

// ✅ CORRECT - Explicit cleanup for large objects
public function handle(): void
{
    $largeData = $this->fetchLargeDataset();
    $this->process($largeData);

    unset($largeData);          // Release memory
    gc_collect_cycles();         // Force garbage collection
}
```

### Worker Configuration
```bash
# Restart worker after 500 jobs (prevents memory buildup)
php artisan queue:work --max-jobs=500

# Restart worker if memory exceeds 128MB
php artisan queue:work --memory=128

# Restart workers after deployment
php artisan queue:restart
```

### Supervisor Configuration
```ini
[program:laravel-worker]
command=php /var/www/artisan queue:work --max-jobs=500 --memory=128
numprocs=4
autostart=true
autorestart=true
```

---

### ADR-023: Select Only Required Columns

### Decision
Use `select()` to fetch only the columns needed. Avoid `SELECT *` patterns.

### Context
Fetching unnecessary columns wastes memory and network bandwidth. A table with 50 columns vs selecting 3 columns can be 10x difference in data transfer.

### Rationale
1. **Memory efficiency**: Each column consumes RAM in PHP and database buffers.
2. **Network bandwidth**: Less data transferred between database and application.
3. **Index optimization**: Covering indexes can serve queries entirely from index.
4. **Serialization cost**: Smaller models serialize faster for caching/queuing.
5. **Security**: Avoid accidentally exposing sensitive columns in API responses.

### Rules

```php
// ❌ WRONG - Fetches all 50 columns
$users = User::where('active', true)->get();

// ✅ CORRECT - Only fetch what's needed
$users = User::select(['id', 'name', 'email'])
    ->where('active', true)
    ->get();

// ✅ CORRECT - When using relationships
$posts = Post::select(['id', 'title', 'user_id'])
    ->with(['author' => fn ($q) => $q->select(['id', 'name'])])
    ->get();

// ✅ CORRECT - For simple key-value needs
$userNames = User::pluck('name', 'id');
```

### Exceptions
- When you genuinely need all columns
- In admin panels where all data is displayed
- API resources that explicitly select fields

---

### ADR-024: Use Database Aggregates Over Collection Methods

### Decision
Use database aggregate functions (`count`, `sum`, `avg`, `min`, `max`) instead of fetching data and computing in PHP.

### Context
The database engine is optimized for aggregate calculations. Computing in PHP requires loading all data into memory first.

### Rationale
1. **Performance**: Database aggregates run in optimized C code with direct disk access.
2. **Memory**: Only the result (single value) crosses the network, not all rows.
3. **Indexing**: Aggregates can use indexes; PHP computation cannot.
4. **Parallelism**: Databases parallelize aggregate operations; PHP is single-threaded.
5. **Accuracy**: Database handles precision and overflow better than PHP floats.

### Rules

```php
// ❌ WRONG - Loads all rows into memory
$count = User::all()->count();
$total = Order::all()->sum('amount');
$average = Product::all()->avg('price');

// ✅ CORRECT - Database does the math
$count = User::count();
$total = Order::sum('amount');
$average = Product::avg('price');

// ✅ CORRECT - With conditions
$activeUsers = User::where('status', 'active')->count();
$revenue = Order::where('paid', true)->sum('amount');

// ✅ CORRECT - Grouped aggregates
$salesByMonth = Order::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
    ->groupByRaw('MONTH(created_at)')
    ->pluck('total', 'month');

// ✅ CORRECT - Multiple aggregates in one query
$stats = User::selectRaw('
    COUNT(*) as total,
    SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active_count,
    AVG(age) as average_age
')->first();
```

---

# Architecture Overview

```
HTTP Request
     │
     ▼
┌─────────────────┐
│  Form Request   │  Validation, authorization, data transformation
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Controller    │  Receive request → call service → return response
│                 │  NO business logic, NO private methods
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Service      │  Business logic, database ops, external APIs
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Policy       │  Model-level authorization (can user do X to Y?)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│     Model       │  Eloquent relationships, scopes, accessors
└─────────────────┘
```

---

# Quick Reference

## Do
- Put all business logic in services
- Put all validation in Form Requests
- Put all authorization in Policies
- Use Eloquent for all database queries
- Use query builder methods (`pluck`, `count`, `exists`) directly
- Use `cursor()` or `lazy()` for large dataset iteration
- Use `chunk()` when updating large datasets
- Use `select()` to fetch only needed columns
- Queue operations >500ms (emails, PDFs, API calls)
- Eager load relationships with `with()`
- Cache expensive operations
- Add indexes to columns in WHERE/ORDER BY
- Return 404 for authorization failures (log 403 internally)
- Add return types to all methods
- Add docblocks to all classes
- Use `Arr::get()` for array access
- Use method injection in controllers
- Use constructor injection in services
- Run `config:cache` and `route:cache` in production

## Don't
- Business logic in controllers
- Validation in controllers (`$request->validate()`)
- Private/protected methods in controllers
- Constructor injection in controllers
- Raw DB queries (`DB::select`, `DB::table`, etc.)
- `->get()->pluck()` or `->get()->count()` (fetch then process)
- `User::all()` on large tables (use `cursor`/`lazy`/`chunk`)
- `SELECT *` when you only need specific columns
- Synchronous external API calls in requests
- N+1 queries (always eager load)
- Return 403 for authorization failures (leaks info)
- Missing return types
- Missing class docblocks
- Direct array access (`$data['key']`)
- Register mutable services as singletons
- Use `env()` outside config files in production

## Naming Conventions
| Type | Pattern | Example |
|------|---------|---------|
| Resource Controller | `{Model}Controller` | `ScanController` |
| Invokable Controller | `{Action}{Model}Controller` | `ExportPdfController` |
| Service | `{Feature}Service` | `ScanService` |
| Form Request | `{Action}{Model}Request` | `StoreScanRequest` |
| Policy | `{Model}Policy` | `ScanPolicy` |
