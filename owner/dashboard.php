<?php
/**
 * Animal Owner Dashboard
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'Owner Dashboard';
$isDashboard = true;
$activePage = 'dashboard';
$user = current_user();
$ownerId = (int)$user['id'];

// 1. Database-driven statistics
$totalAnimals = $pdo->prepare("SELECT COUNT(*) FROM animals WHERE owner_id = ?");
$totalAnimals->execute([$ownerId]);
$animalCount = (int)$totalAnimals->fetchColumn();

$upcomingApp = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE owner_id = ? AND appointment_status IN ('pending', 'scheduled') AND appointment_date >= CURDATE()
");
$upcomingApp->execute([$ownerId]);
$upcomingAppCount = (int)$upcomingApp->fetchColumn();

$completedApp = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE owner_id = ? AND appointment_status = 'completed'
");
$completedApp->execute([$ownerId]);
$completedAppCount = (int)$completedApp->fetchColumn();

// Upcoming vaccinations in the next 30 days or overdue
$vacDueStmt = $pdo->prepare("
    SELECT COUNT(*) FROM vaccinations v
    JOIN animals a ON a.id = v.animal_id
    WHERE a.owner_id = ? 
      AND v.next_due_date IS NOT NULL 
      AND v.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$vacDueStmt->execute([$ownerId]);
$vacDueCount = (int)$vacDueStmt->fetchColumn();

// 2. Fetch upcoming appointments
$appStmt = $pdo->prepare("
    SELECT app.*, a.animal_name, a.species, u.full_name AS vet_name, vp.specialization
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users u ON u.id = app.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE app.owner_id = ?
    ORDER BY app.appointment_date ASC, app.appointment_time ASC
    LIMIT 5
");
$appStmt->execute([$ownerId]);
$recentAppointments = $appStmt->fetchAll();

// 3. Fetch recent medical records
$medStmt = $pdo->prepare("
    SELECT mr.*, a.animal_name, a.species, u.full_name AS vet_name,
           (SELECT COUNT(*) FROM prescriptions p WHERE p.medical_record_id = mr.id) AS rx_count
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users u ON u.id = mr.veterinarian_id
    WHERE a.owner_id = ?
    ORDER BY mr.visit_date DESC
    LIMIT 4
");
$medStmt->execute([$ownerId]);
$recentMedicalRecords = $medStmt->fetchAll();

// 4. Fetch upcoming / overdue vaccination alerts
$vacListStmt = $pdo->prepare("
    SELECT v.*, a.animal_name, a.species, u.full_name AS vet_name,
           DATEDIFF(v.next_due_date, CURDATE()) AS days_left
    FROM vaccinations v
    JOIN animals a ON a.id = v.animal_id
    JOIN users u ON u.id = v.veterinarian_id
    WHERE a.owner_id = ? 
      AND v.next_due_date IS NOT NULL 
      AND v.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 45 DAY)
    ORDER BY v.next_due_date ASC
    LIMIT 5
");
$vacListStmt->execute([$ownerId]);
$vaccineAlerts = $vacListStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/owner_sidebar.php'; ?>

    <main class="dashboard-main">
        <!-- Topbar -->
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Welcome, <?= e($user['full_name']) ?></h5>
                    <small class="text-muted">Animal Healthcare Portal</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="bi bi-calendar-plus me-1"></i> Book Visit
                </a>
                <a href="<?= url('owner/animal_add.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-plus-circle me-1"></i> Add Animal
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Vaccination Due Warning Banner -->
            <?php if ($vacDueCount > 0): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between rounded-4 p-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark p-2 rounded-circle fs-5">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div>
                            <strong class="text-dark">Vaccination Reminder Alert:</strong>
                            <div class="small text-muted">You have <strong><?= $vacDueCount ?></strong> vaccination(s) due or upcoming within 30 days.</div>
                        </div>
                    </div>
                    <a href="<?= url('owner/vaccinations.php') ?>" class="btn btn-sm btn-dark rounded-pill px-3">View Vaccines</a>
                </div>
            <?php endif; ?>

            <!-- Stat Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-teal">
                            <i class="bi bi-shield-heart-fill"></i>
                        </div>
                        <div class="stat-value"><?= $animalCount ?></div>
                        <div class="stat-label">My Registered Animals</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">
                            <i class="bi bi-calendar2-event-fill"></i>
                        </div>
                        <div class="stat-value"><?= $upcomingAppCount ?></div>
                        <div class="stat-label">Upcoming Appointments</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-emerald">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <div class="stat-value"><?= $completedAppCount ?></div>
                        <div class="stat-label">Completed Consultations</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-amber">
                            <i class="bi bi-eyedropper"></i>
                        </div>
                        <div class="stat-value"><?= $vacDueCount ?></div>
                        <div class="stat-label">Vaccination Reminders</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Upcoming Appointments -->
                <div class="col-lg-7">
                    <div class="custom-table-card">
                        <div class="card-header">
                            <span><i class="bi bi-calendar-check text-primary me-2"></i> Recent & Upcoming Appointments</span>
                            <a href="<?= url('owner/appointments.php') ?>" class="btn btn-sm btn-link text-decoration-none">View All</a>
                        </div>
                        <?php if (empty($recentAppointments)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                                <h6 class="fw-bold">No Appointments Yet</h6>
                                <p class="text-muted small">You haven't booked any veterinary visits yet.</p>
                                <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-primary">Book an Appointment</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Animal</th>
                                            <th>Doctor & Specialization</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAppointments as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= e($app['animal_name']) ?></div>
                                                    <small class="text-muted"><?= e($app['species']) ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?= e($app['vet_name']) ?></div>
                                                    <small class="text-muted"><?= e($app['specialization']) ?></small>
                                                </td>
                                                <td>
                                                    <div><?= format_date($app['appointment_date']) ?></div>
                                                    <small class="text-muted"><?= format_time($app['appointment_time']) ?></small>
                                                </td>
                                                <td><?= badge_status($app['appointment_status']) ?></td>
                                                <td class="text-end">
                                                    <a href="<?= url('owner/appointment_view.php?id=' . $app['id']) ?>" class="btn btn-sm btn-light border" title="View details">
                                                        <i class="bi bi-eye"></i>
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

                <!-- Upcoming Vaccinations & Reminders -->
                <div class="col-lg-5">
                    <div class="custom-table-card">
                        <div class="card-header">
                            <span><i class="bi bi-eyedropper text-danger me-2"></i> Vaccination Schedule</span>
                            <a href="<?= url('owner/vaccinations.php') ?>" class="btn btn-sm btn-link text-decoration-none">View All</a>
                        </div>
                        <?php if (empty($vaccineAlerts)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-check2-circle text-success"></i></div>
                                <h6 class="fw-bold">All Vaccinations Up to Date</h6>
                                <p class="text-muted small">No vaccines are due in the next 45 days.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($vaccineAlerts as $v): 
                                    $days = (int)$v['days_left'];
                                    $isOverdue = $days < 0;
                                ?>
                                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($v['vaccine_name']) ?></div>
                                            <div class="text-muted small">
                                                For <strong><?= e($v['animal_name']) ?></strong> &bull; Due: <?= format_date($v['next_due_date']) ?>
                                            </div>
                                        </div>
                                        <div>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    Overdue by <?= abs($days) ?>d
                                                </span>
                                            <?php elseif ($days === 0): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                    Due Today
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                    In <?= $days ?> days
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Medical Records -->
                <div class="col-12">
                    <div class="custom-table-card">
                        <div class="card-header">
                            <span><i class="bi bi-file-earmark-medical text-teal me-2"></i> Recent Clinical Records & Prescriptions</span>
                            <a href="<?= url('owner/medical_history.php') ?>" class="btn btn-sm btn-link text-decoration-none">Full History</a>
                        </div>
                        <?php if (empty($recentMedicalRecords)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                                <h6 class="fw-bold">No Clinical Records Yet</h6>
                                <p class="text-muted small">Medical records will appear here after a veterinary consultation is completed.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Visit Date</th>
                                            <th>Animal</th>
                                            <th>Attending Veterinarian</th>
                                            <th>Diagnosis</th>
                                            <th>Prescription</th>
                                            <th>Follow-up</th>
                                            <th class="text-end">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentMedicalRecords as $rec): ?>
                                            <tr>
                                                <td><span class="fw-semibold"><?= format_date($rec['visit_date']) ?></span></td>
                                                <td>
                                                    <strong><?= e($rec['animal_name']) ?></strong>
                                                    <span class="text-muted small d-block"><?= e($rec['species']) ?></span>
                                                </td>
                                                <td><?= e($rec['vet_name']) ?></td>
                                                <td><span class="text-dark fw-medium"><?= e($rec['diagnosis']) ?></span></td>
                                                <td>
                                                    <?php if ((int)$rec['rx_count'] > 0): ?>
                                                        <a href="<?= url('owner/prescription_view.php?record_id=' . $rec['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1">
                                                            <i class="bi bi-prescription2 me-1"></i> <?= $rec['rx_count'] ?> Meds
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">None</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($rec['follow_up_date'])): ?>
                                                        <span class="badge bg-light text-dark border"><?= format_date($rec['follow_up_date']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="<?= url('owner/animal_view.php?id=' . $rec['animal_id']) ?>" class="btn btn-sm btn-light border">
                                                        <i class="bi bi-journal-text me-1"></i> Record
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
