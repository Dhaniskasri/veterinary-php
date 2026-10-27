<?php
/**
 * Appointment Booking Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);
validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('owner/appointment_add.php');
}

$ownerId = (int)current_user()['id'];
$animalId = (int)($_POST['animal_id'] ?? 0);
$vetId = (int)($_POST['veterinarian_id'] ?? 0);
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$appointmentTime = trim($_POST['appointment_time'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$symptoms = trim($_POST['symptoms'] ?? '');

$errors = [];

// 1. Validate inputs
if ($animalId <= 0) $errors[] = 'Please select a registered animal.';
if ($vetId <= 0) $errors[] = 'Please select a veterinarian.';
if (empty($appointmentDate)) $errors[] = 'Please select an appointment date.';
if (empty($appointmentTime)) $errors[] = 'Please select an available time slot.';
if (empty($reason)) $errors[] = 'Please specify the reason for the visit.';
if (empty($symptoms)) $errors[] = 'Please describe the animal\'s current symptoms.';

// 2. Validate date is not in past
if (!empty($appointmentDate)) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $appointmentDate) {
        $errors[] = 'Invalid appointment date format.';
    } else {
        $today = new DateTime('today');
        if ($dateObj < $today) {
            $errors[] = 'Appointments cannot be booked for past dates.';
        }
    }
}

// 3. Verify Animal Ownership (IDOR Prevention)
if ($animalId > 0) {
    $animalStmt = $pdo->prepare("SELECT id, animal_name FROM animals WHERE id = ? AND owner_id = ?");
    $animalStmt->execute([$animalId, $ownerId]);
    $animal = $animalStmt->fetch();
    if (!$animal) {
        $errors[] = 'The selected animal does not exist or does not belong to your account.';
    }
}

// 4. Verify Veterinarian Approval and Status
if ($vetId > 0) {
    $vetStmt = $pdo->prepare("
        SELECT u.id, u.full_name, vp.approval_status 
        FROM users u
        JOIN veterinarian_profiles vp ON vp.user_id = u.id
        WHERE u.id = ? AND u.role = 'veterinarian' AND u.account_status = 'active' AND vp.approval_status = 'approved'
    ");
    $vetStmt->execute([$vetId]);
    $vet = $vetStmt->fetch();
    if (!$vet) {
        $errors[] = 'The selected veterinarian is not currently available for bookings.';
    }
}

if (!empty($errors)) {
    redirect('owner/appointment_add.php', implode(' ', $errors), 'danger');
}

// 5. Transactional Booking & Race Condition Prevention
try {
    $pdo->beginTransaction();

    // Check availability slot lock
    $conflictStmt = $pdo->prepare("
        SELECT id FROM appointments 
        WHERE veterinarian_id = :vet_id 
          AND appointment_date = :app_date 
          AND appointment_time = :app_time 
          AND appointment_status IN ('pending', 'scheduled')
        FOR UPDATE
    ");
    $conflictStmt->execute([
        ':vet_id'    => $vetId,
        ':app_date'  => $appointmentDate,
        ':app_time'  => $appointmentTime
    ]);

    if ($conflictStmt->fetch()) {
        $pdo->rollBack();
        redirect('owner/appointment_add.php', 'This time slot was just booked by another user. Please choose an alternate slot.', 'warning');
    }

    // Insert Appointment
    $insertStmt = $pdo->prepare("
        INSERT INTO appointments 
        (owner_id, veterinarian_id, animal_id, appointment_date, appointment_time, reason, symptoms, appointment_status, created_at)
        VALUES (:owner_id, :vet_id, :animal_id, :app_date, :app_time, :reason, :symptoms, 'pending', NOW())
    ");
    $insertStmt->execute([
        ':owner_id'  => $ownerId,
        ':vet_id'    => $vetId,
        ':animal_id' => $animalId,
        ':app_date'  => $appointmentDate,
        ':app_time'  => $appointmentTime,
        ':reason'    => substr($reason, 0, 255),
        ':symptoms'  => $symptoms
    ]);

    $appointmentId = (int)$pdo->lastInsertId();
    log_activity($pdo, $ownerId, 'APPOINTMENT_BOOKED', 'appointment', $appointmentId);

    $pdo->commit();

    redirect(
        'owner/appointment_view.php?id=' . $appointmentId,
        'Your appointment request has been submitted successfully! The veterinarian will review and confirm your visit slot.',
        'success'
    );
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Appointment booking error: " . $e->getMessage());
    redirect('owner/appointment_add.php', 'Database error while booking appointment. Please try again.', 'danger');
}
