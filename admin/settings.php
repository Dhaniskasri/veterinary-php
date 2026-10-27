<?php
/**
 * Administrator: System Settings & Diagnostics
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Website & System Settings';
$isDashboard = true;
$activePage = 'settings';
$user = current_user();

// Diagnostics checks
$phpVersion = PHP_VERSION;
$pdoVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$uploadDirWritable = is_writable(UPLOAD_DIR);
$sessionSavePath = session_save_path() ?: sys_get_temp_dir();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Website & System Configuration</h5>
                    <small class="text-muted">Environment telemetry, clinic parameters, and system health</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- System Health & Telemetry -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-cpu-fill text-primary me-2"></i> Local Server Telemetry
                        </h5>

                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <span class="fw-semibold">PHP Version</span>
                                <span class="badge bg-success-subtle text-success font-monospace fs-6">v<?= e($phpVersion) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <span class="fw-semibold">MySQL Server Version</span>
                                <span class="badge bg-info-subtle text-info-emphasis font-monospace fs-6"><?= e($pdoVersion) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <span class="fw-semibold">Database Host & Port</span>
                                <span class="font-monospace text-muted"><?= DB_HOST ?>:<?= DB_PORT ?> (<?= DB_NAME ?>)</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <span class="fw-semibold">Uploads Folder Writable</span>
                                <?php if ($uploadDirWritable): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i> Writable</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i> Not Writable</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <span class="fw-semibold">CSRF Protection</span>
                                <span class="badge bg-success-subtle text-success">Active (HMAC / Cryptographic)</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <span class="fw-semibold">Session Security</span>
                                <span class="badge bg-success-subtle text-success">HttpOnly + SameSite=Lax</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <span class="fw-semibold">Dynamic Base URL</span>
                                <span class="font-monospace text-muted small"><?= e(BASE_URL) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Clinic Parameters Display -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-hospital text-info me-2"></i> Clinic Contact Parameters
                        </h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small mb-0">Platform Title</label>
                            <div class="fw-bold text-dark"><?= e(APP_NAME) ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small mb-0">Clinic Headquarters Address</label>
                            <div class="fw-medium text-dark"><?= e(CLINIC_ADDRESS) ?></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small mb-0">General Inquiries Phone</label>
                                <div class="fw-medium text-dark"><?= e(CLINIC_PHONE) ?></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-0">General Email</label>
                                <div class="fw-medium text-dark"><?= e(CLINIC_EMAIL) ?></div>
                            </div>
                        </div>

                        <div class="p-3 bg-danger-subtle rounded-3 text-danger border border-danger-subtle mb-3">
                            <div class="small fw-bold"><i class="bi bi-exclamation-octagon me-1"></i> 24/7 Emergency Line:</div>
                            <div class="fs-5 fw-bold"><?= e(EMERGENCY_PHONE) ?></div>
                        </div>

                        <small class="text-muted d-block">
                            * To modify clinic constants, edit <code>config/config.php</code> directly.
                        </small>
                    </div>
                </div>

                <!-- Production Readiness & Security Checklist -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-shield-lock-fill text-danger me-2"></i> Production Deployment & Hardening Checklist
                        </h5>
                        <p class="text-secondary small mb-3">
                            When deploying this application beyond a local XAMPP demonstration environment, the following actions must be performed:
                        </p>
                        <div class="row g-3 small">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <strong class="text-dark d-block mb-1"><i class="bi bi-shield-check text-success me-1"></i> 1. Enforce HTTPS & SSL/TLS:</strong>
                                    Ensure web server redirects all HTTP traffic to HTTPS. Secure cookies will automatically activate.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <strong class="text-dark d-block mb-1"><i class="bi bi-file-earmark-lock text-success me-1"></i> 2. Disable Setup Scripts:</strong>
                                    Remove or restrict <code>scripts/create_admin.php</code> to prevent unauthorized administrative accounts.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <strong class="text-dark d-block mb-1"><i class="bi bi-database-lock text-success me-1"></i> 3. Database Credentials:</strong>
                                    Change default MySQL root password, create a dedicated non-root MySQL user with minimum privileges, and set in <code>config/database.php</code>.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <strong class="text-dark d-block mb-1"><i class="bi bi-clock-history text-success me-1"></i> 4. Automated Database Backups:</strong>
                                    Schedule daily MySQL dumps using mysqldump cron jobs or cloud database backup routines.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
