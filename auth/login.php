<?php
/**
 * User Login Page
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    $dest = match(current_role()) {
        ROLE_OWNER => 'owner/dashboard.php',
        ROLE_VET   => 'veterinarian/dashboard.php',
        ROLE_ADMIN => 'admin/dashboard.php',
        default    => 'index.php'
    };
    redirect($dest);
}

$pageTitle = 'Sign In';
$email = trim($_POST['email'] ?? '');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = 'Please enter your email address.';
    }
    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        // Check rate limiting
        if (!check_login_throttle($email)) {
            $errors[] = 'Too many failed login attempts. For security reasons, please wait 5 minutes before trying again.';
        } else {
            $stmt = $pdo->prepare("
                SELECT u.*, vp.approval_status 
                FROM users u
                LEFT JOIN veterinarian_profiles vp ON vp.user_id = u.id
                WHERE u.email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if vet is pending approval
                if ($user['role'] === ROLE_VET && ($user['approval_status'] === 'pending' || $user['account_status'] === 'pending')) {
                    $errors[] = 'Your veterinarian account is currently pending administrative review. You will be able to log in once your medical credentials are approved.';
                } elseif ($user['role'] === ROLE_VET && $user['approval_status'] === 'rejected') {
                    $errors[] = 'Your veterinarian registration was rejected by the administration. Please contact the clinic for further details.';
                } elseif (in_array($user['account_status'], ['inactive', 'suspended'], true)) {
                    $errors[] = 'Your account is currently ' . htmlspecialchars($user['account_status']) . '. Please contact clinic support.';
                } else {
                    // Successful login
                    login_user($user);
                    log_activity($pdo, (int)$user['id'], 'USER_LOGIN', 'user', (int)$user['id']);

                    // Redirect to intended URL or role dashboard
                    $intended = $_SESSION['intended_url'] ?? null;
                    unset($_SESSION['intended_url']);

                    $target = $intended ?: match($user['role']) {
                        ROLE_OWNER => 'owner/dashboard.php',
                        ROLE_VET   => 'veterinarian/dashboard.php',
                        ROLE_ADMIN => 'admin/dashboard.php',
                        default    => 'index.php'
                    };

                    redirect($target, 'Welcome back, ' . e($user['full_name']) . '!', 'success');
                }
            } else {
                record_failed_login($email);
                $errors[] = 'Invalid email address or password. Please check your credentials.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white p-3 rounded-4 shadow-sm mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">Sign In to VetCare</h3>
                    <p class="text-muted small">Access your veterinary healthcare portal</p>
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

                    <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

                    <form action="<?= url('auth/login.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" class="form-control border-start-0" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. user@example.com" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="••••••••" required>
                                <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Account
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        Don't have an account?
                        <div class="mt-2 d-flex justify-content-center gap-2">
                            <a href="<?= url('auth/register_owner.php') ?>" class="text-primary fw-semibold">Register as Animal Owner</a>
                            <span>&bull;</span>
                            <a href="<?= url('auth/register_vet.php') ?>" class="text-primary fw-semibold">Register as Veterinarian</a>
                        </div>
                    </div>
                </div>

                <!-- Demonstration Credentials Card -->
                <div class="card border-0 shadow-sm rounded-4 p-3 mt-4 bg-white">
                    <div class="d-flex align-items-center gap-2 text-dark fw-bold small mb-2">
                        <i class="bi bi-info-circle-fill text-info"></i> Demonstration Test Accounts:
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless small mb-0">
                            <tr class="text-muted">
                                <th>Role</th>
                                <th>Email</th>
                                <th>Password</th>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle text-danger">Admin</span></td>
                                <td><code>admin@veterinary.com</code></td>
                                <td><code>Admin@123</code></td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info-subtle text-info-emphasis">Veterinarian</span></td>
                                <td><code>sarah.jenkins@veterinary.com</code></td>
                                <td><code>Vet@123</code></td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle text-success">Owner</span></td>
                                <td><code>alice@example.com</code></td>
                                <td><code>Owner@123</code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
