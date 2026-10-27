<?php
/**
 * Owner Profile Settings
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'My Profile';
$isDashboard = true;
$activePage = 'profile';
$user = current_user();
$ownerId = (int)$user['id'];

// Fetch latest user details from DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$ownerId]);
$owner = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/owner_sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">My Profile</h5>
                    <small class="text-muted">Personal contact information & account security</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-850 mx-auto">
                <form action="<?= url('actions/profile_update.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_url" value="owner/profile.php">

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Personal Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($owner['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control bg-light" id="email" value="<?= e($owner['email']) ?>" readonly>
                            <small class="text-muted">Email address cannot be changed directly.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($owner['phone']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Status</label>
                            <div><?= badge_status($owner['account_status']) ?></div>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Change Password (Optional)</h5>
                    <p class="text-muted small">Leave password fields blank if you do not wish to change your current password.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Required only if changing password">
                        </div>
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 6 characters">
                        </div>
                        <div class="col-md-6">
                            <label for="new_password_confirm" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" placeholder="Confirm new password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
