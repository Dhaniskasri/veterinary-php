<?php
/**
 * Veterinarian Dashboard
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$pageTitle = 'Veterinarian Dashboard';
$isDashboard = true;
$activePage = 'dashboard';
$user = current_user();
$vetId = (int)$user['id'];

// 1. Database-driven counts
// Today's appointments
$todayStmt = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE veterinarian_id = ? AND appointment_date = CURDATE() AND appointment_status IN ('scheduled', 'pending')
");
$todayStmt->execute([$vetId]);
$todayAppointmentsCount = (int)$todayStmt->fetchColumn();

// Pending requests
$pendingStmt = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE veterinarian_id = ? AND appointment_status = 'pending'
");
$pendingStmt->execute([$vetId]);
$pendingRequestsCount = (int)$pendingStmt->fetchColumn();

// Completed consultations
$completedStmt = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE veterinarian_id = ? AND appointment_status = 'completed'
");
$completedStmt->execute([$vetId]);
$completedConsultationsCount = (int)$completedStmt->fetchColumn();

// Unique patients treated
$patientsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT animal_id) FROM medical_records WHERE veterinarian_id = ?
");
$patientsStmt->execute([$vetId]);
$patientsTreatedCount = (int)$patientsStmt->fetchColumn();

// 2. Fetch today's appointments
$todayListStmt = $pdo->prepare("
    SELECT app.*, a.animal_name, a.species, a.breed, a.weight,
           u.full_name AS owner_name, u.phone AS owner_phone
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users u ON u.id = app.owner_id
    WHERE app.veterinarian_id = ? AND app.appointment_date = CURDATE()
    ORDER BY app.appointment_time ASC
");
$todayListStmt->execute([$vetId]);
$todayAppointments = $todayListStmt->fetchAll();

// 3. Fetch pending appointment requests (next upcoming)
$pendingListStmt = $pdo->prepare("
    SELECT app.*, a.animal_name, a.species,
           u.full_name AS owner_name, u.phone AS owner_phone
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users u ON u.id = app.owner_id
    WHERE app.veterinarian_id = ? AND app.appointment_status = 'pending' AND app.appointment_date >= CURDATE()
    ORDER BY app.appointment_date ASC, app.appointment_time ASC
    LIMIT 5
");
$pendingListStmt->execute([$vetId]);
$pendingRequests = $pendingListStmt->fetchAll();

// 4. Upcoming follow-up patients
$followupsStmt = $pdo->prepare("
    SELECT mr.*, a.animal_name, a.species, u.full_name AS owner_name, u.phone AS owner_phone,
           DATEDIFF(mr.follow_up_date, CURDATE()) AS days_left
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users u ON u.id = a.owner_id
    WHERE mr.veterinarian_id = ? 
      AND mr.follow_up_date IS NOT NULL 
      AND mr.follow_up_date >= CURDATE()
    ORDER BY mr.follow_up_date ASC
    LIMIT 4
");
$followupsStmt->execute([$vetId]);
$upcomingFollowups = $followupsStmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark"><?= e($user['full_name']) ?></h5>
                    <small class="text-muted">Clinical Practice & Patient Care Management</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('veterinarian/medical_record_add.php') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="bi bi-clipboard2-plus me-1"></i> New Consultation Record
                </a>
                <a href="<?= url('veterinarian/vaccination_add.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-eyedropper me-1"></i> Record Vaccine
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Metrics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">
                            <i class="bi bi-calendar2-day-fill"></i>
                        </div>
                        <div class="stat-value"><?= $todayAppointmentsCount ?></div>
                        <div class="stat-label">Today's Visits Scheduled</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-amber">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-value"><?= $pendingRequestsCount ?></div>
                        <div class="stat-label">Pending Booking Requests</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-emerald">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <div class="stat-value"><?= $completedConsultationsCount ?></div>
                        <div class="stat-label">Consultations Completed</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-teal">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </div>
                        <div class="stat-value"><?= $patientsTreatedCount ?></div>
                        <div class="stat-label">Unique Patients Treated</div>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule Table -->
            <div class="custom-table-card mb-4">
                <div class="card-header bg-white">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check text-primary"></i>
                        <span>Today's Appointment Schedule (<?= date('F j, Y') ?>)</span>
                    </span>
                    <span class="badge bg-primary rounded-pill"><?= count($todayAppointments) ?> Patients Today</span>
                </div>

                <?php if (empty($todayAppointments)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-cup-hot"></i></div>
                        <h6 class="fw-bold">No Consultations Scheduled Today</h6>
                        <p class="text-muted small">You have no patient visits on your schedule for today.</p>
                        <a href="<?= url('veterinarian/availability.php') ?>" class="btn btn-sm btn-outline-primary">Check Availability Settings</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Slot Time</th>
                                    <th>Patient Animal</th>
                                    <th>Owner</th>
                                    <th>Reason / Symptoms</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayAppointments as $app): ?>
                                    <tr>
                                        <td><strong class="text-primary fs-6"><?= format_time($app['appointment_time']) ?></strong></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($app['animal_name']) ?></div>
                                            <small class="text-muted"><?= e($app['species']) ?> &bull; <?= e($app['breed'] ?: 'Mixed') ?></small>
                                        </td>
                                        <td>
                                            <div><?= e($app['owner_name']) ?></div>
                                            <small class="text-muted"><?= e($app['owner_phone']) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-truncate text-dark fw-medium" style="max-width: 220px;"><?= e($app['reason']) ?></div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 220px;"><?= e($app['symptoms']) ?></small>
                                        </td>
                                        <td><?= badge_status($app['appointment_status']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($app['appointment_status'] === 'scheduled'): ?>
                                                    <a href="<?= url('veterinarian/medical_record_add.php?animal_id=' . $app['animal_id'] . '&appointment_id=' . $app['id']) ?>" class="btn btn-sm btn-primary" title="Record Consultation">
                                                        <i class="bi bi-clipboard2-pulse me-1"></i> Consult
                                                    </a>
                                                <?php elseif ($app['appointment_status'] === 'pending'): ?>
                                                    <form action="<?= url('actions/appointment_update.php') ?>" method="POST" class="d-inline m-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                                                        <input type="hidden" name="status" value="scheduled">
                                                        <input type="hidden" name="redirect_url" value="veterinarian/dashboard.php">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Accept visit">
                                                            <i class="bi bi-check-lg"></i> Accept
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="<?= url('veterinarian/appointment_view.php?id=' . $app['id']) ?>" class="btn btn-sm btn-light border" title="View details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Two-column: Pending Requests & Upcoming Follow-ups -->
            <div class="row g-4">
                <!-- Pending Requests -->
                <div class="col-lg-7">
                    <div class="custom-table-card">
                        <div class="card-header bg-white">
                            <span><i class="bi bi-inbox-fill text-warning me-2"></i> Pending Appointment Requests</span>
                            <a href="<?= url('veterinarian/appointments.php?status=pending') ?>" class="btn btn-sm btn-link text-decoration-none">View All</a>
                        </div>
                        <?php if (empty($pendingRequests)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-check2-all text-success"></i></div>
                                <h6 class="fw-bold">No Pending Requests</h6>
                                <p class="text-muted small">All appointment requests have been processed.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Requested Date</th>
                                            <th>Patient</th>
                                            <th>Owner</th>
                                            <th>Reason</th>
                                            <th class="text-end">Respond</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingRequests as $pr): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= format_date($pr['appointment_date']) ?></strong>
                                                    <small class="text-muted d-block"><?= format_time($pr['appointment_time']) ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= e($pr['animal_name']) ?></div>
                                                    <small class="text-muted"><?= e($pr['species']) ?></small>
                                                </td>
                                                <td><?= e($pr['owner_name']) ?></td>
                                                <td><small class="text-truncate d-block text-secondary" style="max-width: 140px;"><?= e($pr['reason']) ?></small></td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <form action="<?= url('actions/appointment_update.php') ?>" method="POST" class="d-inline m-0">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="appointment_id" value="<?= $pr['id'] ?>">
                                                            <input type="hidden" name="status" value="scheduled">
                                                            <input type="hidden" name="redirect_url" value="veterinarian/dashboard.php">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Accept appointment">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <form action="<?= url('actions/appointment_update.php') ?>" method="POST" class="d-inline m-0" onsubmit="return confirm('Reject this appointment request?');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="appointment_id" value="<?= $pr['id'] ?>">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <input type="hidden" name="redirect_url" value="veterinarian/dashboard.php">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject request">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Follow-Up Tasks -->
                <div class="col-lg-5">
                    <div class="custom-table-card">
                        <div class="card-header bg-white">
                            <span><i class="bi bi-bell-fill text-info me-2"></i> Upcoming Patient Follow-Ups</span>
                        </div>
                        <?php if (empty($upcomingFollowups)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-calendar-heart"></i></div>
                                <h6 class="fw-bold">No Follow-ups Pending</h6>
                                <p class="text-muted small">No post-consultation follow-ups scheduled for the coming weeks.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingFollowups as $uf): ?>
                                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($uf['animal_name']) ?> (<?= e($uf['species']) ?>)</div>
                                            <div class="text-muted small">
                                                Owner: <?= e($uf['owner_name']) ?> (<?= e($uf['owner_phone']) ?>)<br>
                                                Due: <strong><?= format_date($uf['follow_up_date']) ?></strong>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                In <?= (int)$uf['days_left'] ?> days
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
