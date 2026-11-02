<?php

const PORTAL_FEEDBACK_MAX_FILE_SIZE = 5242880;

function portalFeedbackUploadDirectory(): string
{
    $configuredDirectory = getenv('COLLEGE_FEEDBACK_UPLOAD_DIR');
    return $configuredDirectory !== false && $configuredDirectory !== ''
        ? rtrim($configuredDirectory, DIRECTORY_SEPARATOR)
        : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'college-feedback-system' . DIRECTORY_SEPARATOR . 'feedback';
}

function portalFeedbackAttachmentFilePath(string $storedName): ?string
{
    if ($storedName === '' || basename($storedName) !== $storedName) {
        return null;
    }
    return portalFeedbackUploadDirectory() . DIRECTORY_SEPARATOR . $storedName;
}

function storePortalFeedbackAttachment(array $upload): array
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['error' => '', 'path' => null, 'name' => null];
    }

    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['error' => 'The attachment could not be uploaded. Please choose another file.', 'path' => null, 'name' => null];
    }
    if (!isset($upload['size'], $upload['tmp_name'], $upload['name']) || (int) $upload['size'] < 1 || (int) $upload['size'] > PORTAL_FEEDBACK_MAX_FILE_SIZE) {
        return ['error' => 'Attachments must be 5 MB or smaller.', 'path' => null, 'name' => null];
    }

    $originalName = basename(str_replace('\\', '/', (string) $upload['name']));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedTypes = [
        'jpg' => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
        'jpeg' => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
        'png' => ['mime' => 'image/png', 'extension' => 'png'],
        'pdf' => ['mime' => 'application/pdf', 'extension' => 'pdf'],
    ];
    if (!isset($allowedTypes[$extension]) || !is_uploaded_file((string) $upload['tmp_name']) || !function_exists('finfo_open')) {
        return ['error' => 'Upload a JPG, JPEG, PNG, or PDF file smaller than 5 MB.', 'path' => null, 'name' => null];
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo ? finfo_file($fileInfo, (string) $upload['tmp_name']) : false;
    if ($fileInfo) {
        finfo_close($fileInfo);
    }
    if ($mimeType !== $allowedTypes[$extension]['mime']) {
        return ['error' => 'The attachment content does not match an allowed file type.', 'path' => null, 'name' => null];
    }

    $directory = portalFeedbackUploadDirectory();
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        return ['error' => 'Attachments are unavailable right now. Please try again later.', 'path' => null, 'name' => null];
    }

    $storedName = bin2hex(random_bytes(24)) . '.' . $allowedTypes[$extension]['extension'];
    if (!move_uploaded_file((string) $upload['tmp_name'], $directory . '/' . $storedName)) {
        return ['error' => 'The attachment could not be saved. Please try again.', 'path' => null, 'name' => null];
    }
    chmod($directory . '/' . $storedName, 0600);

    $displayName = preg_replace('/[[:cntrl:]]/', '', $originalName) ?? 'attachment';
    $displayName = function_exists('mb_substr') ? mb_substr($displayName, 0, 255, 'UTF-8') : substr($displayName, 0, 255);
    return ['error' => '', 'path' => $storedName, 'name' => $displayName];
}

function deletePortalFeedbackAttachment(?string $storedName): void
{
    if (!$storedName) {
        return;
    }
    $path = portalFeedbackAttachmentFilePath($storedName);
    if ($path && is_file($path)) {
        unlink($path);
    }
}