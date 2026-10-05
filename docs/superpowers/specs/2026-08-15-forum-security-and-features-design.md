# Forum Security and Feature Hardening Design

Date: 2026-08-15

## Objective

Improve the Laravel forum's security, traffic resilience, sitemap correctness,
administration workflow, and permission-aware posting behavior while preserving
the existing vBulletin 3.8 database and legacy URL compatibility.

The interrupted Codex Security discovery artifacts are treated as leads, not as
validated vulnerabilities. Every production change must be confirmed against
the current source before implementation.

## Scope

### Phase 1: Request and resource protection

- Remove or restrict public maintenance and diagnostic entry points that expose
  logs, credentials, cache controls, or password-reset behavior.
- Make rate counters atomic where the configured cache driver supports it, with
  a conservative fallback for shared hosting.
- Apply route-specific throttles to expensive public endpoints such as login,
  search, contact, redirects, image import, and API reads.
- Keep verified search-engine access compatible with SEO, but do not exempt
  expensive or mutating requests solely because of a user-agent string.
- Protect outbound URL fetching against private-network destinations, redirect
  destination drift, disabled TLS verification, oversized responses, and image
  decompression bombs.
- Do not clear application caches during an attack. Use scheduled cleanup only
  for bounded temporary files and stale operational data.

### Phase 2: Sitemap and legacy SEO

- Generate sitemap entries only for forums and threads that an anonymous user
  is allowed to view.
- Keep the sitemap index, forum sitemap, and paged thread sitemaps internally
  consistent and return XML content types with valid absolute URLs.
- Reject out-of-range sitemap pages instead of returning misleading empty maps.
- Invalidate only sitemap-specific keys after relevant content changes.
- Preserve canonical Laravel URLs and all existing vBulletin 3.8 permanent
  redirects, including standard query URLs and archive URLs.
- Keep restricted and user-profile pages out of public sitemap output.

### Phase 3: Administration and posting permissions

- Retain the existing Filament ThreadResource as the primary admin workflow.
- Ensure editing a thread updates its first post safely and invalidates only the
  related parsed-content, thread, and sitemap caches.
- Restrict move, delete, visibility, lock, and bulk actions to explicit admin or
  moderator permissions; a topic author must not receive global moderation
  powers over other users' replies.
- Add a quick-reply endpoint and form for authenticated users whose vBulletin
  group and forum permission bits allow replying.
- Hide the form when posting is not allowed and re-check authorization on the
  server so hidden UI cannot be bypassed.
- Reject replies to closed, hidden, or inaccessible threads and apply CSRF,
  validation, flood control, and per-user/IP throttling.
- Update thread reply counters and last-post metadata transactionally after a
  successful reply.

## Permission Model

Authorization decisions must be centralized in policies or a dedicated forum
permission service. Views and controllers must call that shared decision rather
than duplicate user-group lists.

Required decisions:

- `viewForum(user, forum)`
- `viewThread(user, thread)`
- `reply(user, thread)`
- `editThread(user, thread)`
- `moderateThread(user, thread)`
- `editPost(user, post)`

Administrator and moderator group mappings must be configured, not scattered as
hard-coded IDs. Existing vBulletin permission bits remain the source of truth
where the legacy tables provide them.

## Data Flow

1. Traffic protection classifies and rate-limits the request before expensive
   controllers and Blade rendering run.
2. The controller loads the minimum forum/thread data needed for authorization.
3. A shared permission decision permits or rejects the operation.
4. Mutating requests validate input and execute related database updates in one
   transaction.
5. Only narrowly related cache keys are invalidated after commit.
6. Sitemap generation reads the same anonymous visibility rules used by public
   pages.

## Failure Behavior

- Rate-limited requests return HTTP 429 with `Retry-After`.
- Unauthorized forum access returns HTTP 403 and must not be cached publicly.
- Missing or out-of-range sitemap pages return HTTP 404.
- Temporary cache failures must not disable authorization.
- Outbound fetch failures return bounded validation errors without exposing
  internal addresses, filesystem paths, or stack traces.
- Attack survival mode may degrade optional features, but public caches are not
  purged and authenticated write operations remain permission-checked.

## Verification

- PHP syntax checks for every changed PHP file.
- Laravel route listing and configuration/cache compatibility checks.
- Focused tests for permission matrices: guest, normal member, restricted group,
  moderator, and administrator.
- Tests for closed/hidden threads, forum ACLs, quick-reply throttling, and reply
  metadata updates.
- Sitemap XML parsing tests, anonymous visibility tests, pagination bounds, and
  legacy redirect regression tests.
- Security tests for private-IP URL fetches, redirect drift, TLS verification,
  response-size limits, and decompression limits.
- Review changed production code with the repository clean-code, security, and
  test quality gates before completion.

## Delivery Order

1. Request protection and removal of dangerous public utilities.
2. Sitemap visibility and pagination correctness.
3. Centralized permission decisions.
4. Filament thread administration hardening.
5. Permission-aware quick reply.
6. Regression and performance verification.

## Out of Scope

- Replacing the vBulletin database schema.
- Scanning or modifying `vendor`, `node_modules`, generated Filament assets, or
  bundled Livewire files.
- Rebuilding the WordPress homepage.
- Enforcing a fixed global visitor ceiling such as 5,000 concurrent visitors.
- Clearing all application caches automatically during an attack.
