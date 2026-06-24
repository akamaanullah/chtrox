# Implementation Plan — Dynamic Joins, Notifications, Files & Directory

This plan details how to make several peripheral screens in the Chatrox application fully dynamic and functional, adding private channel join request approvals, browser notifications, files viewing, and connecting the people directory/quick-connect cards.

---

## User Review Required

> [!IMPORTANT]
> A new database table `channel_join_requests` will be introduced to track join requests. We will also alter the `notifications` table or utilize the existing `'channel_join'` enum value to communicate join requests from users to channel admins.

---

## Proposed Changes

### 1. Database Schema
#### [NEW] [create_channel_join_requests_table.php](file:///d:/xampp/htdocs/chatrox/database/migrations/create_channel_join_requests_table.php)
- Create a database migration script to define `channel_join_requests` table:
  - Columns: `id`, `workspace_id`, `channel_id`, `workspace_member_id`, `status` (`'pending'`, `'accepted'`, `'rejected'`), `created_at`, `updated_at`.
  - Define appropriate foreign keys and a unique index on `(channel_id, workspace_member_id)`.

---

### 2. Channels & Browse Channels
#### [MODIFY] [BrowseChannel.php](file:///d:/xampp/htdocs/chatrox/app/Models/BrowseChannel.php)
- Update `BrowseChannel::all()` to query **all** active channels in the workspace (both `public` and `private`).
- Add a left join with `channel_join_requests` to fetch the join request status (`pending`, `accepted`, `rejected`) for the current user.

#### [MODIFY] [ChannelController.php](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/Api/ChannelController.php)
- Add new endpoints:
  - `requestJoin()`: Validate that a channel is private. Create a `pending` request in `channel_join_requests`. Create a notification in the `notifications` table for the channel admins. Send a real-time event to connected channel admins via WebSockets.
  - `approveJoinRequest()`: Verify caller is channel admin/owner. Update status to `accepted`, add requester to `channel_members` and `conversation_participants`, insert system message into channel chat, mark admin notification read, and broadcast WebSocket success event.
  - `rejectJoinRequest()`: Verify caller is channel admin/owner. Update status to `rejected` and mark admin notification read.

#### [MODIFY] [main.php (Browse Channels)](file:///d:/xampp/htdocs/chatrox/views/front/tabs/browse-channels/main.php)
- Loop over the visibility of each channel:
  - If public: show direct "Join" button.
  - If private & no request exists: show "Request to Join" button.
  - If private & request is pending: show disabled "Requested" button.
  - If joined: show "Joined" badge.
- Bind client-side click events to execute AJAX requests to either join or request to join.

#### [MODIFY] [profile-panel.php](file:///d:/xampp/htdocs/chatrox/views/front/partials/panels/profile-panel.php)
- Dynamically fetch user bio, avatar, name, and email from session data.
- Fetch and display the actual list of channels the current user has joined from the database, rather than static list mockup.

#### [MODIFY] [profile.js](file:///d:/xampp/htdocs/chatrox/public/js/panels/profile.js)
- On profile save (name, email, bio) and profile photo upload: make API requests to `/api/profile/update` and save changes to the database.

---

### 3. Activity Feed & Notifications
#### [MODIFY] [MessageController.php](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/Api/MessageController.php)
- Inside the `react()` method: When a reaction is added, if the reactor is not the message sender, insert a notification of type `reaction` for the message sender and send a real-time WebSocket event.

#### [MODIFY] [ActivityFeed.php](file:///d:/xampp/htdocs/chatrox/app/Models/ActivityFeed.php)
- Join the notifications query with `channel_join_requests` (for `channel_join` notification type) to fetch the request ID and status.

#### [MODIFY] [main.php (Activity)](file:///d:/xampp/htdocs/chatrox/views/front/tabs/activity/main.php)
- If notification type is `channel_join` and request status is `pending`, render "Accept" and "Reject" buttons inside the card.
- If status is `accepted` or `rejected`, render the corresponding status badge.

#### [MODIFY] [activity.js](file:///d:/xampp/htdocs/chatrox/public/js/tabs/activity/activity.js)
- Bind click events for `.js-approve-join` and `.js-reject-join`. Make AJAX calls to approval/rejection endpoints, then smoothly transition the buttons into status badges upon success.

---

### 4. WebSocket & Browser notifications
#### [MODIFY] [websocket.js](file:///d:/xampp/htdocs/chatrox/public/js/websocket.js)
- On script load, request `Notification.requestPermission()`.
- Listen for new WebSocket events:
  - `channel_join_request` (received by admins): Trigger a desktop notification stating "[User Name] requested to join #[Channel Name]".
  - `message_reaction_notification` (received by message sender): Trigger a desktop notification stating "[User Name] reacted with [emoji] to: [Message Text]".
  - `new_message`: Also trigger desktop notifications for DMs (if not actively viewing the DM tab).

---

### 5. People & Quick Connect Directories
#### [MODIFY] [People.php](file:///d:/xampp/htdocs/chatrox/app/Models/People.php)
- Include `'username' => $row['username']` in the directory array results.

#### [MODIFY] [main.php (People)](file:///d:/xampp/htdocs/chatrox/views/front/tabs/people/main.php)
- Wrap the "Chat" button on the cards in an `<a>` link pointing to `dms/<?php echo htmlspecialchars($contact['username']); ?>`.

#### [MODIFY] [main.php (DMs)](file:///d:/xampp/htdocs/chatrox/views/front/tabs/dms/main.php)
- Make connection cards clickable by wrapping them in `<a>` links pointing to `dms/<?php echo htmlspecialchars($card['username']); ?>`.

---

### 6. Files & Previews
#### [MODIFY] [main.php (Files)](file:///d:/xampp/htdocs/chatrox/views/front/tabs/files/main.php)
- Wrap the filename in an `<a>` link pointing to the file download URL with `target="_blank"` (opens in new tab to display inline if previewable).
- Set the download icon button to download with `?download=1` parameter.

#### [MODIFY] [FilesController.php](file:///d:/xampp/htdocs/chatrox/app/Controllers/Front/FilesController.php)
- In the `download()` method, check if `?download=1` is specified.
- If specified, always force `Content-Disposition: attachment`.
- If not specified, set `Content-Disposition: inline` for previewable file types (images, videos, audio).

---

## Verification Plan

### Automated Tests
- Create migration script and run it to verify schema integrity.

### Manual Verification
1. **Join Request Flow**:
   - Log in as a normal user, open **Browse Channels**, click "Request to Join" on a private channel.
   - Verify it changes to "Requested" and stays disabled.
   - Log in as the channel admin. Check the **Activity Feed**. Verify that the request appears with "Accept" and "Reject" buttons.
   - Click "Accept". Verify that the requester is added to the channel, and a system message is posted.
2. **Browser Notifications**:
   - Open two browsers (User A and User B).
   - User A reacts to User B's message. Verify User B gets a real-time browser desktop alert.
   - User A mentions User B in a channel. Verify User B gets a real-time browser desktop alert.
3. **Files Preview**:
   - Go to **Files Tab**, click a PDF or image filename. Verify that it opens in a new tab inline.
   - Click the download icon next to the same file. Verify that it triggers a file download dialog directly.
4. **People & DMs Directory**:
   - Go to **People**, click "Chat" on a contact. Verify that it routes instantly to the DM thread with that user.
   - Go to **DMs**, click a connection card. Verify that it routes instantly to the DM thread with that user.
