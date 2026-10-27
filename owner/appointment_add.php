<?php
/**
 * Book Veterinary Appointment
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'Book Veterinary Appointment';
$isDashboard = true;
$activePage = 'appointment_add';
$user = current_user();
$ownerId = (int)$user['id'];

// Preselected animal or vet from query parameters
$preAnimalId = (int)($_GET['animal_id'] ?? 0);
$preVetId = (int)($_GET['vet_id'] ?? 0);

// Fetch owner's animals
$animalStmt = $pdo->prepare("SELECT id, animal_name, species, breed FROM animals WHERE owner_id = ? ORDER BY animal_name ASC");
$animalStmt->execute([$ownerId]);
$animals = $animalStmt->fetchAll();

// Fetch approved veterinarians
$vetStmt = $pdo->query("
    SELECT u.id, u.full_name, vp.specialization, vp.qualification, vp.consultation_fee
    FROM users u
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE u.role = 'veterinarian' AND u.account_status = 'active' AND vp.approval_status = 'approved'
    ORDER BY u.full_name ASC
");
$veterinarians = $vetStmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Book an Appointment</h5>
                    <small class="text-muted">Choose your animal, veterinarian, and real-time available time slot</small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/appointments.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Appointments
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <?php if (empty($animals)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white max-w-700 mx-auto">
                    <div class="empty-state-icon mx-auto mb-3">
                        <i class="bi bi-shield-plus text-primary fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Please Register an Animal First</h4>
                    <p class="text-muted small mb-4">
                        You need to add at least one companion pet or livestock animal to your account before you can book an appointment.
                    </p>
                    <a href="<?= url('owner/animal_add.php') ?>" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-plus-circle me-1"></i> Register Animal Now
                    </a>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-900 mx-auto">
                    <form action="<?= url('actions/appointment_create.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>

                        <div class="row g-4 mb-4">
                            <!-- 1. Animal Selection -->
                            <div class="col-md-6">
                                <label for="animal_id" class="form-label">Select Registered Animal <span class="text-danger">*</span></label>
                                <select class="form-select" id="animal_id" name="animal_id" required>
                                    <option value="">Choose Animal...</option>
                                    <?php foreach ($animals as $a): ?>
                                        <option value="<?= $a['id'] ?>" <?= $preAnimalId === (int)$a['id'] ? 'selected' : '' ?>>
                                            <?= e($a['animal_name']) ?> (<?= e($a['species']) ?> - <?= e($a['breed'] ?: 'Mixed') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select an animal.</div>
                            </div>

                            <!-- 2. Veterinarian Selection -->
                            <div class="col-md-6">
                                <label for="veterinarian_id" class="form-label">Select Veterinarian <span class="text-danger">*</span></label>
                                <select class="form-select" id="veterinarian_id" name="veterinarian_id" required>
                                    <option value="">Choose Clinician...</option>
                                    <?php foreach ($veterinarians as $v): ?>
                                        <option value="<?= $v['id'] ?>" <?= $preVetId === (int)$v['id'] ? 'selected' : '' ?>>
                                            <?= e($v['full_name']) ?> &bull; <?= e($v['specialization']) ?> ($<?= number_format((float)$v['consultation_fee'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a veterinarian.</div>
                            </div>

                            <!-- 3. Date Selection -->
                            <div class="col-md-6">
                                <label for="appointment_date" class="form-label">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">Please pick a valid date.</div>
                            </div>

                            <!-- 4. Available Slots Selection Container (Dynamic) -->
                            <div class="col-12">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Available Consultation Slots <span class="text-danger">*</span></span>
                                    <span id="slotsLoading" class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
                                </label>
                                <div id="availableSlotsContainer" class="p-3 border rounded-3 bg-light">
                                    <div class="text-muted small text-center">Loading available appointment slots...</div>
                                </div>
                            </div>

                            <!-- 5. Reason for Visit -->
                            <div class="col-12">
                                <label for="reason" class="form-label">Reason for Visit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reason" name="reason" placeholder="e.g. Annual Checkup, Vaccination Booster, Limping, Skin Rash, Loss of Appetite" required>
                                <div class="invalid-feedback">Please enter the reason for consultation.</div>
                            </div>

                            <!-- 6. Symptoms Description -->
                            <div class="col-12">
                                <label for="symptoms" class="form-label">Observed Symptoms & Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="symptoms" name="symptoms" rows="4" placeholder="Describe behavior changes, duration of symptoms, eating habits, temperature if known, or any relevant history..." required></textarea>
                                <div class="invalid-feedback">Please describe the symptoms observed.</div>
                            </div>
                        </div>

                        <!-- Educational / Emergency Notice -->
                        <div class="alert alert-secondary small d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                            <div>Notice: In life-threatening emergencies, do not wait for an online appointment confirmation. Contact our emergency line directly at <strong><?= e(EMERGENCY_PHONE) ?></strong>.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="<?= url('owner/appointments.php') ?>" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-calendar-check-fill me-1"></i> Submit Appointment Request
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
