<?php

namespace App\Core;

use App\Models\WorkspaceFile;
use PDO;

class FileUploader
{
    /**
     * Handles file upload, image compression, deduplication, and database tracking.
     *
     * @param array $fileItem Element from $_FILES array (e.g., $_FILES['file'])
     * @param int $workspaceId Active workspace ID
     * @param int $uploadedBy Active workspace member ID
     * @return array|null Returns file details or null on failure
     */
    public static function upload(array $fileItem, int $workspaceId, int $uploadedBy): ?array
    {
        if (empty($fileItem['name']) || $fileItem['error'] !== UPLOAD_ERR_OK || empty($fileItem['tmp_name'])) {
            return null;
        }

        $tmpPath = $fileItem['tmp_name'];
        $originalName = $fileItem['name'];
        $mimeType = $fileItem['type'] ?? 'application/octet-stream';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Determine category
        $category = 'other';
        if (str_starts_with($mimeType, 'image/')) {
            $category = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $category = 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            $category = 'audio';
        } elseif (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'])) {
            $category = 'document';
        }

        $yearMonth = date('Y-m');
        $uploadSubdir = "uploads/workspace_{$workspaceId}/{$yearMonth}";
        $fullUploadDir = ROOT_DIR . "/storage/" . $uploadSubdir;

        if (!is_dir($fullUploadDir)) {
            mkdir($fullUploadDir, 0777, true);
        }

        $processedTmp = $tmpPath;
        $isProcessedImage = false;
        $finalExtension = $extension;
        $finalMime = $mimeType;

        // Perform image compression/processing if GD is available and file is an image
        if ($category === 'image' && extension_loaded('gd')) {
            $srcImg = null;
            if ($mimeType === 'image/jpeg' || $extension === 'jpg' || $extension === 'jpeg') {
                $srcImg = @imagecreatefromjpeg($tmpPath);
            } elseif ($mimeType === 'image/png' || $extension === 'png') {
                $srcImg = @imagecreatefrompng($tmpPath);
                // Convert PNG to WebP to save space
                $finalExtension = 'webp';
                $finalMime = 'image/webp';
            } elseif ($mimeType === 'image/webp' || $extension === 'webp') {
                $srcImg = @imagecreatefromwebp($tmpPath);
            }

            if ($srcImg) {
                $width = imagesx($srcImg);
                $height = imagesy($srcImg);

                // Resize if dimensions exceed 2048px
                $maxDim = 2048;
                if ($width > $maxDim || $height > $maxDim) {
                    $ratio = $width / $height;
                    if ($ratio > 1) {
                        $newWidth = $maxDim;
                        $newHeight = round($maxDim / $ratio);
                    } else {
                        $newHeight = $maxDim;
                        $newWidth = round($maxDim * $ratio);
                    }
                    $dstImg = imagecreatetruecolor($newWidth, $newHeight);

                    // Preserve alpha transparency for PNG/WebP
                    if ($finalExtension === 'webp') {
                        imagealphablending($dstImg, false);
                        imagesavealpha($dstImg, true);
                    }

                    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($srcImg);
                    $srcImg = $dstImg;
                }

                // Create unique temp file to save processed image
                $tempCompressed = tempnam(sys_get_temp_dir(), 'chatrox_img_');
                $saveSuccess = false;

                if ($finalExtension === 'webp') {
                    $saveSuccess = imagewebp($srcImg, $tempCompressed, 80);
                } else {
                    $saveSuccess = imagejpeg($srcImg, $tempCompressed, 80);
                }

                imagedestroy($srcImg);

                if ($saveSuccess) {
                    $processedTmp = $tempCompressed;
                    $isProcessedImage = true;
                } else {
                    @unlink($tempCompressed);
                }
            }
        }

        // Calculate SHA256 for deduplication
        $sha256 = hash_file('sha256', $processedTmp);
        $fileSize = filesize($processedTmp);

        // Check if this file object already exists in database
        $db = WorkspaceFile::db();
        $stmt = $db->prepare("SELECT * FROM file_objects WHERE sha256 = ?");
        $stmt->execute([$sha256]);
        $existingObj = $stmt->fetch();

        $storagePath = "";

        if ($existingObj) {
            // Deduplication match! Reuse path
            $storagePath = $existingObj['storage_path'];
            // Clean up the processed temp file if we created one
            if ($isProcessedImage) {
                @unlink($processedTmp);
            }
        } else {
            // New unique file. Write it to storage directory
            $fileName = $sha256 . ($finalExtension ? '.' . $finalExtension : '');
            $storagePath = $uploadSubdir . '/' . $fileName;
            $physicalDestination = ROOT_DIR . "/storage/" . $storagePath;

            if ($isProcessedImage) {
                // Rename our processed temp image to the final location
                if (!rename($processedTmp, $physicalDestination)) {
                    @unlink($processedTmp);
                    return null;
                }
            } else {
                // Move uploaded file normally
                if (!move_uploaded_file($tmpPath, $physicalDestination)) {
                    return null;
                }
            }

            // Insert into file_objects table
            $stmt = $db->prepare("
                INSERT INTO file_objects (sha256, storage_disk, storage_path, size_bytes)
                VALUES (?, 'local', ?, ?)
            ");
            $stmt->execute([$sha256, $storagePath, $fileSize]);
        }

        // Insert into files table
        $stmt = $db->prepare("
            INSERT INTO files (workspace_id, uploaded_by, original_name, storage_disk, storage_path, mime_type, extension, size_bytes, sha256, category)
            VALUES (?, ?, ?, 'local', ?, ?, ?, ?, ?, ?)
        ");
        
        // Ensure final extension name has webp if we converted it
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
            $category
        ]);

        $fileId = $db->lastInsertId();

        // Calculate and format size
        $formattedSize = self::formatSize($fileSize);

        return [
            'id' => $fileId,
            'original_name' => $dbOriginalName,
            'storage_path' => $storagePath,
            'mime_type' => $finalMime,
            'extension' => $finalExtension,
            'size_bytes' => $fileSize,
            'size_label' => $formattedSize,
            'category' => $category,
            'url' => BASE_URL . '/files/download/' . $fileId
        ];
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
}
