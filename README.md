# ChatRox 💬

ChatRox is a modern, premium, real-time collaboration and workspace messaging application built with PHP (custom MVC architecture), Vanilla CSS/JS, and WebSockets. It is designed to offer a fluid user experience similar to Slack or Microsoft Teams, packed with advanced messaging, workspace controls, and user customization options.

---

## 🚀 Key Features

### 1. Real-time Communication
*   **WebSockets Integration**: Instant message delivery and presence updates powered by a Ratchet WebSocket server (`bin/websocket-server.php`).
*   **Live Presence**: Real-time tracking of workspace member statuses (online, offline, active times).
*   **Read Receipts**: Double-check read receipts for DMs and group chats, showing exactly who has viewed your messages.

### 2. Channel Management
*   **Public & Private Channels**: Create workspaces for open team discussion or restricted private channels.
*   **Channel Moderator Controls**: Edit channel names and purposes, add members, or remove participants.
*   **Automatic Ownership Transfer**: When a channel creator or owner leaves, the system automatically transfers ownership/administration to the next eligible member to prevent orphaned channels.
*   **Dynamic Sorting**: Channels in the sidebar are automatically sorted by the most recent message activity.
*   **Browse Directory**: Browse public channels, see member counts, and request to join private channels with real-time approval pipelines.

### 3. Rich Messaging Suite
*   **Message Controls**: Edit sent messages, delete messages (soft-delete with placeholders), or reply-to/thread messages.
*   **Forward Messages**: Forward any chat message to other channels or DMs.
*   **Emoji Reactions**: React to messages with instant updates; hover or tap to view a complete summary of who reacted.
*   **Message Pinning**: Pin crucial messages in a channel or DM and browse them in a dedicated "Pinned" tab in the details panel.
*   **Giphy Integration**: Search and send trending animated GIFs directly from the chat compose bar.
*   **Voice Messages**: Record and send voice notes with an inline custom audio player.
*   **Lightbox Previews**: Click shared media files to open a rich, scrollable image lightbox overlay.

### 4. Media & File Sharing
*   **File Uploads & Downloads**: Share files of any type in channels or DMs.
*   **File Management**: Browse all files shared in a specific conversation from the Details panel. Click a file name to view it or click the download icon to save it locally.

### 5. Workspace Directory & People
*   **Workspace Members List**: Browse the profiles, active statuses, roles, and job titles of everyone in the workspace.
*   **Connection Cards**: Direct Message shortcut cards to quickly start conversation flows.

### 6. Interactive Activity Hub & Notifications
*   **Live Notification Center**: A centralized hub (`/activity`) tracking channel invitations, join requests, mentions, and message reactions.
*   **Badge Counts & Browser Alerts**: Real-time visual count indicators on the sidebar and native browser desktop notifications.

### 7. Personalized Workspace Settings
*   **Profile Customization**: Edit display name, job title, bio, and upload custom avatars with immediate workspace-wide synchronization.
*   **Theme Switcher**: Instantly toggle between beautifully polished Light Mode and Sleek HSL-tuned Dark Mode.

### 8. Custom Dialog System
*   **Custom Alert/Confirm Modals**: Replaced generic browser popups (`alert` / `confirm`) with elegant, custom-designed UI dialog modals styled after the application's modern branding.

### 9. Control Panel & Admin Dashboard
*   **Admin Area (`/admin`)**: A comprehensive dashboard for workspace owners and workspace admins.
*   **Analytics & Stats**: Charts and figures tracking daily active users, message counts, and channel engagement.
*   **Moderation Controls**: View and manage all workspace members, edit announcements, audit file logs, and track workspace activities.

---

## 🛠️ Technology Stack

*   **Backend**: PHP 8.x (Custom Object-Oriented MVC Architecture)
*   **Real-time Engine**: Ratchet PHP WebSocket Library
*   **Database**: MySQL / MariaDB
*   **Frontend**: Vanilla HTML5, Vanilla JavaScript (ES6+), and Vanilla CSS3 (curated HSL palettes, smooth glassmorphism, responsive grids)
*   **Icons**: Lucide Icons
*   **APIs**: Giphy API Integration

---

## 📦 Installation & Setup

### 1. Prerequisites
*   Apache Web Server (with `mod_rewrite` enabled)
*   PHP 8.0+
*   MySQL 5.7+ or MariaDB 10.3+
*   Composer

### 2. Configuration
1. Clone the repository into your web directory.
2. Create a `.env` file based on `.env.example`:
   ```bash
   cp .env.example .env
   ```
3. Update `.env` with your database credentials and configuration:
   ```env
   DB_HOST=127.0.0.1
   DB_NAME=chatrox
   DB_USER=root
   DB_PASS=your_password
   WS_PORT=8080
   BASE_URL=http://localhost/chatrox
   ```

### 3. Database Setup
1. Create a MySQL database matching the name in your `.env` file.
2. Import the schema file located in `database/chatrox.sql`.
3. (Optional) Run the database seed script to populate sample workspace members, channels, and logs:
   ```bash
   php database/seed.php
   ```

### 4. Dependencies
Install PHP dependencies via Composer:
```bash
composer install
```

### 5. Starting the WebSocket Server
Run the Ratchet WebSocket server using the PHP CLI:
```bash
php bin/websocket-server.php
```

---

## 👨‍💻 Developed By

This project is developed and maintained by **[Amaanullah](https://amaanullah.com)**. For collaborations, custom software development, or premium digital solutions, visit **[amaanullah.com](https://amaanullah.com)**.

---

## 📄 License
This project is licensed under the MIT License.
