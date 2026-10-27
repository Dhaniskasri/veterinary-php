<?php
/**
 * Administrator: Master Vaccination Registry
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Master Vaccination Registry';
$isDashboard = true;
$activePage = 'vaccinations';
$user = current_user();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT v.*, 
           a.animal_name, a.species, a.breed,
           ow.full_name AS owner_name, ow.phone AS owner_phone,
           doc.full_name AS vet_name,
           DATEDIFF(v.next_due_date, CURDATE()) AS days_left
    FROM vaccinations v
    JOIN animals a ON a.id = v.animal_id
    JOIN users ow ON ow.id = a.owner_id
    JOIN users doc ON doc.id = v.veterinarian_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (a.animal_name LIKE :search OR v.vaccine_name LIKE :search OR v.batch_number LIKE :search OR ow.full_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter === 'overdue') {
    $sql .= " AND v.next_due_date IS NOT NULL AND v.next_due_date < CURDATE()";
} elseif ($statusFilter === 'upcoming') {
    $sql .= " AND v.next_due_date IS NOT NULL AND v.next_due_date >= CURDATE() AND v.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$sql .= " ORDER BY v.next_due_date ASC, v.vaccination_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vaccinations = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Master Vaccination Registry</h5>
                    <small class="text-muted">Immunization tracking, batch lots, and booster due monitoring</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search and Filter -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('admin/vaccinations.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by vaccine, animal, batch #, or owner...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All Vaccination Statuses</option>
                            <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue Boosters</option>
                            <option value="upcoming" <?= $statusFilter === 'upcoming' ? 'selected' : '' ?>>Due in Next 30 Days</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if (!empty($search) || !empty($statusFilter)): ?>
                            <a href="<?= url('admin/vaccinations.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-eyedropper text-primary me-2"></i> Immunization Entries</span>
                    <span class="badge bg-primary-subtle text-primary"><?= count($vaccinations) ?> Records</span>
                </div>

                <?php if (empty($vaccinations)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-eyedropper"></i></div>
                        <h5 class="fw-bold">No Vaccination Records Found</h5>
                        <p class="text-muted small">No entries match the filter criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Patient Animal</th>
                                    <th>Owner</th>
                                    <th>Vaccine Name</th>
                                    <th>Administered On</th>
                                    <th>Next Due Date</th>
                                    <th>Protection Status</th>
                                    <th>Lot / Batch #</th>
                                    <th>Administering Vet</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vaccinations as $v): 
                                    $days = (int)$v['days_left'];
                                    $isOverdue = $v['next_due_date'] && $days < 0;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($v['animal_name']) ?></div>
                                            <small class="text-muted"><?= e($v['species']) ?></small>
                                        </td>
                                        <td>
                                            <div><?= e($v['owner_name']) ?></div>
                                            <small class="text-muted"><?= e($v['owner_phone']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary"><?= e($v['vaccine_name']) ?></div>
                                        </td>
                                        <td><?= format_date($v['vaccination_date']) ?></td>
                                        <td><strong><?= format_date($v['next_due_date']) ?></strong></td>
                                        <td>
                                            <?php if (empty($v['next_due_date'])): ?>
                                                <span class="badge bg-light text-muted border">Completed</span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    Overdue (<?= abs($days) ?>d)
                                                </span>
                                            <?php elseif ($days <= 30): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                    Due in <?= $days ?>d
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    Up to Date
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="font-monospace small"><?= e($v['batch_number'] ?: '—') ?></span></td>
                                        <td><?= e($v['vet_name']) ?></td>
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
