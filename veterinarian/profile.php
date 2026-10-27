<?php
/**
 * Veterinarian Professional Profile
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$pageTitle = 'Veterinarian Profile';
$isDashboard = true;
$activePage = 'profile';
$user = current_user();
$vetId = (int)$user['id'];

$stmt = $pdo->prepare("
    SELECT u.*, vp.* 
    FROM users u
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$vetId]);
$vet = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/vet_sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Professional Profile</h5>
                    <small class="text-muted">Clinical credentials, specialization, and consultation fees</small>
                </div>
            </div>
            <div>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                    <i class="bi bi-patch-check-fill me-1"></i> Status: <?= ucfirst($vet['approval_status']) ?>
                </span>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-900 mx-auto">
                <form action="<?= url('actions/profile_update.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_url" value="veterinarian/profile.php">

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Personal & Contact Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name & Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($vet['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control bg-light" id="email" value="<?= e($vet['email']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($vet['phone']) ?>" required>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Clinical Credentials & Practice Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="qualification" class="form-label">Medical Degrees / Qualifications <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qualification" name="qualification" value="<?= e($vet['qualification']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="specialization" class="form-label">Primary Specialization <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="specialization" name="specialization" value="<?= e($vet['specialization']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="license_number" class="form-label">Medical License Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="license_number" name="license_number" value="<?= e($vet['license_number']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="experience_years" class="form-label">Years of Experience <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="experience_years" name="experience_years" value="<?= (int)$vet['experience_years'] ?>" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="consultation_fee" class="form-label">Consultation Fee ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.50" class="form-control" id="consultation_fee" name="consultation_fee" value="<?= (float)$vet['consultation_fee'] ?>" min="0" required>
                        </div>
                        <div class="col-12">
                            <label for="bio" class="form-label">Professional Bio & Approach to Animal Care</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?= e($vet['bio']) ?></textarea>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Security & Password Update (Optional)</h5>
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
                            <i class="bi bi-check-circle-fill me-1"></i> Update Professional Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
