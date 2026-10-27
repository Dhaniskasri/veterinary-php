<?php
/**
 * Administrator Initial Setup Script
 * Veterinary Care for Animal Medical Solution
 *
 * Can be executed via CLI:
 *   php scripts/create_admin.php
 * Or accessed via Browser in local XAMPP:
 *   http://localhost/veterinary-care/scripts/create_admin.php
 */

require_once __DIR__ . '/../includes/init.php';

$isCli = (PHP_SAPI === 'cli');

// Check existing administrators
$stmt = $pdo->query("SELECT id, full_name, email, created_at FROM users WHERE role = 'admin'");
$existingAdmins = $stmt->fetchAll();
$adminCount = count($existingAdmins);

// CLI Mode
if ($isCli) {
    echo "========================================================\n";
    echo " Veterinary Care - Administrator Setup Utility\n";
    echo "========================================================\n\n";

    if ($adminCount > 0) {
        echo "[!] Notice: Currently there are {$adminCount} administrator(s) registered:\n";
        foreach ($existingAdmins as $a) {
            echo "    - ID {$a['id']}: {$a['full_name']} ({$a['email']})\n";
        }
        echo "\nDo you wish to create an additional administrator? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        if (strtolower($line) !== 'y') {
            echo "Aborted setup.\n";
            exit(0);
        }
    }

    echo "Enter Administrator Full Name [System Administrator]: ";
    $name = trim(fgets(STDIN));
    if (empty($name)) $name = 'System Administrator';

    echo "Enter Administrator Email [admin@veterinary.com]: ";
    $email = trim(fgets(STDIN));
    if (empty($email)) $email = 'admin@veterinary.com';

    echo "Enter Administrator Phone [+1-555-0100]: ";
    $phone = trim(fgets(STDIN));
    if (empty($phone)) $phone = '+1-555-0100';

    echo "Enter Secure Password (min 6 characters): ";
    $password = trim(fgets(STDIN));
    if (strlen($password) < 6) {
        echo "[ERROR] Password must be at least 6 characters long.\n";
        exit(1);
    }

    // Check duplicate email
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo "[ERROR] An account with email '{$email}' already exists.\n";
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("
        INSERT INTO users (full_name, email, phone, password_hash, role, account_status, created_at)
        VALUES (?, ?, ?, ?, 'admin', 'active', NOW())
    ");
    $ins->execute([$name, $email, $phone, $hash]);

    echo "\n[SUCCESS] Administrator created successfully!\n";
    echo "Login Email: {$email}\n";
    echo "Remember to store your password in a secure password manager.\n";
    echo "========================================================\n";
    exit(0);
}

// Browser Web Mode
$pageTitle = 'Administrator Initial Setup';
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (empty($phone)) $phone = '+1-555-0100';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = "An account with the email '{$email}' already exists.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, password_hash, role, account_status, created_at)
                    VALUES (?, ?, ?, ?, 'admin', 'active', NOW())
                ");
                $ins->execute([$name, $email, $phone, $hash]);
                $newId = (int)$pdo->lastInsertId();
                log_activity($pdo, $newId, 'ADMIN_SETUP_CREATED', 'user', $newId);

                $success = "Administrator account '{$email}' created successfully! You may now sign in to access the Admin Dashboard.";
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger text-white p-3 rounded-4 shadow-sm mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">Administrator Setup Utility</h3>
                    <p class="text-muted small">Initialize and manage system administrator accounts securely</p>
                </div>

                <div class="card auth-card border-0">
                    <?php if ($adminCount > 0 && !$success): ?>
                        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                                <div>
                                    <strong class="d-block text-dark">Existing Administrator Found:</strong>
                                    <div class="small text-secondary mb-1">
                                        The database currently has <strong><?= $adminCount ?></strong> registered administrator account(s):
                                    </div>
                                    <ul class="mb-2 ps-3 small text-secondary">
                                        <?php foreach ($existingAdmins as $a): ?>
                                            <li><?= e($a['full_name']) ?> (<code><?= e($a['email']) ?></code>) - Created <?= format_date($a['created_at']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="small text-muted">
                                        Default seeded administrator login: <code>admin@veterinary.com</code> / <code>Admin@123</code>.
                                        You can sign in now or use the form below to create a new dedicated admin account.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 shadow-sm p-4 text-center rounded-3 mb-4">
                            <i class="bi bi-check-circle-fill text-success display-4 mb-2 d-block"></i>
                            <h4 class="fw-bold text-dark">Administrator Initialized!</h4>
                            <p class="text-secondary mb-4"><?= e($success) ?></p>
                            <div class="alert alert-warning small text-start mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Security Recommendation:</strong> For production environments, delete or restrict access to <code>scripts/create_admin.php</code> to prevent unauthorized administrative accounts.
                            </div>
                            <a href="<?= url('auth/login.php') ?>" class="btn btn-primary px-4 rounded-pill">Proceed to Sign In</a>
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

                        <form action="<?= url('scripts/create_admin.php') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Administrator Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? 'System Administrator') ?>" required autofocus>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Administrator Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= e($_POST['email'] ?? 'admin@veterinary.com') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '+1-555-0100') ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Admin Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control border-end-0" id="password" name="password" minlength="6" placeholder="Min. 6 chars" required>
                                        <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="password" tabindex="-1">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control border-end-0" id="password_confirm" name="password_confirm" minlength="6" placeholder="Confirm password" required>
                                        <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="password_confirm" tabindex="-1">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="bi bi-shield-plus me-1"></i> Create Administrator Account
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        <a href="<?= url('auth/login.php') ?>" class="text-primary fw-semibold">&larr; Return to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
