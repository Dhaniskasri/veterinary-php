<?php
/**
 * Appointment Details View
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$appointmentId = (int)($_GET['id'] ?? 0);
$ownerId = (int)current_user()['id'];

if ($appointmentId <= 0) {
    redirect('owner/appointments.php', 'Invalid appointment ID.', 'danger');
}

$stmt = $pdo->prepare("
    SELECT app.*, 
           a.animal_name, a.species, a.breed, a.identification_number,
           u.full_name AS vet_name, u.email AS vet_email, u.phone AS vet_phone,
           vp.qualification, vp.specialization, vp.consultation_fee,
           (SELECT id FROM medical_records mr WHERE mr.appointment_id = app.id LIMIT 1) AS medical_record_id
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users u ON u.id = app.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE app.id = ? AND app.owner_id = ?
");
$stmt->execute([$appointmentId, $ownerId]);
$app = $stmt->fetch();

if (!$app) {
    redirect('owner/appointments.php', 'Appointment not found or you are not authorized to view it.', 'danger');
}

$pageTitle = 'Appointment #APT-' . str_pad($app['id'], 5, '0', STR_PAD_LEFT);
$isDashboard = true;
$activePage = 'appointments';

$canCancel = in_array($app['appointment_status'], ['pending', 'scheduled'], true);

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
                    <h5 class="fw-bold mb-0 text-dark">Appointment Details</h5>
                    <small class="text-muted">Ref: #APT-<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('owner/appointments.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Appointments
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-900 mx-auto">
                <div class="d-flex flex-wrap justify-content-between align-items-start border-bottom pb-4 mb-4 gap-3">
                    <div>
                        <span class="text-muted small d-block">Appointment Reference</span>
                        <h3 class="fw-bold text-dark mb-1">#APT-<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></h3>
                        <div class="text-muted small">Booked on <?= format_date($app['created_at'], 'M j, Y \a\t g:i A') ?></div>
                    </div>
                    <div class="text-end">
                        <span class="d-block mb-1"><?= badge_status($app['appointment_status']) ?></span>
                        <span class="text-muted small">Fee: <strong class="text-dark fs-6">$<?= number_format((float)$app['consultation_fee'], 2) ?></strong></span>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Animal Info -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light h-100 border">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-shield-heart me-1"></i> Patient Details</h6>
                            <div class="mb-2"><strong>Name:</strong> <?= e($app['animal_name']) ?></div>
                            <div class="mb-2"><strong>Species:</strong> <?= e($app['species']) ?></div>
                            <div class="mb-2"><strong>Breed:</strong> <?= e($app['breed'] ?: 'Mixed / Unknown') ?></div>
                            <?php if (!empty($app['identification_number'])): ?>
                                <div class="mb-2 font-monospace small"><strong>Chip / Tag:</strong> <?= e($app['identification_number']) ?></div>
                            <?php endif; ?>
                            <a href="<?= url('owner/animal_view.php?id=' . $app['animal_id']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                                View Animal Health History
                            </a>
                        </div>
                    </div>

                    <!-- Clinician Info -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light h-100 border">
                            <h6 class="fw-bold text-info-emphasis mb-3"><i class="bi bi-hospital me-1"></i> Attending Veterinarian</h6>
                            <div class="mb-2"><strong>Doctor:</strong> <?= e($app['vet_name']) ?></div>
                            <div class="mb-2"><strong>Specialization:</strong> <?= e($app['specialization']) ?></div>
                            <div class="mb-2"><strong>Qualification:</strong> <?= e($app['qualification']) ?></div>
                            <div class="mb-2 small text-muted"><strong>Contact:</strong> <?= e($app['vet_phone']) ?> &bull; <?= e($app['vet_email']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Schedule & Reason -->
                <div class="card border rounded-4 p-4 mb-4 bg-white">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Schedule & Clinical Reason</h6>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Scheduled Date</span>
                            <h5 class="fw-bold text-dark mb-0"><?= format_date($app['appointment_date']) ?></h5>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Scheduled Time Slot</span>
                            <h5 class="fw-bold text-dark mb-0"><?= format_time($app['appointment_time']) ?></h5>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Primary Reason for Visit</span>
                        <p class="fw-semibold text-dark mb-0"><?= e($app['reason']) ?></p>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Observed Symptoms / Case Notes</span>
                        <p class="text-secondary small bg-light p-3 rounded-3 mb-0"><?= nl2br(e($app['symptoms'])) ?></p>
                    </div>

                    <?php if (!empty($app['veterinarian_notes'])): ?>
                        <div class="p-3 rounded-3 bg-info-subtle border border-info-subtle mt-3">
                            <strong class="text-info-emphasis small d-block mb-1"><i class="bi bi-chat-quote-fill me-1"></i> Veterinarian Notes:</strong>
                            <div class="small text-dark"><?= nl2br(e($app['veterinarian_notes'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Related Medical Record Button if Completed -->
                <?php if (!empty($app['medical_record_id'])): ?>
                    <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-check-fill text-success me-2"></i> Consultation Completed</h5>
                            <p class="text-muted small mb-0">The veterinarian has recorded diagnosis, clinical treatment plan, and prescription.</p>
                        </div>
                        <a href="<?= url('owner/prescription_view.php?record_id=' . $app['medical_record_id']) ?>" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-prescription2 me-1"></i> View Prescription
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Cancellation Action -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="<?= url('owner/appointments.php') ?>" class="btn btn-light border px-4">
                        &larr; Back
                    </a>

                    <?php if ($canCancel): ?>
                        <form action="<?= url('actions/appointment_update.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="redirect_url" value="owner/appointments.php">
                            <button type="submit" class="btn btn-outline-danger px-4">
                                <i class="bi bi-x-circle me-1"></i> Cancel Appointment
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
