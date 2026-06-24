<?php

namespace App\Helpers;

use PDO;

/**
 * Upload policy: size limits, quota, safe filenames, server-side MIME.
 * All file extensions are allowed (production teams share .php, .sql, .html, etc.).
 */
class FileUploadPolicy
{
    public static function maxBytes(): int
    {
        $mb = (int)($_ENV['MAX_FILE_SIZE_MB'] ?? 40);
        if ($mb < 1) {
            $mb = 40;
        }

        return $mb * 1024 * 1024;
    }

    /**
     * @return array{ok: bool, error?: string, message?: string}
     */
    public static function validateUploadItem(array $fileItem): array
    {
        if (empty($fileItem['name'])) {
            return ['ok' => false, 'error' => 'empty_name', 'message' => 'File name is required.'];
        }

        $error = (int)($fileItem['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'upload_error', 'message' => self::uploadErrorMessage($error)];
        }

        if (empty($fileItem['tmp_name']) || !is_uploaded_file($fileItem['tmp_name'])) {
            return ['ok' => false, 'error' => 'invalid_upload', 'message' => 'Invalid upload payload.'];
        }

        $size = (int)($fileItem['size'] ?? 0);
        if ($size <= 0) {
            return ['ok' => false, 'error' => 'empty_file', 'message' => 'File is empty.'];
        }

        $maxBytes = self::maxBytes();
        if ($size > $maxBytes) {
            return [
                'ok' => false,
                'error' => 'file_too_large',
                'message' => 'File exceeds the maximum size of ' . self::formatSize($maxBytes) . '.',
            ];
        }

        $name = self::sanitizeOriginalName((string)$fileItem['name']);
        if ($name === '') {
            return ['ok' => false, 'error' => 'invalid_name', 'message' => 'File name is not valid.'];
        }

        return ['ok' => true];
    }

    public static function sanitizeOriginalName(string $name): string
    {
        $name = str_replace(["\0", "\r", "\n"], '', $name);
        $name = basename(str_replace('\\', '/', $name));
        $name = trim($name, ".\x20\t");

        if ($name === '' || $name === '.' || $name === '..') {
            return '';
        }

        if (strlen($name) > 255) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $maxBase = 255 - ($ext !== '' ? strlen($ext) + 1 : 0);
            $name = substr($base, 0, max(1, $maxBase)) . ($ext !== '' ? '.' . $ext : '');
        }

        return $name;
    }

    public static function detectMime(string $path): string
    {
        if (!is_file($path)) {
            return 'application/octet-stream';
        }

        $mime = 'application/octet-stream';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }
        }

        return $mime;
    }

    /**
     * @return array{ok: bool, error?: string, message?: string}
     */
    public static function checkWorkspaceQuota(PDO $db, int $workspaceId, int $additionalBytes): array
    {
        if ($additionalBytes <= 0) {
            return ['ok' => true];
        }

        $stmt = $db->prepare('
            SELECT quota_bytes, used_bytes
            FROM workspace_storage_quotas
            WHERE workspace_id = ?
        ');
        $stmt->execute([$workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $quotaBytes = (int)($row['quota_bytes'] ?? 16106127360);
        $usedBytes = (int)($row['used_bytes'] ?? 0);

        if ($usedBytes + $additionalBytes > $quotaBytes) {
            return [
                'ok' => false,
                'error' => 'quota_exceeded',
                'message' => 'Workspace storage quota exceeded. Free up space or contact your admin.',
            ];
        }

        return ['ok' => true];
    }

    public static function resolveCategory(string $mimeType, string $extension): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        if (
            ($mimeType === 'video/webm' || $mimeType === 'audio/webm')
            && in_array($extension, ['webm', 'weba'], true)
        ) {
            return 'audio';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'], true)) {
            return 'document';
        }

        return 'other';
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds server upload limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary folder is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A server extension blocked this upload.',
            default => 'Upload failed.',
        };
    }
}
