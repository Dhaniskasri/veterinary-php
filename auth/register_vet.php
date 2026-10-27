<?php
/**
 * Veterinarian Registration
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

if (is_logged_in()) {
    redirect('veterinarian/dashboard.php');
}

$pageTitle = 'Veterinarian Registration';
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$qualification = trim($_POST['qualification'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$licenseNumber = trim($_POST['license_number'] ?? '');
$experienceYears = (int)($_POST['experience_years'] ?? 1);
$consultationFee = (float)($_POST['consultation_fee'] ?? 40.00);
$bio = trim($_POST['bio'] ?? '');

$errors = [];
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (empty($phone)) $errors[] = 'Phone number is required.';
    if (empty($qualification)) $errors[] = 'Veterinary qualification is required (e.g., DVM, BVSc).';
    if (empty($specialization)) $errors[] = 'Clinical specialization is required.';
    if (empty($licenseNumber)) $errors[] = 'Medical license number is required.';
    if ($experienceYears < 0) $errors[] = 'Experience years cannot be negative.';
    if ($consultationFee < 0) $errors[] = 'Consultation fee cannot be negative.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check duplicate email
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $checkEmail->execute([':email' => $email]);
        if ($checkEmail->fetch()) {
            $errors[] = 'An account with this email address already exists.';
        }

        // Check duplicate license number
        $checkLic = $pdo->prepare("SELECT id FROM veterinarian_profiles WHERE license_number = :lic");
        $checkLic->execute([':lic' => $licenseNumber]);
        if ($checkLic->fetch()) {
            $errors[] = 'This veterinary license number is already registered in the system.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $userStmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, password_hash, role, account_status, created_at)
                    VALUES (:full_name, :email, :phone, :hash, 'veterinarian', 'pending', NOW())
                ");
                $userStmt->execute([
                    ':full_name' => substr($fullName, 0, 100),
                    ':email'     => substr($email, 0, 150),
                    ':phone'     => substr($phone, 0, 30),
                    ':hash'      => $hash
                ]);
                $newUserId = (int)$pdo->lastInsertId();

                $profileStmt = $pdo->prepare("
                    INSERT INTO veterinarian_profiles 
                    (user_id, qualification, specialization, license_number, experience_years, bio, consultation_fee, approval_status, created_at)
                    VALUES (:user_id, :qual, :spec, :lic, :exp, :bio, :fee, 'pending', NOW())
                ");
                $profileStmt->execute([
                    ':user_id' => $newUserId,
                    ':qual'    => substr($qualification, 0, 150),
                    ':spec'    => substr($specialization, 0, 150),
                    ':lic'     => substr($licenseNumber, 0, 100),
                    ':exp'     => $experienceYears,
                    ':bio'     => $bio,
                    ':fee'     => $consultationFee
                ]);

                log_activity($pdo, $newUserId, 'VET_REGISTERED', 'user', $newUserId);

                $pdo->commit();

                $successMessage = 'Your veterinarian registration has been submitted successfully! For safety and clinical integrity, an administrator will review your medical credentials and license before approving your account. You will then be able to log in.';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Vet registration error: " . $e->getMessage());
                $errors[] = 'Failed to register veterinarian due to a database error. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-info text-white p-3 rounded-4 shadow-sm mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-hospital fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">Join as Certified Veterinarian</h3>
                    <p class="text-muted small">Expand your clinical practice, manage consultations, and record patient outcomes</p>
                </div>

                <div class="card auth-card border-0">
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success border-0 shadow-sm p-4 text-center rounded-3">
                            <i class="bi bi-patch-check-fill text-success display-4 mb-2 d-block"></i>
                            <h4 class="fw-bold text-dark">Registration Submitted!</h4>
                            <p class="text-secondary mb-4"><?= e($successMessage) ?></p>
                            <a href="<?= url('auth/login.php') ?>" class="btn btn-primary px-4 rounded-pill">Return to Sign In</a>
                        </div>
                    <?php else: ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger shadow-sm border-0 small mb-4">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="<?= url('auth/register_vet.php') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>

                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Personal & Contact Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name & Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($fullName) ?>" placeholder="e.g. Dr. Sarah Jenkins" required autofocus>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Professional Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. dr.sarah@clinic.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($phone) ?>" placeholder="e.g. +1 555 0101" required>
                                </div>
                            </div>

                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Medical Credentials & Practice Details</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="qualification" class="form-label">Degrees / Qualification <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="qualification" name="qualification" value="<?= e($qualification) ?>" placeholder="e.g. DVM, MSc Veterinary Surgery" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="specialization" class="form-label">Primary Specialization <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="specialization" name="specialization" value="<?= e($specialization) ?>" placeholder="e.g. Canine & Feline Medicine, Livestock & Equine" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="license_number" class="form-label">License Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="license_number" name="license_number" value="<?= e($licenseNumber) ?>" placeholder="e.g. VET-2026-9901" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="experience_years" class="form-label">Experience (Years) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="experience_years" name="experience_years" value="<?= $experienceYears ?>" min="0" max="60" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="consultation_fee" class="form-label">Consultation Fee ($) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.50" class="form-control" id="consultation_fee" name="consultation_fee" value="<?= $consultationFee ?>" min="0" required>
                                </div>
                                <div class="col-12">
                                    <label for="bio" class="form-label">Professional Bio & Experience Summary</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Brief overview of clinical background, species focus, and approach to animal wellness..."><?= e($bio) ?></textarea>
                                </div>
                            </div>

                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Security & Password</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Create Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control border-end-0" id="password" name="password" minlength="6" required>
                                        <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="password" tabindex="-1">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control border-end-0" id="password_confirm" name="password_confirm" minlength="6" required>
                                        <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="password_confirm" tabindex="-1">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info small d-flex align-items-center gap-2 mb-4">
                                <i class="bi bi-info-circle-fill fs-5"></i>
                                <div>Admin verification notice: New veterinarian registrations are reviewed and verified before consultation booking permissions are activated.</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-shield-check me-1"></i> Submit Veterinarian Application
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        Already have an approved account? <a href="<?= url('auth/login.php') ?>" class="text-primary fw-semibold">Sign in here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
