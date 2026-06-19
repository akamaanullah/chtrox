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
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $uploaded = [];

        // Check if files are uploaded under 'file' (single) or 'files' (multiple)
        if (!empty($_FILES['file'])) {
            $result = FileUploader::upload($_FILES['file'], $workspaceId, $memberId);
            if ($result) {
                $uploaded[] = $result;
            }
        } elseif (!empty($_FILES['files'])) {
            // Handle multiple files
            $files = $_FILES['files'];
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $fileItem = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $result = FileUploader::upload($fileItem, $workspaceId, $memberId);
                    if ($result) {
                        $uploaded[] = $result;
                    }
                }
            } else {
                $result = FileUploader::upload($files, $workspaceId, $memberId);
                if ($result) {
                    $uploaded[] = $result;
                }
            }
        }

        if (empty($uploaded)) {
            $this->jsonResponse(['error' => 'No files uploaded or upload failed'], 400);
        }

        $this->jsonResponse([
            'success' => true,
            'files' => $uploaded
        ]);
    }
}
