<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;

class WorkspaceFile extends Model
{
    public static function all(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT * 
            FROM v_workspace_files 
            WHERE workspace_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$workspaceId]);
        $rows = $stmt->fetchAll();

        $files = [];
        foreach ($rows as $row) {
            $ext = strtolower($row['extension']);
            
            // Map file icon and class
            $icon = 'file';
            $iconClass = 'bg-gray';

            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'fig', 'webp'])) {
                $icon = 'file-image';
                $iconClass = 'bg-orange';
            } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'])) {
                $icon = 'file-text';
                $iconClass = 'bg-blue';
            } elseif (in_array($ext, ['xls', 'xlsx', 'csv', 'ods'])) {
                $icon = 'file-spreadsheet';
                $iconClass = 'bg-green';
            } elseif (in_array($ext, ['zip', 'tar', 'gz', 'rar', '7z'])) {
                $icon = 'file-archive';
                $iconClass = 'bg-orange';
            } elseif (in_array($ext, ['html', 'css', 'js', 'php', 'py', 'sh', 'sql', 'json'])) {
                $icon = 'file-code';
                $iconClass = 'bg-blue';
            }

            $files[] = [
                'id' => $row['id'],
                'name' => $row['original_name'],
                'icon' => $icon,
                'icon_class' => $iconClass,
                'shared_by' => $row['shared_by'],
                'shared_avatar' => $row['shared_avatar'] ?: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150',
                'shared_by_you' => (bool) ($row['uploaded_by'] === $memberId),
                'date' => date('M d, Y', strtotime($row['created_at'])),
                'size' => self::formatSize($row['size_bytes']),
            ];
        }

        return $files;
    }

    private static function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    public static function paginate(int $page = 1, int $perPage = 10): array
    {
        $all = self::all();
        $total = count($all);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($all, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
}
