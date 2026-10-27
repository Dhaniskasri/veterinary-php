<?php
/**
 * Veterinarian Patient Directory
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$pageTitle = 'Patient Directory';
$isDashboard = true;
$activePage = 'my_patients';
$user = current_user();
$vetId = (int)$user['id'];

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT DISTINCT a.*, u.full_name AS owner_name, u.phone AS owner_phone, u.email AS owner_email,
           (SELECT COUNT(*) FROM appointments app WHERE app.animal_id = a.id AND app.veterinarian_id = :vet_id1) AS total_visits,
           (SELECT MAX(visit_date) FROM medical_records mr WHERE mr.animal_id = a.id AND mr.veterinarian_id = :vet_id2) AS last_visit
    FROM animals a
    JOIN users u ON u.id = a.owner_id
    WHERE a.id IN (
        SELECT DISTINCT animal_id FROM appointments WHERE veterinarian_id = :vet_id3
        UNION
        SELECT DISTINCT animal_id FROM medical_records WHERE veterinarian_id = :vet_id4
    )
";
$params = [':vet_id1' => $vetId, ':vet_id2' => $vetId, ':vet_id3' => $vetId, ':vet_id4' => $vetId];

if (!empty($search)) {
    $sql .= " AND (a.animal_name LIKE :search OR a.breed LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY last_visit DESC, a.animal_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Patient Directory</h5>
                    <small class="text-muted">Animals under your clinical care and supervision</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/medical_record_add.php') ?>" class="btn btn-primary rounded-pill px-3">
                    <i class="bi bi-clipboard2-plus me-1"></i> New Consultation
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('veterinarian/my_patients.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by animal name, breed, or owner...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Search Patients</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= url('veterinarian/my_patients.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Patients Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <?php if (empty($patients)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-folder-x"></i></div>
                        <h5 class="fw-bold">No Patients Found</h5>
                        <p class="text-muted small">Patients will appear here once an appointment or clinical examination is scheduled with you.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Patient Animal</th>
                                    <th>Species & Breed</th>
                                    <th>Owner Contact</th>
                                    <th>Last Consultation</th>
                                    <th>Total Visits</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($p['animal_name']) ?></div>
                                            <span class="badge bg-light text-muted border small"><?= ucfirst($p['gender']) ?></span>
                                        </td>
                                        <td>
                                            <div><?= e($p['species']) ?></div>
                                            <small class="text-muted"><?= e($p['breed'] ?: 'Mixed') ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($p['owner_name']) ?></div>
                                            <small class="text-muted"><?= e($p['owner_phone']) ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-medium"><?= format_date($p['last_visit']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">
                                                <?= $p['total_visits'] ?> visits
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="<?= url('veterinarian/medical_record_add.php?animal_id=' . $p['id']) ?>" class="btn btn-sm btn-primary" title="Record Consultation">
                                                    <i class="bi bi-clipboard2-pulse"></i> Consult
                                                </a>
                                                <a href="<?= url('veterinarian/vaccination_add.php?animal_id=' . $p['id']) ?>" class="btn btn-sm btn-outline-info" title="Record Vaccine">
                                                    <i class="bi bi-eyedropper"></i>
                                                </a>
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
