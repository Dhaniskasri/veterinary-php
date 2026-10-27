<?php
/**
 * Administrator: Manage All Registered Animals
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Manage All Animals';
$isDashboard = true;
$activePage = 'animals';
$user = current_user();

$search = trim($_GET['search'] ?? '');
$speciesFilter = trim($_GET['species'] ?? '');

$sql = "
    SELECT a.*, u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone,
           (SELECT COUNT(*) FROM appointments app WHERE app.animal_id = a.id) AS appointment_count,
           (SELECT COUNT(*) FROM medical_records mr WHERE mr.animal_id = a.id) AS medical_count,
           (SELECT COUNT(*) FROM vaccinations v WHERE v.animal_id = a.id) AS vaccination_count
    FROM animals a
    JOIN users u ON u.id = a.owner_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (a.animal_name LIKE :search OR a.breed LIKE :search OR a.identification_number LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($speciesFilter)) {
    $sql .= " AND a.species = :species";
    $params[':species'] = $speciesFilter;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$animals = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">All Registered Animals</h5>
                    <small class="text-muted">Master database of companion pets, equine, and livestock</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search and Filter -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('admin/animals.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by animal name, breed, ID, or owner...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="species" onchange="this.form.submit()">
                            <option value="">All Species</option>
                            <?php foreach (ANIMAL_SPECIES as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $speciesFilter === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if (!empty($search) || !empty($speciesFilter)): ?>
                            <a href="<?= url('admin/animals.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Animals Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-collection-fill text-success me-2"></i> Animals on File</span>
                    <span class="badge bg-success-subtle text-success"><?= count($animals) ?> Animals</span>
                </div>

                <?php if (empty($animals)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-shield-x"></i></div>
                        <h5 class="fw-bold">No Animal Records Found</h5>
                        <p class="text-muted small">No animals match the specified filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Patient Animal</th>
                                    <th>Species & Breed</th>
                                    <th>Registered Owner</th>
                                    <th>Vitals (Age & Weight)</th>
                                    <th>Identification #</th>
                                    <th>Records Summary</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($animals as $a): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($a['animal_name']) ?></div>
                                            <span class="badge bg-light text-muted border small"><?= ucfirst($a['gender']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= e($a['species']) ?></div>
                                            <small class="text-muted"><?= e($a['breed'] ?: 'Mixed / Unknown') ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark"><?= e($a['owner_name']) ?></div>
                                            <small class="text-muted"><?= e($a['owner_phone']) ?></small>
                                        </td>
                                        <td>
                                            <div>Age: <?= $a['age_years'] !== null ? $a['age_years'] . ' yrs' : '—' ?></div>
                                            <small class="text-muted">Weight: <?= $a['weight'] ? $a['weight'] . ' kg' : '—' ?></small>
                                        </td>
                                        <td>
                                            <span class="font-monospace small"><?= e($a['identification_number'] ?: '—') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border small">
                                                <?= $a['appointment_count'] ?> visits &bull; <?= $a['medical_count'] ?> records &bull; <?= $a['vaccination_count'] ?> vacs
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('owner/animal_view.php?id=' . $a['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Patient Profile">
                                                <i class="bi bi-eye"></i> View
                                            </a>
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
