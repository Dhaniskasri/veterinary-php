<?php
/**
 * Add Prescription Item Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role([ROLE_VET, ROLE_ADMIN]);
validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('veterinarian/dashboard.php');
}

$user = current_user();
$userId = (int)$user['id'];
$userRole = $user['role'];

$recordId = (int)($_POST['medical_record_id'] ?? 0);
$medName  = trim($_POST['medicine_name'] ?? '');
$dosage   = trim($_POST['dosage'] ?? '');
$frequency = trim($_POST['frequency'] ?? '');
$duration = trim($_POST['duration'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');

$errors = [];

if ($recordId <= 0) $errors[] = 'Invalid medical record.';
if (empty($medName)) $errors[] = 'Medicine name is required.';
if (empty($dosage)) $errors[] = 'Dosage is required.';
if (empty($frequency)) $errors[] = 'Frequency is required.';
if (empty($duration)) $errors[] = 'Duration is required.';

if (!empty($errors)) {
    redirect('veterinarian/prescription_add.php?record_id=' . $recordId, implode(' ', $errors), 'danger');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO prescriptions (medical_record_id, medicine_name, dosage, frequency, duration, instructions, created_at)
        VALUES (:mr_id, :name, :dosage, :freq, :dur, :inst, NOW())
    ");
    $stmt->execute([
        ':mr_id'  => $recordId,
        ':name'   => substr($medName, 0, 150),
        ':dosage' => substr($dosage, 0, 100),
        ':freq'   => substr($frequency, 0, 100),
        ':dur'    => substr($duration, 0, 100),
        ':inst'   => $instructions
    ]);

    log_activity($pdo, $userId, 'PRESCRIPTION_ADDED', 'prescription', (int)$pdo->lastInsertId());

    redirect('owner/prescription_view.php?record_id=' . $recordId, 'Prescription medicine added successfully.', 'success');
} catch (PDOException $e) {
    error_log("Prescription save error: " . $e->getMessage());
    redirect('veterinarian/prescription_add.php?record_id=' . $recordId, 'Failed to add prescription item.', 'danger');
}
