# vBulletin 3.8 Moderation Suite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a permission-aware vBulletin 3.8-style moderation suite while preserving migrated thread/post data, counters, deletion logs, and Cloudflare-compatible inline JavaScript.

**Architecture:** A single `ModerationPermissionService` will decode the existing vBulletin `moderator.permissions` and `permissions2` bitmasks, resolve global (`forumid = -1`) and forum-specific assignments, and authorize every operation. A transactional `ModerationActionService` will implement soft delete, restore, hard delete, move, merge, stick, open, and close while writing `deletionlog`/`moderatorlog` and rebuilding affected forum/thread counters. Frontend and Filament controllers will call these services instead of changing records directly.

**Tech Stack:** Laravel/PHP 8.2+, Eloquent, MySQL, Filament, Blade, Fetch/AJAX, existing vBulletin tables (`moderator`, `moderatorlog`, `deletionlog`, `thread`, `post`, `forum`).

**Spec:** Approved chat design and `docs/07-vbulletin-moderation-permissions.md` produced by Task 1.

## Global Constraints

- `Administrator` (usergroup 6) bypasses moderation bit restrictions and is the only role allowed to hard-delete.
- `Super Moderator`/`Moderator` use the original `moderator.permissions`, `moderator.permissions2`, and `forumid`; `forumid = -1` means global scope.
- Soft-deleted threads/posts use the existing vBulletin value `visible = 2`; pending content remains `visible = 0`; published content is `visible = 1`.
- Soft-deleting a whole thread changes `thread.visible` only and preserves every child post's existing visibility, matching the migrated vBulletin data (5,317 deleted threads vs. 4,144 independently deleted posts). Soft-deleting an individual post changes that post's visibility only.
- Soft delete must preserve rows and write `deletionlog`; hard delete must be a separate explicit operation.
- `deletionlog` is keyed by `(primaryid, type)`; repeated operations must be idempotent and must not overwrite unrelated deletion records.
- Every mutation of multiple rows must use a database transaction and update all affected forum/thread counters.
- Merged/moved posts must preserve author, dateline, post text, parent relation where possible, and attachments.
- Inline Blade JavaScript must use `/* ... */` comments only; never use `//` in `<script>` blocks because Cloudflare Auto-Minify merges lines.
- JavaScript-managed forms with hidden/display-none controls must use `novalidate`.
- No migration or destructive SQL is allowed without an explicit backup/rollback instruction.

---

### Task 1: Publish the permission and Cloudflare reference

**Files:**
- Create: `docs/07-vbulletin-moderation-permissions.md`
- Modify: `docs/06-بنية-قاعدة-البيانات-Database-Schema.md`

**Interfaces:**
- Produces the canonical constants used by `ModerationPermissionService`.

- [ ] **Step 1: Record verified bitfields**

Document the extracted values from `docs/datastore.csv`:

```text
moderatorpermissions:
caneditposts=1, candeleteposts=2, canopenclose=4, caneditthreads=8,
canmanagethreads=16, canmoderateposts=64, canmoderateattachments=128,
canmassmove=256, canmassprune=512, canremoveposts=131072

moderatorpermissions2:
caneditvisitormessages=1, candeletevisitormessages=2,
canremovevisitormessages=4, canmoderatevisitormessages=8
```

- [ ] **Step 2: Document scope and deletion semantics**

Document `forumid=-1`, groups 5/6/7, `visible` values 0/1/2, deletionlog key, and the distinction between `candeleteposts` (soft delete) and `canremoveposts` (remove/hard-delete capability, still restricted to administrators by project policy).

- [ ] **Step 3: Document Cloudflare rules**

Add the exact Auto-Minify warning, the `novalidate` rule, the verified symptom `Unexpected end of input`, and the fix pattern used by the visitor-message and quick-reply forms.

- [ ] **Step 4: Review the reference**

Search the document for `TODO`, `TBD`, `//`, and contradictory `lastpostid` claims; fix all findings before continuing.

---

### Task 2: Build and test the permission resolver

**Files:**
- Create: `app/Services/ModerationPermissionService.php`
- Create: `app/Support/VBulletinModeratorPermissions.php`
- Modify: `app/Policies/ThreadPolicy.php`
- Modify: `app/Policies/PostPolicy.php`
- Create: `tests/Unit/ModerationPermissionServiceTest.php`

**Interfaces:**
- `hasModeratorBit(User $user, int $forumId, int $bit, string $field = 'permissions'): bool`
- `can(User $user, string $ability, ?Thread $thread = null, ?Post $post = null): bool`
- `isAdministrator(User $user): bool`
- `isScopedModerator(User $user, int $forumId): bool`

- [ ] **Step 1: Write failing tests**

Cover administrator bypass, forum-specific moderator scope, global `forumid=-1`, zero-bit moderator denial, `canmassmove`, `canmassprune`, `canmoderateposts`, `canremovevisitormessages`, and a normal-user denial.

- [ ] **Step 2: Implement constants**

Define named constants for the verified bit values above. Do not hard-code unexplained numbers in controllers.

- [ ] **Step 3: Implement user/group resolution**

Load `usergroupid` plus comma-separated `membergroupids`, then resolve moderator rows by `userid` and either `forumid=-1` or the target forum. Administrators bypass; all other roles require the relevant bit.

- [ ] **Step 4: Integrate policies**

Replace owner-or-staff checks for moderation actions with service calls. Preserve ordinary owners' ability to edit their own pending content, but do not grant them moderator actions.

- [ ] **Step 5: Run unit tests**

Run: `php artisan test --filter=ModerationPermissionServiceTest`

Expected: all permission cases pass. If PHP is unavailable locally, run the test on the deployment server before proceeding.

---

### Task 3: Implement audit-log writers and counter rebuilders

**Files:**
- Create: `app/Services/ModerationAuditService.php`
- Create: `app/Services/ForumCounterService.php`
- Create: `app/Services/ThreadCounterService.php`
- Create: `tests/Unit/ModerationAuditServiceTest.php`

**Interfaces:**
- `recordDeletion(int $primaryId, string $type, User $actor, string $reason): void`
- `recordAction(string $action, User $actor, ?Thread $thread, ?Post $post, array $ids = []): void`
- `rebuildForum(int $forumId): void`
- `rebuildThread(int $threadId): void`

- [ ] **Step 1: Add failing tests for deletionlog and moderatorlog payloads**
- [ ] **Step 2: Write transaction-safe audit writers**

Use the migrated columns exactly: `deletionlog.primaryid,type,userid,username,reason,dateline` and `moderatorlog.userid,forumid,threadid,postid,action,type,threadtitle,ipaddress,id1..id5`.

- [ ] **Step 3: Implement counter rebuilds**

Recalculate visible thread counts, visible reply counts, `lastpost`, `lastpostid`, `lastposter`, `lastthread`, and `lastthreadid` from current rows instead of incrementing blindly after complex operations.

- [ ] **Step 4: Test rebuilds against soft-deleted and pending rows**

Pending and soft-deleted rows must not contribute to public counts; published rows must contribute exactly once.

---

### Task 4: Implement soft delete, restore, and administrator hard delete

**Files:**
- Create: `app/Services/ModerationActionService.php`
- Modify: `app/Services/ModerationService.php`
- Modify: `app/Http/Controllers/ModerationController.php`
- Modify: `app/Http/Controllers/Api/ThreadActionController.php`
- Create: `tests/Feature/ModerationDeleteRestoreTest.php`

**Interfaces:**
- `softDeleteThread(Thread $thread, User $actor, string $reason): void`
- `restoreThread(Thread $thread, User $actor): void`
- `hardDeleteThread(Thread $thread, User $actor): void`
- `softDeletePost(Post $post, User $actor, string $reason): void`
- `restorePost(Post $post, User $actor): void`

- [ ] **Step 1: Write failing feature tests**

Verify rejection of pending thread changes it to `visible=2`, keeps thread/posts, creates `deletionlog(type=thread)`, hides it from normal queries, and allows authorized restore. Verify hard delete rejects non-admins.

- [ ] **Step 2: Implement soft-delete transaction**

For a thread, change only `thread.visible` to 2 and preserve all child-post visibility states. For an individual post, change only that post to 2. Never overwrite an existing deletionlog for an unrelated prior deletion.

- [ ] **Step 3: Implement restore transaction**

Restore only the selected thread or post. Restoring a thread must not change any child-post visibility state; this preserves posts that were independently pending or deleted before the thread operation. Rebuild affected counters.

- [ ] **Step 4: Replace pending-thread rejection**

Change `ModerationController::rejectThread` from physical deletion to `softDeleteThread` and require a reason (default Arabic reason only when the UI explicitly supplies it).

- [ ] **Step 5: Isolate hard delete**

Require administrator authorization, explicit confirmation, delete dependent posts/attachments safely, write the audit record before deletion, and return JSON for AJAX callers.

- [ ] **Step 6: Run feature tests and inspect SQL impact**

Run the focused tests and verify no public `visible=2` rows are included in forum/thread/search/sitemap queries.

---

### Task 5: Add moderation list, restore UI, and pending/rejected visibility rules

**Files:**
- Modify: `app/Models/Thread.php`
- Modify: `app/Models/Post.php`
- Modify: `app/Http/Controllers/ForumController.php`
- Modify: `app/Http/Controllers/ThreadController.php`
- Modify: `app/Filament/Resources/PendingThreadResource.php`
- Create: `app/Filament/Resources/DeletedThreadResource.php`
- Create: `app/Filament/Resources/DeletedThreadResource/Pages/ListDeletedThreads.php`
- Create: `resources/views/filament/deleted-content-preview.blade.php`

- [ ] **Step 1: Add explicit scopes**

Add `published()`, `pending()`, `softDeleted()`, and equivalent post scopes. Replace ambiguous `visible()` calls where moderation context is needed.

- [ ] **Step 2: Restrict deleted content**

Only authorized moderators see soft-deleted content, and only within permitted forums; public, SEO, search, home, and sitemap queries exclude it.

- [ ] **Step 3: Add Filament deleted-content resource**

Provide preview, restore, and administrator-only hard-delete actions with reason and confirmation.

- [ ] **Step 4: Add frontend restore controls**

Expose restore only to authorized moderators in the relevant forum/thread context; use existing JSON/CSRF patterns and Cloudflare-safe inline comments.

---

### Task 6: Implement move and bulk move

**Files:**
- Modify: `app/Services/ModerationActionService.php`
- Modify: `app/Http/Controllers/Api/ThreadActionController.php`
- Create: `app/Http/Controllers/ModerationBulkController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/forum/show.blade.php`
- Modify: `resources/views/thread/show.blade.php`
- Modify: `app/Filament/Resources/ThreadResource.php`
- Create: `tests/Feature/ModerationMoveTest.php`

- [ ] **Step 1: Test a single move**

Verify source/target permission checks, `forumid` change, source/target counter rebuild, and moderatorlog action.

- [ ] **Step 2: Implement transactional single move**

Authorize against source and target forums; reject inactive/link forums where moving is invalid; rebuild both forums.

- [ ] **Step 3: Implement bulk move**

Accept a validated array of thread IDs and one target forum, authorize every source/target pair, process in one transaction, and return per-ID failures without silently moving unauthorized rows.

- [ ] **Step 4: Add frontend selection UI**

Use `novalidate`, `/* */` comments only, CSRF headers, confirmation, and a JSON response. Never use a hidden required field as the only source of selection validity.

- [ ] **Step 5: Add Filament bulk action**

Add target forum selection, authorization, confirmation, and notification with moved/failed counts.

---

### Task 7: Implement stick, unstick, open, and close

**Files:**
- Modify: `app/Services/ModerationActionService.php`
- Modify: `app/Http/Controllers/ModerationController.php`
- Modify: `resources/views/forum/show.blade.php`
- Modify: `resources/views/thread/show.blade.php`
- Modify: `app/Filament/Resources/ThreadResource.php`
- Create: `tests/Feature/ModerationStateActionsTest.php`

- [ ] **Step 1: Add permission tests**
- [ ] **Step 2: Implement idempotent state actions**

Set `sticky` or `open` to 0/1, record action, and return a stable JSON response. Do not alter visibility or counters for these state-only operations.

- [ ] **Step 3: Add frontend and Filament actions**

Render only when `canopenclose`/`canmanagethreads` permits the action.

---

### Task 8: Implement post move/merge and thread merge

**Files:**
- Modify: `app/Services/ModerationActionService.php`
- Create: `app/Http/Controllers/ModerationMergeController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/thread/show.blade.php`
- Modify: `resources/views/forum/show.blade.php`
- Modify: `app/Filament/Resources/ThreadResource.php`
- Create: `tests/Feature/ModerationMergeTest.php`

- [ ] **Step 1: Write failing merge tests**

Cover destination selection, self-merge rejection, cross-forum permissions, published/pending/soft-deleted posts, attachments, reply counts, last-post fields, and rollback on failure.

- [ ] **Step 2: Implement post move/merge**

Update `post.threadid`, preserve post identity/content/author/date/attachments, normalize `parentid` only when the parent is outside the destination, then rebuild both threads.

- [ ] **Step 3: Implement thread merge**

Move posts into the primary thread, update the secondary thread through the approved soft-delete/audit path, rebuild forums and primary thread, and log primary/secondary IDs in `id1`/`id2`.

- [ ] **Step 4: Add UI for one or multiple sources**

Provide a confirmation showing source titles, destination title, post counts, and the exact resulting action. Do not offer hard deletion from a normal moderator UI.

---

### Task 9: Complete Filament moderation workspace

**Files:**
- Modify: `app/Filament/Resources/ThreadResource.php`
- Modify: `app/Filament/Resources/PostResource.php`
- Modify: `app/Filament/Resources/PendingThreadResource.php`
- Modify: `app/Filament/Resources/PendingPostResource.php`
- Create: `app/Filament/Resources/ModerationLogResource.php`
- Create: `app/Filament/Resources/DeletedPostResource.php`

- [ ] **Step 1: Add permission-aware actions**
- [ ] **Step 2: Add bulk move/delete/restore/state actions**
- [ ] **Step 3: Add previews and audit links**
- [ ] **Step 4: Verify administrator-only hard delete**

---

### Task 10: End-to-end verification and deployment package

**Files:**
- Modify: `docs/03-قائمة-الفحص-Checklist.md`
- Create: `docs/08-دليل-اختبار-الإشراف.md`

- [ ] **Step 1: Run syntax and tests**

```bash
php -l app/Services/ModerationPermissionService.php
php artisan test --filter=Moderation
php artisan route:list --path=moderation
```

- [ ] **Step 2: Verify database invariants**

Confirm no public query returns `visible=2`, counters match rebuild calculations, deletionlog keys remain unique, and every moderator action has an audit row.

- [ ] **Step 3: Verify Cloudflare-safe JavaScript**

Search Blade scripts for `//`, run browser checks for submit interception, and test with HTML Auto-Minify enabled.

- [ ] **Step 4: Prepare deployment instructions**

Provide changed-file list, backup commands, upload order, `php artisan optimize:clear`, cache purge guidance for affected URLs, rollback steps, and a manual test matrix for admin, super moderator, scoped moderator, and normal user.

---
