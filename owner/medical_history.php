<?php
/**
 * Owner Medical History & Clinical Records
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'Medical Records & Clinical History';
$isDashboard = true;
$activePage = 'medical_history';
$user = current_user();
$ownerId = (int)$user['id'];

$animalFilter = (int)($_GET['animal_id'] ?? 0);

// Get owner animals for dropdown
$animals = $pdo->prepare("SELECT id, animal_name, species FROM animals WHERE owner_id = ? ORDER BY animal_name ASC");
$animals->execute([$ownerId]);
$ownerAnimals = $animals->fetchAll();

$sql = "
    SELECT mr.*, a.animal_name, a.species, a.breed,
           u.full_name AS vet_name, vp.specialization,
           (SELECT COUNT(*) FROM prescriptions p WHERE p.medical_record_id = mr.id) AS rx_count
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users u ON u.id = mr.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE a.owner_id = :owner_id
";
$params = [':owner_id' => $ownerId];

if ($animalFilter > 0) {
    $sql .= " AND mr.animal_id = :animal_id";
    $params[':animal_id'] = $animalFilter;
}

$sql .= " ORDER BY mr.visit_date DESC, mr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Medical Records</h5>
                    <small class="text-muted">Clinical diagnoses, doctor treatment plans, and prescriptions</small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-primary rounded-pill px-3">
                    <i class="bi bi-calendar-plus me-1"></i> Book Consultation
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Animal Filter -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('owner/medical_history.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-4">
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
                            <a href="<?= url('owner/medical_history.php') ?>" class="btn btn-outline-secondary">Clear Filter</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($records)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <div class="empty-state-icon mx-auto mb-3"><i class="bi bi-clipboard2-pulse"></i></div>
                    <h5 class="fw-bold">No Medical Records on File</h5>
                    <p class="text-muted small">Clinical examination records will be published here after each completed veterinary visit.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($records as $r): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                                <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-3 gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary text-white p-3 rounded-4 shadow-sm">
                                            <i class="bi bi-heart-pulse fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><?= e($r['animal_name']) ?> &bull; <?= format_date($r['visit_date']) ?></h5>
                                            <small class="text-muted">
                                                Examined by <strong><?= e($r['vet_name']) ?></strong> (<?= e($r['specialization']) ?>)
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if ((int)$r['rx_count'] > 0): ?>
                                            <a href="<?= url('owner/prescription_view.php?record_id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="bi bi-prescription2 me-1"></i> View Prescription (<?= $r['rx_count'] ?>)
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= url('owner/animal_view.php?id=' . $r['animal_id']) ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                                            Full Animal Profile
                                        </a>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 h-100">
                                            <span class="text-uppercase text-muted fw-bold small d-block mb-1">Symptoms Presented</span>
                                            <p class="text-secondary small mb-0"><?= nl2br(e($r['symptoms'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-primary-subtle rounded-3 h-100 border border-primary-subtle">
                                            <span class="text-uppercase text-primary fw-bold small d-block mb-1">Doctor's Diagnosis</span>
                                            <p class="text-dark fw-semibold small mb-0"><?= nl2br(e($r['diagnosis'])) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <span class="text-uppercase text-muted fw-bold small d-block mb-1">Treatment Protocol & Recommendations</span>
                                    <div class="p-3 bg-light rounded-3 text-secondary small"><?= nl2br(e($r['treatment_plan'])) ?></div>
                                </div>

                                <?php if (!empty($r['clinical_notes'])): ?>
                                    <div class="mb-2">
                                        <span class="text-muted small fst-italic"><strong>Observations:</strong> <?= e($r['clinical_notes']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($r['follow_up_date'])): ?>
                                    <div class="mt-2 pt-2 border-top d-flex align-items-center gap-2 small text-secondary">
                                        <i class="bi bi-clock-history text-warning fs-5"></i>
                                        <span>Recommended Doctor Follow-Up: <strong><?= format_date($r['follow_up_date']) ?></strong></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
