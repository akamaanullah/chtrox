# ChatRox Dynamic Backend — Implementation Plan

Make the entire ChatRox project fully dynamic, wiring all 27 database tables + 2 views + 6 triggers to the existing MVC frontend.

---

## Key Decisions

| Decision | Answer |
|----------|--------|
| **Workspace model** | No default workspace. Registration creates workspace first, then owner creates/invites users. |
| **User ↔ Workspace** | **One user = one workspace only.** Ek account sirf ek company/workspace se linked ho sakta hai — workspace switcher nahi chahiye. |
| **Real-time messaging** | WebSocket via Ratchet PHP for live message delivery, typing indicators, presence updates. |
| **File uploads** | Local `storage/uploads/` with **image compression** (GD/Imagick). Track `size_bytes` pre/post compression. |
| **Seed data** | 1 workspace, 7 users, 5 channels, 50+ messages, 30 days analytics — via `php database/seed.php`. |

---

## Phase 1 — Core Infrastructure

### [MODIFY] [Database.php](file:///d:/xampp/htdocs/chatrox/app/Core/Database.php)
- Add `ATTR_EMULATE_PREPARES => false` for true prepared statements.

### [MODIFY] [Model.php](file:///d:/xampp/htdocs/chatrox/app/Core/Model.php)
- Each model declares `protected static string $table`
- Add CRUD helpers: `findAll()`, `findById()`, `findOne()`, `count()`, `create()`, `update()`, `delete()`
- Add `query()` and `buildWhere()` for prepared statement query building

### [MODIFY] [Session.php](file:///d:/xampp/htdocs/chatrox/app/Core/Session.php)
- `login()` stores full user context: `user_id`, `workspace_id`, `workspace_member_id`, `username`, `first_name`, `last_name`, `avatar_path`, `email`, `role`
- `adminLogin()` stores same + validates role is `owner` or `admin`
- Add convenience: `userId()`, `workspaceId()`, `workspaceMemberId()`, `username()`

### [NEW] [Auth.php](file:///d:/xampp/htdocs/chatrox/app/Core/Auth.php)
- Workspace-scoped helper wrapping Session
- `user()`, `id()`, `workspaceId()`, `memberId()`, `isAdmin()`, `check()`

---

## Phase 2 — Authentication System

### [MODIFY] [Front AuthController](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/AuthController.php)

**Login**: `User::findByLogin()` → `password_verify()` → `WorkspaceMember::findForUser()` (max 1 row — user belongs to one workspace only) → `UserPresence::setOnline()` → `Session::login()`

**Register** (POST `/register`): 3-step form creates:
1. `workspaces` + `workspace_addresses` (Step 1 & 2)
2. `users` with `password_hash($pw, PASSWORD_BCRYPT)` (Step 3)
3. `workspace_members` with `role = 'owner'`
4. `user_preferences` + `user_presence`
5. Auto-creates `#general` channel + conversation

**Logout**: `UserPresence::setOffline()` → `Session::logout()`

### [MODIFY] [Admin AuthController](file:///d:/xampp/htdocs/chatrox/app/Controllers/Admin/AuthController.php)
- Same `password_verify()` flow + validates `workspace_members.role IN ('owner', 'admin')`

### New Auth Models
| File | Table | Key Methods |
|------|-------|-------------|
| [NEW] `User.php` | `users` | `findByLogin()`, `findByEmail()`, `createWithHash()` |
| [NEW] `Workspace.php` | `workspaces` | `createWithAddress()` |
| [NEW] `WorkspaceMember.php` | `workspace_members` | `findForUser()` (single workspace), `createOwner()` |
| [NEW] `UserPresence.php` | `user_presence` | `setOnline()`, `setOffline()` |

---

## Phase 3 — Front Models (Hardcoded → DB)

> [!IMPORTANT]
> Every model method keeps its **exact same return format** so views need zero changes. Only the data source changes.

| Model | Method | DB Source |
|-------|--------|-----------|
| **HomeDashboard** | `greeting()` | `users` via session |
| | `stats()` | COUNT on `channels`, `workspace_members`, `messages`, `files` |
| | `searchTags()` | `channels.name` for workspace |
| | `worldClocks()` | `user_preferences.favorite_timezones` JSON |
| | `announcements()` | `announcements` WHERE workspace + date range |
| **DmsConversation** | `sidebarDisplayItems()` | `conversations(dm/group_dm)` + `conversation_participants` JOIN `v_people_directory` |
| | `resolveUser()` | `conversation_participants` JOIN `users` |
| | `messages()` | `messages` WHERE conversation_id + sender JOIN |
| **ChannelConversation** | `sidebarDisplayItems()` | `channels` JOIN `channel_members` WHERE current member |
| | `resolveChannel()` | `channels` WHERE id/slug |
| | `messages()` | `messages` via `conversations.channel_id` |
| **People** | `directory()` | `v_people_directory` WHERE workspace_id |
| | `count()` | COUNT on view |
| **ActivityFeed** | `items()` | `notifications` WHERE recipient = current member |
| **WorkspaceFile** | `all()` | `v_workspace_files` WHERE workspace_id |
| **BrowseChannel** | `all()` | `channels` WHERE public + active |
| **Navigation** | `sidebarTabs()` | Static tabs + dynamic badges from `conversation_read_cursors` |

---

## Phase 4 — Admin Models

| File | Table(s) | Key Methods |
|------|----------|-------------|
| [MODIFY] `AdminOverview.php` | Multiple | `stats()` from real COUNT queries, `members()` from `v_people_directory`, `channels()` from `channels`, `activity()` from `audit_logs` |
| [NEW] `Announcement.php` | `announcements` | `forWorkspace()`, `activeForWorkspace()`, `createAnnouncement()`, `updateAnnouncement()`, `deleteAnnouncement()` |
| [NEW] `AuditLog.php` | `audit_logs` | `forWorkspace()`, `log()` — write audit entries on every admin action |
| [NEW] `AnalyticsDashboard.php` | `analytics_*` | `dailyMessages()`, `dailyActiveUsers()`, `topChannels()`, `hourlyActivity()` |

---

## Phase 5 — API Endpoints + WebSocket

### POST API Routes ([index.php](file:///d:/xampp/htdocs/chatrox/public/index.php))

```php
// Auth
$router->post('/register',                    'Front\AuthController@processRegistration');

// Messages API
$router->post('/api/messages/send',            'Front\Api\MessageController@send');
$router->post('/api/messages/react',           'Front\Api\MessageController@react');
$router->post('/api/messages/delete',          'Front\Api\MessageController@delete');
$router->post('/api/messages/read',            'Front\Api\MessageController@markRead');

// Channels API
$router->post('/api/channels/create',          'Front\Api\ChannelController@create');
$router->post('/api/channels/join',            'Front\Api\ChannelController@join');
$router->post('/api/channels/leave',           'Front\Api\ChannelController@leave');

// Files API (with compression)
$router->post('/api/files/upload',             'Front\Api\FileController@upload');

// Profile API
$router->post('/api/profile/update',           'Front\Api\ProfileController@update');
$router->post('/api/profile/theme',            'Front\Api\ProfileController@updateTheme');

// Admin API
$router->post('/admin/announcements/create',   'Admin\AnnouncementsController@create');
$router->post('/admin/announcements/update',   'Admin\AnnouncementsController@update');
$router->post('/admin/announcements/delete',   'Admin\AnnouncementsController@delete');
$router->post('/admin/members/invite',         'Admin\MembersController@invite');
$router->post('/admin/members/remove',         'Admin\MembersController@remove');
$router->post('/admin/members/role',           'Admin\MembersController@updateRole');
$router->post('/admin/channels/archive',       'Admin\ChannelsController@archive');
$router->post('/admin/files/delete',           'Admin\FilesController@delete');
```

### File Upload with Compression

[NEW] `App\Core\FileUploader.php`:
- Images (jpg/png/webp): Compress via GD to 80% quality, resize if >2048px
- Convert PNG screenshots → WebP for ~60% size savings
- Documents (pdf/zip): Store as-is, track original `size_bytes`
- Store to `storage/uploads/{workspace_id}/{Y-m}/` with hashed filenames
- Record `sha256` for deduplication via `file_objects` table

### WebSocket Server

> [!IMPORTANT]
> WebSocket runs as a **separate PHP process** alongside Apache. Uses Ratchet library.

[NEW] `bin/websocket-server.php` — Ratchet WebSocket server on port 8080:

```bash
php bin/websocket-server.php
```

[NEW] `App\WebSocket\ChatServer.php`:
- **Events pushed to clients**: `new_message`, `typing`, `presence_update`, `message_reaction`, `message_deleted`, `read_receipt`
- **Authentication**: Client sends session token on connect; server validates against `user_sessions`
- **Channel subscriptions**: Client subscribes to conversation IDs; server routes messages only to subscribers
- **Presence**: Tracks connected users; updates `user_presence` table on connect/disconnect

[NEW] `public/js/websocket.js` — Client-side WebSocket manager:
- Auto-connect with reconnection logic (exponential backoff)
- Subscribe to active conversations
- Dispatch custom DOM events for UI updates (`chatrox:new_message`, etc.)
- Update sidebar badges, message list, typing indicators in real-time

### New API Controllers

| File | Endpoints |
|------|-----------|
| [NEW] `Front\Api\MessageController` | send, react, delete, markRead |
| [NEW] `Front\Api\ChannelController` | create, join, leave |
| [NEW] `Front\Api\FileController` | upload (with compression) |
| [NEW] `Front\Api\ProfileController` | update, updateTheme |

---

## Phase 6 — Seed Data

### [NEW] [database/seed.php](file:///d:/xampp/htdocs/chatrox/database/seed.php)

Run: `php database/seed.php`

Creates a complete demo workspace:

| Entity | Count | Details |
|--------|-------|---------|
| Workspace | 1 | "NexusTech Solutions" |
| Users | 7 | All with password `password123` |
| Workspace Members | 7 | 1 owner, 1 admin, 5 members |
| Channels | 5 | #general, #engineering, #design, #marketing, #random |
| Channel Members | 25+ | All in #general/#random, rest distributed |
| Conversations | 8 | 5 channel + 3 DM |
| Messages | 55+ | Realistic chat across channels + DMs |
| Announcements | 3 | Important, celebration, update |
| Notifications | 15+ | Mixed types per user |
| Audit Logs | 10+ | Login, channel_create, member_invite |
| Analytics (daily_messages) | 30 days | Realistic message volume curve |
| Analytics (daily_active_users) | 30 days | 4-7 active users/day |
| Analytics (channel_stats) | 30 days × 5 channels | Per-channel message counts |
| Analytics (hourly_activity) | 7 days × 24 hours | Peak at 10am-3pm |
| User Preferences | 7 | Random themes, timezone |
| User Presence | 7 | 3 online, 2 away, 2 offline |
| Files | 5 | Image, PDF, spreadsheet entries |
| Storage Quota | 1 | Based on file sizes |

---

## File Summary

| Phase | New Files | Modified Files |
|-------|-----------|----------------|
| 1. Core | `Auth.php` | `Database.php`, `Model.php`, `Session.php` |
| 2. Auth | `User.php`, `Workspace.php`, `WorkspaceMember.php`, `UserPresence.php` | `Front\AuthController`, `Admin\AuthController`, `register.php` |
| 3. Front Models | — | `HomeDashboard`, `DmsConversation`, `ChannelConversation`, `People`, `ActivityFeed`, `WorkspaceFile`, `BrowseChannel`, `Navigation` |
| 4. Admin Models | `Announcement.php`, `AuditLog.php`, `AnalyticsDashboard.php` | `AdminOverview.php` |
| 5. API + WS | `MessageController`, `ChannelController`, `FileController`, `ProfileController`, `ChatServer.php`, `websocket-server.php`, `websocket.js`, `FileUploader.php` | `index.php` |
| 6. Seed | `seed.php` | — |
| **Total** | **~17 new** | **~22 modified** |

---

## Execution Order

```
Phase 1 → Phase 6 → Phase 2 → Phase 3 → Phase 4 → Phase 5
          ↑ seed first so we have data to test against
```

---

## Verification Plan

### After Each Phase
- `php -l` on all new/modified files
- Seeder runs cleanly: `php database/seed.php`

### End-to-End
- Register → creates workspace + user in DB
- Login → `password_verify()` against `users.password_hash`
- Every front page shows real DB data
- Admin CRUD operations persist to DB
- WebSocket server starts and accepts connections
- File upload compresses images and stores metadata
