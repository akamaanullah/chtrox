-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 03, 2026 at 06:57 PM
-- Server version: 10.11.11-MariaDB-ubu2204
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chat_chat`
--

-- --------------------------------------------------------

--
-- Table structure for table `analytics_channel_stats`
--

CREATE TABLE `analytics_channel_stats` (
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `channel_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `message_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_daily_active_users`
--

CREATE TABLE `analytics_daily_active_users` (
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `active_users` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_daily_messages`
--

CREATE TABLE `analytics_daily_messages` (
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `channel_messages` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dm_messages` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_messages` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_hourly_activity`
--

CREATE TABLE `analytics_hourly_activity` (
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `hour` tinyint(3) UNSIGNED NOT NULL,
  `message_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `tag` enum('IMPORTANT','CELEBRATION','UPDATE') NOT NULL DEFAULT 'UPDATE',
  `message` text NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `actor_member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_label` varchar(100) DEFAULT NULL,
  `status` enum('complete','failed','warning') NOT NULL DEFAULT 'complete',
  `activity_type` enum('login','logout','message_delete','channel_create','channel_archive','member_invite','member_remove','role_change','file_delete','workspace_update','password_change','2fa_enable','2fa_disable','invite_create','settings_update','OTHER') NOT NULL DEFAULT 'OTHER',
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channels`
--

CREATE TABLE `channels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(80) NOT NULL,
  `former_slugs` text DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('public','private') NOT NULL DEFAULT 'public',
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `member_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channel_join_requests`
--

CREATE TABLE `channel_join_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `channel_id` bigint(20) UNSIGNED NOT NULL,
  `workspace_member_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channel_members`
--

CREATE TABLE `channel_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_id` bigint(20) UNSIGNED NOT NULL,
  `workspace_member_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('owner','admin','member') NOT NULL DEFAULT 'member',
  `notifications_muted` tinyint(1) NOT NULL DEFAULT 0,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `channel_members`
--
DELIMITER $$
CREATE TRIGGER `trg_channel_members_after_delete` AFTER DELETE ON `channel_members` FOR EACH ROW BEGIN
    IF OLD.left_at IS NULL THEN
        UPDATE channels
        SET member_count = GREATEST(member_count, 1) - 1
        WHERE id = OLD.channel_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_channel_members_after_insert` AFTER INSERT ON `channel_members` FOR EACH ROW BEGIN
    IF NEW.left_at IS NULL THEN
        UPDATE channels
        SET member_count = member_count + 1
        WHERE id = NEW.channel_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_channel_members_after_update` AFTER UPDATE ON `channel_members` FOR EACH ROW BEGIN
    IF OLD.left_at IS NULL AND NEW.left_at IS NOT NULL THEN
        UPDATE channels
        SET member_count = GREATEST(member_count, 1) - 1
        WHERE id = NEW.channel_id;
    ELSEIF OLD.left_at IS NOT NULL AND NEW.left_at IS NULL THEN
        UPDATE channels
        SET member_count = member_count + 1
        WHERE id = NEW.channel_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('channel','dm','group_dm') NOT NULL,
  `channel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dm_hash` char(64) DEFAULT NULL,
  `last_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_message_at` timestamp(3) NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `workspace_member_id` bigint(20) UNSIGNED NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_read_cursors`
--

CREATE TABLE `conversation_read_cursors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_member_id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `last_read_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_read_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `original_name` varchar(512) NOT NULL,
  `storage_disk` enum('local','s3') NOT NULL DEFAULT 'local',
  `storage_path` varchar(1024) NOT NULL,
  `mime_type` varchar(128) NOT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `sha256` char(64) DEFAULT NULL,
  `category` enum('image','video','document','audio','other') NOT NULL DEFAULT 'other',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `files`
--
DELIMITER $$
CREATE TRIGGER `trg_files_after_delete` AFTER DELETE ON `files` FOR EACH ROW BEGIN
    UPDATE workspace_storage_quotas
    SET used_bytes = IF(used_bytes >= OLD.size_bytes, used_bytes - OLD.size_bytes, 0),  
        updated_at = CURRENT_TIMESTAMP
    WHERE workspace_id = OLD.workspace_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_files_after_insert` AFTER INSERT ON `files` FOR EACH ROW BEGIN
    INSERT INTO workspace_storage_quotas (workspace_id, used_bytes)
    VALUES (NEW.workspace_id, NEW.size_bytes)
    ON DUPLICATE KEY UPDATE
        used_bytes = used_bytes + NEW.size_bytes,
        updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `file_objects`
--

CREATE TABLE `file_objects` (
  `sha256` char(64) NOT NULL,
  `storage_disk` enum('local','s3') NOT NULL DEFAULT 's3',
  `storage_path` varchar(1024) NOT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `reply_to_id` bigint(20) UNSIGNED DEFAULT NULL,
  `forwarded_from_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `body` text NOT NULL,
  `message_type` enum('text','file','gif','system','voice') NOT NULL DEFAULT 'text',
  `edited_at` timestamp(3) NULL DEFAULT NULL,
  `deleted_for_everyone_at` timestamp(3) NULL DEFAULT NULL,
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `messages`
--
DELIMITER $$
CREATE TRIGGER `trg_messages_after_insert` AFTER INSERT ON `messages` FOR EACH ROW UPDATE conversations SET last_message_id = NEW.id, last_message_at = NEW.created_at WHERE id = NEW.conversation_id
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_delivery_states`
--

CREATE TABLE `message_delivery_states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_member_id` bigint(20) UNSIGNED NOT NULL,
  `state` enum('sent','delivered','read') NOT NULL DEFAULT 'sent',
  `read_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) ON UPDATE current_timestamp(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_forwards`
--

CREATE TABLE `message_forwards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `source_message_id` bigint(20) UNSIGNED NOT NULL,
  `target_conversation_id` bigint(20) UNSIGNED NOT NULL,
  `forwarded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_pins`
--

CREATE TABLE `message_pins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `pinned_by` bigint(20) UNSIGNED NOT NULL,
  `pinned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_reactions`
--

CREATE TABLE `message_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `workspace_member_id` bigint(20) UNSIGNED NOT NULL,
  `emoji` varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_user_deletions`
--

CREATE TABLE `message_user_deletions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `workspace_member_id` bigint(20) UNSIGNED NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('mention','file_share','file_upload','reaction','system','missed_call','channel_join','project','reply') NOT NULL,
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `body_html` text DEFAULT NULL,
  `reference_type` enum('message','file','channel','conversation','announcement') DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `key` varchar(128) NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_cache`
--

CREATE TABLE `system_cache` (
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `avatar_path` varchar(512) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `theme_color` varchar(20) NOT NULL DEFAULT 'indigo',
  `favorite_timezones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`favorite_timezones`)),
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_settings`)),
  `locale` varchar(10) NOT NULL DEFAULT 'en',
  `timezone` varchar(64) NOT NULL DEFAULT 'UTC',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_presence`
--

CREATE TABLE `user_presence` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('online','offline','away','dnd') NOT NULL DEFAULT 'offline',
  `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_security`
--

CREATE TABLE `user_security` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_people_directory`
-- (See below for the actual view)
--
CREATE TABLE `v_people_directory` (
`workspace_member_id` bigint(20) unsigned
,`workspace_id` bigint(20) unsigned
,`workspace_role` enum('owner','admin','member')
,`job_title` varchar(100)
,`member_status` enum('active','invited','suspended','deactivated')
,`joined_at` timestamp
,`user_id` bigint(20) unsigned
,`username` varchar(64)
,`email` varchar(255)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`display_name` varchar(201)
,`avatar_path` varchar(512)
,`bio` text
,`presence_status` varchar(7)
,`last_seen_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_workspace_files`
-- (See below for the actual view)
--
CREATE TABLE `v_workspace_files` (
`id` bigint(20) unsigned
,`workspace_id` bigint(20) unsigned
,`original_name` varchar(512)
,`mime_type` varchar(128)
,`extension` varchar(20)
,`size_bytes` bigint(20) unsigned
,`category` enum('image','video','document','audio','other')
,`created_at` timestamp
,`uploaded_by` bigint(20) unsigned
,`first_name` varchar(100)
,`last_name` varchar(100)
,`shared_by` varchar(201)
,`shared_avatar` varchar(512)
);

-- --------------------------------------------------------

--
-- Table structure for table `websocket_tickets`
--

CREATE TABLE `websocket_tickets` (
  `ticket` varchar(64) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `workspace_member_id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workspaces`
--

CREATE TABLE `workspaces` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `industry` enum('technology','healthcare','finance','education','manufacturing','retail','services','other') NOT NULL DEFAULT 'technology',
  `organization_type` enum('corporation','llc','partnership','sole_proprietorship','non_profit','other') NOT NULL DEFAULT 'corporation',
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `logo_path` varchar(512) DEFAULT NULL,
  `plan` enum('free','pro','enterprise') NOT NULL DEFAULT 'free',
  `status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workspace_addresses`
--

CREATE TABLE `workspace_addresses` (
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workspace_invites`
--

CREATE TABLE `workspace_invites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `token_hash` char(64) NOT NULL,
  `invited_by` bigint(20) UNSIGNED NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workspace_members`
--

CREATE TABLE `workspace_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('owner','admin','member') NOT NULL DEFAULT 'member',
  `job_title` varchar(100) DEFAULT NULL,
  `status` enum('active','invited','suspended','deactivated') NOT NULL DEFAULT 'active',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active_at` timestamp NULL DEFAULT NULL,
  `left_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workspace_storage_quotas`
--

CREATE TABLE `workspace_storage_quotas` (
  `workspace_id` bigint(20) UNSIGNED NOT NULL,
  `quota_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 16106127360,
  `used_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_people_directory`
--
DROP TABLE IF EXISTS `v_people_directory`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_people_directory`  AS SELECT `wm`.`id` AS `workspace_member_id`, `wm`.`workspace_id` AS `workspace_id`, `wm`.`role` AS `workspace_role`, `wm`.`job_title` AS `job_title`, `wm`.`status` AS `member_status`, `wm`.`joined_at` AS `joined_at`, `u`.`id` AS `user_id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `display_name`, `u`.`avatar_path` AS `avatar_path`, `u`.`bio` AS `bio`, coalesce(max(`up`.`status`),'offline') AS `presence_status`, max(`up`.`last_seen_at`) AS `last_seen_at` FROM ((`workspace_members` `wm` join `users` `u` on(`u`.`id` = `wm`.`user_id` and `u`.`deleted_at` is null)) left join `user_presence` `up` on(`up`.`user_id` = `u`.`id`)) WHERE `wm`.`status` = 'active' AND `wm`.`left_at` is null GROUP BY `wm`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_workspace_files`
--
DROP TABLE IF EXISTS `v_workspace_files`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_workspace_files`  AS SELECT `f`.`id` AS `id`, `f`.`workspace_id` AS `workspace_id`, `f`.`original_name` AS `original_name`, `f`.`mime_type` AS `mime_type`, `f`.`extension` AS `extension`, `f`.`size_bytes` AS `size_bytes`, `f`.`category` AS `category`, `f`.`created_at` AS `created_at`, `f`.`uploaded_by` AS `uploaded_by`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `shared_by`, `u`.`avatar_path` AS `shared_avatar` FROM ((`files` `f` join `workspace_members` `wm` on(`wm`.`id` = `f`.`uploaded_by`)) join `users` `u` on(`u`.`id` = `wm`.`user_id`)) WHERE `f`.`deleted_at` is null ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `channels`
--
ALTER TABLE `channels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `channel_join_requests`
--
ALTER TABLE `channel_join_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `channel_members`
--
ALTER TABLE `channel_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversation_read_cursors`
--
ALTER TABLE `conversation_read_cursors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `messages` ADD FULLTEXT KEY `ft_messages_body` (`body`);

--
-- Indexes for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message_delivery_states`
--
ALTER TABLE `message_delivery_states`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message_forwards`
--
ALTER TABLE `message_forwards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message_pins`
--
ALTER TABLE `message_pins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message_user_deletions`
--
ALTER TABLE `message_user_deletions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workspaces`
--
ALTER TABLE `workspaces`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workspace_invites`
--
ALTER TABLE `workspace_invites`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workspace_members`
--
ALTER TABLE `workspace_members`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channels`
--
ALTER TABLE `channels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channel_join_requests`
--
ALTER TABLE `channel_join_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channel_members`
--
ALTER TABLE `channel_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation_read_cursors`
--
ALTER TABLE `conversation_read_cursors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_delivery_states`
--
ALTER TABLE `message_delivery_states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_forwards`
--
ALTER TABLE `message_forwards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_pins`
--
ALTER TABLE `message_pins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_reactions`
--
ALTER TABLE `message_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_user_deletions`
--
ALTER TABLE `message_user_deletions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workspaces`
--
ALTER TABLE `workspaces`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workspace_invites`
--
ALTER TABLE `workspace_invites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workspace_members`
--
ALTER TABLE `workspace_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
