<?php
/**
 * Printable Digital Prescription View
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_login();

$recordId = (int)($_GET['record_id'] ?? 0);
$user = current_user();
$userId = (int)$user['id'];
$userRole = $user['role'];

if ($recordId <= 0) {
    redirect('index.php', 'Invalid prescription record.', 'danger');
}

// Fetch record, doctor, animal, owner
$stmt = $pdo->prepare("
    SELECT mr.*, 
           a.animal_name, a.species, a.breed, a.age_years, a.weight, a.identification_number, a.owner_id,
           ow.full_name AS owner_name, ow.phone AS owner_phone, ow.email AS owner_email,
           doc.full_name AS doctor_name, doc.phone AS doctor_phone, doc.email AS doctor_email,
           vp.qualification, vp.specialization, vp.license_number
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users ow ON ow.id = a.owner_id
    JOIN users doc ON doc.id = mr.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = doc.id
    WHERE mr.id = ?
");
$stmt->execute([$recordId]);
$record = $stmt->fetch();

if (!$record) {
    redirect('index.php', 'Medical record not found.', 'danger');
}

// Authorization check: owner must own the animal, or vet, or admin
if ($userRole === ROLE_OWNER && (int)$record['owner_id'] !== $userId) {
    redirect('owner/dashboard.php', 'Unauthorized: You do not have permission to view this prescription.', 'danger');
}

// Fetch medicines
$rxStmt = $pdo->prepare("SELECT * FROM prescriptions WHERE medical_record_id = ? ORDER BY id ASC");
$rxStmt->execute([$recordId]);
$medicines = $rxStmt->fetchAll();

$pageTitle = 'Prescription - ' . $record['animal_name'];
$isDashboard = false;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4 my-3 max-w-850">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Go Back
        </a>
        <button onclick="window.print()" class="btn btn-primary px-4">
            <i class="bi bi-printer-fill me-1"></i> Print / Save as PDF
        </button>
    </div>

    <!-- Official Prescription Slip Card -->
    <div class="card border shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <!-- Clinic Header -->
        <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center justify-content-center text-white bg-primary rounded-4 p-3 shadow-sm" style="width: 58px; height: 58px;">
                    <i class="bi bi-hospital fs-2"></i>
                </span>
                <div>
                    <h4 class="fw-bold text-dark mb-0"><?= e(APP_NAME) ?></h4>
                    <div class="text-muted small"><?= e(CLINIC_ADDRESS) ?> &bull; Phone: <?= e(CLINIC_PHONE) ?></div>
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-primary text-white fs-6 px-3 py-1.5 rounded-pill font-monospace">Rx Slip #<?= str_pad($record['id'], 6, '0', STR_PAD_LEFT) ?></span>
                <div class="text-muted small mt-1">Date: <strong><?= format_date($record['visit_date']) ?></strong></div>
            </div>
        </div>

        <!-- Doctor & Patient Details Grid -->
        <div class="row g-4 mb-4 pb-3 border-bottom">
            <div class="col-6">
                <h6 class="text-uppercase text-muted fw-bold small letter-spacing-1 mb-2">Prescribing Clinician</h6>
                <h5 class="fw-bold text-dark mb-1"><?= e($record['doctor_name']) ?></h5>
                <div class="text-primary small fw-semibold"><?= e($record['qualification']) ?></div>
                <div class="text-muted small"><?= e($record['specialization']) ?></div>
                <div class="text-muted small">License No: <strong><?= e($record['license_number']) ?></strong></div>
            </div>

            <div class="col-6 text-end">
                <h6 class="text-uppercase text-muted fw-bold small letter-spacing-1 mb-2">Patient & Owner Info</h6>
                <h5 class="fw-bold text-dark mb-1"><?= e($record['animal_name']) ?> <span class="badge bg-light text-dark border small"><?= e($record['species']) ?></span></h5>
                <div class="text-muted small">Breed: <?= e($record['breed'] ?: 'Mixed') ?> &bull; Weight: <?= $record['weight'] ? $record['weight'] . ' kg' : '—' ?></div>
                <div class="text-muted small">Owner: <strong><?= e($record['owner_name']) ?></strong> (<?= e($record['owner_phone']) ?>)</div>
                <?php if (!empty($record['identification_number'])): ?>
                    <div class="font-monospace small text-muted">Tag/ID: <?= e($record['identification_number']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Clinical Diagnosis -->
        <div class="p-3 bg-light rounded-3 mb-4 border">
            <div class="row g-2">
                <div class="col-md-6">
                    <span class="text-muted small fw-bold d-block text-uppercase">Presenting Symptoms:</span>
                    <div class="small text-dark"><?= nl2br(e($record['symptoms'])) ?></div>
                </div>
                <div class="col-md-6">
                    <span class="text-primary small fw-bold d-block text-uppercase">Clinical Diagnosis:</span>
                    <div class="fw-bold text-dark small"><?= nl2br(e($record['diagnosis'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Medication Orders (Rx) -->
        <div class="mb-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="display-6 fw-bold text-primary" style="font-family: serif;">&#8478;</span>
                <h5 class="fw-bold text-dark mb-0">Medication Regimen</h5>
            </div>

            <?php if (empty($medicines)): ?>
                <div class="alert alert-secondary small text-center p-3">No prescription medications prescribed for this consultation.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered custom-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 30%;">Medicine Name</th>
                                <th style="width: 15%;">Dosage</th>
                                <th style="width: 20%;">Frequency</th>
                                <th style="width: 15%;">Duration</th>
                                <th style="width: 15%;">Instructions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $idx => $m): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                                    <td><strong class="text-dark"><?= e($m['medicine_name']) ?></strong></td>
                                    <td><?= e($m['dosage']) ?></td>
                                    <td><?= e($m['frequency']) ?></td>
                                    <td><?= e($m['duration']) ?></td>
                                    <td class="small text-secondary"><?= e($m['instructions'] ?: 'As directed') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Treatment Advice & Doctor Instructions -->
        <div class="mb-4">
            <h6 class="fw-bold text-dark mb-2">Doctor's Advice & Care Plan:</h6>
            <div class="p-3 border rounded-3 text-secondary small bg-white">
                <?= nl2br(e($record['treatment_plan'])) ?>
            </div>
        </div>

        <?php if (!empty($record['follow_up_date'])): ?>
            <div class="alert alert-info border-0 rounded-3 small d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-calendar2-check-fill fs-5"></i>
                <div>
                    Next Clinical Follow-up Scheduled: <strong><?= format_date($record['follow_up_date']) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <!-- Disclaimer & Signature -->
        <div class="row align-items-end pt-4 border-top mt-4">
            <div class="col-8">
                <p class="text-muted small mb-0" style="font-size: 0.775rem;">
                    * Notice: This prescription is issued by a licensed veterinarian. Do not alter dosages without direct consultation. Keep all medications out of reach of children and other animals.
                </p>
            </div>
            <div class="col-4 text-center">
                <div class="border-bottom border-dark pb-1 mb-1 font-monospace fw-bold text-primary">
                    <?= e($record['doctor_name']) ?>
                </div>
                <div class="text-muted small" style="font-size: 0.75rem;">Authorized Signature & Seal</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
