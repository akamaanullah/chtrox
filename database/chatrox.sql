-- =============================================================================
-- ChatRox Enterprise Database Schema
-- MySQL 8.0+ | InnoDB | utf8mb4
-- Multi-workspace SaaS (Slack-style team chat)
--
-- SCALABILITY GUIDELINES:                                                      -- CHANGED: scale comment block at top for production routing decisions
-- - Use Redis for user_presence above 10k concurrent users                     -- CHANGED: offload hot presence reads/writes to Redis at scale
-- - Restrict message_delivery_states to DM/group_dm only above 1M messages     -- CHANGED: avoid channel-wide delivery row explosion
-- - Partition messages by RANGE(YEAR(created_at)) above 50M rows               -- CHANGED: keep message history queries bounded per partition
-- - Add read replica routing for all analytics_* queries                       -- CHANGED: isolate heavy rollup reads from primary writer
--
-- Import:
--   mysql -u root -p < database/chatrox.sql
-- Or create DB first:
--   CREATE DATABASE chatrox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   USE chatrox;
--   SOURCE database/chatrox.sql;
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------------------------------
-- Drop existing objects (fresh install)
-- -----------------------------------------------------------------------------

DROP VIEW IF EXISTS v_people_directory;
DROP VIEW IF EXISTS v_workspace_files;

DROP TRIGGER IF EXISTS trg_channel_members_after_insert;
DROP TRIGGER IF EXISTS trg_channel_members_after_delete;
DROP TRIGGER IF EXISTS trg_channel_members_after_update;                          -- CHANGED: drop soft-leave member_count trigger on reinstall
DROP TRIGGER IF EXISTS trg_messages_after_insert;
DROP TRIGGER IF EXISTS trg_files_after_insert;
DROP TRIGGER IF EXISTS trg_files_after_delete;

DROP TABLE IF EXISTS analytics_hourly_activity;
DROP TABLE IF EXISTS analytics_channel_stats;
DROP TABLE IF EXISTS analytics_daily_active_users;
DROP TABLE IF EXISTS analytics_daily_messages;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS workspace_storage_quotas;
DROP TABLE IF EXISTS message_attachments;
DROP TABLE IF EXISTS files;
DROP TABLE IF EXISTS file_objects;                                                -- CHANGED: drop deduplicated blob registry before files
DROP TABLE IF EXISTS message_read_receipts;                                       -- CHANGED: table merged into message_delivery_states
DROP TABLE IF EXISTS message_delivery_states;
DROP TABLE IF EXISTS conversation_read_cursors;
DROP TABLE IF EXISTS message_forwards;
DROP TABLE IF EXISTS message_pins;
DROP TABLE IF EXISTS message_reactions;
DROP TABLE IF EXISTS message_user_deletions;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversation_participants;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS channel_join_requests;
DROP TABLE IF EXISTS channel_members;
DROP TABLE IF EXISTS channels;
DROP TABLE IF EXISTS workspace_invites;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS system_cache;
DROP TABLE IF EXISTS websocket_tickets;
DROP TABLE IF EXISTS user_presence;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS user_security;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS workspace_members;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS workspace_addresses;
DROP TABLE IF EXISTS workspaces;

-- -----------------------------------------------------------------------------
-- 1. Tenancy & Identity
-- -----------------------------------------------------------------------------

CREATE TABLE workspaces (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug            VARCHAR(64)     NOT NULL,
    name            VARCHAR(255)    NOT NULL,
    industry        ENUM('technology','healthcare','finance','education','manufacturing','retail','services','other') NOT NULL DEFAULT 'technology',
    organization_type ENUM('corporation','llc','partnership','sole_proprietorship','non_profit','other') NOT NULL DEFAULT 'corporation',
    email           VARCHAR(255)    NOT NULL,
    phone           VARCHAR(30)     NULL,
    logo_path       VARCHAR(512)    NULL,
    plan            ENUM('free','pro','enterprise') NOT NULL DEFAULT 'free',
    status          ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_workspaces_slug (slug),
    KEY idx_workspaces_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workspace_addresses (
    workspace_id    BIGINT UNSIGNED NOT NULL,
    address_line1   VARCHAR(255)    NOT NULL,
    city            VARCHAR(100)    NOT NULL,
    state           VARCHAR(100)    NULL,
    country         VARCHAR(100)    NOT NULL,
    postal_code     VARCHAR(20)     NOT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (workspace_id),
    CONSTRAINT fk_workspace_addresses_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email               VARCHAR(255)    NOT NULL,
    username            VARCHAR(64)     NOT NULL,
    password_hash       VARCHAR(255)    NOT NULL,
    first_name          VARCHAR(100)    NOT NULL,
    last_name           VARCHAR(100)    NOT NULL,
    phone               VARCHAR(30)     NULL,
    avatar_path         VARCHAR(512)    NULL,
    bio                 TEXT            NULL,
    email_verified_at   TIMESTAMP       NULL DEFAULT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workspace_members (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id    BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    role            ENUM('owner','admin','member') NOT NULL DEFAULT 'member',
    job_title       VARCHAR(100)    NULL,
    status          ENUM('active','invited','suspended','deactivated') NOT NULL DEFAULT 'active',
    joined_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active_at  TIMESTAMP       NULL DEFAULT NULL,
    left_at         TIMESTAMP       NULL DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_workspace_members_ws_user (workspace_id, user_id),
    KEY idx_workspace_members_ws (workspace_id, status, joined_at),
    KEY idx_workspace_members_user (user_id),
    CONSTRAINT fk_workspace_members_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_workspace_members_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_preferences (
    user_id                 BIGINT UNSIGNED NOT NULL,
    theme_color             VARCHAR(20)     NOT NULL DEFAULT 'indigo',
    favorite_timezones      JSON            NULL,
    notification_settings   JSON            NULL,
    locale                  VARCHAR(10)     NOT NULL DEFAULT 'en',
    timezone                VARCHAR(64)     NOT NULL DEFAULT 'UTC',
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_preferences_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_security (
    user_id                 BIGINT UNSIGNED NOT NULL,
    two_factor_enabled      TINYINT(1)      NOT NULL DEFAULT 0,
    two_factor_secret       VARCHAR(255)    NULL,
    password_changed_at     TIMESTAMP       NULL DEFAULT NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_security_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_sessions (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    session_token   VARCHAR(128)    NOT NULL,
    device_name     VARCHAR(100)    NOT NULL,
    ip_address      VARCHAR(45)     NULL,
    user_agent      VARCHAR(512)    NULL,
    last_seen_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at      TIMESTAMP       NULL DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_sessions_token (session_token),
    KEY idx_user_sessions_user (user_id, last_seen_at DESC),
    CONSTRAINT fk_user_sessions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_presence (
    user_id         BIGINT UNSIGNED NOT NULL,
    status          ENUM('online','offline','away','dnd') NOT NULL DEFAULT 'offline',
    last_seen_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    KEY idx_user_presence_status (status, last_seen_at),
    CONSTRAINT fk_user_presence_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    token_hash      CHAR(64)        NOT NULL,
    expires_at      TIMESTAMP       NOT NULL,
    used_at         TIMESTAMP       NULL DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_token (token_hash),
    KEY idx_password_reset_user (user_id),
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workspace_invites (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id        BIGINT UNSIGNED NOT NULL,
    email               VARCHAR(255)    NOT NULL,
    role                ENUM('admin','member') NOT NULL DEFAULT 'member',
    token_hash          CHAR(64)        NOT NULL,
    invited_by          BIGINT UNSIGNED NOT NULL,
    accepted_at         TIMESTAMP       NULL DEFAULT NULL,
    expires_at          TIMESTAMP       NOT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_workspace_invites_token (token_hash),
    KEY idx_workspace_invites_ws_email (workspace_id, email),
    CONSTRAINT fk_workspace_invites_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_workspace_invites_invited_by
        FOREIGN KEY (invited_by) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Channels
-- -----------------------------------------------------------------------------

CREATE TABLE channels (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id    BIGINT UNSIGNED NOT NULL,
    slug            VARCHAR(80)     NOT NULL,
    former_slugs    TEXT            NULL,
    name            VARCHAR(100)    NOT NULL,
    description     TEXT            NULL,
    visibility      ENUM('public','private') NOT NULL DEFAULT 'public',
    status          ENUM('active','archived') NOT NULL DEFAULT 'active',
    is_default      TINYINT(1)      NOT NULL DEFAULT 0,
    created_by      BIGINT UNSIGNED NOT NULL,
    member_count    INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived_at     TIMESTAMP       NULL DEFAULT NULL,
    deleted_at      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_channels_ws_slug (workspace_id, slug),
    KEY idx_channels_ws_visibility (workspace_id, visibility, status),
    KEY idx_channels_created_by (created_by),
    CONSTRAINT fk_channels_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_channels_created_by
        FOREIGN KEY (created_by) REFERENCES workspace_members (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE channel_members (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    channel_id              BIGINT UNSIGNED NOT NULL,
    workspace_member_id     BIGINT UNSIGNED NOT NULL,
    role                    ENUM('owner','admin','member') NOT NULL DEFAULT 'member',
    notifications_muted     TINYINT(1)      NOT NULL DEFAULT 0,
    joined_at               TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at                 TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_channel_members (channel_id, workspace_member_id),
    KEY idx_channel_members_member (workspace_member_id),
    CONSTRAINT fk_channel_members_channel
        FOREIGN KEY (channel_id) REFERENCES channels (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_channel_members_member
        FOREIGN KEY (workspace_member_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE channel_join_requests (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id            BIGINT UNSIGNED NOT NULL,
    channel_id              BIGINT UNSIGNED NOT NULL,
    workspace_member_id     BIGINT UNSIGNED NOT NULL,
    status                  ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_channel_join_requests (channel_id, workspace_member_id),
    KEY idx_channel_join_requests_workspace (workspace_id),
    KEY idx_channel_join_requests_channel (channel_id),
    KEY idx_channel_join_requests_member (workspace_member_id),
    CONSTRAINT fk_channel_join_requests_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_channel_join_requests_channel
        FOREIGN KEY (channel_id) REFERENCES channels (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_channel_join_requests_member
        FOREIGN KEY (workspace_member_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Unified Messaging (DM + Channel)
-- -----------------------------------------------------------------------------

CREATE TABLE conversations (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id        BIGINT UNSIGNED NOT NULL,
    type                ENUM('channel','dm','group_dm') NOT NULL,
    channel_id          BIGINT UNSIGNED NULL,
    dm_hash             CHAR(64)        NULL,
    last_message_id     BIGINT UNSIGNED NULL,                                         -- CHANGED: plain column only; no FK to messages (circular dependency removed)
    last_message_at     TIMESTAMP(3)    NULL DEFAULT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conversations_channel (channel_id),
    UNIQUE KEY uq_conversations_dm_hash (workspace_id, dm_hash),
    KEY idx_conversations_ws_last (workspace_id, last_message_at DESC),
    KEY idx_conversations_type (workspace_id, type),
    CONSTRAINT fk_conversations_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_conversations_channel
        FOREIGN KEY (channel_id) REFERENCES channels (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conversation_participants (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id         BIGINT UNSIGNED NOT NULL,
    workspace_member_id     BIGINT UNSIGNED NOT NULL,
    joined_at               TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at                 TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conversation_participants (conversation_id, workspace_member_id),
    KEY idx_conversation_participants_member (workspace_member_id),
    CONSTRAINT fk_conversation_participants_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversations (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_conversation_participants_member
        FOREIGN KEY (workspace_member_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id                BIGINT UNSIGNED NOT NULL,
    conversation_id             BIGINT UNSIGNED NOT NULL,
    sender_id                   BIGINT UNSIGNED NOT NULL,
    reply_to_id                 BIGINT UNSIGNED NULL,
    forwarded_from_message_id   BIGINT UNSIGNED NULL,
    body                        TEXT            NOT NULL,
    message_type                ENUM('text','file','gif','system','voice','image','audio','video','document') NOT NULL DEFAULT 'text',
    edited_at                   TIMESTAMP(3)    NULL DEFAULT NULL,
    deleted_for_everyone_at     TIMESTAMP(3)    NULL DEFAULT NULL,
    created_at                  TIMESTAMP(3)    NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_messages_conv_id (conversation_id, id DESC),
    KEY idx_messages_conv_deleted (conversation_id, deleted_for_everyone_at, id DESC),
    KEY idx_messages_conv_unread (conversation_id, id, created_at),                   -- CHANGED: composite index for unread count vs read cursor
    KEY idx_messages_workspace_created (workspace_id, created_at),
    KEY idx_messages_sender (sender_id, created_at DESC),
    KEY idx_messages_reply_to (reply_to_id),
    KEY idx_messages_forwarded_from (forwarded_from_message_id),
    FULLTEXT KEY ft_messages_body (body),
    CONSTRAINT fk_messages_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_messages_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversations (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id) REFERENCES workspace_members (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_messages_reply_to
        FOREIGN KEY (reply_to_id) REFERENCES messages (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_messages_forwarded_from
        FOREIGN KEY (forwarded_from_message_id) REFERENCES messages (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_user_deletions (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id              BIGINT UNSIGNED NOT NULL,
    workspace_member_id     BIGINT UNSIGNED NOT NULL,
    deleted_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_message_user_deletions (message_id, workspace_member_id),
    CONSTRAINT fk_message_user_deletions_message
        FOREIGN KEY (message_id) REFERENCES messages (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_user_deletions_member
        FOREIGN KEY (workspace_member_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_reactions (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id              BIGINT UNSIGNED NOT NULL,
    workspace_member_id     BIGINT UNSIGNED NOT NULL,
    emoji                   VARCHAR(32)     NOT NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_message_reactions (message_id, workspace_member_id, emoji),
    KEY idx_message_reactions_message (message_id),
    CONSTRAINT fk_message_reactions_message
        FOREIGN KEY (message_id) REFERENCES messages (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_reactions_member
        FOREIGN KEY (workspace_member_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_pins (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id     BIGINT UNSIGNED NOT NULL,
    message_id          BIGINT UNSIGNED NOT NULL,
    pinned_by           BIGINT UNSIGNED NOT NULL,
    pinned_at           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_message_pins (pinned_by, message_id),
    KEY idx_message_pins_conversation (conversation_id, pinned_at DESC),
    CONSTRAINT fk_message_pins_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversations (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_pins_message
        FOREIGN KEY (message_id) REFERENCES messages (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_pins_pinned_by
        FOREIGN KEY (pinned_by) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_forwards (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_message_id       BIGINT UNSIGNED NOT NULL,
    target_conversation_id  BIGINT UNSIGNED NOT NULL,
    forwarded_by            BIGINT UNSIGNED NOT NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_message_forwards_source (source_message_id),
    KEY idx_message_forwards_target (target_conversation_id),
    CONSTRAINT fk_message_forwards_source
        FOREIGN KEY (source_message_id) REFERENCES messages (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_forwards_target
        FOREIGN KEY (target_conversation_id) REFERENCES conversations (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_forwards_by
        FOREIGN KEY (forwarded_by) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conversation_read_cursors (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_member_id     BIGINT UNSIGNED NOT NULL,
    conversation_id         BIGINT UNSIGNED NOT NULL,
    last_read_message_id    BIGINT UNSIGNED NULL,
    last_read_at            TIMESTAMP(3)    NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_read_cursors (workspace_member_id, conversation_id),
    KEY idx_read_cursors_conversation (conversation_id),
    CONSTRAINT fk_read_cursors_member
        FOREIGN KEY (workspace_member_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_read_cursors_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversations (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_read_cursors_message
        FOREIGN KEY (last_read_message_id) REFERENCES messages (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_delivery_states (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id              BIGINT UNSIGNED NOT NULL,
    recipient_member_id     BIGINT UNSIGNED NOT NULL,
    state                   ENUM('sent','delivered','read') NOT NULL DEFAULT 'sent',
    read_at                 TIMESTAMP(3)    NULL DEFAULT NULL,                        -- CHANGED: merged from message_read_receipts for delivery + read timestamp
    updated_at              TIMESTAMP(3)    NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_message_delivery (message_id, recipient_member_id),
    KEY idx_message_delivery_recipient (recipient_member_id, updated_at DESC),
    CONSTRAINT fk_message_delivery_message
        FOREIGN KEY (message_id) REFERENCES messages (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_delivery_recipient
        FOREIGN KEY (recipient_member_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Files & Storage
-- -----------------------------------------------------------------------------

CREATE TABLE file_objects (                                                         -- CHANGED: global blob registry for cross-workspace S3 deduplication
    sha256        CHAR(64)       NOT NULL,
    storage_disk  ENUM('local','s3') NOT NULL DEFAULT 's3',
    storage_path  VARCHAR(1024)  NOT NULL,
    size_bytes    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE files (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id    BIGINT UNSIGNED NOT NULL,
    uploaded_by     BIGINT UNSIGNED NOT NULL,
    original_name   VARCHAR(512)    NOT NULL,
    storage_disk    ENUM('local','s3') NOT NULL DEFAULT 'local',
    storage_path    VARCHAR(1024)   NOT NULL,
    mime_type       VARCHAR(128)    NOT NULL,
    extension       VARCHAR(20)     NULL,
    size_bytes      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    sha256          CHAR(64)        NULL,
    category        ENUM('image','video','document','audio','other') NOT NULL DEFAULT 'other',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_files_workspace_created (workspace_id, created_at DESC),
    KEY idx_files_workspace_uploader (workspace_id, uploaded_by),
    KEY idx_files_sha256 (workspace_id, sha256),
    KEY idx_files_category (workspace_id, category),
    CONSTRAINT fk_files_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_files_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES workspace_members (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_files_sha256_object                                                  -- CHANGED: link workspace file row to deduplicated blob
        FOREIGN KEY (sha256) REFERENCES file_objects (sha256)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_attachments (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id      BIGINT UNSIGNED NOT NULL,
    file_id         BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_message_attachments (message_id, file_id),
    KEY idx_message_attachments_file (file_id),
    CONSTRAINT fk_message_attachments_message
        FOREIGN KEY (message_id) REFERENCES messages (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_attachments_file
        FOREIGN KEY (file_id) REFERENCES files (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workspace_storage_quotas (
    workspace_id    BIGINT UNSIGNED NOT NULL,
    quota_bytes     BIGINT UNSIGNED NOT NULL DEFAULT 16106127360,
    used_bytes      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (workspace_id),
    CONSTRAINT fk_storage_quotas_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Notifications (user feed) vs Audit Logs (admin compliance)
-- -----------------------------------------------------------------------------

CREATE TABLE notifications (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id    BIGINT UNSIGNED NOT NULL,
    recipient_id    BIGINT UNSIGNED NOT NULL,
    type            ENUM('mention','file_share','file_upload','reaction','system','missed_call','channel_join','project','reply') NOT NULL,
    actor_id        BIGINT UNSIGNED NULL,
    title           VARCHAR(255)    NULL,
    body            TEXT            NOT NULL,
    body_html       TEXT            NULL,
    reference_type  ENUM('message','file','channel','conversation','announcement') NULL,
    reference_id    BIGINT UNSIGNED NULL,
    read_at         TIMESTAMP       NULL DEFAULT NULL,
    deleted_at      TIMESTAMP       NULL DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_recipient (recipient_id, workspace_id, created_at DESC),
    KEY idx_notifications_type (recipient_id, type),
    KEY idx_notifications_unread (recipient_id, read_at, created_at DESC),
    KEY idx_notifications_ttl (created_at, read_at),                                  -- CHANGED: TTL/cron cleanup of stale read notifications
    CONSTRAINT fk_notifications_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_recipient
        FOREIGN KEY (recipient_id) REFERENCES workspace_members (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_actor
        FOREIGN KEY (actor_id) REFERENCES workspace_members (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id        BIGINT UNSIGNED NOT NULL,
    actor_member_id     BIGINT UNSIGNED NULL,
    actor_label         VARCHAR(100)    NULL,
    status              ENUM('complete','failed','warning') NOT NULL DEFAULT 'complete',
    activity_type       ENUM('login','logout','message_delete','channel_create','channel_archive','member_invite','member_remove','role_change','file_delete','workspace_update','password_change','2fa_enable','2fa_disable','invite_create','settings_update','OTHER') NOT NULL DEFAULT 'OTHER',  -- CHANGED: typed audit events with OTHER fallback
    message             TEXT            NOT NULL,
    ip_address          VARCHAR(45)     NULL,
    metadata            JSON            NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_workspace (workspace_id, created_at DESC),
    KEY idx_audit_logs_type (workspace_id, activity_type, created_at DESC),
    KEY idx_audit_logs_status (workspace_id, status, created_at DESC),
    CONSTRAINT fk_audit_logs_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_audit_logs_actor
        FOREIGN KEY (actor_member_id) REFERENCES workspace_members (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Announcements
-- -----------------------------------------------------------------------------

CREATE TABLE announcements (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    workspace_id    BIGINT UNSIGNED NOT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    tag             ENUM('IMPORTANT','CELEBRATION','UPDATE') NOT NULL DEFAULT 'UPDATE',
    message         TEXT            NOT NULL,
    start_date      DATETIME        NOT NULL,                                         -- CHANGED: DATE -> DATETIME for time-bound broadcasts
    end_date        DATETIME        NOT NULL,                                         -- CHANGED: DATE -> DATETIME for time-bound broadcasts
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_announcements_ws_dates (workspace_id, start_date, end_date),
    KEY idx_announcements_tag (workspace_id, tag),
    CONSTRAINT fk_announcements_workspace
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_announcements_created_by
        FOREIGN KEY (created_by) REFERENCES workspace_members (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6b. Rate Limiting & System Cache & WebSocket Tickets
-- -----------------------------------------------------------------------------

CREATE TABLE rate_limits (
    `key`       VARCHAR(128) NOT NULL,
    hits        INT UNSIGNED NOT NULL DEFAULT 1,
    expires_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`),
    KEY idx_rate_limits_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_cache (
    `key`       VARCHAR(255) NOT NULL,
    `value`     LONGTEXT NOT NULL,
    expires_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`),
    KEY idx_system_cache_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE websocket_tickets (
    ticket                VARCHAR(64) NOT NULL,
    session_token         VARCHAR(128) NOT NULL,
    user_id               BIGINT UNSIGNED NOT NULL,
    workspace_member_id   BIGINT UNSIGNED NOT NULL,
    workspace_id          BIGINT UNSIGNED NOT NULL,
    expires_at            TIMESTAMP NOT NULL,
    PRIMARY KEY (ticket),
    KEY idx_ws_tickets_expires (expires_at),
    CONSTRAINT fk_ws_tickets_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Analytics Rollups (cron-populated)
-- -----------------------------------------------------------------------------

CREATE TABLE analytics_daily_messages (
    workspace_id        BIGINT UNSIGNED NOT NULL,
    date                DATE            NOT NULL,
    channel_messages    INT UNSIGNED    NOT NULL DEFAULT 0,
    dm_messages         INT UNSIGNED    NOT NULL DEFAULT 0,
    total_messages      INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (workspace_id, date),
    CONSTRAINT fk_analytics_daily_messages_ws
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics_daily_active_users (
    workspace_id    BIGINT UNSIGNED NOT NULL,
    date            DATE            NOT NULL,
    active_users    INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (workspace_id, date),
    CONSTRAINT fk_analytics_daily_active_users_ws
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics_channel_stats (
    workspace_id    BIGINT UNSIGNED NOT NULL,
    channel_id      BIGINT UNSIGNED NOT NULL,
    date            DATE            NOT NULL,
    message_count   INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (workspace_id, channel_id, date),
    KEY idx_analytics_channel_stats_count (workspace_id, date, message_count DESC),
    CONSTRAINT fk_analytics_channel_stats_ws
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_analytics_channel_stats_channel
        FOREIGN KEY (channel_id) REFERENCES channels (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics_hourly_activity (
    workspace_id    BIGINT UNSIGNED NOT NULL,
    date            DATE            NOT NULL,
    hour            TINYINT UNSIGNED NOT NULL,
    message_count   INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (workspace_id, date, hour),
    CONSTRAINT fk_analytics_hourly_ws
        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_hour CHECK (hour BETWEEN 0 AND 23)                                 -- CHANGED: enforce valid hour bucket 0-23
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Triggers (denormalized counters)
-- -----------------------------------------------------------------------------

DELIMITER $$

CREATE TRIGGER trg_channel_members_after_insert
AFTER INSERT ON channel_members
FOR EACH ROW
BEGIN
    IF NEW.left_at IS NULL THEN
        UPDATE channels
        SET member_count = member_count + 1
        WHERE id = NEW.channel_id;
    END IF;
END$$

CREATE TRIGGER trg_channel_members_after_delete
AFTER DELETE ON channel_members
FOR EACH ROW
BEGIN
    IF OLD.left_at IS NULL THEN
        UPDATE channels
        SET member_count = GREATEST(member_count, 1) - 1
        WHERE id = OLD.channel_id;
    END IF;
END$$

CREATE TRIGGER trg_channel_members_after_update                                          -- CHANGED: adjust member_count on soft-leave / rejoin
AFTER UPDATE ON channel_members
FOR EACH ROW
BEGIN
    IF OLD.left_at IS NULL AND NEW.left_at IS NOT NULL THEN
        UPDATE channels
        SET member_count = GREATEST(member_count, 1) - 1
        WHERE id = NEW.channel_id;
    ELSEIF OLD.left_at IS NOT NULL AND NEW.left_at IS NULL THEN
        UPDATE channels
        SET member_count = member_count + 1
        WHERE id = NEW.channel_id;
    END IF;
END$$

CREATE TRIGGER trg_messages_after_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    UPDATE conversations
    SET last_message_id = NEW.id,
        last_message_at = NEW.created_at
    WHERE id = NEW.conversation_id;
END$$

CREATE TRIGGER trg_files_after_insert
AFTER INSERT ON files
FOR EACH ROW
BEGIN
    INSERT INTO workspace_storage_quotas (workspace_id, used_bytes)
    VALUES (NEW.workspace_id, NEW.size_bytes)
    ON DUPLICATE KEY UPDATE
        used_bytes = used_bytes + NEW.size_bytes,
        updated_at = CURRENT_TIMESTAMP;
END$$

CREATE TRIGGER trg_files_after_delete
AFTER DELETE ON files
FOR EACH ROW
BEGIN
    UPDATE workspace_storage_quotas
    SET used_bytes = IF(used_bytes >= OLD.size_bytes, used_bytes - OLD.size_bytes, 0),  -- CHANGED: prevent unsigned underflow on quota
        updated_at = CURRENT_TIMESTAMP
    WHERE workspace_id = OLD.workspace_id;
END$$

DELIMITER ;

-- -----------------------------------------------------------------------------
-- 9. Views (simplify model queries)
-- -----------------------------------------------------------------------------

CREATE VIEW v_people_directory AS
SELECT
    wm.id              AS workspace_member_id,
    wm.workspace_id,
    wm.role            AS workspace_role,
    wm.job_title,
    wm.status          AS member_status,
    wm.joined_at,
    u.id               AS user_id,
    u.username,
    u.email,
    u.first_name,
    u.last_name,
    CONCAT(u.first_name, ' ', u.last_name) AS display_name,
    u.avatar_path,
    u.bio,
    COALESCE(up.status, 'offline') AS presence_status,
    up.last_seen_at
FROM workspace_members wm
INNER JOIN users u ON u.id = wm.user_id AND u.deleted_at IS NULL
LEFT JOIN user_presence up ON up.user_id = u.id
WHERE wm.status = 'active' AND wm.left_at IS NULL;

CREATE VIEW v_workspace_files AS
SELECT
    f.id,
    f.workspace_id,
    f.original_name,
    f.mime_type,
    f.extension,
    f.size_bytes,
    f.category,
    f.created_at,
    f.uploaded_by,
    u.first_name,
    u.last_name,
    CONCAT(u.first_name, ' ', u.last_name) AS shared_by,
    u.avatar_path AS shared_avatar
FROM files f
INNER JOIN workspace_members wm ON wm.id = f.uploaded_by
INNER JOIN users u ON u.id = wm.user_id
WHERE f.deleted_at IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Scale notes (apply when rows exceed ~50M):
--
-- 1. Partition messages by RANGE (YEAR(created_at)) or HASH(workspace_id)
-- 2. Partition audit_logs monthly for retention policies
-- 3. Purge message_read_receipts older than 7 days via nightly cron
-- 4. Use read replicas for analytics_* queries
-- 5. Cache presence + unread counts in Redis (app layer)
-- =============================================================================
