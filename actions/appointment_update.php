<?php
/**
 * Appointment Status Update Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_login();
validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$user = current_user();
$userId = (int)$user['id'];
$userRole = $user['role'];

$appointmentId = (int)($_POST['appointment_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$vetNotes = trim($_POST['veterinarian_notes'] ?? '');
$redirectUrl = trim($_POST['redirect_url'] ?? '');

if ($appointmentId <= 0) {
    redirect('index.php', 'Invalid appointment request.', 'danger');
}

// Fetch current appointment
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    redirect('index.php', 'Appointment not found.', 'danger');
}

$allowedStatuses = ['pending', 'scheduled', 'completed', 'cancelled', 'rejected'];
if (!in_array($newStatus, $allowedStatuses, true)) {
    redirect('index.php', 'Invalid appointment status requested.', 'danger');
}

// Role-based state transition verification
if ($userRole === ROLE_OWNER) {
    if ((int)$appointment['owner_id'] !== $userId) {
        redirect('owner/appointments.php', 'Unauthorized: You do not own this appointment.', 'danger');
    }
    // Owners can only cancel their own pending or scheduled appointments
    if ($newStatus !== 'cancelled') {
        redirect('owner/appointments.php', 'Owners can only cancel eligible appointments.', 'danger');
    }
    if (in_array($appointment['appointment_status'], ['completed', 'cancelled', 'rejected'], true)) {
        redirect('owner/appointments.php', 'This appointment cannot be cancelled because it is already ' . $appointment['appointment_status'] . '.', 'warning');
    }
} elseif ($userRole === ROLE_VET) {
    if ((int)$appointment['veterinarian_id'] !== $userId) {
        redirect('veterinarian/appointments.php', 'Unauthorized: You are not assigned to this appointment.', 'danger');
    }
    // Vets can accept (scheduled), reject, complete, or cancel
    if ($appointment['appointment_status'] === 'completed' && $newStatus !== 'completed') {
        redirect('veterinarian/appointments.php', 'Completed consultations cannot be reverted.', 'warning');
    }
} elseif ($userRole === ROLE_ADMIN) {
    // Admin has overall authority
} else {
    redirect('index.php', 'Unauthorized action.', 'danger');
}

try {
    $sql = "UPDATE appointments SET appointment_status = :status, updated_at = NOW()";
    $params = [
        ':status' => $newStatus,
        ':id'     => $appointmentId
    ];

    if (!empty($vetNotes) || $userRole === ROLE_VET) {
        $sql .= ", veterinarian_notes = :notes";
        $params[':notes'] = !empty($vetNotes) ? $vetNotes : $appointment['veterinarian_notes'];
    }

    $sql .= " WHERE id = :id";
    $upStmt = $pdo->prepare($sql);
    $upStmt->execute($params);

    log_activity($pdo, $userId, 'APPOINTMENT_STATUS_' . strtoupper($newStatus), 'appointment', $appointmentId);

    $msg = 'Appointment status successfully updated to ' . ucfirst($newStatus) . '.';

    // Safe fallback redirects based on role
    if (!empty($redirectUrl) && (str_starts_with($redirectUrl, 'owner/') || str_starts_with($redirectUrl, 'veterinarian/') || str_starts_with($redirectUrl, 'admin/'))) {
        redirect($redirectUrl, $msg, 'success');
    }

    if ($userRole === ROLE_OWNER) {
        redirect('owner/appointments.php', $msg, 'success');
    } elseif ($userRole === ROLE_VET) {
        redirect('veterinarian/appointments.php', $msg, 'success');
    } else {
        redirect('admin/appointments.php', $msg, 'success');
    }
} catch (PDOException $e) {
    error_log("Update appointment error: " . $e->getMessage());
    redirect('index.php', 'Error updating appointment status. Please try again.', 'danger');
}
