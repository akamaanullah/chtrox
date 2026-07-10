<?php
 
namespace App\Controllers\Front\Api;
 
use App\Core\Controller;
use App\Core\Session;
use App\Core\FileUploader;
 
class FileController extends Controller
{
    public function upload(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);
 
        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
 
        $uploaded = [];
        $errors = [];
        $items = $this->normalizeUploadedItems($_FILES);
 
        if ($items === []) {
            $this->jsonResponse(['error' => 'No files uploaded', 'message' => 'No files were received.'], 400);
        }
 
        foreach ($items as $index => $fileItem) {
            $result = FileUploader::upload($fileItem, $workspaceId, $memberId);
            if (!empty($result['success']) && !empty($result['file'])) {
                $uploaded[] = $result['file'];
                continue;
            }
 
            $errors[] = [
                'index' => $index,
                'name' => $fileItem['name'] ?? 'file',
                'error' => $result['error'] ?? 'upload_failed',
                'message' => $result['message'] ?? 'Upload failed.',
            ];
        }
 
        if ($uploaded === []) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'upload_failed',
                'message' => $errors[0]['message'] ?? 'No files uploaded or upload failed.',
                'errors' => $errors,
            ], 400);
        }
 
        $payload = [
            'success' => true,
            'files' => $uploaded,
        ];
 
        if ($errors !== []) {
            $payload['partial'] = true;
            $payload['errors'] = $errors;
        }
 
        $this->jsonResponse($payload);
    }
 
    public function list(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);
 

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
 
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 18)));
        $offset = ($page - 1) * $perPage;
 
        $search = trim((string)($_GET['search'] ?? ''));
        $filter = trim((string)($_GET['filter'] ?? 'all'));
 
        $db = \App\Core\Model::db();
 
        // Base where conditions - scope to conversations the user participates in
        $params = ['workspace_id' => $workspaceId];

        if ($filter === 'shared_by_me') {
            // Only files uploaded by this user in this workspace
            $scopeCondition = "workspace_id = :workspace_id AND uploaded_by = :member_id";
            $params['member_id'] = $memberId;
        } elseif ($filter === 'shared_by_others') {
            // Files from conversations the user is in, uploaded by others
            $scopeCondition = "workspace_id = :workspace_id AND uploaded_by != :member_id AND id IN (
                SELECT ma.file_id FROM message_attachments ma
                JOIN messages msg ON msg.id = ma.message_id
                JOIN conversations c ON c.id = msg.conversation_id
                WHERE c.workspace_id = :workspace_id AND msg.deleted_for_everyone_at IS NULL
                AND (
                    -- Channels the user is a member of
                    (c.type = 'channel' AND c.channel_id IN (
                        SELECT channel_id FROM channel_members WHERE workspace_member_id = :member_id AND left_at IS NULL
                    ))
                    OR
                    -- DMs the user participates in
                    (c.type = 'dm' AND c.id IN (
                        SELECT conversation_id FROM conversation_participants WHERE workspace_member_id = :member_id AND left_at IS NULL
                    ))
                )
            )";
            $params['member_id'] = $memberId;
        } else {
            // Default 'all': only show files from conversations the user is part of
            $scopeCondition = "workspace_id = :workspace_id AND id IN (
                SELECT ma.file_id FROM message_attachments ma
                JOIN messages msg ON msg.id = ma.message_id
                JOIN conversations c ON c.id = msg.conversation_id
                WHERE c.workspace_id = :workspace_id AND msg.deleted_for_everyone_at IS NULL
                AND (
                    -- Channels the user is a member of
                    (c.type = 'channel' AND c.channel_id IN (
                        SELECT channel_id FROM channel_members WHERE workspace_member_id = :member_id AND left_at IS NULL
                    ))
                    OR
                    -- DMs the user participates in
                    (c.type = 'dm' AND c.id IN (
                        SELECT conversation_id FROM conversation_participants WHERE workspace_member_id = :member_id AND left_at IS NULL
                    ))
                    OR
                    -- Files uploaded by the user
                    uploaded_by = :member_id
                )
            )";
            $params['member_id'] = $memberId;
        }

        $where = [$scopeCondition];

        if ($search !== '') {
            $where[] = "(original_name LIKE :search OR shared_by LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $whereClause = implode(" AND ", $where);

        // Count query
        $countStmt = $db->prepare("SELECT COUNT(*) FROM v_workspace_files WHERE {$whereClause}");
        $countStmt->execute($params);
        $totalRows = (int)$countStmt->fetchColumn();

        // Data query with bindValue to handle LIMIT/OFFSET correctly
        $dataStmt = $db->prepare("
            SELECT *
            FROM v_workspace_files
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $val) {
            $dataStmt->bindValue(':' . $key, $val);
        }
        $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();
 
        $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);


 
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
                'shared_avatar' => $row['shared_avatar'] ?: DEFAULT_AVATAR_URL,
                'shared_by_you' => (bool) ($row['uploaded_by'] === $memberId),
                'date' => date('M d, Y', strtotime($row['created_at'])),
                'size' => \App\Helpers\FileUploadPolicy::formatSize($row['size_bytes']),
            ];
        }
 
        $this->jsonResponse([
            'success' => true,
            'files' => $files,
            'total_rows' => $totalRows,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($totalRows / $perPage)
        ]);
    }
 

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeUploadedItems(array $files): array
    {
        $items = [];
 
        if (!empty($files['file'])) {
            $items[] = $files['file'];
        }
 
        if (!empty($files['files'])) {
            $batch = $files['files'];
            if (is_array($batch['name'] ?? null)) {
                $count = count($batch['name']);
                for ($i = 0; $i < $count; $i++) {
                    $items[] = [
                        'name' => $batch['name'][$i] ?? '',
                        'type' => $batch['type'][$i] ?? '',
                        'tmp_name' => $batch['tmp_name'][$i] ?? '',
                        'error' => $batch['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $batch['size'][$i] ?? 0,
                    ];
                }
            } else {
                $items[] = $batch;
            }
        }
 
        return $items;
    }
}
