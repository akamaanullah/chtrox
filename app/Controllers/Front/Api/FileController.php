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
