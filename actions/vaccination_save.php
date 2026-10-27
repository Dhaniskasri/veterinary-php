<?php
/**
 * Vaccination Save Action
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

$animalId = (int)($_POST['animal_id'] ?? 0);
$vetId = ($userRole === ROLE_ADMIN && !empty($_POST['veterinarian_id'])) ? (int)$_POST['veterinarian_id'] : $userId;

$vaccineName = trim($_POST['vaccine_name'] ?? '');
$vaccinationDate = trim($_POST['vaccination_date'] ?? date('Y-m-d'));
$nextDueDate = !empty($_POST['next_due_date']) ? $_POST['next_due_date'] : null;
$batchNumber = trim($_POST['batch_number'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$errors = [];

if ($animalId <= 0) $errors[] = 'Please select an animal.';
if (empty($vaccineName)) $errors[] = 'Vaccine name is required.';
if (empty($vaccinationDate)) $errors[] = 'Vaccination date is required.';

if (!empty($errors)) {
    redirect('veterinarian/vaccination_add.php', implode(' ', $errors), 'danger');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO vaccinations 
        (animal_id, veterinarian_id, vaccine_name, vaccination_date, next_due_date, batch_number, notes, created_at)
        VALUES (:animal_id, :vet_id, :vname, :vdate, :due, :batch, :notes, NOW())
    ");
    $stmt->execute([
        ':animal_id' => $animalId,
        ':vet_id'    => $vetId,
        ':vname'     => substr($vaccineName, 0, 150),
        ':vdate'     => $vaccinationDate,
        ':due'       => $nextDueDate,
        ':batch'     => substr($batchNumber, 0, 100),
        ':notes'     => $notes
    ]);

    $newVacId = (int)$pdo->lastInsertId();
    log_activity($pdo, $userId, 'VACCINATION_RECORDED', 'vaccination', $newVacId);

    $dest = ($userRole === ROLE_ADMIN) ? 'admin/vaccinations.php' : 'veterinarian/dashboard.php';
    redirect($dest, 'Vaccination record for "' . e($vaccineName) . '" successfully recorded.', 'success');
} catch (PDOException $e) {
    error_log("Vaccination save error: " . $e->getMessage());
    redirect('veterinarian/vaccination_add.php', 'Database error while saving vaccination record.', 'danger');
}
