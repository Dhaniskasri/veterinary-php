<?php
/**
 * Record Veterinary Consultation & Digital Prescriptions
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$pageTitle = 'Record Consultation';
$isDashboard = true;
$activePage = 'medical_record_add';
$user = current_user();
$vetId = (int)$user['id'];

// Optional pre-selected animal or appointment
$preAnimalId = (int)($_GET['animal_id'] ?? 0);
$preAppId = (int)($_GET['appointment_id'] ?? 0);

// Fetch all registered animals with owner names
$animals = $pdo->query("
    SELECT a.id, a.animal_name, a.species, a.breed, u.full_name AS owner_name
    FROM animals a
    JOIN users u ON u.id = a.owner_id
    ORDER BY a.animal_name ASC
")->fetchAll();

// If appointment was passed, pre-fetch symptoms & reason
$preSymptoms = '';
if ($preAppId > 0) {
    $appStmt = $pdo->prepare("SELECT symptoms, reason FROM appointments WHERE id = ?");
    $appStmt->execute([$preAppId]);
    $appInfo = $appStmt->fetch();
    if ($appInfo) {
        $preSymptoms = "Visit Reason: " . $appInfo['reason'] . "\nReported Symptoms: " . $appInfo['symptoms'];
    }
}

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
                    <h5 class="fw-bold mb-0 text-dark">Record Clinical Consultation</h5>
                    <small class="text-muted">Document patient vitals, diagnosis, treatment, and medications</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/dashboard.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-900 mx-auto">
                <form action="<?= url('actions/medical_record_save.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <?php if ($preAppId > 0): ?>
                        <input type="hidden" name="appointment_id" value="<?= $preAppId ?>">
                    <?php endif; ?>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Consultation Particulars</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="animal_id" class="form-label">Patient Animal <span class="text-danger">*</span></label>
                            <select class="form-select" id="animal_id" name="animal_id" required>
                                <option value="">Select Patient...</option>
                                <?php foreach ($animals as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $preAnimalId === (int)$a['id'] ? 'selected' : '' ?>>
                                        <?= e($a['animal_name']) ?> (<?= e($a['species']) ?> &bull; Owner: <?= e($a['owner_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a patient animal.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="visit_date" class="form-label">Visit / Consultation Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Clinical Evaluation & Diagnosis</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="symptoms" class="form-label">Presenting Complaints & History <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="symptoms" name="symptoms" rows="3" placeholder="Symptoms reported by owner, onset time, changes in appetite or gait..." required><?= e($preSymptoms) ?></textarea>
                            <div class="invalid-feedback">Please enter presenting symptoms.</div>
                        </div>

                        <div class="col-12">
                            <label for="clinical_notes" class="form-label">Physical Examination & Vitals</label>
                            <textarea class="form-control" id="clinical_notes" name="clinical_notes" rows="2" placeholder="e.g. Temp: 38.6°C, HR: 110 bpm, Mucous membranes: pink, CRT < 2s, Lung sounds: clear..."></textarea>
                        </div>

                        <div class="col-12">
                            <label for="diagnosis" class="form-label">Primary Diagnosis <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="diagnosis" name="diagnosis" placeholder="e.g. Acute Canine Gastroenteritis, Feline Upper Respiratory Infection, Bovine Mastitis" required>
                            <div class="invalid-feedback">Please provide a clinical diagnosis.</div>
                        </div>

                        <div class="col-12">
                            <label for="treatment_plan" class="form-label">Treatment Plan & Clinical Advice <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="treatment_plan" name="treatment_plan" rows="3" placeholder="Detail dietary modifications, wound management, rehydration, exercise restrictions, etc." required></textarea>
                            <div class="invalid-feedback">Please specify the treatment plan.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="follow_up_date" class="form-label">Scheduled Follow-up Date (Optional)</label>
                            <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" min="<?= date('Y-m-d') ?>">
                            <small class="text-muted">Will trigger proactive reminder alerts on owner and vet dashboards.</small>
                        </div>
                    </div>

                    <!-- 3. Dynamic Prescription Items -->
                    <div class="border rounded-4 p-4 mb-4 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-prescription2 text-primary me-2"></i> Prescribed Medications</h5>
                                <small class="text-muted">Add medications to generate an official digital prescription slip</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addPrescriptionRowBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add Another Medicine
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white" id="prescriptionTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Medicine Name</th>
                                        <th style="width: 15%;">Dosage</th>
                                        <th style="width: 15%;">Frequency</th>
                                        <th style="width: 15%;">Duration</th>
                                        <th style="width: 22%;">Instructions</th>
                                        <th style="width: 8%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="prescriptionTableBody">
                                    <tr>
                                        <td>
                                            <input type="text" name="prescriptions[0][medicine_name]" class="form-control form-control-sm" placeholder="e.g. Amoxicillin 250mg">
                                        </td>
                                        <td>
                                            <input type="text" name="prescriptions[0][dosage]" class="form-control form-control-sm" placeholder="e.g. 1 tablet">
                                        </td>
                                        <td>
                                            <input type="text" name="prescriptions[0][frequency]" class="form-control form-control-sm" placeholder="e.g. BID (twice daily)">
                                        </td>
                                        <td>
                                            <input type="text" name="prescriptions[0][duration]" class="form-control form-control-sm" placeholder="e.g. 5 days">
                                        </td>
                                        <td>
                                            <input type="text" name="prescriptions[0][instructions]" class="form-control form-control-sm" placeholder="e.g. Administer with meals">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-rx-row-btn" title="Remove row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('veterinarian/dashboard.php') ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Finalize Consultation & Issue Rx
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
