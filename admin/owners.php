<?php
/**
 * Administrator: Manage Animal Owners
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Manage Animal Owners';
$isDashboard = true;
$activePage = 'owners';
$user = current_user();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM animals a WHERE a.owner_id = u.id) AS animal_count,
           (SELECT COUNT(*) FROM appointments app WHERE app.owner_id = u.id) AS appointment_count
    FROM users u
    WHERE u.role = 'owner'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($statusFilter)) {
    $sql .= " AND u.account_status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$owners = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Animal Owners Directory</h5>
                    <small class="text-muted">Manage registered client accounts and access permissions</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search and Filter -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('admin/owners.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by name, email, or phone...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All Account Statuses</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if (!empty($search) || !empty($statusFilter)): ?>
                            <a href="<?= url('admin/owners.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Owners Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-people-fill text-primary me-2"></i> Registered Owners</span>
                    <span class="badge bg-primary-subtle text-primary"><?= count($owners) ?> Accounts</span>
                </div>

                <?php if (empty($owners)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                        <h5 class="fw-bold">No Animal Owners Found</h5>
                        <p class="text-muted small">No owners match the current search filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Owner Name</th>
                                    <th>Contact Information</th>
                                    <th>Animals Owned</th>
                                    <th>Appointments</th>
                                    <th>Status</th>
                                    <th>Joined On</th>
                                    <th class="text-end">Manage Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($owners as $ow): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($ow['full_name']) ?></div>
                                            <small class="text-muted font-monospace">UID: #<?= $ow['id'] ?></small>
                                        </td>
                                        <td>
                                            <div><i class="bi bi-envelope me-1 text-muted"></i> <?= e($ow['email']) ?></div>
                                            <small class="text-muted"><i class="bi bi-telephone me-1 text-muted"></i> <?= e($ow['phone']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2.5 py-1">
                                                <i class="bi bi-shield-heart me-1 text-primary"></i> <?= $ow['animal_count'] ?> Animals
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2.5 py-1">
                                                <i class="bi bi-calendar2 me-1 text-info"></i> <?= $ow['appointment_count'] ?> Visits
                                            </span>
                                        </td>
                                        <td><?= badge_status($ow['account_status']) ?></td>
                                        <td><small><?= format_date($ow['created_at']) ?></small></td>
                                        <td class="text-end">
                                            <form action="<?= url('actions/veterinarian_approval.php') ?>" method="POST" class="d-inline m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $ow['id'] ?>">
                                                <input type="hidden" name="redirect_url" value="admin/owners.php">
                                                
                                                <?php if ($ow['account_status'] === 'active'): ?>
                                                    <input type="hidden" name="status" value="suspended">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Suspend account" onclick="return confirm('Suspend this owner account?');">
                                                        <i class="bi bi-pause-circle me-1"></i> Suspend
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Activate account">
                                                        <i class="bi bi-play-circle me-1"></i> Activate
                                                    </button>
                                                <?php endif; ?>
                                            </form>
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
