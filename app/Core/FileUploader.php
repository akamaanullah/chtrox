<?php

namespace App\Core;

use App\Helpers\FileUploadPolicy;
use App\Models\WorkspaceFile;
use PDO;

class FileUploader
{
    /**
     * @return array{success: bool, file?: array, error?: string, message?: string}
     */
    public static function upload(array $fileItem, int $workspaceId, int $uploadedBy): array
    {
        $validation = FileUploadPolicy::validateUploadItem($fileItem);
        if (!$validation['ok']) {
            return [
                'success' => false,
                'error' => $validation['error'] ?? 'validation_failed',
                'message' => $validation['message'] ?? 'Upload validation failed.',
            ];
        }

        $tmpPath = $fileItem['tmp_name'];
        $originalName = FileUploadPolicy::sanitizeOriginalName((string)$fileItem['name']);
        $clientMime = $fileItem['type'] ?? 'application/octet-stream';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $detectedMime = FileUploadPolicy::detectMime($tmpPath);
        $mimeType = $detectedMime !== 'application/octet-stream' ? $detectedMime : $clientMime;
        $category = FileUploadPolicy::resolveCategory($mimeType, $extension);

        $db = WorkspaceFile::db();
        $incomingSize = (int)($fileItem['size'] ?? 0);
        $quotaCheck = FileUploadPolicy::checkWorkspaceQuota($db, $workspaceId, $incomingSize);
        if (!$quotaCheck['ok']) {
            return [
                'success' => false,
                'error' => $quotaCheck['error'] ?? 'quota_exceeded',
                'message' => $quotaCheck['message'] ?? 'Workspace storage quota exceeded.',
            ];
        }

        $yearMonth = date('Y-m');
        $uploadSubdir = "uploads/workspace_{$workspaceId}/{$yearMonth}";
        $fullUploadDir = ROOT_DIR . '/storage/' . $uploadSubdir;

        if (!is_dir($fullUploadDir)) {
            mkdir($fullUploadDir, 0755, true);
        }

        $processedTmp = $tmpPath;
        $isProcessedImage = false;
        $finalExtension = $extension;
        $finalMime = $mimeType;

        if ($category === 'image' && extension_loaded('gd')) {
            $srcImg = null;
            if ($mimeType === 'image/jpeg' || in_array($extension, ['jpg', 'jpeg'], true)) {
                $srcImg = @imagecreatefromjpeg($tmpPath);
            } elseif ($mimeType === 'image/png' || $extension === 'png') {
                $srcImg = @imagecreatefrompng($tmpPath);
                $finalExtension = 'webp';
                $finalMime = 'image/webp';
            } elseif ($mimeType === 'image/webp' || $extension === 'webp') {
                $srcImg = @imagecreatefromwebp($tmpPath);
            }

            if ($srcImg) {
                $width = imagesx($srcImg);
                $height = imagesy($srcImg);
                $maxDim = 2048;

                if ($width > $maxDim || $height > $maxDim) {
                    $ratio = $width / $height;
                    if ($ratio > 1) {
                        $newWidth = $maxDim;
                        $newHeight = (int)round($maxDim / $ratio);
                    } else {
                        $newHeight = $maxDim;
                        $newWidth = (int)round($maxDim * $ratio);
                    }
                    $dstImg = imagecreatetruecolor($newWidth, $newHeight);

                    if ($finalExtension === 'webp') {
                        imagealphablending($dstImg, false);
                        imagesavealpha($dstImg, true);
                    }

                    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($srcImg);
                    $srcImg = $dstImg;
                }

                $tempCompressed = tempnam(sys_get_temp_dir(), 'chatrox_img_');
                $saveSuccess = $finalExtension === 'webp'
                    ? imagewebp($srcImg, $tempCompressed, 80)
                    : imagejpeg($srcImg, $tempCompressed, 80);

                imagedestroy($srcImg);

                if ($saveSuccess) {
                    $processedTmp = $tempCompressed;
                    $isProcessedImage = true;
                } else {
                    @unlink($tempCompressed);
                }
            }
        }

        $sha256 = hash_file('sha256', $processedTmp);
        $fileSize = (int)filesize($processedTmp);

        if ($fileSize > FileUploadPolicy::maxBytes()) {
            if ($isProcessedImage) {
                @unlink($processedTmp);
            }

            return [
                'success' => false,
                'error' => 'file_too_large',
                'message' => 'File exceeds the maximum size of ' . FileUploadPolicy::formatSize(FileUploadPolicy::maxBytes()) . '.',
            ];
        }

        // Strict quota check using the final processed file size (applied to both new and deduplicated uploads)
        $quotaCheck = FileUploadPolicy::checkWorkspaceQuota($db, $workspaceId, $fileSize);
        if (!$quotaCheck['ok']) {
            if ($isProcessedImage) {
                @unlink($processedTmp);
            }

            return [
                'success' => false,
                'error' => $quotaCheck['error'] ?? 'quota_exceeded',
                'message' => $quotaCheck['message'] ?? 'Workspace storage quota exceeded. Free up space or contact your admin.',
            ];
        }

        $stmt = $db->prepare('SELECT * FROM file_objects WHERE sha256 = ?');
        $stmt->execute([$sha256]);
        $existingObj = $stmt->fetch(PDO::FETCH_ASSOC);

        $storagePath = '';

        if ($existingObj) {
            $storagePath = $existingObj['storage_path'];
            if ($isProcessedImage) {
                @unlink($processedTmp);
            }
        } else {
            $fileName = $sha256 . ($finalExtension !== '' ? '.' . $finalExtension : '');
            $storagePath = $uploadSubdir . '/' . $fileName;
            $physicalDestination = ROOT_DIR . '/storage/' . $storagePath;

            if ($isProcessedImage) {
                if (!rename($processedTmp, $physicalDestination)) {
                    @unlink($processedTmp);

                    return [
                        'success' => false,
                        'error' => 'storage_failed',
                        'message' => 'Could not save processed image.',
                    ];
                }
            } elseif (!move_uploaded_file($tmpPath, $physicalDestination)) {
                return [
                    'success' => false,
                    'error' => 'storage_failed',
                    'message' => 'Could not save uploaded file.',
                ];
            }

            $stmt = $db->prepare('
                INSERT INTO file_objects (sha256, storage_disk, storage_path, size_bytes)
                VALUES (?, \'local\', ?, ?)
            ');
            $stmt->execute([$sha256, $storagePath, $fileSize]);
        }

        $stmt = $db->prepare('
            INSERT INTO files (workspace_id, uploaded_by, original_name, storage_disk, storage_path, mime_type, extension, size_bytes, sha256, category)
            VALUES (?, ?, ?, \'local\', ?, ?, ?, ?, ?, ?)
        ');

        $dbOriginalName = $originalName;
        if ($isProcessedImage && $extension !== 'webp' && $finalExtension === 'webp') {
            $dbOriginalName = pathinfo($originalName, PATHINFO_FILENAME) . '.webp';
        }

        $stmt->execute([
            $workspaceId,
            $uploadedBy,
            $dbOriginalName,
            $storagePath,
            $finalMime,
            $finalExtension,
            $fileSize,
            $sha256,
            $category,
        ]);

        $fileId = (int)$db->lastInsertId();

        return [
            'success' => true,
            'file' => [
                'id' => $fileId,
                'original_name' => $dbOriginalName,
                'storage_path' => $storagePath,
                'mime_type' => $finalMime,
                'extension' => $finalExtension,
                'size_bytes' => $fileSize,
                'size_label' => FileUploadPolicy::formatSize($fileSize),
                'category' => $category,
                'url' => BASE_URL . '/files/download/' . $fileId,
            ],
        ];
    }
}
