<?php
/**
 * Manage Veterinarian Availability Schedule
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$pageTitle = 'Manage Availability';
$isDashboard = true;
$activePage = 'availability';
$user = current_user();
$vetId = (int)$user['id'];

// Fetch upcoming availability entries
$stmt = $pdo->prepare("
    SELECT * FROM veterinarian_availability 
    WHERE veterinarian_id = ? AND available_date >= CURDATE()
    ORDER BY available_date ASC
");
$stmt->execute([$vetId]);
$schedules = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Consultation Availability</h5>
                    <small class="text-muted">Define your clinical calendar, operating hours, and booking slot duration</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/appointments.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-calendar-check me-1"></i> View Appointments
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- Add / Update Availability Form -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-clock-history text-primary me-2"></i> Set Working Hours
                        </h5>

                        <form action="<?= url('actions/availability_save.php') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save">

                            <div class="mb-3">
                                <label for="available_date" class="form-label">Available Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="available_date" name="available_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">Please choose a valid date.</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" value="09:00" required>
                                </div>
                                <div class="col-6">
                                    <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" value="17:00" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="slot_duration_minutes" class="form-label">Consultation Slot Duration</label>
                                <select class="form-select" id="slot_duration_minutes" name="slot_duration_minutes">
                                    <option value="15">15 Minutes (Express checkup)</option>
                                    <option value="30" selected>30 Minutes (Standard consultation)</option>
                                    <option value="45">45 Minutes (Thorough / Livestock)</option>
                                    <option value="60">60 Minutes (Surgical / Comprehensive)</option>
                                </select>
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_available" name="is_available" value="1" checked>
                                <label class="form-check-label fw-medium" for="is_available">Available for Bookings</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-save me-1"></i> Save Working Schedule
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Upcoming Schedule Table -->
                <div class="col-lg-7">
                    <div class="custom-table-card">
                        <div class="card-header bg-white">
                            <span><i class="bi bi-calendar2-range text-primary me-2"></i> Upcoming Configured Schedule</span>
                            <span class="badge bg-primary-subtle text-primary"><?= count($schedules) ?> Dates</span>
                        </div>

                        <?php if (empty($schedules)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-calendar-minus"></i></div>
                                <h6 class="fw-bold">No Custom Schedules Configured</h6>
                                <p class="text-muted small">Standard operating hours (9:00 AM – 5:00 PM) will be used by default until you configure custom dates.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Working Hours</th>
                                            <th>Slot Duration</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules as $s): ?>
                                            <tr>
                                                <td><strong><?= format_date($s['available_date']) ?></strong></td>
                                                <td>
                                                    <span class="text-dark fw-medium">
                                                        <?= format_time($s['start_time']) ?> – <?= format_time($s['end_time']) ?>
                                                    </span>
                                                </td>
                                                <td><?= (int)$s['slot_duration_minutes'] ?> mins</td>
                                                <td>
                                                    <?php if ($s['is_available']): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Off / Blocked</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <form action="<?= url('actions/availability_save.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Remove schedule for this date?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="availability_id" value="<?= $s['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete entry">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
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
