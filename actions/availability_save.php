<?php
/**
 * Veterinarian Availability Schedule Save Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);
validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('veterinarian/availability.php');
}

$vetId = (int)current_user()['id'];
$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $availId = (int)($_POST['availability_id'] ?? 0);
    if ($availId > 0) {
        $delStmt = $pdo->prepare("DELETE FROM veterinarian_availability WHERE id = ? AND veterinarian_id = ?");
        $delStmt->execute([$availId, $vetId]);
        redirect('veterinarian/availability.php', 'Schedule entry deleted.', 'info');
    }
}

$availDate = trim($_POST['available_date'] ?? '');
$startTime = trim($_POST['start_time'] ?? '09:00');
$endTime = trim($_POST['end_time'] ?? '17:00');
$duration = (int)($_POST['slot_duration_minutes'] ?? 30);
$isAvailable = isset($_POST['is_available']) ? 1 : 0;

$errors = [];

if (empty($availDate)) $errors[] = 'Date is required.';
if (empty($startTime)) $errors[] = 'Start time is required.';
if (empty($endTime)) $errors[] = 'End time is required.';
if ($startTime >= $endTime) $errors[] = 'End time must be after start time.';
if ($duration <= 0) $duration = 30;

if (!empty($errors)) {
    redirect('veterinarian/availability.php', implode(' ', $errors), 'danger');
}

try {
    // Check if record for this vet and date already exists
    $check = $pdo->prepare("SELECT id FROM veterinarian_availability WHERE veterinarian_id = ? AND available_date = ?");
    $check->execute([$vetId, $availDate]);
    $existing = $check->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE veterinarian_availability SET
                start_time = :start,
                end_time = :end,
                slot_duration_minutes = :dur,
                is_available = :avail
            WHERE id = :id
        ");
        $stmt->execute([
            ':start' => $startTime,
            ':end'   => $endTime,
            ':dur'   => $duration,
            ':avail' => $isAvailable,
            ':id'    => $existing['id']
        ]);
        $msg = 'Working hours updated for ' . format_date($availDate);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO veterinarian_availability (veterinarian_id, available_date, start_time, end_time, slot_duration_minutes, is_available)
            VALUES (:vet_id, :vdate, :start, :end, :dur, :avail)
        ");
        $stmt->execute([
            ':vet_id' => $vetId,
            ':vdate'  => $availDate,
            ':start'  => $startTime,
            ':end'    => $endTime,
            ':dur'    => $duration,
            ':avail'  => $isAvailable
        ]);
        $msg = 'New availability schedule added for ' . format_date($availDate);
    }

    log_activity($pdo, $vetId, 'AVAILABILITY_SAVED', 'veterinarian_availability', $vetId);
    redirect('veterinarian/availability.php', $msg, 'success');
} catch (PDOException $e) {
    error_log("Availability save error: " . $e->getMessage());
    redirect('veterinarian/availability.php', 'Database error while saving schedule.', 'danger');
}
