<?php
/**
 * Owner Vaccination Records & Immunization Tracking
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'Vaccination Tracker & Immunizations';
$isDashboard = true;
$activePage = 'vaccinations';
$user = current_user();
$ownerId = (int)$user['id'];

$animalFilter = (int)($_GET['animal_id'] ?? 0);

// Get owner animals
$animals = $pdo->prepare("SELECT id, animal_name, species FROM animals WHERE owner_id = ? ORDER BY animal_name ASC");
$animals->execute([$ownerId]);
$ownerAnimals = $animals->fetchAll();

$sql = "
    SELECT v.*, a.animal_name, a.species, a.breed,
           u.full_name AS vet_name,
           DATEDIFF(v.next_due_date, CURDATE()) AS days_left
    FROM vaccinations v
    JOIN animals a ON a.id = v.animal_id
    JOIN users u ON u.id = v.veterinarian_id
    WHERE a.owner_id = :owner_id
";
$params = [':owner_id' => $ownerId];

if ($animalFilter > 0) {
    $sql .= " AND v.animal_id = :animal_id";
    $params[':animal_id'] = $animalFilter;
}

$sql .= " ORDER BY v.next_due_date ASC, v.vaccination_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vaccinations = $stmt->fetchAll();

// Counts
$overdueCount = 0;
$upcomingCount = 0;
foreach ($vaccinations as $v) {
    if ($v['next_due_date']) {
        $days = (int)$v['days_left'];
        if ($days < 0) $overdueCount++;
        elseif ($days <= 30) $upcomingCount++;
    }
}

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
                    <h5 class="fw-bold mb-0 text-dark">Vaccination Records</h5>
                    <small class="text-muted">Immunization schedules, booster due dates, and protection status</small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-primary rounded-pill px-3">
                    <i class="bi bi-calendar-plus me-1"></i> Book Vaccination
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Status Alert Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="p-3 bg-white border rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="p-3 bg-danger-subtle text-danger rounded-3 fs-4"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $overdueCount ?></h3>
                            <div class="text-muted small">Overdue Booster Vaccines</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="p-3 bg-white border rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 fs-4"><i class="bi bi-bell-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $upcomingCount ?></h3>
                            <div class="text-muted small">Due Within Next 30 Days</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-lg-4">
                    <div class="p-3 bg-white border rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="p-3 bg-success-subtle text-success rounded-3 fs-4"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= count($vaccinations) ?></h3>
                            <div class="text-muted small">Total Vaccines Administered</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('owner/vaccinations.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <select class="form-select" name="animal_id" onchange="this.form.submit()">
                            <option value="">All Animals</option>
                            <?php foreach ($ownerAnimals as $oa): ?>
                                <option value="<?= $oa['id'] ?>" <?= $animalFilter === (int)$oa['id'] ? 'selected' : '' ?>>
                                    <?= e($oa['animal_name']) ?> (<?= e($oa['species']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($animalFilter > 0): ?>
                        <div class="col-auto">
                            <a href="<?= url('owner/vaccinations.php') ?>" class="btn btn-outline-secondary">Reset Filter</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Vaccinations Table Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <?php if (empty($vaccinations)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-eyedropper"></i></div>
                        <h5 class="fw-bold">No Vaccination Records Found</h5>
                        <p class="text-muted small">Immunization records will appear here after a veterinary consultation.</p>
                        <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-primary rounded-pill px-4">Book Vaccination Visit</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Vaccine Name</th>
                                    <th>Date Administered</th>
                                    <th>Next Due Date</th>
                                    <th>Status</th>
                                    <th>Batch #</th>
                                    <th>Administering Vet</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vaccinations as $v): 
                                    $days = (int)$v['days_left'];
                                    $isOverdue = $v['next_due_date'] && $days < 0;
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('owner/animal_view.php?id=' . $v['animal_id']) ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= e($v['animal_name']) ?>
                                            </a>
                                            <small class="text-muted d-block"><?= e($v['species']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary"><?= e($v['vaccine_name']) ?></div>
                                        </td>
                                        <td><?= format_date($v['vaccination_date']) ?></td>
                                        <td>
                                            <strong><?= format_date($v['next_due_date']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if (empty($v['next_due_date'])): ?>
                                                <span class="badge bg-light text-muted border">Completed</span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    <i class="bi bi-exclamation-octagon me-1"></i> Overdue (<?= abs($days) ?>d)
                                                </span>
                                            <?php elseif ($days <= 30): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                    <i class="bi bi-bell me-1"></i> Due in <?= $days ?>d
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-shield-check me-1"></i> Up to Date
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="font-monospace small text-muted"><?= e($v['batch_number'] ?: '—') ?></span></td>
                                        <td><?= e($v['vet_name']) ?></td>
                                        <td class="small text-muted"><?= e($v['notes'] ?: '—') ?></td>
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
