<?php
/**
 * Veterinarian Medical Records History
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$pageTitle = 'Authored Medical Records';
$isDashboard = true;
$activePage = 'medical_history';
$user = current_user();
$vetId = (int)$user['id'];

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT mr.*, a.animal_name, a.species, a.breed,
           u.full_name AS owner_name,
           (SELECT COUNT(*) FROM prescriptions p WHERE p.medical_record_id = mr.id) AS rx_count
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users u ON u.id = a.owner_id
    WHERE mr.veterinarian_id = :vet_id
";
$params = [':vet_id' => $vetId];

if (!empty($search)) {
    $sql .= " AND (a.animal_name LIKE :search OR mr.diagnosis LIKE :search OR mr.symptoms LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY mr.visit_date DESC, mr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Clinical Records History</h5>
                    <small class="text-muted">Consultation notes, diagnoses, and medical prescriptions issued by you</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/medical_record_add.php') ?>" class="btn btn-primary rounded-pill px-3">
                    <i class="bi bi-plus-circle me-1"></i> New Consultation Record
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('veterinarian/medical_history.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by patient, diagnosis, or owner...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= url('veterinarian/medical_history.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table of Records -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <?php if (empty($records)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                        <h5 class="fw-bold">No Clinical Records Found</h5>
                        <p class="text-muted small">You haven't recorded any medical consultations yet.</p>
                        <a href="<?= url('veterinarian/medical_record_add.php') ?>" class="btn btn-primary rounded-pill px-4">Create Consultation Record</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Visit Date</th>
                                    <th>Patient</th>
                                    <th>Owner</th>
                                    <th>Diagnosis</th>
                                    <th>Prescription</th>
                                    <th>Follow-up</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $r): ?>
                                    <tr>
                                        <td><strong><?= format_date($r['visit_date']) ?></strong></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($r['animal_name']) ?></div>
                                            <small class="text-muted"><?= e($r['species']) ?></small>
                                        </td>
                                        <td><?= e($r['owner_name']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 200px;"><?= e($r['diagnosis']) ?></div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 200px;"><?= e($r['symptoms']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ((int)$r['rx_count'] > 0): ?>
                                                <a href="<?= url('owner/prescription_view.php?record_id=' . $r['id']) ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-2 py-1">
                                                    <?= $r['rx_count'] ?> Meds
                                                </a>
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
                                            <div class="d-inline-flex gap-1">
                                                <a href="<?= url('owner/prescription_view.php?record_id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Prescription">
                                                    <i class="bi bi-prescription2"></i>
                                                </a>
                                                <a href="<?= url('veterinarian/prescription_add.php?record_id=' . $r['id']) ?>" class="btn btn-sm btn-light border" title="Add Medication">
                                                    <i class="bi bi-plus-lg"></i>
                                                </a>
                                                <a href="<?= url('veterinarian/medical_record_edit.php?id=' . $r['id']) ?>" class="btn btn-sm btn-light border" title="Edit Clinical Notes">
                                                    <i class="bi bi-pencil"></i>
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
