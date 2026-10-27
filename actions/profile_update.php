<?php
/**
 * Profile Update Action
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

$fullName = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
$redirectUrl = trim($_POST['redirect_url'] ?? 'owner/profile.php');

$errors = [];

if (empty($fullName)) {
    $errors[] = 'Full name is required.';
}
if (empty($phone)) {
    $errors[] = 'Phone number is required.';
}

// Password change validation
$updatePassword = false;
if (!empty($newPassword)) {
    if (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if ($newPassword !== $newPasswordConfirm) {
        $errors[] = 'New passwords do not match.';
    }

    // Verify current password
    $pwdStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $pwdStmt->execute([$userId]);
    $currHash = $pwdStmt->fetchColumn();

    if (!$currHash || !password_verify($currentPassword, $currHash)) {
        $errors[] = 'Your current password was entered incorrectly.';
    } else {
        $updatePassword = true;
    }
}

// Vet-specific fields
if ($userRole === ROLE_VET) {
    $qualification = trim($_POST['qualification'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $licenseNumber = trim($_POST['license_number'] ?? '');
    $experienceYears = (int)($_POST['experience_years'] ?? 0);
    $consultationFee = (float)($_POST['consultation_fee'] ?? 0.0);
    $bio = trim($_POST['bio'] ?? '');

    if (empty($qualification)) $errors[] = 'Qualification is required.';
    if (empty($specialization)) $errors[] = 'Specialization is required.';
    if (empty($licenseNumber)) $errors[] = 'License number is required.';
}

if (!empty($errors)) {
    redirect($redirectUrl, implode(' ', $errors), 'danger');
}

try {
    $pdo->beginTransaction();

    // Update users table
    if ($updatePassword) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $userUp = $pdo->prepare("
            UPDATE users SET full_name = :name, phone = :phone, password_hash = :hash, updated_at = NOW() WHERE id = :id
        ");
        $userUp->execute([
            ':name'  => substr($fullName, 0, 100),
            ':phone' => substr($phone, 0, 30),
            ':hash'  => $newHash,
            ':id'    => $userId
        ]);
    } else {
        $userUp = $pdo->prepare("
            UPDATE users SET full_name = :name, phone = :phone, updated_at = NOW() WHERE id = :id
        ");
        $userUp->execute([
            ':name'  => substr($fullName, 0, 100),
            ':phone' => substr($phone, 0, 30),
            ':id'    => $userId
        ]);
    }

    // If veterinarian, update profile
    if ($userRole === ROLE_VET) {
        $vetUp = $pdo->prepare("
            UPDATE veterinarian_profiles SET
                qualification = :qual,
                specialization = :spec,
                license_number = :lic,
                experience_years = :exp,
                consultation_fee = :fee,
                bio = :bio,
                updated_at = NOW()
            WHERE user_id = :uid
        ");
        $vetUp->execute([
            ':qual' => substr($qualification, 0, 150),
            ':spec' => substr($specialization, 0, 150),
            ':lic'  => substr($licenseNumber, 0, 100),
            ':exp'  => $experienceYears,
            ':fee'  => $consultationFee,
            ':bio'  => $bio,
            ':uid'  => $userId
        ]);
    }

    // Update session state
    $_SESSION['user']['full_name'] = $fullName;
    $_SESSION['user']['phone'] = $phone;

    log_activity($pdo, $userId, 'PROFILE_UPDATED', 'user', $userId);

    $pdo->commit();

    redirect($redirectUrl, 'Your profile details have been successfully updated.', 'success');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Profile update error: " . $e->getMessage());
    redirect($redirectUrl, 'Database error while saving profile updates.', 'danger');
}
