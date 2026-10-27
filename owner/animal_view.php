<?php
/**
 * Animal Detailed Health Profile
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$animalId = (int)($_GET['id'] ?? 0);
$ownerId = (int)current_user()['id'];

if ($animalId <= 0) {
    redirect('owner/animals.php', 'Invalid animal profile requested.', 'danger');
}

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM animals WHERE id = ? AND owner_id = ?");
$stmt->execute([$animalId, $ownerId]);
$animal = $stmt->fetch();

if (!$animal) {
    redirect('owner/animals.php', 'Animal not found or you do not have permission to view this record.', 'danger');
}

$pageTitle = $animal['animal_name'] . ' - Medical Profile';
$isDashboard = true;
$activePage = 'animals';

// 1. Fetch Medical Records for this animal
$medStmt = $pdo->prepare("
    SELECT mr.*, u.full_name AS vet_name, vp.specialization
    FROM medical_records mr
    JOIN users u ON u.id = mr.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE mr.animal_id = ?
    ORDER BY mr.visit_date DESC
");
$medStmt->execute([$animalId]);
$medicalRecords = $medStmt->fetchAll();

// 2. Fetch Prescriptions indexed by medical_record_id
$rxStmt = $pdo->prepare("
    SELECT p.*, p.medical_record_id 
    FROM prescriptions p
    JOIN medical_records mr ON mr.id = p.medical_record_id
    WHERE mr.animal_id = ?
");
$rxStmt->execute([$animalId]);
$allRx = $rxStmt->fetchAll();
$rxByRecord = [];
foreach ($allRx as $r) {
    $rxByRecord[$r['medical_record_id']][] = $r;
}

// 3. Fetch Vaccinations for this animal
$vacStmt = $pdo->prepare("
    SELECT v.*, u.full_name AS vet_name,
           DATEDIFF(v.next_due_date, CURDATE()) AS days_left
    FROM vaccinations v
    JOIN users u ON u.id = v.veterinarian_id
    WHERE v.animal_id = ?
    ORDER BY v.vaccination_date DESC
");
$vacStmt->execute([$animalId]);
$vaccinations = $vacStmt->fetchAll();

// 4. Fetch Appointments for this animal
$appStmt = $pdo->prepare("
    SELECT app.*, u.full_name AS vet_name, vp.specialization
    FROM appointments app
    JOIN users u ON u.id = app.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE app.animal_id = ?
    ORDER BY app.appointment_date DESC, app.appointment_time DESC
");
$appStmt->execute([$animalId]);
$appointments = $appStmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark"><?= e($animal['animal_name']) ?>'s Profile</h5>
                    <small class="text-muted">Species: <?= e($animal['species']) ?> &bull; Breed: <?= e($animal['breed'] ?: 'Mixed') ?></small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('owner/appointment_add.php?animal_id=' . $animalId) ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="bi bi-calendar-plus me-1"></i> Book Appointment
                </a>
                <a href="<?= url('owner/animal_edit.php?id=' . $animalId) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-pencil me-1"></i> Edit Profile
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Animal Identity Header Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="row align-items-center g-4">
                    <div class="col-md-3 col-lg-2 text-center">
                        <div class="rounded-4 overflow-hidden border shadow-sm mx-auto" style="width: 130px; height: 130px; background: #f8fafc;">
                            <?php if (!empty($animal['photo_path']) && file_exists(UPLOAD_DIR . DIRECTORY_SEPARATOR . $animal['photo_path'])): ?>
                                <img src="<?= e(UPLOAD_URL . '/' . $animal['photo_path']) ?>" alt="<?= e($animal['animal_name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <i class="bi <?= get_species_icon($animal['species']) ?> display-4 text-primary opacity-50"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-9 col-lg-10">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h3 class="fw-bold text-dark mb-1"><?= e($animal['animal_name']) ?></h3>
                                <div class="d-flex flex-wrap gap-2 align-items-center text-muted small">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($animal['species']) ?></span>
                                    <span>&bull;</span>
                                    <span><strong>Breed:</strong> <?= e($animal['breed'] ?: 'Mixed / Unknown') ?></span>
                                    <span>&bull;</span>
                                    <span><strong>Gender:</strong> <?= ucfirst($animal['gender']) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($animal['identification_number'])): ?>
                                <div class="badge bg-light text-dark border p-2 font-monospace small">
                                    <i class="bi bi-upc-scan text-primary me-1"></i> Tag/Chip: <?= e($animal['identification_number']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 pt-2 border-top mt-2">
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small d-block">Age</span>
                                <strong class="text-dark"><?= $animal['age_years'] !== null ? $animal['age_years'] . ' Years' : '—' ?></strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small d-block">Weight</span>
                                <strong class="text-dark"><?= $animal['weight'] !== null ? $animal['weight'] . ' kg' : '—' ?></strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small d-block">Color</span>
                                <strong class="text-dark"><?= e($animal['color'] ?: '—') ?></strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small d-block">Registered On</span>
                                <strong class="text-dark"><?= format_date($animal['created_at']) ?></strong>
                            </div>
                        </div>

                        <?php if (!empty($animal['allergies'])): ?>
                            <div class="mt-3 p-2 rounded bg-danger-subtle text-danger small border border-danger-subtle">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Allergies / Warnings:</strong> <?= e($animal['allergies']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabs: Medical Records, Vaccinations, Appointments -->
            <ul class="nav nav-pills mb-4 gap-2" id="animalTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-4 py-2 rounded-pill fw-semibold" id="records-tab" data-bs-toggle="pill" data-bs-target="#records" type="button" role="tab">
                        <i class="bi bi-journal-medical me-1"></i> Medical History (<?= count($medicalRecords) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 py-2 rounded-pill fw-semibold" id="vaccines-tab" data-bs-toggle="pill" data-bs-target="#vaccines" type="button" role="tab">
                        <i class="bi bi-eyedropper me-1"></i> Vaccinations (<?= count($vaccinations) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 py-2 rounded-pill fw-semibold" id="appointments-tab" data-bs-toggle="pill" data-bs-target="#appointments" type="button" role="tab">
                        <i class="bi bi-calendar2-week me-1"></i> Appointments (<?= count($appointments) ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="animalTabsContent">
                <!-- 1. Medical Records Tab -->
                <div class="tab-pane fade show active" id="records" role="tabpanel">
                    <?php if (empty($medicalRecords)): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                            <div class="empty-state-icon mx-auto mb-3"><i class="bi bi-clipboard2-pulse"></i></div>
                            <h5 class="fw-bold">No Medical Records Recorded</h5>
                            <p class="text-muted small">Once an authorized veterinarian examines this animal, clinical notes, diagnosis, and prescriptions will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($medicalRecords as $record): 
                            $rxList = $rxByRecord[$record['id']] ?? [];
                        ?>
                            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                                <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary text-white p-2 rounded-3">
                                            <i class="bi bi-calendar-event fs-5"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">Consultation: <?= format_date($record['visit_date']) ?></h5>
                                            <small class="text-muted">Attending Clinician: <strong><?= e($record['vet_name']) ?></strong> (<?= e($record['specialization']) ?>)</small>
                                        </div>
                                    </div>
                                    <?php if (!empty($rxList)): ?>
                                        <a href="<?= url('owner/prescription_view.php?record_id=' . $record['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-printer me-1"></i> Print Prescription
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <h6 class="text-dark fw-bold small text-uppercase letter-spacing-1 mb-1">Symptoms Reported:</h6>
                                        <p class="text-secondary small bg-light p-3 rounded-3"><?= nl2br(e($record['symptoms'])) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-dark fw-bold small text-uppercase letter-spacing-1 mb-1">Doctor's Diagnosis:</h6>
                                        <p class="text-dark fw-medium small bg-primary-subtle p-3 rounded-3 border border-primary-subtle"><?= nl2br(e($record['diagnosis'])) ?></p>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-dark fw-bold small text-uppercase letter-spacing-1 mb-1">Clinical Treatment Plan:</h6>
                                    <p class="text-secondary small bg-light p-3 rounded-3"><?= nl2br(e($record['treatment_plan'])) ?></p>
                                </div>

                                <?php if (!empty($record['clinical_notes'])): ?>
                                    <div class="mb-3">
                                        <h6 class="text-dark fw-bold small text-uppercase letter-spacing-1 mb-1">Clinical Observations & Vitals:</h6>
                                        <p class="text-muted small fst-italic"><?= nl2br(e($record['clinical_notes'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Prescriptions Table -->
                                <?php if (!empty($rxList)): ?>
                                    <div class="border rounded-3 p-3 bg-white mt-3">
                                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-capsule me-1"></i> Prescribed Medications:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless small mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Medicine Name</th>
                                                        <th>Dosage</th>
                                                        <th>Frequency</th>
                                                        <th>Duration</th>
                                                        <th>Administration Instructions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($rxList as $rx): ?>
                                                        <tr>
                                                            <td class="fw-bold text-dark"><?= e($rx['medicine_name']) ?></td>
                                                            <td><?= e($rx['dosage']) ?></td>
                                                            <td><?= e($rx['frequency']) ?></td>
                                                            <td><?= e($rx['duration']) ?></td>
                                                            <td><?= e($rx['instructions'] ?: 'As directed') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($record['follow_up_date'])): ?>
                                    <div class="mt-3 pt-3 border-top d-flex align-items-center gap-2 small text-secondary">
                                        <i class="bi bi-bell-fill text-warning fs-5"></i>
                                        <span>Next Recommended Follow-Up Visit: <strong><?= format_date($record['follow_up_date']) ?></strong></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- 2. Vaccinations Tab -->
                <div class="tab-pane fade" id="vaccines" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                        <?php if (empty($vaccinations)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-eyedropper"></i></div>
                                <h5 class="fw-bold">No Vaccinations Administered Yet</h5>
                                <p class="text-muted small">Immunization history will be tracked here once administered by a clinician.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Vaccine Name</th>
                                            <th>Date Administered</th>
                                            <th>Next Due Date</th>
                                            <th>Status</th>
                                            <th>Batch Number</th>
                                            <th>Administered By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vaccinations as $v): 
                                            $days = (int)$v['days_left'];
                                            $isOverdue = $days < 0;
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><?= e($v['vaccine_name']) ?></td>
                                                <td><?= format_date($v['vaccination_date']) ?></td>
                                                <td>
                                                    <strong><?= format_date($v['next_due_date']) ?></strong>
                                                </td>
                                                <td>
                                                    <?php if (empty($v['next_due_date'])): ?>
                                                        <span class="badge bg-light text-muted border">Completed</span>
                                                    <?php elseif ($isOverdue): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Overdue (<?= abs($days) ?>d)</span>
                                                    <?php elseif ($days <= 30): ?>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Due in <?= $days ?>d</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Protected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="font-monospace small text-muted"><?= e($v['batch_number'] ?: '—') ?></span></td>
                                                <td><?= e($v['vet_name']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Appointments Tab -->
                <div class="tab-pane fade" id="appointments" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                        <?php if (empty($appointments)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                                <h5 class="fw-bold">No Appointments on Record</h5>
                                <p class="text-muted small">You haven't booked any appointments for <?= e($animal['animal_name']) ?> yet.</p>
                                <a href="<?= url('owner/appointment_add.php?animal_id=' . $animalId) ?>" class="btn btn-sm btn-primary">Book Consultation</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Veterinarian</th>
                                            <th>Reason for Visit</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= format_date($app['appointment_date']) ?></div>
                                                    <small class="text-muted"><?= format_time($app['appointment_time']) ?></small>
                                                </td>
                                                <td>
                                                    <div><?= e($app['vet_name']) ?></div>
                                                    <small class="text-muted"><?= e($app['specialization']) ?></small>
                                                </td>
                                                <td><?= e($app['reason']) ?></td>
                                                <td><?= badge_status($app['appointment_status']) ?></td>
                                                <td class="text-end">
                                                    <a href="<?= url('owner/appointment_view.php?id=' . $app['id']) ?>" class="btn btn-sm btn-light border">
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
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
