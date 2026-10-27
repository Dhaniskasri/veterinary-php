<?php
/**
 * Edit Consultation Record
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role([ROLE_VET, ROLE_ADMIN]);

$recordId = (int)($_GET['id'] ?? 0);
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

// Ownership check
if ($userRole === ROLE_VET && (int)$record['veterinarian_id'] !== $userId) {
    redirect('veterinarian/medical_history.php', 'Unauthorized: You can only edit records you authored.', 'danger');
}

$pageTitle = 'Edit Consultation Record #' . $recordId;
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
                    <h5 class="fw-bold mb-0 text-dark">Edit Record #MR-<?= $recordId ?></h5>
                    <small class="text-muted">Patient: <?= e($record['animal_name']) ?> (Owner: <?= e($record['owner_name']) ?>)</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/medical_history.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to History
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-850 mx-auto">
                <form action="<?= url('actions/medical_record_save.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="record_id" value="<?= $recordId ?>">
                    <input type="hidden" name="animal_id" value="<?= $record['animal_id'] ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Patient Animal</label>
                            <input type="text" class="form-control bg-light" value="<?= e($record['animal_name']) ?> (<?= e($record['species']) ?>)" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="visit_date" class="form-label">Visit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?= e($record['visit_date']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="symptoms" class="form-label">Presenting Symptoms <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="symptoms" name="symptoms" rows="3" required><?= e($record['symptoms']) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="clinical_notes" class="form-label">Clinical Examination & Observations</label>
                            <textarea class="form-control" id="clinical_notes" name="clinical_notes" rows="2"><?= e($record['clinical_notes']) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="diagnosis" class="form-label">Diagnosis <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="diagnosis" name="diagnosis" value="<?= e($record['diagnosis']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="treatment_plan" class="form-label">Treatment Plan & Clinical Advice <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="treatment_plan" name="treatment_plan" rows="3" required><?= e($record['treatment_plan']) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="follow_up_date" class="form-label">Scheduled Follow-up Date</label>
                            <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" value="<?= e($record['follow_up_date']) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('veterinarian/medical_history.php') ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Update Medical Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
