<?php

/**
 * ChatRox Database Migration Script (Old Schema to New Schema)
 * 
 * This script migrates core data from the old schema (e.g. chtrox_old)
 * to the new workspace-based schema (e.g. chatrox).
 * 
 * Usage:
 *   php bin/migrate_data.php \
 *     --src-db=chtrox_old \
 *     --tgt-db=chatrox \
 *     --src-user=root \
 *     --src-pass= \
 *     --tgt-user=root \
 *     --tgt-pass= \
 *     --clear-target
 */

declare(strict_types=1);

// Increase memory limit for large datasets
ini_set('memory_limit', '512M');

// Prevent execution via web server
if (PHP_SAPI !== 'cli') {
    die("This script can only be run via CLI.\n");
}

// Parse command line arguments
$options = getopt('', [
    'src-host::',
    'src-port::',
    'src-user::',
    'src-pass::',
    'src-db:',
    'tgt-host::',
    'tgt-port::',
    'tgt-user::',
    'tgt-pass::',
    'tgt-db:',
    'clear-target'
]);

$srcHost = $options['src-host'] ?? '127.0.0.1';
$srcPort = $options['src-port'] ?? '3306';
$srcUser = $options['src-user'] ?? 'root';
$srcPass = $options['src-pass'] ?? '';
$srcDb   = $options['src-db'] ?? null;

$tgtHost = $options['tgt-host'] ?? '127.0.0.1';
$tgtPort = $options['tgt-port'] ?? '3306';
$tgtUser = $options['tgt-user'] ?? 'root';
$tgtPass = $options['tgt-pass'] ?? '';
$tgtDb   = $options['tgt-db'] ?? null;

$clearTarget = isset($options['clear-target']);

if (!$srcDb || !$tgtDb) {
    echo "Usage Error: Both --src-db and --tgt-db parameters are required.\n";
    echo "Example:\n";
    echo "  php bin/migrate_data.php --src-db=chtrox_old --tgt-db=chatrox --clear-target\n";
    exit(1);
}

try {
    echo "Connecting to Source Database: {$srcDb}...\n";
    $srcPdo = new PDO("mysql:host={$srcHost};port={$srcPort};dbname={$srcDb};charset=utf8mb4", $srcUser, $srcPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connecting to Target Database: {$tgtDb}...\n";
    $tgtPdo = new PDO("mysql:host={$tgtHost};port={$tgtPort};dbname={$tgtDb};charset=utf8mb4", $tgtUser, $tgtPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Disable foreign keys check for target database to allow clean import/clearing
    $tgtPdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Truncate tables if --clear-target is requested
    if ($clearTarget) {
        echo "Clearing Target Database Tables...\n";
        $tablesToClear = [
            'workspaces',
            'workspace_addresses',
            'users',
            'workspace_members',
            'user_presence',
            'user_preferences',
            'user_security',
            'channels',
            'channel_members',
            'conversations',
            'conversation_participants',
            'messages',
            'files',
            'message_attachments',
            'user_sessions',
            'password_reset_tokens'
        ];
        foreach ($tablesToClear as $table) {
            $tgtPdo->exec("TRUNCATE TABLE `{$table}`;");
            echo "  - Truncated table: {$table}\n";
        }
    }

    echo "\nStarting Migration Workflow...\n";
    echo str_repeat('=', 60) . "\n";

    // -------------------------------------------------------------------------
    // STEP 1: Companies -> Workspaces & Addresses
    // -------------------------------------------------------------------------
    echo "[STEP 1] Migrating Companies to Workspaces...\n";
    $companies = $srcPdo->query("SELECT * FROM companies")->fetchAll();
    
    $stmtWs = $tgtPdo->prepare("
        INSERT INTO workspaces (id, slug, name, industry, organization_type, email, phone, logo_path, plan, status, created_at, updated_at)
        VALUES (:id, :slug, :name, :industry, :organization_type, :email, :phone, NULL, 'free', 'active', :created_at, :updated_at)
    ");
    $stmtAddr = $tgtPdo->prepare("
        INSERT INTO workspace_addresses (workspace_id, address_line1, city, state, country, postal_code, created_at, updated_at)
        VALUES (:workspace_id, :address_line1, :city, :state, :country, :postal_code, :created_at, :updated_at)
    ");

    $validIndustries = ['technology', 'healthcare', 'finance', 'education', 'manufacturing', 'retail', 'services', 'other'];
    $validOrgTypes = ['corporation', 'llc', 'partnership', 'sole_proprietorship', 'non_profit', 'other'];

    foreach ($companies as $company) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $company['name']));
        $slug = trim($slug, '-');

        $industry = strtolower($company['industry']);
        if (!in_array($industry, $validIndustries, true)) {
            $industry = 'technology';
        }

        $orgType = strtolower($company['organization_type']);
        if (!in_array($orgType, $validOrgTypes, true)) {
            $orgType = 'corporation';
        }

        $stmtWs->execute([
            ':id' => $company['id'],
            ':slug' => $slug,
            ':name' => $company['name'],
            ':industry' => $industry,
            ':organization_type' => $orgType,
            ':email' => $company['email'],
            ':phone' => $company['phone'],
            ':created_at' => $company['created_at'],
            ':updated_at' => $company['updated_at']
        ]);

        $stmtAddr->execute([
            ':workspace_id' => $company['id'],
            ':address_line1' => $company['address'] ?? '',
            ':city' => $company['city'] ?? '',
            ':state' => $company['state'] ?? '',
            ':country' => $company['country'] ?? '',
            ':postal_code' => $company['zip_code'] ?? '',
            ':created_at' => $company['created_at'],
            ':updated_at' => $company['updated_at']
        ]);
    }
    echo "  - Migrated " . count($companies) . " workspaces.\n";

    // -------------------------------------------------------------------------
    // STEP 2: Users -> Users & Workspace Members & Preferences
    // -------------------------------------------------------------------------
    echo "[STEP 2] Migrating Users to Users & Workspace Members...\n";
    $users = $srcPdo->query("SELECT * FROM users")->fetchAll();

    $stmtUser = $tgtPdo->prepare("
        INSERT INTO users (id, email, username, password_hash, first_name, last_name, phone, avatar_path, bio, email_verified_at, created_at, updated_at, deleted_at)
        VALUES (:id, :email, :username, :password_hash, :first_name, :last_name, :phone, :avatar_path, :bio, :email_verified_at, :created_at, :updated_at, :deleted_at)
    ");
    $stmtMember = $tgtPdo->prepare("
        INSERT INTO workspace_members (workspace_id, user_id, role, job_title, status, joined_at, created_at, updated_at)
        VALUES (:workspace_id, :user_id, :role, NULL, :status, :joined_at, :created_at, :updated_at)
    ");
    $stmtPres = $tgtPdo->prepare("
        INSERT INTO user_presence (user_id, status, last_seen_at, updated_at, preferred_status)
        VALUES (:user_id, 'offline', :created_at, :created_at, 'online')
    ");
    $stmtPref = $tgtPdo->prepare("
        INSERT INTO user_preferences (user_id, theme_color, locale, timezone, created_at, updated_at)
        VALUES (:user_id, 'indigo', 'en', 'UTC', :created_at, :updated_at)
    ");
    $stmtSec = $tgtPdo->prepare("
        INSERT INTO user_security (user_id, two_factor_enabled, created_at, updated_at)
        VALUES (:user_id, 0, :created_at, :updated_at)
    ");

    // Mapping tracking array: (workspace_id, user_id) => workspace_member_id
    $memberMap = [];

    foreach ($users as $user) {
        $avatarPath = $user['profile_picture'];
        if ($avatarPath === 'includes/image/default-avatar.jpg') {
            $avatarPath = 'assets/images/default-avatar.svg';
        }

        $emailVerifiedAt = $user['email_verified'] ? $user['created_at'] : null;
        $deletedAt = $user['active'] ? null : $user['updated_at'];

        $stmtUser->execute([
            ':id' => $user['id'],
            ':email' => $user['email'],
            ':username' => $user['username'],
            ':password_hash' => $user['password'],
            ':first_name' => $user['first_name'],
            ':last_name' => $user['last_name'],
            ':phone' => $user['phone'],
            ':avatar_path' => $avatarPath,
            ':bio' => $user['bio'],
            ':email_verified_at' => $emailVerifiedAt,
            ':created_at' => $user['created_at'],
            ':updated_at' => $user['updated_at'],
            ':deleted_at' => $deletedAt
        ]);

        $role = $user['role'] === 'admin' ? 'admin' : 'member';
        $status = $user['active'] ? 'active' : 'deactivated';

        $stmtMember->execute([
            ':workspace_id' => $user['company_id'],
            ':user_id' => $user['id'],
            ':role' => $role,
            ':status' => $status,
            ':joined_at' => $user['created_at'],
            ':created_at' => $user['created_at'],
            ':updated_at' => $user['updated_at']
        ]);

        $memberId = (int)$tgtPdo->lastInsertId();
        $memberMap[$user['company_id']][$user['id']] = $memberId;

        $stmtPres->execute([
            ':user_id' => $user['id'],
            ':created_at' => $user['created_at']
        ]);

        $stmtPref->execute([
            ':user_id' => $user['id'],
            ':created_at' => $user['created_at'],
            ':updated_at' => $user['updated_at']
        ]);

        $stmtSec->execute([
            ':user_id' => $user['id'],
            ':created_at' => $user['created_at'],
            ':updated_at' => $user['updated_at']
        ]);
    }
    echo "  - Migrated " . count($users) . " users and registered workspace memberships.\n";

    // -------------------------------------------------------------------------
    // STEP 3: Channels -> Channels, conversations, members
    // -------------------------------------------------------------------------
    echo "[STEP 3] Migrating Channels and Channel Members...\n";
    $channels = $srcPdo->query("SELECT * FROM channels")->fetchAll();

    $stmtChan = $tgtPdo->prepare("
        INSERT INTO channels (id, workspace_id, slug, former_slugs, name, description, visibility, status, is_default, created_by, member_count, created_at, updated_at)
        VALUES (:id, :workspace_id, :slug, NULL, :name, :description, :visibility, 'active', :is_default, :created_by, 0, :created_at, :updated_at)
    ");
    $stmtConvo = $tgtPdo->prepare("
        INSERT INTO conversations (workspace_id, type, channel_id, dm_hash, last_message_id, last_message_at, created_at)
        VALUES (:workspace_id, 'channel', :channel_id, NULL, NULL, NULL, :created_at)
    ");
    $stmtChanMember = $tgtPdo->prepare("
        INSERT INTO channel_members (channel_id, workspace_member_id, role, notifications_muted, joined_at)
        VALUES (:channel_id, :workspace_member_id, :role, 0, :joined_at)
    ");
    $stmtConvoPart = $tgtPdo->prepare("
        INSERT INTO conversation_participants (conversation_id, workspace_member_id, joined_at)
        VALUES (:conversation_id, :workspace_member_id, :joined_at)
    ");

    // Maps to track conversation IDs
    $channelConvoMap = []; // channel_id => conversation_id

    foreach ($channels as $channel) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $channel['name']));
        $slug = trim($slug, '-');

        $visibility = $channel['is_private'] ? 'private' : 'public';
        $isDefault = in_array(strtolower($channel['name']), ['general', 'random'], true) ? 1 : 0;

        $workspaceId = $channel['company_id'];
        $createdByUserId = $channel['created_by'];
        $createdByMemberId = $memberMap[$workspaceId][$createdByUserId] ?? 0;

        $stmtChan->execute([
            ':id' => $channel['id'],
            ':workspace_id' => $workspaceId,
            ':slug' => $slug,
            ':name' => $channel['name'],
            ':description' => $channel['description'],
            ':visibility' => $visibility,
            ':is_default' => $isDefault,
            ':created_by' => $createdByMemberId,
            ':created_at' => $channel['created_at'],
            ':updated_at' => $channel['updated_at']
        ]);

        $stmtConvo->execute([
            ':workspace_id' => $workspaceId,
            ':channel_id' => $channel['id'],
            ':created_at' => $channel['created_at']
        ]);

        $convoId = (int)$tgtPdo->lastInsertId();
        $channelConvoMap[$channel['id']] = $convoId;

        // Fetch channel members
        $chanMembers = $srcPdo->prepare("SELECT * FROM channel_members WHERE channel_id = ?");
        $chanMembers->execute([$channel['id']]);
        $membersList = $chanMembers->fetchAll();

        foreach ($membersList as $member) {
            $wsMemberId = $memberMap[$workspaceId][$member['user_id']] ?? null;
            if ($wsMemberId === null) {
                continue;
            }

            $role = 'member';
            if ($member['user_id'] === $createdByUserId) {
                $role = 'owner';
            } elseif ($member['role'] === 'admin') {
                $role = 'admin';
            }

            $stmtChanMember->execute([
                ':channel_id' => $channel['id'],
                ':workspace_member_id' => $wsMemberId,
                ':role' => $role,
                ':joined_at' => $member['joined_at']
            ]);

            $stmtConvoPart->execute([
                ':conversation_id' => $convoId,
                ':workspace_member_id' => $wsMemberId,
                ':joined_at' => $member['joined_at']
            ]);
        }
    }
    echo "  - Migrated " . count($channels) . " channels, created conversation rows, and registered participants.\n";

    // -------------------------------------------------------------------------
    // STEP 4: DM Conversations Roster Setup
    // -------------------------------------------------------------------------
    echo "[STEP 4] Setting Up DM Conversations...\n";
    $dmsList = $srcPdo->query("
        SELECT DISTINCT company_id, sender_id, receiver_id 
        FROM messages 
        WHERE receiver_id IS NOT NULL AND channel_id IS NULL
    ")->fetchAll();

    $stmtConvoDm = $tgtPdo->prepare("
        INSERT INTO conversations (workspace_id, type, channel_id, dm_hash, last_message_id, last_message_at, created_at)
        VALUES (:workspace_id, 'dm', NULL, :dm_hash, NULL, NULL, :created_at)
    ");

    $dmConvoMap = []; // dm_hash => conversation_id

    foreach ($dmsList as $dm) {
        $wsId = $dm['company_id'];
        $senderMemberId = $memberMap[$wsId][$dm['sender_id']] ?? null;
        $receiverMemberId = $memberMap[$wsId][$dm['receiver_id']] ?? null;

        if ($senderMemberId === null || $receiverMemberId === null) {
            continue;
        }

        $memberIds = [$senderMemberId, $receiverMemberId];
        sort($memberIds);
        $dmHash = hash('sha256', implode(':', $memberIds));

        if (isset($dmConvoMap[$dmHash])) {
            continue;
        }

        // Get oldest DM message timestamp as conversation created_at
        $stmtTime = $srcPdo->prepare("
            SELECT MIN(created_at) FROM messages 
            WHERE company_id = ? AND sender_id = ? AND receiver_id = ? AND channel_id IS NULL
        ");
        $stmtTime->execute([$wsId, $dm['sender_id'], $dm['receiver_id']]);
        $oldestMsgTime = $stmtTime->fetchColumn() ?: date('Y-m-d H:i:s');

        $stmtConvoDm->execute([
            ':workspace_id' => $wsId,
            ':dm_hash' => $dmHash,
            ':created_at' => $oldestMsgTime
        ]);

        $convoId = (int)$tgtPdo->lastInsertId();
        $dmConvoMap[$dmHash] = $convoId;

        // Add both participants to conversation_participants
        $stmtConvoPart->execute([
            ':conversation_id' => $convoId,
            ':workspace_member_id' => $senderMemberId,
            ':joined_at' => $oldestMsgTime
        ]);
        if ($senderMemberId !== $receiverMemberId) {
            $stmtConvoPart->execute([
                ':conversation_id' => $convoId,
                ':workspace_member_id' => $receiverMemberId,
                ':joined_at' => $oldestMsgTime
            ]);
        }
    }
    echo "  - Prepared " . count($dmConvoMap) . " unique DM conversation threads and participants.\n";

    // -------------------------------------------------------------------------
    // STEP 5: Messages (Chronological Merging)
    // -------------------------------------------------------------------------
    echo "[STEP 5] Extracting, Sorting, and Migrating Messages (Streamed)...\n";
    
    $query = "
        (SELECT 'channel_messages' AS source_table, id, company_id AS workspace_id, channel_id, NULL AS receiver_id, user_id, message, has_media, has_voice, is_deleted, is_edited, reply_to_id, created_at, updated_at
         FROM channel_messages)
        UNION ALL
        (SELECT 'messages' AS source_table, id, company_id AS workspace_id, NULL AS channel_id, receiver_id, sender_id AS user_id, message, has_media, has_voice, is_deleted, is_edited, reply_to_id, created_at, updated_at
         FROM messages
         WHERE receiver_id IS NOT NULL AND channel_id IS NULL)
        ORDER BY created_at ASC
    ";

    $stmtSelectMsg = $srcPdo->prepare($query);
    $stmtSelectMsg->execute();

    $stmtInsertMsg = $tgtPdo->prepare("
        INSERT INTO messages (workspace_id, conversation_id, sender_id, reply_to_id, forwarded_from_message_id, body, message_type, edited_at, deleted_for_everyone_at, created_at)
        VALUES (:workspace_id, :conversation_id, :sender_id, :reply_to_id, NULL, :body, :message_type, :edited_at, :deleted_for_everyone_at, :created_at)
    ");

    // Tracks: (source_table, old_id) => new_message_id
    $messageIdMap = [];
    $migratedMsgCount = 0;
    
    // We will collect reply_to relationships that need post-processing 
    // to make sure they are resolved correctly in a second pass.
    $repliesToResolve = [];

    // Transaction for fast bulk inserts
    $tgtPdo->beginTransaction();

    while ($msg = $stmtSelectMsg->fetch()) {
        // Exclude voice messages
        if ((int)$msg['has_voice'] === 1) {
            continue;
        }

        $wsId = (int)$msg['workspace_id'];
        $senderMemberId = $memberMap[$wsId][(int)$msg['user_id']] ?? null;
        if ($senderMemberId === null) {
            continue;
        }

        $convoId = null;
        if ($msg['source_table'] === 'channel_messages') {
            $convoId = $channelConvoMap[(int)$msg['channel_id']] ?? null;
        } else {
            // It is a DM. Map using sender and receiver workspace_member_ids
            $receiverMemberId = $memberMap[$wsId][(int)$msg['receiver_id']] ?? null;
            if ($receiverMemberId === null) {
                continue;
            }
            $memberIds = [$senderMemberId, $receiverMemberId];
            sort($memberIds);
            $dmHash = hash('sha256', implode(':', $memberIds));
            $convoId = $dmConvoMap[$dmHash] ?? null;
        }

        if ($convoId === null) {
            continue;
        }

        $messageType = (int)$msg['has_media'] === 1 ? 'file' : 'text';
        $editedAt = (int)$msg['is_edited'] === 1 ? $msg['updated_at'] : null;
        $deletedAt = (int)$msg['is_deleted'] === 1 ? $msg['updated_at'] : null;

        $body = mb_substr($msg['message'] ?? '', 0, 65000, 'UTF-8');

        $stmtInsertMsg->execute([
            ':workspace_id' => $wsId,
            ':conversation_id' => $convoId,
            ':sender_id' => $senderMemberId,
            ':reply_to_id' => null, // Will update in second pass
            ':body' => $body,
            ':message_type' => $messageType,
            ':edited_at' => $editedAt,
            ':deleted_for_everyone_at' => $deletedAt,
            ':created_at' => $msg['created_at']
        ]);

        $newMsgId = (int)$tgtPdo->lastInsertId();
        $messageIdMap[$msg['source_table']][(int)$msg['id']] = $newMsgId;
        
        if ($msg['reply_to_id'] !== null) {
            $repliesToResolve[] = [
                'new_id' => $newMsgId,
                'source_table' => $msg['source_table'],
                'old_reply_to_id' => (int)$msg['reply_to_id']
            ];
        }

        $migratedMsgCount++;
    }

    $tgtPdo->commit();
    echo "  - Migrated " . $migratedMsgCount . " text and file messages.\n";

    // -------------------------------------------------------------------------
    // STEP 6: Resolving Thread Replies
    // -------------------------------------------------------------------------
    echo "[STEP 6] Resolving Thread Reply Relations...\n";
    $stmtUpdateReply = $tgtPdo->prepare("UPDATE messages SET reply_to_id = ? WHERE id = ?");

    $tgtPdo->beginTransaction();
    $repliesUpdated = 0;

    foreach ($repliesToResolve as $reply) {
        $newReplyToId = $messageIdMap[$reply['source_table']][$reply['old_reply_to_id']] ?? null;

        if ($newReplyToId !== null) {
            $stmtUpdateReply->execute([$newReplyToId, $reply['new_id']]);
            $repliesUpdated++;
        }
    }
    $tgtPdo->commit();
    echo "  - Resolved " . $repliesUpdated . " thread replies.\n";

    // -------------------------------------------------------------------------
    // STEP 7: Files and Message Attachments
    // -------------------------------------------------------------------------
    echo "[STEP 7] Migrating File Uploads and Attachments...\n";
    
    // Format helpers
    $mimeMapping = [
        'png'  => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
        'pdf'  => 'application/pdf', 'zip' => 'application/zip', 'txt' => 'text/plain',
        'mp4'  => 'video/mp4', 'webm' => 'video/webm', 'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    $stmtFile = $tgtPdo->prepare("
        INSERT INTO files (workspace_id, uploaded_by, original_name, storage_disk, storage_path, mime_type, extension, size_bytes, sha256, category, created_at)
        VALUES (:workspace_id, :uploaded_by, :original_name, 'local', :storage_path, :mime_type, :extension, 0, NULL, :category, :created_at)
    ");
    $stmtAttach = $tgtPdo->prepare("
        INSERT INTO message_attachments (message_id, file_id, created_at)
        VALUES (:message_id, :file_id, :created_at)
    ");

    $tgtPdo->beginTransaction();
    $filesCount = 0;

    // Helper closure to process file records
    $migrateFileFunc = function (array $mediaRecord, string $sourceTable, PDO $tgtPdo, PDOStatement $stmtFile, PDOStatement $stmtAttach, array $messageIdMap, array $mimeMapping) use (&$filesCount) {
        $newMsgId = $messageIdMap[$sourceTable][$mediaRecord['message_id']] ?? null;
        if ($newMsgId === null) {
            return;
        }

        // Get workspace_id and sender_id from the new message row
        $stmtInfo = $tgtPdo->prepare("SELECT workspace_id, sender_id FROM messages WHERE id = ?");
        $stmtInfo->execute([$newMsgId]);
        $msgInfo = $stmtInfo->fetch();
        if (!$msgInfo) {
            return;
        }

        $origName = basename($mediaRecord['file_path']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $mime = $mimeMapping[$ext] ?? 'application/octet-stream';
        $category = 'other';
        if (isset($mediaRecord['file_type'])) {
            $category = $mediaRecord['file_type'] === 'image' ? 'image' : ($mediaRecord['file_type'] === 'video' ? 'video' : 'document');
        }

        $stmtFile->execute([
            ':workspace_id' => $msgInfo['workspace_id'],
            ':uploaded_by' => $msgInfo['sender_id'],
            ':original_name' => $origName,
            ':storage_path' => $mediaRecord['file_path'],
            ':mime_type' => $mime,
            ':extension' => $ext,
            ':category' => $category,
            ':created_at' => $mediaRecord['uploaded_at']
        ]);

        $fileId = (int)$tgtPdo->lastInsertId();

        $stmtAttach->execute([
            ':message_id' => $newMsgId,
            ':file_id' => $fileId,
            ':created_at' => $mediaRecord['uploaded_at']
        ]);

        $filesCount++;
    };

    // 1. Channel media attachments
    $chanMedia = $srcPdo->query("SELECT * FROM channel_message_media")->fetchAll();
    foreach ($chanMedia as $media) {
        $migrateFileFunc($media, 'channel_messages', $tgtPdo, $stmtFile, $stmtAttach, $messageIdMap, $mimeMapping);
    }

    // 2. DM media attachments
    $dmMedia = $srcPdo->query("SELECT * FROM media")->fetchAll();
    foreach ($dmMedia as $media) {
        $migrateFileFunc($media, 'messages', $tgtPdo, $stmtFile, $stmtAttach, $messageIdMap, $mimeMapping);
    }

    $tgtPdo->commit();
    echo "  - Migrated " . $filesCount . " uploaded files and attachment entries.\n";

    // -------------------------------------------------------------------------
    // STEP 8: User Sessions and Password Reset Tokens
    // -------------------------------------------------------------------------
    echo "[STEP 8] Migrating User Sessions and Reset Tokens...\n";
    
    // User sessions
    $sessions = $srcPdo->query("SELECT * FROM sessions")->fetchAll();
    $stmtSession = $tgtPdo->prepare("
        INSERT INTO user_sessions (user_id, session_token, device_name, ip_address, user_agent, last_seen_at, revoked_at, created_at)
        VALUES (:user_id, :session_token, 'Unknown', :ip_address, :user_agent, :last_seen_at, NULL, :created_at)
    ");

    $tgtPdo->beginTransaction();
    foreach ($sessions as $sess) {
        $stmtSession->execute([
            ':user_id' => $sess['user_id'],
            ':session_token' => $sess['session_id'],
            ':ip_address' => $sess['ip_address'],
            ':user_agent' => $sess['user_agent'] ? substr($sess['user_agent'], 0, 512) : null,
            ':last_seen_at' => $sess['expires_at'],
            ':created_at' => $sess['created_at']
        ]);
    }
    $tgtPdo->commit();
    echo "  - Migrated " . count($sessions) . " user sessions.\n";

    // Password resets
    $resets = $srcPdo->query("SELECT * FROM password_resets")->fetchAll();
    $stmtReset = $tgtPdo->prepare("
        INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, used_at, created_at)
        VALUES (:user_id, :token_hash, :expires_at, :used_at, :created_at)
    ");

    $tgtPdo->beginTransaction();
    foreach ($resets as $res) {
        $tokenHash = hash('sha256', $res['token']);
        $usedAt = $res['used'] ? $res['created_at'] : null;

        $stmtReset->execute([
            ':user_id' => $res['user_id'],
            ':token_hash' => $tokenHash,
            ':expires_at' => $res['expires_at'],
            ':used_at' => $usedAt,
            ':created_at' => $res['created_at']
        ]);
    }
    $tgtPdo->commit();
    echo "  - Migrated " . count($resets) . " password reset tokens.\n";

    // -------------------------------------------------------------------------
    // STEP 9: Ensuring Default Channels (general and announcements) Exist
    // -------------------------------------------------------------------------
    echo "[STEP 9] Ensuring Default Channels (general and announcements) Exist...\n";

    $workspacesList = $tgtPdo->query("SELECT id FROM workspaces")->fetchAll();

    $stmtCheckChan = $tgtPdo->prepare("SELECT id FROM channels WHERE workspace_id = ? AND slug = ? LIMIT 1");
    
    $stmtCreateChan = $tgtPdo->prepare("
        INSERT INTO channels (workspace_id, slug, former_slugs, name, description, visibility, status, is_default, created_by, member_count, created_at, updated_at)
        VALUES (:workspace_id, :slug, NULL, :name, :description, 'public', 'active', 1, :created_by, 0, NOW(), NOW())
    ");

    $stmtCreateConvo = $tgtPdo->prepare("
        INSERT INTO conversations (workspace_id, type, channel_id, dm_hash, last_message_id, last_message_at, created_at)
        VALUES (:workspace_id, 'channel', :channel_id, NULL, NULL, NULL, NOW())
    ");

    $stmtCheckMember = $tgtPdo->prepare("SELECT 1 FROM channel_members WHERE channel_id = ? AND workspace_member_id = ? LIMIT 1");

    $stmtAddChanMember = $tgtPdo->prepare("
        INSERT INTO channel_members (channel_id, workspace_member_id, role, notifications_muted, joined_at)
        VALUES (:channel_id, :workspace_member_id, 'member', 0, NOW())
    ");

    $stmtCheckConvoPart = $tgtPdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ? LIMIT 1");

    $stmtAddConvoPart = $tgtPdo->prepare("
        INSERT INTO conversation_participants (conversation_id, workspace_member_id, joined_at)
        VALUES (:conversation_id, :workspace_member_id, NOW())
    ");

    foreach ($workspacesList as $ws) {
        $wsId = (int)$ws['id'];

        // Get all active members in this workspace
        $stmtAllMembers = $tgtPdo->prepare("SELECT id FROM workspace_members WHERE workspace_id = ? AND status = 'active'");
        $stmtAllMembers->execute([$wsId]);
        $wsMembers = $stmtAllMembers->fetchAll();

        if (empty($wsMembers)) {
            continue;
        }

        // We will default the created_by of these default channels to the first member
        $firstMemberId = (int)$wsMembers[0]['id'];

        $defaultChannels = [
            [
                'slug' => 'general',
                'name' => 'General',
                'description' => 'Company-wide announcements and work-based matters.'
            ],
            [
                'slug' => 'announcements',
                'name' => 'Announcements',
                'description' => 'Official announcements and updates.'
            ]
        ];

        foreach ($defaultChannels as $defChan) {
            // Check if exists
            $stmtCheckChan->execute([$wsId, $defChan['slug']]);
            $chanId = $stmtCheckChan->fetchColumn();

            if (!$chanId) {
                // Create channel
                $stmtCreateChan->execute([
                    ':workspace_id' => $wsId,
                    ':slug' => $defChan['slug'],
                    ':name' => $defChan['name'],
                    ':description' => $defChan['description'],
                    ':created_by' => $firstMemberId
                ]);
                $chanId = (int)$tgtPdo->lastInsertId();

                // Create conversation
                $stmtCreateConvo->execute([
                    ':workspace_id' => $wsId,
                    ':channel_id' => $chanId
                ]);
                $convoId = (int)$tgtPdo->lastInsertId();
                echo "  - Created default channel '{$defChan['name']}' in workspace ID: {$wsId}\n";
            } else {
                $chanId = (int)$chanId;
                // Get existing conversation ID
                $stmtGetConvo = $tgtPdo->prepare("SELECT id FROM conversations WHERE channel_id = ? LIMIT 1");
                $stmtGetConvo->execute([$chanId]);
                $convoId = (int)$stmtGetConvo->fetchColumn();
            }

            // Ensure every active member is in the channel and conversation
            $tgtPdo->beginTransaction();
            foreach ($wsMembers as $member) {
                $mId = (int)$member['id'];

                // Check channel member
                $stmtCheckMember->execute([$chanId, $mId]);
                if (!$stmtCheckMember->fetchColumn()) {
                    $stmtAddChanMember->execute([
                        ':channel_id' => $chanId,
                        ':workspace_member_id' => $mId
                    ]);
                }

                // Check conversation participant
                if ($convoId) {
                    $stmtCheckConvoPart->execute([$convoId, $mId]);
                    if (!$stmtCheckConvoPart->fetchColumn()) {
                        $stmtAddConvoPart->execute([
                            ':conversation_id' => $convoId,
                            ':workspace_member_id' => $mId
                        ]);
                    }
                }
            }
            $tgtPdo->commit();
        }
    }
    echo "  - Default channels ensured and populated.\n";

    // -------------------------------------------------------------------------
    // STEP 10: Refreshing Channel Member Counts
    // -------------------------------------------------------------------------
    echo "[STEP 10] Refreshing Channel Member Counts...\n";
    $channelsList = $tgtPdo->query("SELECT id FROM channels")->fetchAll();
    $stmtUpdateCount = $tgtPdo->prepare("UPDATE channels SET member_count = ? WHERE id = ?");

    $tgtPdo->beginTransaction();
    foreach ($channelsList as $chan) {
        $stmtCount = $tgtPdo->prepare("SELECT COUNT(*) FROM channel_members WHERE channel_id = ?");
        $stmtCount->execute([$chan['id']]);
        $count = (int)$stmtCount->fetchColumn();

        $stmtUpdateCount->execute([$count, $chan['id']]);
    }
    $tgtPdo->commit();
    echo "  - Channel member counts refreshed.\n";

    // Re-enable target database foreign keys check
    $tgtPdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo str_repeat('=', 60) . "\n";
    echo "Migration Completed Successfully!\n";

} catch (PDOException $e) {
    if (isset($tgtPdo) && $tgtPdo->inTransaction()) {
        $tgtPdo->rollBack();
    }
    echo "\nFATAL DATABASE ERROR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
