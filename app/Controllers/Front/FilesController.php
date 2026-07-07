<?php

namespace App\Controllers\Front;

use App\Helpers\FileAccess;
use App\Models\WorkspaceFile;

class FilesController extends FrontController
{
    public function index(): void
    {
        $this->renderApp('files', [
            'workspace_files' => [],
        ]);
    }

    public function download(string $id): void
    {
        $user = \App\Core\Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0) {
            $this->abortDownload(404);
        }

        $fileId = (int)$id;
        if ($fileId <= 0) {
            $this->abortDownload(404);
        }

        $db = WorkspaceFile::db();
        $stmt = $db->prepare('SELECT * FROM files WHERE id = ?');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if (!$file) {
            $this->abortDownload(404);
        }

        if (!FileAccess::canMemberAccessFile($db, $file, $memberId, $workspaceId)) {
            $this->abortDownload(404);
        }

        $physicalPath = ROOT_DIR . '/storage/' . $file['storage_path'];
        if (!is_file($physicalPath)) {
            $this->abortDownload(404);
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $mime = (string)($file['mime_type'] ?? 'application/octet-stream');
        $extension = strtolower((string)($file['extension'] ?? ''));
        $safeName = preg_replace('/[^\w.\- ]+/u', '_', basename((string)$file['original_name'])) ?: 'download';

        if (
            ($file['category'] ?? '') === 'audio'
            && in_array($extension, ['webm', 'weba'], true)
            && str_starts_with($mime, 'video/')
        ) {
            $mime = 'audio/webm';
        }

        $size = (int)filesize($physicalPath);
        $disposition = FileAccess::isInlineDisposition($mime, $extension) ? 'inline' : 'attachment';

        $escapedName = str_replace(['"', "\r", "\n"], ['\\"', '', ''], $safeName);
        $rfc5987Name = rawurlencode($safeName);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        header('Content-Disposition: ' . $disposition . '; filename="' . $escapedName . '"; filename*=UTF-8\'\'' . $rfc5987Name);
        header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, max-age=3600');
        header('Pragma: private');

        $start = 0;
        $end = $size - 1;
        $length = $size;

        if ($size > 0 && isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=(\d*)-(\d*)/', (string)$_SERVER['HTTP_RANGE'], $matches)) {
                if ($matches[1] !== '') {
                    $start = (int)$matches[1];
                }
                if ($matches[2] !== '') {
                    $end = (int)$matches[2];
                }

                if ($start > $end || $start >= $size) {
                    http_response_code(416);
                    header('Content-Range: bytes */' . $size);
                    exit;
                }

                if ($end >= $size) {
                    $end = $size - 1;
                }

                $length = $end - $start + 1;
                http_response_code(206);
                header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
            }
        }

        header('Content-Length: ' . $length);

        $fp = fopen($physicalPath, 'rb');
        if ($fp === false) {
            $this->abortDownload(500);
        }

        if ($start > 0) {
            fseek($fp, $start);
        }

        $chunkSize = 8192;
        $bytesLeft = $length;

        while ($bytesLeft > 0 && !feof($fp)) {
            $readLength = min($chunkSize, $bytesLeft);
            $buffer = fread($fp, $readLength);
            if ($buffer === false) {
                break;
            }
            echo $buffer;
            $bytesLeft -= strlen($buffer);
        }

        fclose($fp);
        exit;
    }

    private function abortDownload(int $status): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'File not found.';
        exit;
    }
}
