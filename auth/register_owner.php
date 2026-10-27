<?php
/**
 * Animal Owner Registration
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

if (is_logged_in()) {
    redirect('owner/dashboard.php');
}

$pageTitle = 'Animal Owner Registration';
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($fullName)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check for duplicate email
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            $errors[] = 'An account with this email address already exists. Please sign in or use another email.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, password_hash, role, account_status, created_at)
                    VALUES (:full_name, :email, :phone, :hash, 'owner', 'active', NOW())
                ");
                $stmt->execute([
                    ':full_name' => substr($fullName, 0, 100),
                    ':email'     => substr($email, 0, 150),
                    ':phone'     => substr($phone, 0, 30),
                    ':hash'      => $hash
                ]);

                $newUserId = (int)$pdo->lastInsertId();
                log_activity($pdo, $newUserId, 'OWNER_REGISTERED', 'user', $newUserId);

                // Auto-login the new owner
                $newUser = [
                    'id'             => $newUserId,
                    'full_name'      => $fullName,
                    'email'          => $email,
                    'phone'          => $phone,
                    'role'           => ROLE_OWNER,
                    'account_status' => 'active'
                ];
                login_user($newUser);

                redirect('owner/dashboard.php', 'Your account has been created successfully! Welcome to VetCare.', 'success');
            } catch (PDOException $e) {
                error_log("Owner register error: " . $e->getMessage());
                $errors[] = 'Registration failed due to a database error. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-6">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white p-3 rounded-4 shadow-sm mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-person-heart fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">Register as Animal Owner</h3>
                    <p class="text-muted small">Manage companion pets, cattle herds, vaccinations & appointments</p>
                </div>

                <div class="card auth-card border-0">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm border-0 small mb-4">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('auth/register_owner.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($fullName) ?>" placeholder="e.g. Alice Morgan" required autofocus>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. alice@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($phone) ?>" placeholder="e.g. +1 555 0192" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Create Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="password" name="password" placeholder="Min. 6 chars" minlength="6" required>
                                    <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="password_confirm" name="password_confirm" placeholder="Confirm password" minlength="6" required>
                                    <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="password_confirm" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle-fill me-1"></i> Register Animal Owner Account
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        Already have an account? <a href="<?= url('auth/login.php') ?>" class="text-primary fw-semibold">Sign in here</a>
                        <br>
                        Are you a veterinary clinician? <a href="<?= url('auth/register_vet.php') ?>" class="text-success fw-semibold">Register as Veterinarian</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
