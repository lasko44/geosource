# Project Coding Standards

This document defines the coding standards and Laravel best practices for this project. All generated code must follow these guidelines.

---

# Architectural Decisions Record (ADR)

This section documents the key architectural decisions made in this project, the reasoning behind them, and alternatives that were considered.

## ADR-001: Invokable Controllers vs Laravel Actions

### Decision
Use native Laravel **invokable controllers** for single-action endpoints rather than the `lorisleiva/laravel-actions` package.

### Context
When refactoring controllers to follow single-responsibility principle, we needed a pattern for non-CRUD actions like `ExportPdf`, `ScanCancel`, `CheckCooldown`, etc.

### Options Considered

**Option A: Laravel Actions Package**
```php
class ExportPdf
{
    use AsAction;

    public function handle(Scan $scan, User $user): string { }
    public function asController(Scan $scan): Response { }
    public function asJob(Scan $scan, User $user): void { }
}
```
- Pros: Single class works as controller, job, listener, or command
- Cons: Additional dependency, more complex mental model, overkill when actions don't need multiple execution contexts

**Option B: Invokable Controllers (Chosen)**
```php
class ExportPdfController extends Controller
{
    public function __invoke(Scan $scan, ScanService $service) { }
}
```
- Pros: Native Laravel, no dependencies, simple, clear separation of concerns
- Cons: Less flexible if same logic needed in multiple contexts

### Rationale
1. **Separation of concerns**: Controllers handle HTTP, services handle business logic, jobs handle async work. This is already clean.
2. **YAGNI (You Aren't Gonna Need It)**: We don't have use cases requiring the same action as both HTTP endpoint and queued job.
3. **No external dependencies**: Reduces maintenance burden and potential security vulnerabilities.
4. **Team familiarity**: Standard Laravel patterns are easier for new developers to understand.

### When to Reconsider
If we frequently need identical logic running as HTTP endpoints AND background jobs, Laravel Actions would reduce duplication.

---

## ADR-002: Method Injection vs Constructor Injection

### Decision
Use **method injection** in controllers, not constructor injection.

### Context
Laravel supports both patterns. We needed a consistent approach.

### Options Considered

**Option A: Constructor Injection**
```php
class ScanController extends Controller
{
    public function __construct(
        private ScanService $scanService,
        private TokenService $tokenService,
    ) {}

    public function show(Scan $scan) {
        $this->scanService->process($scan);
    }
}
```

**Option B: Method Injection (Chosen)**
```php
class ScanController extends Controller
{
    public function show(Scan $scan, ScanService $scanService) {
        $scanService->process($scan);
    }
}
```

### Rationale
1. **Only inject what you need**: Each method declares its own dependencies. The `index` method might not need `TokenService`, so why inject it for every action?
2. **Explicit dependencies**: Reading a method signature tells you exactly what it needs.
3. **Testing simplicity**: Mock only what the specific method uses.
4. **Controller lifecycle**: Controllers are instantiated per-request. Constructor injection offers no performance benefit and may instantiate unused services.
5. **Laravel convention**: Taylor Otwell (Laravel creator) recommends method injection for controllers.

### Trade-offs
- Constructor injection can reduce parameter count in methods
- Some teams prefer constructor injection for consistency with other classes
- Services themselves should use constructor injection (they're singletons with consistent dependencies)

---

## ADR-003: Skinny Controllers, Fat Services

### Decision
Controllers contain **zero business logic**. All logic lives in service classes.

### Context
The original `ScanController` was 1,487 lines with mixed responsibilities: validation, authorization, business logic, response formatting, and database operations.

### The Pattern
```
Request → Controller → Service → Model → Database
              ↓
           Response
```

**Controller responsibilities:**
- Receive HTTP request
- Call service method(s)
- Return HTTP response

**Service responsibilities:**
- Business logic
- Database operations
- External API calls
- Complex calculations

### Rationale
1. **Testability**: Services can be unit tested without HTTP layer. Controllers need feature tests.
2. **Reusability**: Services can be called from controllers, jobs, commands, or other services.
3. **Readability**: A 50-line controller is easier to understand than a 500-line one.
4. **Single Responsibility**: Each class has one reason to change.
5. **Debugging**: When something breaks, you know where to look based on the type of issue.

### Example
```php
// ❌ Before: 80-line controller method
public function store(Request $request) {
    // Validation (20 lines)
    // Authorization (10 lines)
    // Business logic (30 lines)
    // Database operations (15 lines)
    // Response (5 lines)
}

// ✅ After: 5-line controller method
public function store(StoreScanRequest $request, ScanService $service) {
    $scan = $service->executeScan($request->user(), $request->validated());
    return redirect()->route('scans.show', $scan);
}
```

---

## ADR-004: Form Requests for Validation

### Decision
All validation logic lives in **Form Request** classes, not controllers.

### Context
Laravel provides Form Requests as dedicated classes for validation and authorization.

### Rationale
1. **Reusability**: Same validation can be used by multiple endpoints.
2. **Separation of concerns**: Controllers don't need to know validation rules.
3. **Authorization co-location**: `authorize()` method keeps auth logic with related validation.
4. **Cleaner controllers**: No `$request->validate([...])` blocks.
5. **Custom error messages**: Centralized in one place.
6. **Data transformation**: Helper methods like `getTier()` encapsulate input transformation.

### Pattern
```php
class StoreScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Scan::class);
    }

    public function rules(): array
    {
        return ['url' => 'required|url', 'tier' => 'in:basic,pro'];
    }

    // Helper methods for clean data access
    public function getTier(): string
    {
        return $this->input('tier', 'basic');
    }
}
```

---

## ADR-005: Policy Classes for Authorization

### Decision
Use **Policy classes** for all model-based authorization, not inline checks.

### Context
Authorization was scattered across controllers with repeated conditional logic.

### Before (Inline Authorization)
```php
public function show(Scan $scan) {
    if ($scan->user_id !== auth()->id()) {
        if ($scan->team_id && !auth()->user()->allTeams()->contains('id', $scan->team_id)) {
            abort(403);
        }
    }
    // ...
}
```

### After (Policy)
```php
// Controller
public function show(Scan $scan) {
    $this->authorize('view', $scan);
    // ...
}

// Policy
class ScanPolicy {
    public function view(User $user, Scan $scan): bool {
        return $scan->user_id === $user->id
            || $user->allTeams()->contains('id', $scan->team_id);
    }
}
```

### Rationale
1. **DRY**: Authorization logic written once, used everywhere.
2. **Testability**: Policies are easily unit tested.
3. **Blade integration**: `@can('view', $scan)` works automatically.
4. **Consistency**: All authorization follows the same pattern.
5. **Discoverability**: All rules for a model are in one file.

---

## ADR-006: Array Access via Arr Helper

### Decision
Always use `Illuminate\Support\Arr::get()` for array access instead of direct bracket notation.

### Rationale
1. **Null safety**: `Arr::get($data, 'key')` returns null instead of throwing an error.
2. **Default values**: `Arr::get($data, 'key', 'default')` is cleaner than `$data['key'] ?? 'default'`.
3. **Nested access**: `Arr::get($data, 'user.profile.name')` handles missing intermediate keys.
4. **Consistency**: One pattern across the codebase.

```php
// ❌ Risky
$name = $response['data']['user']['name'];

// ✅ Safe
$name = Arr::get($response, 'data.user.name', 'Unknown');
```

---

## ADR-007: No Private Methods in Controllers

### Decision
Controllers must not contain `private` or `protected` methods.

### Context
Private controller methods indicate business logic that should live elsewhere.

### Rationale
1. **Signals misplaced logic**: If you need a helper method, it belongs in a service.
2. **Untestable**: Private methods can't be unit tested directly.
3. **Not reusable**: Other controllers can't use private methods.
4. **Controller bloat**: Private methods are a slippery slope to 500-line controllers.

### Where Helper Logic Should Live
| Logic Type | Location |
|------------|----------|
| Business logic | Service |
| Data transformation | Form Request or Service |
| Authorization | Policy |
| Query building | Service or Model scope |
| Formatting | Service or dedicated Formatter class |

---

## ADR-008: Feature-Based Directory Structure

### Decision
Organize controllers and requests by **feature/domain**, not by type.

### Structure
```
app/Http/Controllers/
├── Scans/
│   ├── ScanController.php
│   ├── BulkScanController.php
│   ├── ScanStatusController.php
│   └── ExportPdfController.php
├── GA4/
│   ├── GA4ConnectionController.php
│   ├── GA4SyncController.php
│   └── GA4CallbackController.php
└── Teams/
    ├── TeamController.php
    └── TeamMemberController.php
```

### Rationale
1. **Cohesion**: Related code lives together.
2. **Discoverability**: Find all scan-related controllers in one folder.
3. **Scalability**: Adding features doesn't bloat a single directory.
4. **Bounded contexts**: Mirrors domain-driven design principles.

---

## ADR-009: Services Use Constructor Injection

### Decision
While controllers use method injection, **services use constructor injection**.

### Rationale
1. **Different lifecycle**: Services are often singletons or have consistent dependencies.
2. **All methods need same dependencies**: Unlike controllers, service methods typically share dependencies.
3. **Cleaner method signatures**: Service methods focus on business parameters, not infrastructure.

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

## ADR-010: Type Hints and Return Types Everywhere

### Decision
All method parameters and return types must have explicit type hints. **Every function must declare a return type.**

### Rationale
1. **Self-documenting**: The signature tells you what the method expects and returns.
2. **IDE support**: Autocomplete, refactoring, and error detection.
3. **Runtime safety**: PHP enforces types, catching bugs early.
4. **Static analysis**: Tools like PHPStan can verify correctness.
5. **Contract clarity**: Return types make the method's contract explicit.

```php
// ✅ CORRECT - Has return type
public function executeScan(User $user, string $url, ?Team $team = null): Scan
{
    // ...
}

// ✅ CORRECT - Void return type for methods that don't return
public function sendNotification(User $user): void
{
    // ...
}

// ✅ CORRECT - Union types when multiple returns possible
public function findScan(string $uuid): Scan|null
{
    // ...
}

// ❌ WRONG - Missing return type
public function executeScan(User $user, string $url)
{
    // ...
}
```

---

## ADR-011: Class Documentation

### Decision
Every class must have a docblock explaining its purpose.

### Rationale
1. **Discoverability**: Developers can quickly understand what a class does without reading all its code.
2. **Onboarding**: New team members understand the codebase faster.
3. **IDE integration**: Docblocks appear in hover tooltips and autocomplete.
4. **Intentional design**: Writing a description forces you to clarify the class's single responsibility.

### Pattern
```php
/**
 * Handles the execution and management of website scans.
 *
 * Responsible for creating scans, checking quotas, managing tokens,
 * and coordinating with external scanning services.
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
    public function __invoke(Scan $scan, ScanService $service): Response
    {
        // ...
    }
}

/**
 * Validates and authorizes requests to create a new scan.
 */
class StoreScanRequest extends FormRequest
{
    // ...
}
```

### Guidelines
- Keep descriptions concise (1-3 sentences)
- Focus on **what** the class does, not **how**
- For controllers, describe the action it performs
- For services, describe the domain it manages
- For form requests, mention validation/authorization purpose

---

## Summary: The Architecture at a Glance

```
┌─────────────────────────────────────────────────────────────────┐
│                        HTTP Request                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Form Request                                │
│  • Validation rules                                              │
│  • Authorization (can user make this request?)                   │
│  • Data transformation helpers                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Controller                                 │
│  • Receives request                                              │
│  • Calls service(s)                                              │
│  • Returns response                                              │
│  • NO business logic                                             │
│  • NO private methods                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Service                                   │
│  • All business logic                                            │
│  • Database operations                                           │
│  • External API calls                                            │
│  • Reusable across controllers, jobs, commands                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Policy (if needed)                           │
│  • Model-level authorization                                     │
│  • Can user view/update/delete this resource?                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         Model                                    │
│  • Eloquent relationships                                        │
│  • Accessors/mutators                                            │
│  • Query scopes                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Controller Standards

### Resource Actions Only
Controllers should only contain the 7 standard resource actions as defined by Laravel:

| Verb      | URI                    | Action  | Route Name     |
|-----------|------------------------|---------|----------------|
| GET       | /photos                | index   | photos.index   |
| GET       | /photos/create         | create  | photos.create  |
| POST      | /photos                | store   | photos.store   |
| GET       | /photos/{photo}        | show    | photos.show    |
| GET       | /photos/{photo}/edit   | edit    | photos.edit    |
| PUT/PATCH | /photos/{photo}        | update  | photos.update  |
| DELETE    | /photos/{photo}        | destroy | photos.destroy |

### Single-Action Controllers
For non-CRUD actions, use invokable single-action controllers with `__invoke()`:

```php
class ExportPdfController extends Controller
{
    public function __invoke(Scan $scan, ScanService $scanService)
    {
        // Single action logic
    }
}
```

### No Private Methods in Controllers
Controllers must NOT contain private or protected methods. All helper logic belongs in:
- **Services** - Business logic, complex operations
- **Form Requests** - Validation and authorization
- **Policies** - Authorization rules
- **Models** - Data-related methods

### Dependency Injection
Use **method injection**, NOT constructor injection:

```php
// ✅ CORRECT - Method injection
public function index(Request $request, ScanService $scanService): Response
{
    // ...
}

// ❌ WRONG - Constructor injection
public function __construct(private ScanService $scanService) {}
```

### Skinny Controllers
Controllers should be thin orchestrators. They should:
1. Receive the request
2. Call services to perform business logic
3. Return the response

## Service Layer

### All Business Logic in Services
Complex logic, database operations, and business rules belong in service classes:

```php
// app/Services/ScanService.php
class ScanService
{
    public function executeScan(User $user, string $url, string $tier): Scan
    {
        // Complex business logic here
    }
}
```

### Reusable Helper Methods
Any logic that would be a private method in a controller should be a public method in a service.

## Form Requests

### Validation in Form Requests
All validation logic belongs in Form Request classes:

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
}
```

### Helper Methods on Form Requests
Form Requests can have helper methods to transform/access validated data:

```php
public function getTier(): string
{
    return $this->input('tier', 'basic');
}

public function getValidatedTeam(): ?Team
{
    // Team validation logic
}
```

## Code Readability

### Avoid Complex Conditionals
Break down complex if statements into readable methods:

```php
// ❌ WRONG - Complex nested condition
if ($connection->user_id !== $user->id) {
    if ($connection->team_id && !$user->allTeams()->contains('id', $connection->team_id)) {
        abort(403);
    }
}

// ✅ CORRECT - Use a policy or service method
$this->authorize('view', $connection);

// Or in a service:
if (!$this->canAccessConnection($user, $connection)) {
    abort(403);
}
```

### Small, Focused Functions
Break large functions into smaller, digestible pieces:

```php
// ❌ WRONG - Large function doing many things
public function index(Request $request)
{
    // 50+ lines of mixed logic
}

// ✅ CORRECT - Delegate to service methods
public function index(Request $request, DashboardService $service): Response
{
    $user = $request->user();
    $teamContext = $service->resolveTeamContext($user, $request);
    $stats = $service->buildStats($user, $teamContext);

    return Inertia::render('Dashboard', compact('stats', 'teamContext'));
}
```

### Extract Repeated Code
If the same logic appears in multiple places, extract it to a service:

```php
// ❌ WRONG - Repeated team context logic
$currentTeamId = session('current_team_id');
$team = null;
if ($currentTeamId && $currentTeamId !== 'personal') {
    $team = $user->allTeams()->firstWhere('id', $currentTeamId);
}

// ✅ CORRECT - Use a service method
$team = $scanService->getCurrentTeam($user);
```

## Type Hints and Documentation

### Always Use Type Hints
All method parameters and return types must have type hints:

```php
public function show(Scan $scan, ScanService $scanService): Response
{
    // ...
}

public function executeScan(User $user, string $url, ?Team $team): Scan
{
    // ...
}

// Void for methods that don't return a value
public function sendEmail(User $user): void
{
    // ...
}
```

### Every Class Needs a Docblock
All classes must have a docblock explaining their purpose:

```php
/**
 * Handles website scan execution and lifecycle management.
 */
class ScanService
{
    // ...
}

/**
 * Cancels a pending or running scan.
 */
class ScanCancelController extends Controller
{
    public function __invoke(Scan $scan): RedirectResponse
    {
        // ...
    }
}
```

### Document Complex Logic
Add comments to explain non-obvious code:

```php
/**
 * Filter pillars based on the scan's requested tier.
 * Users see all pillars they paid for, whether via subscription or tokens.
 * The effective tier is the higher of: user's subscription tier OR scan's requested tier.
 */
public function filterPillarsForScanTier(array $pillars, Scan $scan, User $user): array
{
    // Priority mapping: basic=0, pro=1, agency=2, admin=3
    $tierPriority = [...];

    // Use the higher tier (user may have subscription OR paid tokens)
    $effectivePriority = max($userPriority, $scanPriority);

    // ...
}
```

## Array Access

### Use Laravel's Arr Helper
Always use `Arr::get()` when accessing array values:

```php
use Illuminate\Support\Arr;

// ✅ CORRECT
$currentTeamId = Arr::get($teamContext, 'currentTeamId');
$name = Arr::get($data, 'user.name', 'Default');

// ❌ WRONG
$currentTeamId = $teamContext['currentTeamId'];
$name = $data['user']['name'] ?? 'Default';
```

### Extract to Variables
When accessing the same array key multiple times, extract to a variable:

```php
// ✅ CORRECT
$currentTeamId = Arr::get($teamContext, 'currentTeamId');
$currentTeam = Arr::get($teamContext, 'currentTeam');

$query = $service->buildQuery($user, $currentTeamId);
$usage = $service->getUsage($user, $currentTeam);
```

## Route Conventions

### Use Resource Routes
Prefer `Route::resource()` for standard CRUD:

```php
Route::resource('scans', ScanController::class)->only(['index', 'show', 'store', 'destroy']);
```

### Route Ordering
Place specific routes before wildcard routes:

```php
// ✅ CORRECT - Specific routes first
Route::get('/scans/bulk', [BulkScanController::class, 'index']);
Route::get('/scans/{scan}', [ScanController::class, 'show']);

// ❌ WRONG - Wildcard catches 'bulk'
Route::get('/scans/{scan}', [ScanController::class, 'show']);
Route::get('/scans/bulk', [BulkScanController::class, 'index']);
```

### Invokable Controller Routes
For single-action controllers:

```php
Route::post('/scans/{scan}/cancel', ScanCancelController::class)->name('scans.cancel');
```

## Authorization

### Use Policies
Create policy classes for authorization:

```php
// app/Policies/ScanPolicy.php
class ScanPolicy
{
    public function view(User $user, Scan $scan): bool
    {
        if ($scan->user_id === $user->id) {
            return true;
        }

        if ($scan->team_id) {
            return $user->allTeams()->contains('id', $scan->team_id);
        }

        return false;
    }
}
```

### Authorize in Controllers
Use `$this->authorize()` in controllers:

```php
public function show(Scan $scan): Response
{
    $this->authorize('view', $scan);
    // ...
}
```

### Authorize in Form Requests
For store/update actions, authorize in the Form Request:

```php
public function authorize(): bool
{
    return $this->user()->can('create', Scan::class);
}
```

## Naming Conventions

### Controllers
- Resource controllers: `{Model}Controller` (e.g., `ScanController`)
- Single-action controllers: `{Action}{Model}Controller` (e.g., `ExportPdfController`, `ScanCancelController`)
- Grouped controllers: `{Feature}/{Model}Controller` (e.g., `Scans/BulkScanController`)

### Services
- `{Feature}Service` (e.g., `ScanService`, `DashboardService`)
- Namespace by feature: `Services/Analytics/GA4Service`

### Form Requests
- `{Action}{Model}Request` (e.g., `StoreScanRequest`, `UpdateScanRequest`)

## What NOT to Do

1. **No constructor injection in controllers**
2. **No private/protected methods in controllers**
3. **No complex nested conditionals**
4. **No repeated code blocks** - extract to services
5. **No business logic in controllers** - move to services
6. **No validation in controllers** - use Form Requests
7. **No direct array access** - use `Arr::get()`
8. **No missing type hints** - all parameters must be typed
9. **No missing return types** - every function must declare its return type
10. **No classes without docblocks** - every class needs a description
11. **No overly long functions** - break into smaller pieces
12. **No magic strings for repeated values** - use constants or config

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php      # Resource controller
│   │   ├── Scans/
│   │   │   ├── ScanController.php       # Resource controller
│   │   │   ├── BulkScanController.php   # Resource controller
│   │   │   ├── ScanStatusController.php # Invokable
│   │   │   ├── ScanCancelController.php # Invokable
│   │   │   └── ExportPdfController.php  # Invokable
│   │   └── ...
│   └── Requests/
│       └── Scans/
│           ├── StoreScanRequest.php
│           └── RescanRequest.php
├── Services/
│   ├── ScanService.php
│   ├── DashboardService.php
│   └── Analytics/
│       └── GA4Service.php
├── Policies/
│   ├── ScanPolicy.php
│   └── GA4ConnectionPolicy.php
└── Models/
    └── Scan.php
```
