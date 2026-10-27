<?php
/**
 * Veterinarian Appointment Details & Status Control
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$appointmentId = (int)($_GET['id'] ?? 0);
$vetId = (int)current_user()['id'];

if ($appointmentId <= 0) {
    redirect('veterinarian/appointments.php', 'Invalid appointment ID.', 'danger');
}

$stmt = $pdo->prepare("
    SELECT app.*, 
           a.animal_name, a.species, a.breed, a.age_years, a.weight, a.identification_number, a.allergies, a.notes AS animal_notes,
           u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone,
           (SELECT id FROM medical_records mr WHERE mr.appointment_id = app.id LIMIT 1) AS medical_record_id
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users u ON u.id = app.owner_id
    WHERE app.id = ? AND app.veterinarian_id = ?
");
$stmt->execute([$appointmentId, $vetId]);
$app = $stmt->fetch();

if (!$app) {
    redirect('veterinarian/appointments.php', 'Appointment not found or you are not authorized to access this record.', 'danger');
}

$pageTitle = 'Manage Appointment #APT-' . str_pad($app['id'], 5, '0', STR_PAD_LEFT);
$isDashboard = true;
$activePage = 'appointments';

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
                    <h5 class="fw-bold mb-0 text-dark">Appointment #APT-<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></h5>
                    <small class="text-muted">Patient: <?= e($app['animal_name']) ?> (<?= e($app['species']) ?>)</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/appointments.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Appointments
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-900 mx-auto">
                <div class="d-flex flex-wrap justify-content-between align-items-start border-bottom pb-4 mb-4 gap-3">
                    <div>
                        <span class="text-muted small d-block">Scheduled Consultation</span>
                        <h3 class="fw-bold text-dark mb-1">
                            <?= format_date($app['appointment_date']) ?> at <?= format_time($app['appointment_time']) ?>
                        </h3>
                        <div class="text-muted small">Submitted by owner on <?= format_date($app['created_at'], 'M j, Y \a\t g:i A') ?></div>
                    </div>
                    <div>
                        <?= badge_status($app['appointment_status']) ?>
                    </div>
                </div>

                <!-- Patient & Owner Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light h-100 border">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-shield-heart me-1"></i> Patient Profile</h6>
                            <div class="mb-1"><strong>Name:</strong> <?= e($app['animal_name']) ?></div>
                            <div class="mb-1"><strong>Species & Breed:</strong> <?= e($app['species']) ?> &bull; <?= e($app['breed'] ?: 'Mixed') ?></div>
                            <div class="mb-1"><strong>Age & Weight:</strong> <?= $app['age_years'] ? $app['age_years'] . ' yrs' : '—' ?> &bull; <?= $app['weight'] ? $app['weight'] . ' kg' : '—' ?></div>
                            <?php if (!empty($app['identification_number'])): ?>
                                <div class="mb-1 font-monospace small"><strong>Tag/Chip ID:</strong> <?= e($app['identification_number']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($app['allergies'])): ?>
                                <div class="mt-2 text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Allergies:</strong> <?= e($app['allergies']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light h-100 border">
                            <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-person-circle me-1"></i> Owner Details</h6>
                            <div class="mb-1"><strong>Name:</strong> <?= e($app['owner_name']) ?></div>
                            <div class="mb-1"><strong>Phone:</strong> <a href="tel:<?= e($app['owner_phone']) ?>"><?= e($app['owner_phone']) ?></a></div>
                            <div class="mb-1"><strong>Email:</strong> <?= e($app['owner_email']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Clinical Presentation -->
                <div class="card border rounded-4 p-4 mb-4 bg-white">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Presenting Clinical Concern</h6>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Primary Reason:</span>
                        <div class="fw-bold text-dark"><?= e($app['reason']) ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Symptoms Reported by Owner:</span>
                        <div class="p-3 bg-light rounded-3 small text-secondary"><?= nl2br(e($app['symptoms'])) ?></div>
                    </div>
                </div>

                <!-- Doctor Notes & Status Controls Form -->
                <div class="card border rounded-4 p-4 mb-4 bg-light">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Clinical Status & Doctor Notes</h6>
                    
                    <form action="<?= url('actions/appointment_update.php') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                        <input type="hidden" name="redirect_url" value="veterinarian/appointment_view.php?id=<?= $app['id'] ?>">

                        <div class="mb-3">
                            <label for="status" class="form-label">Appointment Status</label>
                            <select class="form-select" id="status" name="status" <?= $app['appointment_status'] === 'completed' ? 'disabled' : '' ?>>
                                <option value="pending" <?= $app['appointment_status'] === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                                <option value="scheduled" <?= $app['appointment_status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled (Confirmed)</option>
                                <option value="completed" <?= $app['appointment_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $app['appointment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="rejected" <?= $app['appointment_status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="veterinarian_notes" class="form-label">Internal Doctor Notes / Pre-visit Preparation</label>
                            <textarea class="form-control" id="veterinarian_notes" name="veterinarian_notes" rows="3" placeholder="e.g. Instruct owner to fast dog prior to blood panel, bring fecal sample, etc."><?= e($app['veterinarian_notes']) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Update Status & Notes
                        </button>
                    </form>
                </div>

                <!-- Call to action: Record Consultation -->
                <?php if ($app['appointment_status'] === 'scheduled'): ?>
                    <div class="d-flex align-items-center justify-content-between p-4 bg-primary-subtle border border-primary-subtle rounded-4 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Ready for Consultation?</h5>
                            <p class="text-secondary small mb-0">Record physical examination vitals, differential diagnosis, and prescribe medications.</p>
                        </div>
                        <a href="<?= url('veterinarian/medical_record_add.php?animal_id=' . $app['animal_id'] . '&appointment_id=' . $app['id']) ?>" class="btn btn-primary btn-lg rounded-pill px-4">
                            <i class="bi bi-clipboard2-pulse-fill me-1"></i> Conduct Consultation
                        </a>
                    </div>
                <?php elseif (!empty($app['medical_record_id'])): ?>
                    <div class="d-flex align-items-center justify-content-between p-4 bg-success-subtle border border-success-subtle rounded-4 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Consultation Completed</h5>
                            <p class="text-secondary small mb-0">Medical record #MR-<?= $app['medical_record_id'] ?> on file.</p>
                        </div>
                        <a href="<?= url('owner/prescription_view.php?record_id=' . $app['medical_record_id']) ?>" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-prescription2 me-1"></i> View Prescription
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
