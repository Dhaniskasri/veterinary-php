<?php
/**
 * Global Helper Functions
 * Veterinary Care for Animal Medical Solution
 */

/**
 * Escapes text for safe HTML output.
 *
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generates an absolute application URL from a relative path.
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return rtrim(BASE_URL, '/') . ($path !== '' ? '/' . $path : '');
}

/**
 * Redirects user to specified path with an optional flash message.
 *
 * @param string $path
 * @param string|null $message
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @return void
 */
function redirect(string $path, ?string $message = null, string $type = 'info'): void {
    if ($message !== null) {
        set_flash($type, $message);
    }
    header('Location: ' . url($path));
    exit;
}

/**
 * Stores a flash message in the session.
 *
 * @param string $type
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieves and clears all stored flash messages.
 *
 * @return array
 */
function get_flashes(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Formats a MySQL date string into human-friendly format.
 *
 * @param string|null $date
 * @param string $format
 * @return string
 */
function format_date(?string $date, string $format = 'M j, Y'): string {
    if (empty($date)) {
        return '—';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '—';
}

/**
 * Formats a MySQL time string into 12-hour AM/PM format.
 *
 * @param string|null $time
 * @return string
 */
function format_time(?string $time): string {
    if (empty($time)) {
        return '—';
    }
    $timestamp = strtotime($time);
    return $timestamp ? date('g:i A', $timestamp) : '—';
}

/**
 * Returns a styled Bootstrap badge for appointment or account status.
 *
 * @param string $status
 * @return string
 */
function badge_status(string $status): string {
    $statusLower = strtolower(trim($status));
    $map = [
        'active'     => ['bg-success-subtle text-success border border-success-subtle', 'Active'],
        'approved'   => ['bg-success-subtle text-success border border-success-subtle', 'Approved'],
        'completed'  => ['bg-success-subtle text-success border border-success-subtle', 'Completed'],
        'scheduled'  => ['bg-primary-subtle text-primary border border-primary-subtle', 'Scheduled'],
        'pending'    => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'Pending Review'],
        'cancelled'  => ['bg-secondary-subtle text-secondary border border-secondary-subtle', 'Cancelled'],
        'rejected'   => ['bg-danger-subtle text-danger border border-danger-subtle', 'Rejected'],
        'inactive'   => ['bg-secondary-subtle text-secondary border border-secondary-subtle', 'Inactive'],
        'suspended'  => ['bg-danger-subtle text-danger border border-danger-subtle', 'Suspended'],
        'unread'     => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'New Inbound'],
        'read'       => ['bg-light text-muted border', 'Read'],
        'replied'    => ['bg-success-subtle text-success border border-success-subtle', 'Replied']
    ];

    $classes = $map[$statusLower][0] ?? 'bg-light text-dark border';
    $label = $map[$statusLower][1] ?? ucfirst($statusLower);

    return '<span class="badge rounded-pill ' . $classes . ' px-2.5 py-1.5 fw-medium">' . e($label) . '</span>';
}

/**
 * Returns an appropriate icon name for a species.
 *
 * @param string $species
 * @return string
 */
function get_species_icon(string $species): string {
    $s = strtolower($species);
    if (str_contains($s, 'dog')) return 'bi-award';
    if (str_contains($s, 'cat')) return 'bi-heart-pulse';
    if (str_contains($s, 'cow') || str_contains($s, 'bovine') || str_contains($s, 'buffalo')) return 'bi-shield-check';
    if (str_contains($s, 'horse')) return 'bi-trophy';
    if (str_contains($s, 'poultry') || str_contains($s, 'bird') || str_contains($s, 'avian')) return 'bi-egg';
    return 'bi-suit-heart';
}

/**
 * Safely handles an uploaded animal photo.
 *
 * @param array $file
 * @param string $destinationDir
 * @return array ['success' => bool, 'path' => ?string, 'error' => ?string]
 */
function handle_file_upload(array $file, string $destinationDir): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => 'File upload error code: ' . $file['error']];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'path' => null, 'error' => 'File size exceeds 5MB limit.'];
    }

    // Verify MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file format. Allowed types: JPG, PNG, WEBP.'];
    }

    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_IMAGE_EXTENSIONS, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file extension.'];
    }

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }

    // Secure randomized filename
    $newFilename = 'animal_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $extension;
    $targetPath = $destinationDir . DIRECTORY_SEPARATOR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to move uploaded file.'];
    }

    return ['success' => true, 'path' => $newFilename, 'error' => null];
}

/**
 * Logs an activity into the activity_logs table without sensitive credentials.
 *
 * @param PDO $pdo
 * @param int|null $userId
 * @param string $action
 * @param string $entityType
 * @param int|null $entityId
 * @return void
 */
function log_activity(PDO $pdo, ?int $userId, string $action, string $entityType, ?int $entityId = null): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, created_at)
            VALUES (:user_id, :action, :entity_type, :entity_id, NOW())
        ");
        $stmt->execute([
            ':user_id'     => $userId,
            ':action'      => substr($action, 0, 100),
            ':entity_type' => substr($entityType, 0, 50),
            ':entity_id'   => $entityId
        ]);
    } catch (Exception $e) {
        // Activity logging failures should never halt primary user transactions
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Sends a JSON response and exits.
 *
 * @param mixed $data
 * @param int $status
 * @return void
 */
function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Checks if current request expects JSON.
 *
 * @return bool
 */
function is_ajax(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
}
