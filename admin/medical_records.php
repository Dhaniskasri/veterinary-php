<?php
/**
 * Administrator: Master Medical Records Oversight
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Master Clinical Records';
$isDashboard = true;
$activePage = 'medical_records';
$user = current_user();

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT mr.*, 
           a.animal_name, a.species, a.breed,
           ow.full_name AS owner_name, ow.phone AS owner_phone,
           doc.full_name AS vet_name, vp.specialization,
           (SELECT COUNT(*) FROM prescriptions p WHERE p.medical_record_id = mr.id) AS rx_count
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users ow ON ow.id = a.owner_id
    JOIN users doc ON doc.id = mr.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = doc.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (a.animal_name LIKE :search OR mr.diagnosis LIKE :search OR doc.full_name LIKE :search OR ow.full_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY mr.visit_date DESC, mr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Medical Records Oversight</h5>
                    <small class="text-muted">Master clinical examination records and digital prescriptions</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('admin/medical_records.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by patient, diagnosis, vet, or owner...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Search Records</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= url('admin/medical_records.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-journal-medical text-primary me-2"></i> Clinical Records</span>
                    <span class="badge bg-primary-subtle text-primary"><?= count($records) ?> Records</span>
                </div>

                <?php if (empty($records)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-journal-x"></i></div>
                        <h5 class="fw-bold">No Medical Records Found</h5>
                        <p class="text-muted small">No clinical consultation logs match the search query.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Ref #</th>
                                    <th>Visit Date</th>
                                    <th>Patient</th>
                                    <th>Owner</th>
                                    <th>Attending Vet</th>
                                    <th>Diagnosis</th>
                                    <th>Prescriptions</th>
                                    <th>Follow-up</th>
                                    <th class="text-end">Prescription Slip</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $r): ?>
                                    <tr>
                                        <td><span class="font-monospace text-muted small">#MR-<?= $r['id'] ?></span></td>
                                        <td><strong><?= format_date($r['visit_date']) ?></strong></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($r['animal_name']) ?></div>
                                            <small class="text-muted"><?= e($r['species']) ?></small>
                                        </td>
                                        <td><?= e($r['owner_name']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= e($r['vet_name']) ?></div>
                                            <small class="text-muted"><?= e($r['specialization']) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-medium text-truncate" style="max-width: 200px;"><?= e($r['diagnosis']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ((int)$r['rx_count'] > 0): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                    <?= $r['rx_count'] ?> Meds
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($r['follow_up_date'])): ?>
                                                <span class="badge bg-light text-dark border"><?= format_date($r['follow_up_date']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('owner/prescription_view.php?record_id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="View Printable Rx">
                                                <i class="bi bi-prescription2 me-1"></i> Rx Slip
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
