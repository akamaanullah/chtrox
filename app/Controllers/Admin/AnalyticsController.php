<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;
use App\Core\Session;
use App\Core\Database;
use PDO;

class AnalyticsController extends AdminController
{
    public function index(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $db = Database::connection();

        // 1. Total Messages
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE c.workspace_id = ? AND m.deleted_for_everyone_at IS NULL
        ");
        $stmt->execute([$workspaceId]);
        $totalMessages = (int)$stmt->fetchColumn();

        // 2. Monthly Active Users (Sent at least 1 message in past 30 days)
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT m.sender_id) 
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE c.workspace_id = ? AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND m.deleted_for_everyone_at IS NULL
        ");
        $stmt->execute([$workspaceId]);
        $activeUsers = (int)$stmt->fetchColumn();

        // 3. Storage Used
        $stmt = $db->prepare("
            SELECT SUM(size_bytes) 
            FROM files 
            WHERE workspace_id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$workspaceId]);
        $storageBytes = (int)$stmt->fetchColumn();

        if ($storageBytes >= 1073741824) {
            $storageLabel = number_format($storageBytes / 1073741824, 1) . ' GB';
        } elseif ($storageBytes >= 1048576) {
            $storageLabel = number_format($storageBytes / 1048576, 1) . ' MB';
        } elseif ($storageBytes >= 1024) {
            $storageLabel = number_format($storageBytes / 1024, 1) . ' KB';
        } else {
            $storageLabel = $storageBytes . ' B';
        }

        // 4. Top Active Channels
        $stmt = $db->prepare("
            SELECT ch.name, COUNT(m.id) as message_count
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            JOIN channels ch ON ch.id = c.channel_id
            WHERE c.workspace_id = ? AND c.channel_id IS NOT NULL AND m.deleted_for_everyone_at IS NULL
            GROUP BY ch.id, ch.name
            ORDER BY message_count DESC
            LIMIT 8
        ");
        $stmt->execute([$workspaceId]);
        $topChannels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pinned stats
        $stmt = $db->prepare("
            SELECT COUNT(mp.id) 
            FROM message_pins mp 
            JOIN conversations c ON mp.conversation_id = c.id 
            WHERE c.workspace_id = ?
        ");
        $stmt->execute([$workspaceId]);
        $totalPins = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT created_at FROM workspaces WHERE id = ?");
        $stmt->execute([$workspaceId]);
        $wsCreatedAt = $stmt->fetchColumn();
        $daysActive = max(1, round((time() - strtotime($wsCreatedAt)) / 86400));
        $pinsPerDay = number_format($totalPins / $daysActive, 1);

        if ($totalPins > 20) {
            $priorityLevel = 'High';
        } elseif ($totalPins > 5) {
            $priorityLevel = 'Medium';
        } else {
            $priorityLevel = 'Low';
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM workspace_members WHERE workspace_id = ? AND status = 'active'");
        $stmt->execute([$workspaceId]);
        $totalMembers = max(1, (int)$stmt->fetchColumn());

        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT mp.pinned_by) 
            FROM message_pins mp 
            JOIN conversations c ON mp.conversation_id = c.id 
            WHERE c.workspace_id = ?
        ");
        $stmt->execute([$workspaceId]);
        $membersWhoPinned = (int)$stmt->fetchColumn();
        $interactionPct = round(($membersWhoPinned / $totalMembers) * 100) . '%';

        $this->renderDashboard('analytics', [
            'page_title' => 'Analytics - ChatRox',
            'stats' => AdminOverview::stats(),
            'total_messages' => number_format($totalMessages),
            'active_users' => number_format($activeUsers),
            'storage_label' => $storageLabel,
            'top_channels' => $topChannels,
            'total_pins' => $totalPins,
            'pins_per_day' => $pinsPerDay,
            'priority_level' => $priorityLevel,
            'interaction_pct' => $interactionPct
        ]);
    }

    public function data(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $db = Database::connection();

        // 1. Message Volume Trend (Last 7 Days)
        $trendData = [];
        $trendLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $label = date('D', strtotime($date));
            $trendLabels[] = $label;

            $stmt = $db->prepare("
                SELECT COUNT(m.id) 
                FROM messages m
                JOIN conversations c ON m.conversation_id = c.id
                WHERE c.workspace_id = ? AND DATE(m.created_at) = ? AND m.deleted_for_everyone_at IS NULL
            ");
            $stmt->execute([$workspaceId, $date]);
            $trendData[] = (int)$stmt->fetchColumn();
        }

        // 2. Channels vs DMs Engagement
        $stmt = $db->prepare("
            SELECT (c.channel_id IS NOT NULL) as is_channel, COUNT(m.id) as count
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE c.workspace_id = ? AND m.deleted_for_everyone_at IS NULL
            GROUP BY is_channel
        ");
        $stmt->execute([$workspaceId]);
        $engagementRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $channelsCount = 0;
        $dmsCount = 0;
        foreach ($engagementRows as $row) {
            if ($row['is_channel']) {
                $channelsCount = (int)$row['count'];
            } else {
                $dmsCount = (int)$row['count'];
            }
        }
        $totalEng = $channelsCount + $dmsCount;
        $channelsPct = $totalEng > 0 ? round(($channelsCount / $totalEng) * 100) : 50;
        $dmsPct = $totalEng > 0 ? round(($dmsCount / $totalEng) * 100) : 50;

        // 3. Peak Hours (24 hours bucketed by hourly usage)
        $peakHours = array_fill(0, 12, 0);
        $peakLabels = ['12am', '2am', '4am', '6am', '8am', '10am', '12pm', '2pm', '4pm', '6pm', '8pm', '10pm'];
        $stmt = $db->prepare("
            SELECT HOUR(m.created_at) as hr, COUNT(m.id) as count
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE c.workspace_id = ? AND m.deleted_for_everyone_at IS NULL
            GROUP BY hr
        ");
        $stmt->execute([$workspaceId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $h = (int)$row['hr'];
            $idx = (int)floor($h / 2);
            if ($idx >= 0 && $idx < 12) {
                $peakHours[$idx] += (int)$row['count'];
            }
        }

        // 4. Member Growth (Last 6 Months)
        $growthLabels = [];
        $growthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-{$i} months"));
            $monthEnd = date('Y-m-t', strtotime("-{$i} months"));
            $label = date('M', strtotime($monthStart));
            $growthLabels[] = $label;

            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM workspace_members 
                WHERE workspace_id = ? AND status = 'active' AND created_at <= ?
            ");
            $stmt->execute([$workspaceId, $monthEnd . ' 23:59:59']);
            $growthData[] = (int)$stmt->fetchColumn();
        }

        // 5. File Distribution Categories
        $stmt = $db->prepare("
            SELECT category, SUM(size_bytes) as size
            FROM files
            WHERE workspace_id = ? AND deleted_at IS NULL
            GROUP BY category
        ");
        $stmt->execute([$workspaceId]);
        $fileDistribution = [
            'image' => 0,
            'document' => 0,
            'video' => 0,
            'other' => 0
        ];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cat = $row['category'] ?: 'other';
            if (isset($fileDistribution[$cat])) {
                $fileDistribution[$cat] = (int)$row['size'];
            } else {
                $fileDistribution['other'] += (int)$row['size'];
            }
        }
        $fileSeries = [
            round($fileDistribution['image'] / 1024 / 1024, 1),
            round($fileDistribution['document'] / 1024 / 1024, 1),
            round($fileDistribution['video'] / 1024 / 1024, 1),
            round($fileDistribution['other'] / 1024 / 1024, 1),
        ];

        $totalBytes = array_sum($fileDistribution);
        if ($totalBytes >= 1073741824) {
            $storageLabel = number_format($totalBytes / 1073741824, 1) . ' GB';
        } elseif ($totalBytes >= 1048576) {
            $storageLabel = number_format($totalBytes / 1048576, 1) . ' MB';
        } elseif ($totalBytes >= 1024) {
            $storageLabel = number_format($totalBytes / 1024, 1) . ' KB';
        } else {
            $storageLabel = $totalBytes . ' B';
        }

        // 6. Pin Trends
        $pinLabels = [];
        $pinData = [];
        for ($i = 5; $i >= 0; $i--) {
            $weekStart = date('Y-m-d', strtotime("-{$i} weeks"));
            $weekEnd = date('Y-m-d', strtotime("-{$i} weeks +6 days"));
            $pinLabels[] = 'Week ' . (6 - $i);

            $stmt = $db->prepare("
                SELECT COUNT(mp.id)
                FROM message_pins mp
                JOIN conversations c ON mp.conversation_id = c.id
                WHERE c.workspace_id = ? AND DATE(mp.pinned_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$workspaceId, $weekStart, $weekEnd]);
            $pinData[] = (int)$stmt->fetchColumn();
        }

        $this->jsonResponse([
            'success' => true,
            'trend' => [
                'labels' => $trendLabels,
                'data' => $trendData
            ],
            'engagement' => [
                'series' => [$channelsPct, $dmsPct]
            ],
            'peak' => [
                'labels' => $peakLabels,
                'data' => $peakHours
            ],
            'growth' => [
                'labels' => $growthLabels,
                'data' => $growthData
            ],
            'files' => [
                'series' => $fileSeries,
                'total_formatted' => $storageLabel
            ],
            'pins' => [
                'labels' => $pinLabels,
                'data' => $pinData
            ]
        ]);
    }
}
