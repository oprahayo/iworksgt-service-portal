<?php

declare(strict_types=1);

function file_access_error(string $message, int $status = 400): RuntimeException
{
    return new RuntimeException($message, $status);
}

function resolve_portal_file_request(array $user, array $query): array
{
    $announcementId = (int) ($query['announcement_id'] ?? 0);
    if ($announcementId > 0) {
        $announcement = get_announcement($announcementId);
        if (!$announcement || empty($announcement['attachment_path'])) {
            throw file_access_error('Announcement attachment not found.', 404);
        }

        if (!can_access_announcement($user, $announcement)) {
            throw file_access_error('Access denied.', 403);
        }

        $path = (string) $announcement['attachment_path'];
        return [
            'path' => $path,
            'name' => basename($path),
            'mime' => '',
        ];
    }

    $chatMessageId = (int) ($query['chat_message_id'] ?? 0);
    if ($chatMessageId > 0) {
        $chatMessage = get_chat_message($chatMessageId);
        if (!$chatMessage || empty($chatMessage['attachment_path'])) {
            throw file_access_error('Chat attachment not found.', 404);
        }

        $canAccess = (int) $chatMessage['admin_id'] === (int) $user['id']
            || (int) $chatMessage['agent_id'] === (int) $user['id'];
        if (!$canAccess) {
            throw file_access_error('Access denied.', 403);
        }

        $path = (string) $chatMessage['attachment_path'];
        return [
            'path' => $path,
            'name' => (string) ($chatMessage['attachment_name'] ?: basename($path)),
            'mime' => (string) ($chatMessage['attachment_mime'] ?? ''),
        ];
    }

    $supportMessageId = (int) ($query['support_message_id'] ?? 0);
    if ($supportMessageId > 0) {
        $supportMessage = get_support_ticket_message($supportMessageId);
        if (!$supportMessage || empty($supportMessage['attachment_path'])) {
            throw file_access_error('Support attachment not found.', 404);
        }

        $isOwner = (int) $supportMessage['client_id'] === (int) $user['id'];
        $isSupportTeam = in_array((string) ($user['role'] ?? ''), ['admin', 'finance'], true);
        if (!$isOwner && !$isSupportTeam) {
            throw file_access_error('Access denied.', 403);
        }

        $path = (string) $supportMessage['attachment_path'];
        return [
            'path' => $path,
            'name' => (string) ($supportMessage['attachment_name'] ?: basename($path)),
            'mime' => (string) ($supportMessage['attachment_mime'] ?? ''),
        ];
    }

    $paymentId = (int) ($query['payment_id'] ?? 0);
    if ($paymentId > 0) {
        $payment = find_payment_for_download($paymentId);
        if (!$payment || empty($payment['proof_path'])) {
            throw file_access_error('Payment proof not found.', 404);
        }

        $isOwner = (int) $payment['client_id'] === (int) $user['id'];
        $isFinance = in_array((string) ($user['role'] ?? ''), ['admin', 'finance'], true);
        if (!$isOwner && !$isFinance) {
            throw file_access_error('Access denied.', 403);
        }

        $path = (string) $payment['proof_path'];
        return [
            'path' => $path,
            'name' => basename($path),
            'mime' => '',
        ];
    }

    $intakeSubmissionId = (int) ($query['intake_submission_id'] ?? 0);
    if ($intakeSubmissionId > 0) {
        $fieldKey = trim((string) ($query['field_key'] ?? ''));
        $fileIndex = (int) ($query['file_index'] ?? 0);
        $entry = find_intake_submission_file($intakeSubmissionId, $fieldKey, $fileIndex);
        if (!$entry) {
            throw file_access_error('Intake file not found.', 404);
        }

        $submission = $entry['submission'];
        if (!can_access_intake_submission($user, $submission)) {
            throw file_access_error('Access denied.', 403);
        }

        $file = $entry['file'];
        $path = (string) ($file['storage_key'] ?? '');
        return [
            'path' => $path,
            'name' => (string) ($file['original_name'] ?? basename($path)),
            'mime' => (string) ($file['mime'] ?? ''),
        ];
    }

    $fileId = (int) ($query['id'] ?? 0);
    if ($fileId <= 0) {
        throw file_access_error('File not found.', 404);
    }

    $file = find_file($fileId);
    if (!$file) {
        throw file_access_error('File not found.', 404);
    }

    // Never authorize against a partial projection or assume a missing
    // payment state is "paid". Fetch the full job used by the page policy,
    // including managed_by_admin_id and effective payment status.
    $job = get_job((int) ($file['job_id'] ?? 0));
    if (!$job || !can_access_job($user, $job)) {
        throw file_access_error('Access denied.', 403);
    }

    return [
        'path' => (string) $file['file_path'],
        'name' => basename((string) $file['file_name']),
        'mime' => (string) ($file['mime_type'] ?: ''),
    ];
}

function detect_preview_kind(string $mime, string $name): string
{
    $mime = strtolower(trim($mime));
    $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

    if (str_starts_with($mime, 'image/')) {
        return 'image';
    }
    if ($mime === 'application/pdf') {
        return 'pdf';
    }
    if (str_starts_with($mime, 'video/')) {
        return 'video';
    }
    if (str_starts_with($mime, 'audio/')) {
        return 'audio';
    }
    if (
        str_starts_with($mime, 'text/')
        || str_contains($mime, 'json')
        || str_contains($mime, 'xml')
        || str_contains($mime, 'javascript')
        || str_contains($mime, 'csv')
    ) {
        return 'text';
    }

    if (
        str_contains($mime, 'msword')
        || str_contains($mime, 'officedocument')
        || str_contains($mime, 'opendocument')
        || str_contains($mime, 'ms-excel')
        || str_contains($mime, 'ms-powerpoint')
    ) {
        return 'office';
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'avif'], true)) {
        return 'image';
    }
    if ($ext === 'pdf') {
        return 'pdf';
    }
    if (in_array($ext, ['mp4', 'webm', 'ogg', 'm4v', 'mov'], true)) {
        return 'video';
    }
    if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'], true)) {
        return 'audio';
    }
    if (in_array($ext, ['txt', 'csv', 'log', 'json', 'xml', 'yml', 'yaml', 'ini', 'conf', 'cfg', 'sql', 'md', 'js', 'ts', 'jsx', 'tsx', 'php', 'css', 'html', 'htm', 'sh', 'bat', 'py', 'java', 'go', 'rb', 'rs', 'c', 'cpp', 'h', 'hpp', 'swift'], true)) {
        return 'text';
    }
    if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf'], true)) {
        return 'office';
    }

    return 'other';
}
