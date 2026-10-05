# Public Surface Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove unauthenticated maintenance/debug entry points, prevent the shared-hosting front controller from executing arbitrary public PHP files, and make flood counters concurrency-safe.

**Architecture:** The shared-hosting root front controller will serve only normalized, allow-listed static assets and forward every application request to Laravel. Operational maintenance remains available only through Artisan/Filament. Firewall counters use cache-native atomic increment operations with a bounded compatibility fallback.

**Tech Stack:** PHP 8.2, Laravel 11, PHPUnit 11, Laravel cache contracts, PowerShell verification commands.

---

### Task 1: Add public-surface regression checks

**Files:**
- Create: `tests/Security/PublicSurfaceTest.php`
- Create: `tests/TestCase.php`
- Create: `phpunit.xml`

- [ ] **Step 1: Write a failing test for dangerous utility files**

```php
public function test_public_maintenance_scripts_are_absent(): void
{
    foreach (['clear-cache.php', 'cc.php', 'debug_admin.php', 'bbcode_test.php', 'test.php'] as $file) {
        $this->assertFileDoesNotExist(base_path('public/' . $file));
    }
}
```

- [ ] **Step 2: Write a failing test for root diagnostic files**

```php
public function test_root_diagnostic_scripts_are_absent(): void
{
    foreach (['check.php', 'check_debug.php', 'check_login.php', 'reset_password.php'] as $file) {
        $this->assertFileDoesNotExist(base_path($file));
    }
}
```

- [ ] **Step 3: Run the focused test and verify failure**

Run: `php vendor/bin/phpunit tests/Security/PublicSurfaceTest.php`

Expected: FAIL because the listed public and root diagnostic files currently exist.

### Task 2: Remove dangerous utilities and harden the front controller

**Files:**
- Delete: `check.php`
- Delete: `check_debug.php`
- Delete: `check_login.php`
- Delete: `reset_password.php`
- Delete: `public/clear-cache.php`
- Delete: `public/cc.php`
- Delete: `public/debug_admin.php`
- Delete: `public/bbcode_test.php`
- Delete: `public/test.php`
- Modify: `index.php`

- [ ] **Step 1: Delete the unauthenticated utility scripts**

Delete only the files listed above. Maintenance operations must be performed by
authenticated Filament actions or local Artisan commands.

- [ ] **Step 2: Normalize and constrain static asset paths**

Replace direct `file_exists(__DIR__ . '/public' . $relativePath)` dispatch with:

```php
$publicRoot = realpath(__DIR__ . '/public');
$candidate = $publicRoot !== false
    ? realpath($publicRoot . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\'))
    : false;
$allowedStaticExtensions = [
    'css', 'js', 'json', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico',
    'woff', 'woff2', 'ttf', 'eot', 'map',
];

$isPublicFile = $candidate !== false
    && str_starts_with($candidate, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($candidate)
    && in_array(strtolower(pathinfo($candidate, PATHINFO_EXTENSION)), $allowedStaticExtensions, true);
```

Never include `.php` files through the shared-hosting proxy.

- [ ] **Step 3: Serve allow-listed static files with safe headers**

Use the resolved `$candidate`, send the mapped content type, add
`X-Content-Type-Options: nosniff`, stream the file, and return. Forward all other
requests to `public/index.php`.

- [ ] **Step 4: Run the focused regression test**

Run: `php vendor/bin/phpunit tests/Security/PublicSurfaceTest.php`

Expected: PASS.

### Task 3: Make firewall counters atomic

**Files:**
- Modify: `app/Services/FirewallDecisionService.php`
- Test: `tests/Unit/Services/FirewallDecisionServiceTest.php`

- [ ] **Step 1: Write tests for first and subsequent increments**

Use a spy/fake cache store to verify the service calls `add($key, 0, $ttl)` and
then `increment($key)`, returning an integer count.

- [ ] **Step 2: Run the test and verify failure**

Run: `php vendor/bin/phpunit tests/Unit/Services/FirewallDecisionServiceTest.php`

Expected: FAIL because the current implementation performs a non-atomic
read-modify-write sequence.

- [ ] **Step 3: Implement atomic increment with fallback**

```php
private function increment(string $key, int $ttl): int
{
    Cache::add($key, 0, $ttl);

    try {
        return (int) Cache::increment($key);
    } catch (\Throwable $exception) {
        $value = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $value, $ttl);
        return $value;
    }
}
```

Use the same helper for traffic-class counters instead of a second
read-modify-write implementation.

- [ ] **Step 4: Run the focused test**

Run: `php vendor/bin/phpunit tests/Unit/Services/FirewallDecisionServiceTest.php`

Expected: PASS.

### Task 4: Add route-specific throttles

**Files:**
- Modify: `routes/web.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Security/RouteThrottleTest.php`

- [ ] **Step 1: Add route middleware assertions**

Assert that login POST, contact POST, search suggestions, redirector, image
upload/import, and public API read routes include explicit throttle middleware.

- [ ] **Step 2: Apply conservative shared-hosting limits**

- Login POST: `6,1`
- Contact POST: `3,10`
- Search suggestion: `30,1`
- Redirector and legacy search redirects: `60,1`
- Authenticated editor uploads: `10,1`
- Public API reads: `60,1`

- [ ] **Step 3: Run route and feature checks**

Run: `php artisan route:list`

Expected: command succeeds and the protected routes show throttle middleware.

### Task 5: Verify the security hotfix

**Files:**
- Verify all files changed in Tasks 1-4.

- [ ] **Step 1: Run PHP syntax checks**

Run: `Get-ChildItem app,routes,tests -Recurse -Filter '*.php' | ForEach-Object { php -l $_.FullName }`

Expected: every file reports `No syntax errors detected`.

- [ ] **Step 2: Run focused and full tests**

Run: `php vendor/bin/phpunit tests/Security tests/Unit/Services tests/Feature/Security`

Expected: PASS.

- [ ] **Step 3: Verify Laravel bootstrap and cached routes**

Run: `php artisan about`

Run: `php artisan route:cache`

Run: `php artisan route:clear`

Expected: all commands succeed.

- [ ] **Step 4: Review the final diff manually**

Confirm no secrets were added, no repository libraries/generated assets were
modified, and no cache-flush behavior is reachable from public HTTP requests.
