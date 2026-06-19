<?php

/**
 * ChatRox Database Seeder
 * 
 * Creates a complete demo workspace with realistic data:
 *   1 workspace, 7 users, 5 channels, 55+ messages, 30 days analytics
 * 
 * Usage:
 *   php database/seed.php
 * 
 * All user passwords: password123
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────

$rootDir = dirname(__DIR__);

// Load autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . $relative . '.php';
    if (is_file($file)) require $file;
});

// Load .env
$envPath = $rootDir . '/.env';
if (is_file($envPath)) {
    (new App\Core\DotEnv($envPath))->load();
}

require_once $rootDir . '/config/config.php';

// ── Connect ──────────────────────────────────────────────────────────────────

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    echo "❌ Database connection failed: {$e->getMessage()}\n";
    echo "   Make sure you've imported database/chatrox.sql first.\n";
    exit(1);
}

echo "🚀 ChatRox Seeder — Starting...\n\n";

// ── Helper ───────────────────────────────────────────────────────────────────

function insert(PDO $pdo, string $table, array $data): int
{
    $cols = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $stmt = $pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$placeholders})");
    $stmt->execute(array_values($data));
    return (int) $pdo->lastInsertId();
}

function timestampDaysAgo(int $days): string
{
    return date('Y-m-d H:i:s', strtotime("-{$days} days"));
}

function randomTimestampBetween(string $start, string $end): string
{
    $startTs = strtotime($start);
    $endTs = strtotime($end);
    return date('Y-m-d H:i:s', mt_rand($startTs, $endTs));
}

$passwordHash = password_hash('password123', PASSWORD_BCRYPT);

// ══════════════════════════════════════════════════════════════════════════════
// 1. WORKSPACE
// ══════════════════════════════════════════════════════════════════════════════

echo "📦 Creating workspace...\n";

$workspaceId = insert($pdo, 'workspaces', [
    'slug' => 'nexustech',
    'name' => 'NexusTech Solutions',
    'industry' => 'technology',
    'organization_type' => 'corporation',
    'email' => 'contact@nexustech.io',
    'phone' => '+1 415 555 0100',
    'plan' => 'pro',
    'status' => 'active',
]);

insert($pdo, 'workspace_addresses', [
    'workspace_id' => $workspaceId,
    'address_line1' => '742 Innovation Drive, Suite 400',
    'city' => 'San Francisco',
    'state' => 'California',
    'country' => 'United States',
    'postal_code' => '94107',
]);

insert($pdo, 'workspace_storage_quotas', [
    'workspace_id' => $workspaceId,
    'quota_bytes' => 16106127360, // 15 GB
    'used_bytes' => 0,
]);

echo "   ✅ Workspace: NexusTech Solutions (ID: {$workspaceId})\n";

// ══════════════════════════════════════════════════════════════════════════════
// 2. USERS (7)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n👤 Creating users...\n";

$usersData = [
    ['email' => 'sarah@nexustech.io',   'username' => 'sarah.mitchell',   'first_name' => 'Sarah',   'last_name' => 'Mitchell',   'phone' => '+1 415 555 0101', 'bio' => 'CEO & Founder. Building the future of team communication.', 'avatar_path' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150'],
    ['email' => 'james@nexustech.io',   'username' => 'james.rodriguez',  'first_name' => 'James',   'last_name' => 'Rodriguez',  'phone' => '+1 415 555 0102', 'bio' => 'Lead Backend Engineer. Distributed systems enthusiast.', 'avatar_path' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150'],
    ['email' => 'emily@nexustech.io',   'username' => 'emily.chen',       'first_name' => 'Emily',   'last_name' => 'Chen',       'phone' => '+1 415 555 0103', 'bio' => 'Senior Frontend Developer & UI/UX Designer.', 'avatar_path' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150'],
    ['email' => 'michael@nexustech.io', 'username' => 'michael.thompson', 'first_name' => 'Michael', 'last_name' => 'Thompson', 'phone' => '+1 415 555 0104', 'bio' => 'DevOps & Cloud Infrastructure. AWS certified.', 'avatar_path' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150'],
    ['email' => 'priya@nexustech.io',   'username' => 'priya.sharma',     'first_name' => 'Priya',   'last_name' => 'Sharma',     'phone' => '+1 415 555 0105', 'bio' => 'Head of Marketing. Growth hacker and brand strategist.', 'avatar_path' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150'],
    ['email' => 'david@nexustech.io',   'username' => 'david.kim',        'first_name' => 'David',   'last_name' => 'Kim',        'phone' => '+1 415 555 0106', 'bio' => 'Full-Stack Developer. React & Node.js specialist.', 'avatar_path' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=150'],
    ['email' => 'olivia@nexustech.io',  'username' => 'olivia.brown',     'first_name' => 'Olivia',  'last_name' => 'Brown',      'phone' => '+1 415 555 0107', 'bio' => 'Product Designer. Figma & design systems.', 'avatar_path' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150'],
];

$userIds = [];
$memberIds = [];
$roles = ['owner', 'admin', 'member', 'member', 'member', 'member', 'member'];
$presenceStatuses = ['online', 'online', 'online', 'away', 'away', 'offline', 'offline'];
$themes = ['indigo', 'blue', 'emerald', 'violet', 'rose', 'sky', 'lime'];
$timezones = ['America/Los_Angeles', 'America/New_York', 'Asia/Shanghai', 'America/Chicago', 'Asia/Kolkata', 'Asia/Seoul', 'Europe/London'];

foreach ($usersData as $i => $userData) {
    // Create user
    $userId = insert($pdo, 'users', [
        'email' => $userData['email'],
        'username' => $userData['username'],
        'password_hash' => $passwordHash,
        'first_name' => $userData['first_name'],
        'last_name' => $userData['last_name'],
        'phone' => $userData['phone'],
        'avatar_path' => $userData['avatar_path'],
        'bio' => $userData['bio'],
        'email_verified_at' => date('Y-m-d H:i:s'),
        'created_at' => timestampDaysAgo(60),
    ]);
    $userIds[] = $userId;

    // Create workspace member
    $memberId = insert($pdo, 'workspace_members', [
        'workspace_id' => $workspaceId,
        'user_id' => $userId,
        'role' => $roles[$i],
        'job_title' => match ($i) {
            0 => 'CEO & Founder',
            1 => 'Lead Backend Engineer',
            2 => 'Senior Frontend Developer',
            3 => 'DevOps Engineer',
            4 => 'Head of Marketing',
            5 => 'Full-Stack Developer',
            6 => 'Product Designer',
        },
        'status' => 'active',
        'joined_at' => timestampDaysAgo(60 - $i * 3),
        'last_active_at' => timestampDaysAgo($i < 3 ? 0 : $i),
    ]);
    $memberIds[] = $memberId;

    // User preferences
    insert($pdo, 'user_preferences', [
        'user_id' => $userId,
        'theme_color' => $themes[$i],
        'locale' => 'en',
        'timezone' => $timezones[$i],
        'favorite_timezones' => json_encode(['America/New_York', 'Europe/London', 'Asia/Tokyo']),
    ]);

    // User security
    insert($pdo, 'user_security', [
        'user_id' => $userId,
        'two_factor_enabled' => $i === 0 ? 1 : 0,
        'password_changed_at' => timestampDaysAgo(30),
    ]);

    // User presence
    insert($pdo, 'user_presence', [
        'user_id' => $userId,
        'status' => $presenceStatuses[$i],
        'last_seen_at' => $presenceStatuses[$i] === 'online'
            ? date('Y-m-d H:i:s')
            : timestampDaysAgo($i < 5 ? 0 : 1),
    ]);

    echo "   ✅ {$userData['first_name']} {$userData['last_name']} ({$roles[$i]}) — {$userData['username']} / password123\n";
}

// ══════════════════════════════════════════════════════════════════════════════
// 3. CHANNELS (5) + CONVERSATIONS + MEMBERS
// ══════════════════════════════════════════════════════════════════════════════

$channelsData = [
    ['slug' => 'general',                   'name' => 'General',                    'description' => 'Company-wide announcements and general discussion.', 'visibility' => 'public', 'is_default' => 1],
    ['slug' => 'development-announcements', 'name' => 'Development Announcements',  'description' => 'Announcements related to product development, updates, and releases.', 'visibility' => 'public', 'is_default' => 1],
    ['slug' => 'announcements',             'name' => 'Announcements',              'description' => 'Workspace announcements, news, and notifications.', 'visibility' => 'public', 'is_default' => 1],
    ['slug' => 'engineering',               'name' => 'Engineering',                'description' => 'Technical discussions, code reviews, and architecture decisions.', 'visibility' => 'public', 'is_default' => 0],
    ['slug' => 'design',                    'name' => 'Design',                     'description' => 'UI/UX design discussions, feedback, and resources.', 'visibility' => 'public', 'is_default' => 0],
    ['slug' => 'marketing',                 'name' => 'Marketing',                  'description' => 'Marketing campaigns, analytics, and brand strategy.', 'visibility' => 'private', 'is_default' => 0],
    ['slug' => 'random',                    'name' => 'Random',                     'description' => 'Non-work banter, memes, and fun stuff. Keep it friendly!', 'visibility' => 'public', 'is_default' => 0],
];

// Which members are in which channels (by index)
$channelMemberships = [
    'general' => [0, 1, 2, 3, 4, 5, 6],     // #general — everyone
    'announcements' => [0, 1, 2, 3, 4, 5, 6], // #announcements — everyone
    'development-announcements' => [0, 1, 2, 3, 4, 5, 6], // #development-announcements — everyone
    'engineering' => [0, 1, 2, 3, 5],            // #engineering — Sarah, James, Emily, Michael, David
    'design' => [0, 2, 4, 6],               // #design — Sarah, Emily, Priya, Olivia
    'marketing' => [0, 3, 4, 6],               // #marketing — Sarah, Michael, Priya, Olivia
    'random' => [0, 1, 2, 3, 4, 5, 6],     // #random — everyone
];

$channelIds = [];
$channelConversationIds = [];

foreach ($channelsData as $chData) {
    $slug = $chData['slug'];
    $channelId = insert($pdo, 'channels', [
        'workspace_id' => $workspaceId,
        'slug' => $slug,
        'name' => $chData['name'],
        'description' => $chData['description'],
        'visibility' => $chData['visibility'],
        'is_default' => $chData['is_default'],
        'status' => 'active',
        'created_by' => $memberIds[0], // Sarah created all
        'member_count' => 0, // trigger will update
    ]);
    $channelIds[] = $channelId;

    // Create conversation for channel
    $convId = insert($pdo, 'conversations', [
        'workspace_id' => $workspaceId,
        'type' => 'channel',
        'channel_id' => $channelId,
    ]);
    $channelConversationIds[$slug] = $convId;

    // Add members
    foreach ($channelMemberships[$slug] as $mi) {
        insert($pdo, 'channel_members', [
            'channel_id' => $channelId,
            'workspace_member_id' => $memberIds[$mi],
            'role' => $mi === 0 ? 'owner' : 'member',
            'joined_at' => timestampDaysAgo(55 - $mi),
        ]);
    }

    echo "   ✅ #{$chData['name']} — " . count($channelMemberships[$slug]) . " members\n";
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. DM CONVERSATIONS (3)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n💬 Creating DM conversations...\n";

$dmPairs = [
    [0, 1], // Sarah ↔ James
    [0, 2], // Sarah ↔ Emily
    [1, 5], // James ↔ David
];

$dmConversationIds = [];

foreach ($dmPairs as $pair) {
    $hash = hash('sha256', min($memberIds[$pair[0]], $memberIds[$pair[1]]) . ':' . max($memberIds[$pair[0]], $memberIds[$pair[1]]));

    $dmConvId = insert($pdo, 'conversations', [
        'workspace_id' => $workspaceId,
        'type' => 'dm',
        'dm_hash' => $hash,
    ]);
    $dmConversationIds[] = $dmConvId;

    // Add participants
    foreach ($pair as $mi) {
        insert($pdo, 'conversation_participants', [
            'conversation_id' => $dmConvId,
            'workspace_member_id' => $memberIds[$mi],
        ]);
    }

    echo "   ✅ DM: {$usersData[$pair[0]]['first_name']} ↔ {$usersData[$pair[1]]['first_name']}\n";
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. MESSAGES (55+)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n✉️  Seeding messages...\n";

$messageCount = 0;

// --- #general messages (12) ---
$generalMessages = [
    [0, 'Welcome to NexusTech\'s ChatRox workspace! 🎉 This is our central hub for all communication.', 28],
    [1, 'Excited to be here! This is way better than our old email chains.', 28],
    [2, 'Love the UI! Who designed this? 😍', 27],
    [0, 'Quick reminder: All-hands meeting tomorrow at 10am PST. I\'ll share the agenda in #announcements.', 25],
    [3, 'Got the new CI/CD pipeline running. Build times down by 40%! 🚀', 20],
    [4, 'Marketing report is ready. We hit 50k MAU this month! 📈', 18],
    [5, 'Anyone tried the new dark mode? It looks incredible.', 15],
    [6, 'Just pushed a new design system update. Check the Figma link in #design.', 12],
    [1, 'Hey team, we\'re migrating to MySQL 8.0 this weekend. Downtime expected: ~30 minutes.', 8],
    [0, '@james.rodriguez Great work on the migration plan. Let\'s review it in our 1:1.', 7],
    [2, 'New feature branch is ready for review: feat/real-time-messaging 🔥', 3],
    [4, 'Don\'t forget to submit your Q3 OKRs by Friday!', 1],
];

foreach ($generalMessages as [$mi, $body, $daysAgo]) {
    $msgId = insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $channelConversationIds['general'],
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

// --- #announcements messages (2) ---
$announcementsMessages = [
    [0, '📢 Welcome to the NexusTech announcements channel. Important company updates will be posted here.', 30],
    [0, '🗓️ Reminder: The Q3 All-Hands meeting is scheduled for tomorrow at 10 AM PST. Please make sure to update your slides.', 24],
];
foreach ($announcementsMessages as [$mi, $body, $daysAgo]) {
    insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $channelConversationIds['announcements'],
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

// --- #development-announcements messages (2) ---
$devAnnMessages = [
    [1, '🚀 Product Development: Version 2.0 has been successfully merged into production! Thanks everyone for the hard work.', 15],
    [2, '🎨 The updated design tokens and stylesheet guidelines are now live in the development-announcements repo.', 10],
];
foreach ($devAnnMessages as [$mi, $body, $daysAgo]) {
    insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $channelConversationIds['development-announcements'],
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

// --- #engineering messages (12) ---
$engMessages = [
    [1, 'Alright team, let\'s discuss the WebSocket architecture for real-time messaging.', 25],
    [5, 'I suggest using Ratchet for the PHP WebSocket server. It integrates cleanly with our stack.', 25],
    [2, 'From the frontend side, I can implement the client with reconnection logic and exponential backoff.', 24],
    [1, 'Good plan. @michael.thompson can you set up the WebSocket server on a separate port?', 24],
    [3, 'On it. I\'ll configure nginx to proxy /ws to the Ratchet server on port 8080.', 23],
    [0, 'How are we handling message delivery receipts? Slack-style or WhatsApp-style?', 20],
    [1, 'I recommend Slack-style — per-conversation read cursors rather than per-message ticks. Much better at scale.', 20],
    [5, 'Agreed. I\'ve already set up the conversation_read_cursors table for this.', 19],
    [2, 'PR #142 is ready: Implements the unified messaging model. Channel + DM in one table. Please review.', 14],
    [1, 'Reviewed and approved! Clean abstraction. Left a few minor comments on the SQL indexes.', 13],
    [3, 'Deployment pipeline is green. All tests passing. Ready to merge.', 10],
    [5, 'Just optimized the message query. Went from 120ms to 8ms with the new composite index. 🔥', 5],
];

foreach ($engMessages as [$mi, $body, $daysAgo]) {
    insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $channelConversationIds['engineering'],
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

// --- #design messages (8) ---
$designMessages = [
    [6, 'New design system v2.0 is ready in Figma! Key changes: updated color tokens, new component library.', 22],
    [2, 'This looks amazing @olivia.brown! The glassmorphism cards are 🔥', 21],
    [4, 'Can we get a marketing variant of the hero section? Need it for the landing page.', 19],
    [6, 'Sure! I\'ll create a marketing-specific variant. Should have it by EOD.', 19],
    [0, 'Love the new sidebar navigation. Much cleaner than v1. Ship it!', 15],
    [2, 'Implemented the new design tokens in CSS variables. Theme switching is instant now.', 10],
    [6, 'Accessibility audit complete: We\'re at 98/100 on Lighthouse. Just need to fix a few contrast ratios.', 6],
    [4, 'The new email templates are gorgeous. Great work team! 💅', 2],
];

foreach ($designMessages as [$mi, $body, $daysAgo]) {
    insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $channelConversationIds['design'],
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

// --- #marketing messages (8) ---
$mktMessages = [
    [4, 'Q3 marketing plan is finalized. Focus areas: content marketing, SEO, and community building.', 26],
    [0, 'Budget approved for the influencer campaign. Let\'s target tech YouTubers and newsletter authors.', 22],
    [6, 'Social media assets for the product launch are ready. 12 variants for A/B testing.', 18],
    [3, 'Analytics dashboard shows 23% increase in organic traffic this month. Our SEO strategy is working! 📊', 15],
    [4, 'Blog post "Why Teams Are Switching to ChatRox" went viral — 45k views in 3 days!', 11],
    [0, 'Amazing work @priya.sharma! Let\'s double down on thought leadership content.', 10],
    [6, 'New brand guidelines are uploaded to the shared drive. Please use the updated logo assets.', 7],
    [4, 'Webinar next Thursday: "Building High-Performance Remote Teams". Send me speaker suggestions!', 2],
];

foreach ($mktMessages as [$mi, $body, $daysAgo]) {
    insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $channelConversationIds['marketing'],
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

// --- #random messages (8) ---
$randomMessages = [
    [5, 'Who\'s up for coffee at the new place on Market Street? ☕', 20],
    [2, 'Count me in! Their oat milk latte is legendary.', 20],
    [6, 'Just adopted a puppy! Meet Luna 🐕 She\'s already chewing my keyboard cables.', 16],
    [1, 'Luna is adorable! Pro tip: get cable organizers. Trust me. 😅', 16],
    [3, 'Anyone watching the new sci-fi series on Netflix? No spoilers please!', 10],
    [4, 'Friday game night is BACK! This week: Among Us. 7pm PST. Be there or be suspicious. 🕵️', 6],
    [0, 'Happy birthday @david.kim! 🎂🎉 Cake in the break room!', 3],
    [5, 'Thanks everyone! Best team ever. Now let\'s eat some cake! 🍰', 3],
];

foreach ($randomMessages as [$mi, $body, $daysAgo]) {
    insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $channelConversationIds['random'],
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

// --- DM messages (9 total across 3 conversations) ---
$dmMessages = [
    // Sarah ↔ James
    [$dmConversationIds[0], 0, 'Hey James, can we sync on the database migration timeline?', 10],
    [$dmConversationIds[0], 1, 'Sure! I\'ve prepared the rollback plan. We\'re good to go for Saturday 2am PST.', 10],
    [$dmConversationIds[0], 0, 'Perfect. I\'ll notify the team. Thanks for handling this! 🙏', 9],

    // Sarah ↔ Emily
    [$dmConversationIds[1], 0, 'Emily, the new dashboard design is incredible! Clients are going to love it.', 8],
    [$dmConversationIds[1], 2, 'Thank you Sarah! 😊 I spent extra time on the micro-animations. Glad it shows!', 7],
    [$dmConversationIds[1], 0, 'Definitely shows. Let\'s present it at the all-hands. Can you prepare 5 slides?', 7],

    // James ↔ David
    [$dmConversationIds[2], 1, 'David, can you review my PR for the message delivery system? It\'s #156.', 5],
    [$dmConversationIds[2], 5, 'On it! I see you used a composite index on (conversation_id, id DESC). Smart choice.', 4],
    [$dmConversationIds[2], 1, 'Yeah, it dropped the query time from 45ms to 3ms. Huge win for the chat scroll.', 4],
];

foreach ($dmMessages as [$convId, $mi, $body, $daysAgo]) {
    insert($pdo, 'messages', [
        'workspace_id' => $workspaceId,
        'conversation_id' => $convId,
        'sender_id' => $memberIds[$mi],
        'body' => $body,
        'message_type' => 'text',
        'created_at' => randomTimestampBetween(timestampDaysAgo($daysAgo + 1), timestampDaysAgo($daysAgo)),
    ]);
    $messageCount++;
}

echo "   ✅ {$messageCount} messages created across channels and DMs\n";

// ══════════════════════════════════════════════════════════════════════════════
// 6. MESSAGE REACTIONS
// ══════════════════════════════════════════════════════════════════════════════

echo "\n😀 Seeding reactions...\n";

// Get some message IDs to react to
$stmt = $pdo->query("SELECT id FROM messages WHERE workspace_id = {$workspaceId} ORDER BY id LIMIT 15");
$msgIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$emojis = ['👍', '🔥', '❤️', '🎉', '😂', '👀', '🚀', '💯'];
$reactionCount = 0;

foreach (array_slice($msgIds, 0, 10) as $idx => $mId) {
    // 1-3 reactions per message
    $numReactions = mt_rand(1, 3);
    $reactors = array_rand($memberIds, min($numReactions, count($memberIds)));
    if (!is_array($reactors)) $reactors = [$reactors];

    foreach ($reactors as $ri) {
        try {
            insert($pdo, 'message_reactions', [
                'message_id' => $mId,
                'workspace_member_id' => $memberIds[$ri],
                'emoji' => $emojis[array_rand($emojis)],
            ]);
            $reactionCount++;
        } catch (PDOException $e) {
            // duplicate — skip
        }
    }
}

echo "   ✅ {$reactionCount} reactions added\n";

// ══════════════════════════════════════════════════════════════════════════════
// 7. ANNOUNCEMENTS (3)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n📣 Creating announcements...\n";

$announcements = [
    ['title' => 'Q4 Planning Kickoff', 'tag' => 'IMPORTANT', 'message' => 'All teams please submit your Q4 OKRs by November 1st. We\'ll review them in the all-hands meeting on November 5th. Templates are available in the shared drive.', 'start' => '-5 days', 'end' => '+25 days'],
    ['title' => 'New Office Grand Opening! 🎉', 'tag' => 'CELEBRATION', 'message' => 'We\'re thrilled to announce our new San Francisco office is officially open! Join us for the ribbon-cutting ceremony and office tour on Friday. Lunch and refreshments will be served.', 'start' => '-2 days', 'end' => '+12 days'],
    ['title' => 'Platform Update v3.2', 'tag' => 'UPDATE', 'message' => 'We\'ve rolled out ChatRox v3.2 with WebSocket-based real-time messaging, file compression, and improved search. Please report any issues in #engineering.', 'start' => '-1 day', 'end' => '+30 days'],
];

foreach ($announcements as $ann) {
    insert($pdo, 'announcements', [
        'workspace_id' => $workspaceId,
        'created_by' => $memberIds[0],
        'title' => $ann['title'],
        'tag' => $ann['tag'],
        'message' => $ann['message'],
        'start_date' => date('Y-m-d H:i:s', strtotime($ann['start'])),
        'end_date' => date('Y-m-d H:i:s', strtotime($ann['end'])),
    ]);
    echo "   ✅ [{$ann['tag']}] {$ann['title']}\n";
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. NOTIFICATIONS (15)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n🔔 Creating notifications...\n";

$notificationsData = [
    [1, 'mention', 0, 'Mentioned you in #general', '@james.rodriguez Great work on the migration plan.', 7],
    [2, 'reply', 1, 'Replied to your message', 'Reviewed and approved! Clean abstraction.', 13],
    [0, 'file_upload', 6, 'Shared a file', 'design-system-v2.fig uploaded to #design', 22],
    [4, 'channel_join', null, 'New channel member', 'Olivia Brown joined #marketing', 15],
    [0, 'reaction', 1, 'Reacted to your message', 'James reacted with 🔥 to your message', 8],
    [5, 'mention', 1, 'Mentioned you in #engineering', '@david.kim can you review my PR?', 5],
    [3, 'system', null, 'System notification', 'CI/CD pipeline deployment successful', 10],
    [0, 'project', 4, 'Marketing update', 'Q3 marketing report is now available', 18],
    [6, 'mention', 2, 'Mentioned you in #design', '@olivia.brown This looks amazing!', 21],
    [1, 'file_upload', 2, 'Shared a file', 'frontend-architecture.pdf uploaded', 14],
    [0, 'reaction', 5, 'Reacted to your message', 'David reacted with 👍 to your message', 3],
    [4, 'reply', 0, 'Replied to your message', 'Amazing work! Let\'s double down on content.', 10],
    [2, 'channel_join', null, 'New channel member', 'David Kim joined #engineering', 45],
    [3, 'system', null, 'System update', 'Your password was changed successfully', 30],
    [6, 'mention', 4, 'Mentioned you in #marketing', '@olivia.brown New brand guidelines uploaded', 7],
];

foreach ($notificationsData as [$ri, $type, $ai, $title, $body, $daysAgo]) {
    insert($pdo, 'notifications', [
        'workspace_id' => $workspaceId,
        'recipient_id' => $memberIds[$ri],
        'type' => $type,
        'actor_id' => $ai !== null ? $memberIds[$ai] : null,
        'title' => $title,
        'body' => $body,
        'read_at' => $daysAgo > 5 ? timestampDaysAgo($daysAgo - 1) : null,
        'created_at' => timestampDaysAgo($daysAgo),
    ]);
}

echo "   ✅ 15 notifications created\n";

// ══════════════════════════════════════════════════════════════════════════════
// 9. AUDIT LOGS (12)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n📋 Creating audit logs...\n";

$auditLogs = [
    [0, 'login', 'complete', 'Sarah Mitchell logged in', 55],
    [0, 'workspace_update', 'complete', 'Workspace "NexusTech Solutions" created', 60],
    [0, 'channel_create', 'complete', 'Channel #general created', 55],
    [0, 'channel_create', 'complete', 'Channel #engineering created', 55],
    [0, 'channel_create', 'complete', 'Channel #design created', 55],
    [0, 'member_invite', 'complete', 'Invited james@nexustech.io to workspace', 57],
    [0, 'member_invite', 'complete', 'Invited emily@nexustech.io to workspace', 54],
    [0, 'role_change', 'complete', 'Changed James Rodriguez role to admin', 50],
    [1, 'login', 'complete', 'James Rodriguez logged in', 40],
    [0, 'settings_update', 'complete', 'Workspace plan upgraded to Pro', 35],
    [3, 'login', 'failed', 'Failed login attempt for michael@nexustech.io', 20],
    [3, 'login', 'complete', 'Michael Thompson logged in', 20],
];

foreach ($auditLogs as [$mi, $type, $status, $message, $daysAgo]) {
    insert($pdo, 'audit_logs', [
        'workspace_id' => $workspaceId,
        'actor_member_id' => $memberIds[$mi],
        'actor_label' => $usersData[$mi]['first_name'] . ' ' . $usersData[$mi]['last_name'],
        'status' => $status,
        'activity_type' => $type,
        'message' => $message,
        'ip_address' => '192.168.1.' . mt_rand(10, 250),
        'created_at' => timestampDaysAgo($daysAgo),
    ]);
}

echo "   ✅ 12 audit log entries created\n";

// ══════════════════════════════════════════════════════════════════════════════
// 10. FILES (5)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n📁 Creating file entries...\n";

$filesData = [
    ['uploaded_by_idx' => 6, 'original_name' => 'design-system-v2.fig', 'mime_type' => 'application/octet-stream', 'ext' => 'fig', 'size' => 4521984, 'category' => 'document', 'days_ago' => 22],
    ['uploaded_by_idx' => 2, 'original_name' => 'frontend-architecture.pdf', 'mime_type' => 'application/pdf', 'ext' => 'pdf', 'size' => 2148576, 'category' => 'document', 'days_ago' => 14],
    ['uploaded_by_idx' => 4, 'original_name' => 'q3-marketing-report.xlsx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ext' => 'xlsx', 'size' => 892416, 'category' => 'document', 'days_ago' => 18],
    ['uploaded_by_idx' => 0, 'original_name' => 'team-photo-2024.jpg', 'mime_type' => 'image/jpeg', 'ext' => 'jpg', 'size' => 3145728, 'category' => 'image', 'days_ago' => 30],
    ['uploaded_by_idx' => 1, 'original_name' => 'api-documentation.pdf', 'mime_type' => 'application/pdf', 'ext' => 'pdf', 'size' => 1572864, 'category' => 'document', 'days_ago' => 8],
];

$totalFileSize = 0;
foreach ($filesData as $file) {
    $sha = hash('sha256', $file['original_name'] . mt_rand());

    insert($pdo, 'files', [
        'workspace_id' => $workspaceId,
        'uploaded_by' => $memberIds[$file['uploaded_by_idx']],
        'original_name' => $file['original_name'],
        'storage_disk' => 'local',
        'storage_path' => "uploads/{$workspaceId}/" . date('Y-m', strtotime("-{$file['days_ago']} days")) . "/{$sha}.{$file['ext']}",
        'mime_type' => $file['mime_type'],
        'extension' => $file['ext'],
        'size_bytes' => $file['size'],
        'category' => $file['category'],
        'created_at' => timestampDaysAgo($file['days_ago']),
    ]);
    $totalFileSize += $file['size'];
}

// Storage quota is updated by trigger, but since files are inserted above, let's verify
echo "   ✅ 5 files created (" . round($totalFileSize / 1024 / 1024, 1) . " MB total)\n";

// ══════════════════════════════════════════════════════════════════════════════
// 11. ANALYTICS — 30 DAYS
// ══════════════════════════════════════════════════════════════════════════════

echo "\n📊 Generating 30 days of analytics...\n";

for ($d = 30; $d >= 0; $d--) {
    $date = date('Y-m-d', strtotime("-{$d} days"));
    $isWeekend = in_array(date('N', strtotime($date)), ['6', '7']);

    // Daily messages
    $channelMsgs = $isWeekend ? mt_rand(5, 15) : mt_rand(20, 55);
    $dmMsgs = $isWeekend ? mt_rand(2, 8) : mt_rand(10, 30);

    insert($pdo, 'analytics_daily_messages', [
        'workspace_id' => $workspaceId,
        'date' => $date,
        'channel_messages' => $channelMsgs,
        'dm_messages' => $dmMsgs,
        'total_messages' => $channelMsgs + $dmMsgs,
    ]);

    // Daily active users
    $activeUsers = $isWeekend ? mt_rand(2, 4) : mt_rand(5, 7);
    insert($pdo, 'analytics_daily_active_users', [
        'workspace_id' => $workspaceId,
        'date' => $date,
        'active_users' => $activeUsers,
    ]);

    // Per-channel stats
    foreach ($channelIds as $ci => $chId) {
        $weight = match ($ci) {
            0 => 1.0,    // #general — highest
            1 => 0.6,    // #development-announcements
            2 => 0.5,    // #announcements
            3 => 0.7,    // #engineering
            4 => 0.4,    // #design
            5 => 0.35,   // #marketing
            6 => 0.5,    // #random
            default => 0.2
        };
        $chMsgs = $isWeekend
            ? mt_rand(1, (int)(8 * $weight))
            : mt_rand(3, (int)(18 * $weight));

        insert($pdo, 'analytics_channel_stats', [
            'workspace_id' => $workspaceId,
            'channel_id' => $chId,
            'date' => $date,
            'message_count' => max(1, $chMsgs),
        ]);
    }

    // Hourly activity (last 7 days only)
    if ($d <= 7) {
        for ($h = 0; $h < 24; $h++) {
            // Peak hours: 9am-5pm
            $hourMsgs = ($h >= 9 && $h <= 17)
                ? mt_rand(3, 12)
                : ($h >= 6 && $h <= 22 ? mt_rand(0, 4) : 0);

            if ($isWeekend) $hourMsgs = (int)($hourMsgs * 0.3);

            insert($pdo, 'analytics_hourly_activity', [
                'workspace_id' => $workspaceId,
                'date' => $date,
                'hour' => $h,
                'message_count' => $hourMsgs,
            ]);
        }
    }
}

echo "   ✅ 31 days × daily_messages, daily_active_users, channel_stats\n";
echo "   ✅ 8 days × 24 hours = 192 hourly activity records\n";

// ══════════════════════════════════════════════════════════════════════════════
// 12. READ CURSORS (set everyone as "read up to" recent messages)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n📖 Setting read cursors...\n";

// For each channel conversation, set read cursor for each member
foreach ($channelConversationIds as $ci => $convId) {
    $lastMsg = $pdo->query("SELECT id FROM messages WHERE conversation_id = {$convId} ORDER BY id DESC LIMIT 1")->fetch();
    if (!$lastMsg) continue;

    foreach ($channelMemberships[$ci] as $mi) {
        // Some users are caught up, others have unread
        $readMsgId = ($mi < 3) ? $lastMsg['id'] : max(1, $lastMsg['id'] - mt_rand(0, 3));

        insert($pdo, 'conversation_read_cursors', [
            'workspace_member_id' => $memberIds[$mi],
            'conversation_id' => $convId,
            'last_read_message_id' => $readMsgId,
            'last_read_at' => timestampDaysAgo($mi < 3 ? 0 : mt_rand(0, 2)),
        ]);
    }
}

// DM read cursors
foreach ($dmConversationIds as $di => $convId) {
    $lastMsg = $pdo->query("SELECT id FROM messages WHERE conversation_id = {$convId} ORDER BY id DESC LIMIT 1")->fetch();
    if (!$lastMsg) continue;

    foreach ($dmPairs[$di] as $mi) {
        insert($pdo, 'conversation_read_cursors', [
            'workspace_member_id' => $memberIds[$mi],
            'conversation_id' => $convId,
            'last_read_message_id' => $lastMsg['id'],
            'last_read_at' => timestampDaysAgo(0),
        ]);
    }
}

echo "   ✅ Read cursors set for all conversations\n";

// ══════════════════════════════════════════════════════════════════════════════
// DONE
// ══════════════════════════════════════════════════════════════════════════════

echo "\n" . str_repeat('═', 60) . "\n";
echo "🎉 Seed complete!\n\n";
echo "📦 Workspace:     NexusTech Solutions\n";
echo "👤 Users:          7 (password: password123)\n";
echo "📢 Channels:       7 (#general, #announcements, #development-announcements, #engineering, #design, #marketing, #random)\n";
echo "💬 Messages:       {$messageCount}\n";
echo "📊 Analytics:      31 days of data\n";
echo "📣 Announcements:  3\n";
echo "🔔 Notifications:  15\n";
echo "📋 Audit Logs:     12\n";
echo "📁 Files:          5\n";
echo "\n";
echo "Login credentials:\n";
foreach ($usersData as $i => $u) {
    echo "  {$u['username']} / password123  ({$roles[$i]})\n";
}
echo "\n" . str_repeat('═', 60) . "\n";
