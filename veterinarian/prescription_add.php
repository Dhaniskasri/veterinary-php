<?php
/**
 * Add Prescription to Existing Medical Record
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role([ROLE_VET, ROLE_ADMIN]);

$recordId = (int)($_GET['record_id'] ?? 0);
$user = current_user();
$userId = (int)$user['id'];
$userRole = $user['role'];

if ($recordId <= 0) {
    redirect('veterinarian/medical_history.php', 'Invalid medical record.', 'danger');
}

$stmt = $pdo->prepare("
    SELECT mr.*, a.animal_name, a.species, u.full_name AS owner_name
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users u ON u.id = a.owner_id
    WHERE mr.id = ?
");
$stmt->execute([$recordId]);
$record = $stmt->fetch();

if (!$record) {
    redirect('veterinarian/medical_history.php', 'Record not found.', 'danger');
}

$pageTitle = 'Add Prescription Item';
$isDashboard = true;
$activePage = 'medical_history';

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
                    <h5 class="fw-bold mb-0 text-dark">Add Prescription Item</h5>
                    <small class="text-muted">Record #MR-<?= $recordId ?> &bull; Patient: <?= e($record['animal_name']) ?></small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/prescription_view.php?record_id=' . $recordId) ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-eye me-1"></i> View Prescription
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-700 mx-auto">
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <div class="fw-bold text-dark mb-1">Patient: <?= e($record['animal_name']) ?> (<?= e($record['species']) ?>)</div>
                    <div class="text-muted small">Consultation on <?= format_date($record['visit_date']) ?> &bull; Diagnosis: <strong><?= e($record['diagnosis']) ?></strong></div>
                </div>

                <form action="<?= url('actions/prescription_save.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="medical_record_id" value="<?= $recordId ?>">

                    <div class="mb-3">
                        <label for="medicine_name" class="form-label">Medicine / Pharmaceutical Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="medicine_name" name="medicine_name" placeholder="e.g. Meloxicam Oral Suspension 1.5mg/ml" required autofocus>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="dosage" class="form-label">Dosage <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dosage" name="dosage" placeholder="e.g. 0.5 ml per 10kg" required>
                        </div>
                        <div class="col-md-6">
                            <label for="frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="frequency" name="frequency" placeholder="e.g. Once daily with food" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Duration <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="duration" name="duration" placeholder="e.g. 7 days" required>
                        </div>
                        <div class="col-md-6">
                            <label for="instructions" class="form-label">Special Administration Instructions</label>
                            <input type="text" class="form-control" id="instructions" name="instructions" placeholder="e.g. Shake well before use">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('owner/prescription_view.php?record_id=' . $recordId) ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-plus-circle me-1"></i> Add Medication
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
