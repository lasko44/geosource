# Security Standards (OWASP Top 10)

This document outlines how the GeoSource codebase addresses the OWASP Top 10 security vulnerabilities and documents security best practices for development.

---

## OWASP Top 10 Coverage

### A01:2021 - Broken Access Control

**Implementation:**

1. **Policy-Based Authorization** (`app/Policies/`)
   - All model-level authorization uses Laravel Policies
   - Controllers call `$this->authorize('action', $model)` before operations
   - Example: `ScanPolicy`, `TeamPolicy`, `CitationQueryPolicy`

2. **Team-Based Isolation** (`app/Services/RAG/VectorStore.php`)
   - Documents are isolated by `team_id` or `user_id`
   - All queries include isolation constraints to prevent cross-tenant data access
   ```php
   if ($teamId !== null) {
       $query->where('team_id', $teamId);
   } else {
       $query->where('user_id', $userId)->whereNull('team_id');
   }
   ```

3. **404 Instead of 403 (ADR-005)** (`bootstrap/app.php`)
   - Authorization failures return 404 to prevent resource enumeration
   - Actual 403s are logged for security monitoring
   ```php
   $exceptions->render(function (AuthorizationException $e, $request) {
       Log::warning('Authorization denied (returned as 404)', [...]);
       throw new NotFoundHttpException('Not Found');
   });
   ```

4. **Route Middleware** (`routes/web.php`)
   - Protected routes use `auth` and `verified` middleware
   - Team routes verify membership via `TeamMemberMiddleware`

---

### A02:2021 - Cryptographic Failures

**Implementation:**

1. **Password Hashing** (`config/hashing.php`)
   - Bcrypt with 12 rounds: `BCRYPT_ROUNDS=12`
   - Never stores plaintext passwords

2. **Sensitive Data Hidden** (`app/Models/User.php`)
   ```php
   protected $hidden = [
       'password',
       'remember_token',
       'stripe_id',
       'pm_type',
       'pm_last_four',
       'trial_ends_at',
   ];
   ```

3. **Session Encryption** (`.env`)
   - `SESSION_ENCRYPT=true` encrypts all session data
   - `SESSION_DRIVER=database` for server-side storage

4. **API Keys in Environment**
   - All secrets stored in `.env`, never in code
   - Sensitive config values accessed via `config()` helper

---

### A03:2021 - Injection

**Implementation:**

1. **Eloquent ORM (ADR-004)**
   - All database queries use Eloquent with parameterized queries
   - Raw `DB::` statements prohibited except for PostgreSQL-specific features
   - When raw queries required, use parameterized bindings:
   ```php
   // CORRECT - Parameterized
   DB::statement('UPDATE documents SET embedding = ?::vector WHERE id = ?', [$vectorString, $this->id]);

   // WRONG - String interpolation
   DB::raw("'$userInput'::vector")  // SQL Injection risk!
   ```

2. **pgvector Queries** (`app/Models/Document.php`, `app/Services/RAG/VectorStore.php`)
   - Vector embedding updates use parameterized `DB::statement()`
   - Vector search uses parameterized `selectRaw()` and `whereRaw()`
   ```php
   ->selectRaw('*, 1 - (embedding <=> ?) as similarity', [$vectorString])
   ```

3. **Input Validation** (`app/Http/Requests/`)
   - All user input validated via Form Request classes
   - URL validation, email validation, enum checks

4. **Metadata Filter Validation** (`app/Services/RAG/VectorStore.php:594`)
   ```php
   private function validateFilterKey(string $key): string
   {
       if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
           throw new \InvalidArgumentException("Invalid metadata filter key");
       }
       return $key;
   }
   ```

---

### A04:2021 - Insecure Design

**Implementation:**

1. **Rate Limiting** (`routes/web.php`, `app/Services/TokenService.php`)
   - API routes use `throttle` middleware
   - Token code redemption has exponential backoff:
   ```php
   // After 5 attempts, implement exponential backoff (2, 4, 8... minutes)
   $backoffMinutes = min((int) pow(2, $userAttempts - $maxAttempts), 60);
   ```
   - IP-based rate limiting for additional protection

2. **Subscription Verification** (`app/Jobs/ScanWebsiteJob.php`)
   - Re-verifies subscription before completing queued scans
   - Prevents downgraded users from completing premium-tier scans

3. **Token Deduction Upfront**
   - Tokens deducted before scan starts, not after
   - Refunds issued if scan fails

---

### A05:2021 - Security Misconfiguration

**Implementation:**

1. **Debug Mode** (`.env.example`)
   - `APP_DEBUG=false` in production
   - `LOG_LEVEL=warning` to avoid sensitive data in logs

2. **Error Messages** (`app/Http/Controllers/GA4/GA4CallbackController.php`)
   - Exception details logged, but not exposed to users
   ```php
   Log::error('GA4 OAuth callback error', [
       'error_type' => class_basename($e),
       'user_id' => $user->id,
       'message' => $e->getMessage(),
   ]);

   return redirect()->route('citations.analytics')
       ->withErrors(['oauth' => 'Failed to connect. Please try again or contact support.']);
   ```

3. **CORS Configuration** (`config/cors.php`)
   - Explicitly configured allowed origins
   - Not using wildcard `*` in production

4. **Secure Headers** (via Laravel defaults)
   - X-Content-Type-Options
   - X-Frame-Options
   - Content-Security-Policy (if configured)

---

### A06:2021 - Vulnerable and Outdated Components

**Implementation:**

1. **Dependency Management**
   - Composer for PHP dependencies
   - npm for JavaScript dependencies
   - Regular `composer update` and `npm update`

2. **Security Audits**
   - Run `composer audit` for PHP vulnerabilities
   - Run `npm audit` for JavaScript vulnerabilities

---

### A07:2021 - Identification and Authentication Failures

**Implementation:**

1. **Laravel Fortify/Jetstream**
   - Secure authentication implementation
   - Email verification required
   - Password confirmation for sensitive actions

2. **Session Security** (`.env`)
   ```
   SESSION_DRIVER=database
   SESSION_LIFETIME=120
   SESSION_ENCRYPT=true
   ```

3. **OAuth Security** (`app/Services/Analytics/GA4Service.php`)
   - State parameter validated for CSRF protection
   - Tokens stored in encrypted session

---

### A08:2021 - Software and Data Integrity Failures

**Implementation:**

1. **CSRF Protection**
   - Laravel's built-in CSRF middleware on all POST/PUT/PATCH/DELETE routes
   - Inertia.js includes CSRF token automatically

2. **Mass Assignment Protection** (All Models)
   - All models define `$fillable` or `$guarded`
   - Example from `app/Models/Scan.php`:
   ```php
   protected $fillable = [
       'user_id', 'team_id', 'url', 'title', 'status', ...
   ];
   ```

3. **Webhook Verification** (if applicable)
   - Stripe webhooks verified with signature

---

### A09:2021 - Security Logging and Monitoring Failures

**Implementation:**

1. **Authorization Logging** (`bootstrap/app.php`)
   ```php
   Log::warning('Authorization denied (returned as 404)', [
       'user_id' => $request->user()?->id,
       'ip' => $request->ip(),
       'url' => $request->fullUrl(),
       'method' => $request->method(),
   ]);
   ```

2. **Brute Force Detection** (`app/Services/TokenService.php`)
   ```php
   Log::warning('Possible token code brute force from IP', [
       'ip' => $ip,
       'user_id' => $user->id,
       'attempts' => $ipAttempts,
   ]);
   ```

3. **Scan Failure Logging** (`app/Jobs/ScanWebsiteJob.php`)
   - All scan failures logged with details
   - Admin email notifications for failures

4. **Token Transaction Logging** (`app/Services/TokenService.php`)
   - All token purchases, spends, and refunds logged

---

### A10:2021 - Server-Side Request Forgery (SSRF)

**Implementation:**

1. **URL Validation** (`app/Http/Requests/Scans/StoreScanRequest.php`)
   - URLs validated before scanning
   - Private IP ranges should be blocked in production

2. **HTTP Client Timeouts** (`app/Jobs/ScanWebsiteJob.php`)
   ```php
   Http::timeout(30)
       ->connectTimeout(15)
   ```

---

## XSS Prevention

### Backend (Blade Templates)

Laravel automatically escapes output in Blade templates:
```blade
{{ $variable }}  <!-- Escaped -->
{!! $variable !!}  <!-- NOT escaped - avoid -->
```

### Frontend (Vue Components)

1. **Text Interpolation** (Safe)
   ```vue
   {{ userInput }}  <!-- Escaped automatically -->
   ```

2. **v-html** (Requires Caution)
   - Used for admin-controlled content (learning resources)
   - Content comes from database, managed by admins
   - Consider adding DOMPurify for defense-in-depth:
   ```vue
   import DOMPurify from 'dompurify';
   const sanitizedContent = computed(() => DOMPurify.sanitize(props.content));
   ```

3. **Affected Files** (Admin-Controlled Content Only)
   - `resources/js/components/resource-blocks/blocks/*.vue`
   - `resources/js/pages/Resources/Show.vue`

---

## Security Checklist for New Features

When adding new features, verify:

- [ ] User input validated via Form Request
- [ ] Authorization checked via Policy
- [ ] Database queries use Eloquent (not raw SQL)
- [ ] Sensitive data not logged
- [ ] Error messages don't expose internal details
- [ ] Rate limiting on sensitive endpoints
- [ ] CSRF token required for state-changing operations
- [ ] Model has `$fillable` defined (no mass assignment vulnerabilities)

---

## Reporting Security Issues

If you discover a security vulnerability, please email security@geosource.ai.

Do not open public issues for security vulnerabilities.
