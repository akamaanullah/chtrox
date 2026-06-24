# ChatRox — Complete Code Audit Report

**Audit Date:** 2026-06-22  
**Scope:** Full project — PHP backend, WebSocket server, models, helpers, middleware  

---

## 🔴 Critical Bugs (Will Cause Incorrect Behavior or Security Issues)

### 1. API Controllers — `jsonResponse()` Does NOT Stop Execution

**Files affected:** Every API controller (`MessageController`, `ChannelController`, `ProfileController`, etc.)

`jsonResponse()` in the base `Controller` is declared as `never` and calls `exit`. However, many API controller methods call it **without `return`** before continuing on the next line — meaning when the method is used as a logic guard it actually _does_ stop, but the pattern is fragile and misleading. 

More critically: **In `Admin\AuthController::login()`**, the guard checks:

```php
if ($username === '' || $password === '') {
    Session::setFlash('error', '...');
    $this->redirect('/admin/login');
    // ← no return here — but redirect() is `never`, so OK
}
```

BUT in the **Front `MessageController`**, `react()`, `send()`, `edit()`, `delete()`, `pin()`, `markRead()`, `forward()` — after `$this->jsonResponse(...)` guards inside conditions, PHP execution continues because `jsonResponse()` returns `never` and does `exit`. The pattern is correct mechanically but is **not consistent across files** — some places call `jsonResponse()` mid-function without `return`, which only works because the base `exit`s. If the base class `jsonResponse` is ever refactored (e.g., for testing), these code paths will break silently.

**Concrete actual bug:** In `ProfileController::updateTheme()` (line 14):
```php
$userId = $user['user_id'] ?? 0;
```
The session stores the key `'id'` (not `'user_id'`), so `$userId` is **always 0** — the auth check at line 16 always redirects with 401 Unauthorized. **Theme updates are completely broken.**

---

### 2. `ProfileController::update()` — Same Wrong Session Key

**File:** [`ProfileController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/Api/ProfileController.php#L49)

```php
$userId = $user['user_id'] ?? 0;  // ← BUG: should be $user['id']
```

The session (set in `AuthController::authenticate()`) stores user id as `'id'`, not `'user_id'`. This causes `$userId === 0`, failing the auth check and returning 401 on every profile update attempt. **Profile updates are completely broken.**

---

### 3. Registration — Logo Upload Has No MIME Validation (Security Risk)

**File:** [`AuthController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/AuthController.php#L218-L230)

```php
$ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
```

Extension-only check is easily bypassed by renaming any file (e.g. `evil.php.png`). The actual `FileUploadPolicy::detectMime()` helper already exists but is NOT used here. Additionally, **SVG is allowed** — SVGs can contain JavaScript/`<script>` tags and are a stored XSS vector.

---

### 4. Registration — Error Leaks Full Exception Message to User

**File:** [`AuthController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/AuthController.php#L418)

```php
'error' => 'Registration failed: ' . $e->getMessage(),
```

This exposes raw database error messages (table names, column constraints, etc.) to the end-user in production. `APP_DEBUG` is not consulted here — it only controls PHP's `display_errors`. The registration error handler should show a generic message in production.

---

### 5. `FileUploader` — Quota Check Is Done Twice With The Wrong Value

**File:** [`FileUploader.php`](file:///d:/xampp/htdocs/chatrox/app/Core/FileUploader.php#L35-L148)

The first quota check at line 35 uses `$incomingSize` (the raw unprocessed client-reported size). After image processing, the actual file size is `$fileSize` (from `filesize($processedTmp)`). The second check at line 138 correctly uses `$fileSize`. But the first check (before processing) can mistakenly block uploads when the processed WebP is actually smaller than the quota.

More importantly: **if a file already exists in `file_objects` (duplicate sha256), the second quota check is entirely skipped** (lines 132–136) — so a user can associate an existing large file to their workspace without any quota enforcement.

---

### 6. WebSocket `authenticateToken()` — No Workspace Scope Filtering

**File:** [`ChatServer.php`](file:///d:/xampp/htdocs/chatrox/app/WebSocket/ChatServer.php#L180)

```sql
JOIN workspace_members wm ON wm.user_id = u.id AND wm.left_at IS NULL AND wm.status = 'active'
```

If a user belongs to **multiple workspaces**, this query can return a random workspace membership — leading to the WebSocket connection operating in the wrong workspace context. The session token doesn't encode workspace context, so workspace membership selection is non-deterministic.

---

### 7. `DmsConversation::getOrCreateConversationId()` — Race Condition (No Transaction Protection)

**File:** [`DmsConversation.php`](file:///d:/xampp/htdocs/chatrox/app/Models/DmsConversation.php#L945-L955)

The SELECT for existing conversation and the subsequent INSERT are not atomic. Between the SELECT (line 951) and the INSERT (line 964), another concurrent request can insert the same DM conversation, causing a **duplicate conversation** to be created. There is no `UNIQUE` constraint guard for the `dm_hash` used here.

**Fix:** Use `INSERT IGNORE` + then SELECT, or ensure `dm_hash` has a UNIQUE index.

---

### 8. `ChannelConversation::getReplySnippet()` — Raw `strip_tags()` On Body

**File:** [`ChannelConversation.php`](file:///d:/xampp/htdocs/chatrox/app/Models/ChannelConversation.php#L452)

```php
$text = strip_tags($row['body']);
```

Unlike `DmsConversation::getReplySnippet()` (which uses `bodyToPlainText()` with proper HTML entity decoding), this raw `strip_tags` does **not decode HTML entities**. So a reply snippet to a channel message with `&amp;` or `&lt;` in the body will display raw HTML entities in the UI.

---

## 🟠 High Priority Issues (Logic Bugs / Data Integrity)

### 9. `FilesController::download()` — File ID Not Cast to Integer

**File:** [`FilesController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/FilesController.php#L20-L21)

```php
$stmt = $db->prepare('SELECT * FROM files WHERE id = ?');
$stmt->execute([$id]);  // $id is a raw string from the URL
```

The route parameter `{id}` accepts `[a-zA-Z0-9_\-\.]+`. A value like `1.0` would still be passed as a string to `execute()`. While PDO will handle the cast, it's cleaner and safer to explicitly cast: `(int)$id`. Without validation, if someone passes `1e5` it may behave unexpectedly.

---

### 10. `MessageController::send()` — `$state` Variable Used Before Defined

**File:** [`MessageController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/Api/MessageController.php#L237)

```php
'read_status' => $state ?? 'sent'
```

`$state` is only defined inside the `if ($conversation['type'] === 'dm')` block (line 156). For channel messages, `$state` is **undefined**, and the `?? 'sent'` fallback only works because PHP uses null coalescing — but this generates a PHP notice/warning in strict mode. Refactor: initialize `$state = 'sent'` before the `if` block.

---

### 11. `ChannelController::join()` — Member Count Not Updated

**File:** [`ChannelController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/Api/ChannelController.php#L157-L196)

When a user joins a channel, `channels.member_count` is never incremented. Similarly in `leave()`, it's never decremented. The `member_count` field is denormalized and must be maintained on every join/leave event, but both operations are missing the UPDATE statement.

---

### 12. `ChannelController::leave()` — Owner Can Leave Their Own Channel

**File:** [`ChannelController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/Api/ChannelController.php#L199-L259)

There is no check preventing a channel owner from leaving a channel they created. If the owner leaves, the channel becomes orphaned with no admin. A guard should be added: owners must transfer ownership or delete the channel, not simply leave.

---

### 13. `MessageController::markRead()` — Missing `return` After Early Exit

**File:** [`MessageController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/Api/MessageController.php#L862-L864)

```php
if ($lastReadMessageId === null) {
    $this->jsonResponse(['success' => true, 'message' => 'No messages to mark read']);
}
// execution CONTINUES even though jsonResponse exits — but only because it calls exit()
// If refactored, this is a silent bug
```

Technically correct because `jsonResponse` calls `exit`, but it's missing `return` making it an implicit assumption.

---

### 14. `Session::verifyCsrf()` — CSRF Token Is Single-Use But API Routes Are Exempt

**File:** [`Session.php`](file:///d:/xampp/htdocs/chatrox/app/Core/Session.php#L134-L136)

The CSRF token is regenerated (deleted) after each successful POST verification. This means a page with multiple form submissions (or AJAX calls) will fail after the first one. The CSRF verification is **only applied to login/register** forms — all API routes (`/api/*`) do **not** verify CSRF tokens. This means the API endpoints are vulnerable to CSRF attacks from malicious websites. The `AuthMiddleware` only validates the session token, not CSRF.

**Impact:** An attacker can craft a cross-origin form that posts to `/api/messages/send` or `/api/channels/create` because no CSRF check exists on API routes.

---

### 15. `WorkspaceSearch::searchFiles()` — No Access Control

**File:** [`WorkspaceSearch.php`](file:///d:/xampp/htdocs/chatrox/app/Models/WorkspaceSearch.php#L219-L251)

```sql
WHERE f.workspace_id = ? AND f.deleted_at IS NULL
```

This returns **all files** in the workspace regardless of whether the searching member has access to the conversations where those files were shared. A member could discover files from private channels they aren't a member of via the search. The `searchMessages` method correctly filters by conversation membership, but `searchFiles` does not.

---

## 🟡 Medium Priority Issues (Quality / Robustness)

### 16. `DotEnv` — GIPHY API Key Hardcoded in `.env`

**File:** [`.env`](file:///d:/xampp/htdocs/chatrox/.env#L12)

```
GIPHY_API_KEY=aLfTxEAuXRNEfbfdLcC7EgTNNtId1l2L
```

A real API key is committed in the `.env` file. If this is pushed to a public git repository, the key is exposed. The `.env` file should be in `.gitignore` and `.env.example` should have a placeholder.

---

### 17. `Database.php` — Error Message Swallows Original Exception Details

**File:** [`Database.php`](file:///d:/xampp/htdocs/chatrox/app/Core/Database.php#L41-L43)

```php
throw new \RuntimeException('Database connection failed.', 0, $e);
```

The original `$e` is passed as previous exception, which is good. However the `SELECT 1` ping (line 18) silently swallows the PDO exception and reconnects — this could loop or mask persistent connection errors under high load. The ping approach works for WebSocket but adds ~1ms latency per HTTP request.

---

### 18. `FileUploader` — GD Image Processing Has No `imagegif()` Support

**File:** [`FileUploader.php`](file:///d:/xampp/htdocs/chatrox/app/Core/FileUploader.php#L57-L108)

GIF files are accepted (extension/MIME `image/gif`) but there's no `imagecreatefromgif()` or `imagegif()` in the GD processing block. This means animated GIFs will lose animation and be saved as static WebP or JPEG. Since `srcImg` will be `null` for GIF inputs, they fall through to `move_uploaded_file` without processing — which is actually the correct behavior, but may confuse maintainers.

---

### 19. `Router` — URL Parameter Regex Too Restrictive

**File:** [`Router.php`](file:///d:/xampp/htdocs/chatrox/app/Core/Router.php#L37)

```php
$routeRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_\-\.]+)', $route);
```

This pattern does not allow `@` or `+` in URL segments. While these are edge cases, usernames or channel slugs containing special characters (valid URL-encoded chars) would not be matched.

---

### 20. `ErrorHandler` — Fatal Error Handler Uses Wrong Fallback Template

**File:** [`ErrorHandler.php`](file:///d:/xampp/htdocs/chatrox/app/Core/ErrorHandler.php#L83-L84)

```php
$codeTemplate = VIEW_DIR . '/errors/' . $code . '.php';
$fallbackTemplate = VIEW_DIR . '/errors/404.php';
```

When a 500 error occurs and there's no `500.php` view, it falls back to `404.php` — a misleading 404 error page being shown for a 500 server error. The fallback should be generic, or a `500.php` template should exist.

---

### 21. `MessageController` — Large `php://input` Reads Are Unbounded

All API controllers do:
```php
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
```

There is no size limit on `php://input`. A malicious client could POST a gigantic body, consuming server memory. Should be limited: check `$_SERVER['CONTENT_LENGTH']` or use `stream_get_contents` with a max length.

---

### 22. `DmsConversation::sidebarDisplayItems()` — Correlated Subqueries Per Row (N+1 Performance)

**File:** [`DmsConversation.php`](file:///d:/xampp/htdocs/chatrox/app/Models/DmsConversation.php#L77-L151)

The query contains **7 correlated subqueries** per DM conversation row (last_message_id, last_message_sender_id, last_message_body, etc.). For a workspace with 50 DM conversations, this executes hundreds of sub-queries. These should be refactored into a single JOIN with `GROUP BY` and window functions, or split into 2 queries with application-side joining.

---

### 23. `ChannelConversation::applyChannelReadReceipts()` — Hardcoded External URL

**File:** [`ChannelConversation.php`](file:///d:/xampp/htdocs/chatrox/app/Models/ChannelConversation.php#L576)

```php
'avatar' => $user['avatar_path'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150'
```

This hardcoded Unsplash fallback avatar URL appears in **10+ places** across models. It should be a shared constant (e.g., `DEFAULT_AVATAR_URL`) defined once in config to avoid maintenance headaches.

---

### 24. `UserSession::create()` — IP Address Not Validated

**File:** [`UserSession.php`](file:///d:/xampp/htdocs/chatrox/app/Models/UserSession.php#L18)

```php
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
```

`REMOTE_ADDR` can be a proxy IP and doesn't consider `X-Forwarded-For`. More importantly, it's stored raw without any validation — invalid IP addresses can be stored. Should use `filter_var($ip, FILTER_VALIDATE_IP)`.

---

### 25. `AuthController` (Admin) — Admin Login Not Checking `APP_DEBUG` in Error Display

**File:** [`Admin/AuthController.php`](file:///d:/xampp/htdocs/chatrox/app/Controllers/Admin/AuthController.php#L49-L51)

The admin login controller uses `Session::setFlash + redirect` pattern which is clean. However, since the flash message is passed directly from the validation layer, detailed error info (like "Access denied. You do not have administrator permissions for any workspace.") might leak information about account existence.

---

## 🟢 Minor Issues / Code Quality

### 26. `Session::destroy()` Not Used for Full Logout

**File:** [`Session.php`](file:///d:/xampp/htdocs/chatrox/app/Core/Session.php#L82-L85)

`Session::logout()` only `unset`s the user key. `Session::destroy()` (which calls `session_destroy()`) is never called anywhere. The PHP session file persists on disk indefinitely with only the user key removed. This means session IDs can be reused and `$_SESSION` data (including admin data) is not fully cleared.

**Recommendation:** Call `Session::destroy()` on logout and redirect to force a new session.

---

### 27. `config.php` — `APP_DEBUG=true` in `.env`

The `.env` has `APP_DEBUG=true`. This should be `false` in any non-local environment. While the error handler respects this flag, debug mode is shipping as default.

---

### 28. `FileUploadPolicy` — All File Types Allowed (Including Executable Scripts)

**File:** [`FileUploadPolicy.php`](file:///d:/xampp/htdocs/chatrox/app/Helpers/FileUploadPolicy.php#L7-L10)

The comment says:
> All file extensions are allowed (production teams share .php, .sql, .html, etc.)

Allowing `.php`, `.phtml`, `.php5`, etc. uploads — even though they're stored in `/storage/` and not directly web-accessible by default — is dangerous. If the web server is misconfigured or the storage directory is ever moved to a public path, uploaded PHP files could be executed.

**Recommendation:** At minimum, add a blocklist for executable extensions (`.php`, `.phtml`, `.sh`, `.pl`, `.py`, `.asp`, etc.).

---

## Summary Table

| # | Severity | File | Issue |
|---|----------|------|-------|
| 1 | 🔴 Critical | ProfileController | Wrong session key `user_id` vs `id` — theme update always 401 |
| 2 | 🔴 Critical | ProfileController | Wrong session key `user_id` vs `id` — profile update always 401 |
| 3 | 🔴 Critical | AuthController (Front) | Logo upload: extension-only check, SVG allows XSS |
| 4 | 🔴 Critical | AuthController (Front) | Registration error leaks raw exception message |
| 5 | 🔴 Critical | FileUploader | Quota bypassed on duplicate file (sha256 dedup path) |
| 6 | 🔴 Critical | ChatServer | Multi-workspace: random workspace selected on WS connect |
| 7 | 🔴 Critical | DmsConversation | Race condition in DM creation (no unique constraint guard) |
| 8 | 🔴 Critical | ChannelConversation | `getReplySnippet()` uses raw `strip_tags()` — HTML entities not decoded |
| 9 | 🟠 High | FilesController | File ID not cast to int (minor, but bad practice) |
| 10 | 🟠 High | MessageController | `$state` used before defined for channel messages |
| 11 | 🟠 High | ChannelController | `member_count` never updated on join/leave |
| 12 | 🟠 High | ChannelController | Channel owner can abandon their channel |
| 13 | 🟠 High | Session | CSRF only on login/register — all API routes CSRF-unprotected |
| 14 | 🟠 High | WorkspaceSearch | File search ignores conversation access control |
| 15 | 🟡 Medium | .env | Real Giphy API key committed to source |
| 16 | 🟡 Medium | Database | Ping on every HTTP request adds latency |
| 17 | 🟡 Medium | FileUploader | No GIF animation support noted/documented |
| 18 | 🟡 Medium | Router | URL param regex excludes valid chars |
| 19 | 🟡 Medium | ErrorHandler | 500 falls back to 404 template |
| 20 | 🟡 Medium | All API controllers | Unbounded `php://input` reads |
| 21 | 🟡 Medium | DmsConversation | N+1 correlated subqueries in sidebar query |
| 22 | 🟡 Medium | All models | Hardcoded Unsplash URL repeated 10+ times |
| 23 | 🟡 Medium | UserSession | Raw `REMOTE_ADDR` without validation |
| 24 | 🟢 Minor | Session | `destroy()` never called — session files persist |
| 25 | 🟢 Minor | .env | `APP_DEBUG=true` in default config |
| 26 | 🟢 Minor | FileUploadPolicy | Executable script uploads allowed |

---

## Top Fixes to Apply First

1. **Fix `user_id` → `id` session key in `ProfileController`** (both `update` and `updateTheme`)
2. **Add `return` after all early `jsonResponse()`/`redirect()` calls** for code clarity
3. **Add CSRF protection headers or token verification to all API routes**
4. **Fix logo upload: use `detectMime()` + block SVG or sanitize it server-side**
5. **Initialize `$state = 'sent'` before the DM conditional in `MessageController::send()`**
6. **Add `UPDATE channels SET member_count = member_count ± 1` to join/leave operations**
7. **Add `UNIQUE` index on `conversations.dm_hash` to prevent race condition duplicates**
