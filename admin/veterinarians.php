<?php
/**
 * Administrator: Manage Veterinarians & Approvals
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Manage Veterinarians';
$isDashboard = true;
$activePage = 'veterinarians';
$user = current_user();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT u.*, vp.qualification, vp.specialization, vp.license_number, vp.experience_years, vp.consultation_fee, vp.approval_status,
           (SELECT COUNT(*) FROM appointments app WHERE app.veterinarian_id = u.id) AS total_appointments,
           (SELECT COUNT(*) FROM medical_records mr WHERE mr.veterinarian_id = u.id) AS total_records
    FROM users u
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE u.role = 'veterinarian'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR vp.license_number LIKE :search OR vp.specialization LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($statusFilter)) {
    $sql .= " AND vp.approval_status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY vp.approval_status = 'pending' DESC, u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vets = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Veterinarians & Licensure Approvals</h5>
                    <small class="text-muted">Review credentials, grant practice permissions, and manage provider status</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search and Filter -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('admin/veterinarians.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by doctor name, license, specialization...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All Approval Statuses</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if (!empty($search) || !empty($statusFilter)): ?>
                            <a href="<?= url('admin/veterinarians.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Veterinarians Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-hospital text-info me-2"></i> Veterinary Practitioners</span>
                    <span class="badge bg-info-subtle text-info-emphasis"><?= count($vets) ?> Practitioners</span>
                </div>

                <?php if (empty($vets)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-person-x"></i></div>
                        <h5 class="fw-bold">No Veterinarians Found</h5>
                        <p class="text-muted small">No records match the requested filter.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Doctor Name</th>
                                    <th>Medical Credentials</th>
                                    <th>License & Fee</th>
                                    <th>Consultations</th>
                                    <th>Approval Status</th>
                                    <th>Account Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vets as $v): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($v['full_name']) ?></div>
                                            <small class="text-muted"><?= e($v['email']) ?> &bull; <?= e($v['phone']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= e($v['specialization']) ?></div>
                                            <small class="text-muted"><?= e($v['qualification']) ?> (<?= $v['experience_years'] ?> yrs)</small>
                                        </td>
                                        <td>
                                            <span class="font-monospace small d-block">Lic: <?= e($v['license_number']) ?></span>
                                            <strong class="text-dark">$<?= number_format((float)$v['consultation_fee'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= $v['total_appointments'] ?> visits &bull; <?= $v['total_records'] ?> records
                                            </span>
                                        </td>
                                        <td><?= badge_status($v['approval_status']) ?></td>
                                        <td><?= badge_status($v['account_status']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($v['approval_status'] === 'pending'): ?>
                                                    <form action="<?= url('actions/veterinarian_approval.php') ?>" method="POST" class="d-inline m-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="approve_vet">
                                                        <input type="hidden" name="user_id" value="<?= $v['id'] ?>">
                                                        <input type="hidden" name="redirect_url" value="admin/veterinarians.php">
                                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3" title="Approve">
                                                            <i class="bi bi-check-lg me-1"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form action="<?= url('actions/veterinarian_approval.php') ?>" method="POST" class="d-inline m-0" onsubmit="return confirm('Reject this veterinarian application?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="reject_vet">
                                                        <input type="hidden" name="user_id" value="<?= $v['id'] ?>">
                                                        <input type="hidden" name="redirect_url" value="admin/veterinarians.php">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Reject">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form action="<?= url('actions/veterinarian_approval.php') ?>" method="POST" class="d-inline m-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= $v['id'] ?>">
                                                        <input type="hidden" name="redirect_url" value="admin/veterinarians.php">
                                                        <?php if ($v['account_status'] === 'active'): ?>
                                                            <input type="hidden" name="status" value="inactive">
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Deactivate" onclick="return confirm('Deactivate this veterinarian account?');">
                                                                <i class="bi bi-slash-circle"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="status" value="active">
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                                                                <i class="bi bi-check-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
