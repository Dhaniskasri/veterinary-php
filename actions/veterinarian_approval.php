<?php
/**
 * Administrator Approval & User Status Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);
validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/dashboard.php');
}

$adminId = (int)current_user()['id'];
$action = $_POST['action'] ?? '';
$targetUserId = (int)($_POST['user_id'] ?? 0);
$redirectUrl = trim($_POST['redirect_url'] ?? 'admin/veterinarians.php');

if ($targetUserId <= 0) {
    redirect($redirectUrl, 'Invalid user ID.', 'danger');
}

// Ensure admin does not accidentally deactivate themselves
if ($targetUserId === $adminId && in_array($action, ['deactivate', 'suspend', 'reject'], true)) {
    redirect($redirectUrl, 'Action prohibited: You cannot deactivate your own active administrator account.', 'danger');
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve_vet') {
        // Approve veterinarian
        $upUser = $pdo->prepare("UPDATE users SET account_status = 'active', updated_at = NOW() WHERE id = ?");
        $upUser->execute([$targetUserId]);

        $upProf = $pdo->prepare("UPDATE veterinarian_profiles SET approval_status = 'approved', updated_at = NOW() WHERE user_id = ?");
        $upProf->execute([$targetUserId]);

        log_activity($pdo, $adminId, 'VET_APPROVED', 'veterinarian', $targetUserId);
        $msg = 'Veterinarian has been approved and granted clinical practice access.';
    } elseif ($action === 'reject_vet') {
        // Reject veterinarian
        $upUser = $pdo->prepare("UPDATE users SET account_status = 'inactive', updated_at = NOW() WHERE id = ?");
        $upUser->execute([$targetUserId]);

        $upProf = $pdo->prepare("UPDATE veterinarian_profiles SET approval_status = 'rejected', updated_at = NOW() WHERE user_id = ?");
        $upProf->execute([$targetUserId]);

        log_activity($pdo, $adminId, 'VET_REJECTED', 'veterinarian', $targetUserId);
        $msg = 'Veterinarian application rejected.';
    } elseif ($action === 'toggle_status') {
        $newStatus = trim($_POST['status'] ?? 'active');
        if (!in_array($newStatus, ['active', 'inactive', 'suspended', 'pending'], true)) {
            $newStatus = 'active';
        }

        $upUser = $pdo->prepare("UPDATE users SET account_status = :status, updated_at = NOW() WHERE id = :id");
        $upUser->execute([':status' => $newStatus, ':id' => $targetUserId]);

        log_activity($pdo, $adminId, 'USER_STATUS_' . strtoupper($newStatus), 'user', $targetUserId);
        $msg = 'Account status updated to ' . ucfirst($newStatus) . '.';
    } else {
        $msg = 'No action performed.';
    }

    $pdo->commit();
    redirect($redirectUrl, $msg, 'success');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Vet approval action error: " . $e->getMessage());
    redirect($redirectUrl, 'Database error while updating account status.', 'danger');
}
