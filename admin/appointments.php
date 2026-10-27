<?php
/**
 * Administrator: Master Appointments Overview
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Master Appointments Management';
$isDashboard = true;
$activePage = 'appointments';
$user = current_user();

$statusFilter = trim($_GET['status'] ?? '');
$vetFilter = (int)($_GET['vet_id'] ?? 0);
$dateFilter = trim($_GET['date'] ?? '');

$sql = "
    SELECT app.*, 
           a.animal_name, a.species, a.breed,
           ow.full_name AS owner_name, ow.phone AS owner_phone,
           doc.full_name AS vet_name, vp.specialization
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users ow ON ow.id = app.owner_id
    JOIN users doc ON doc.id = app.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = doc.id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND app.appointment_status = :status";
    $params[':status'] = $statusFilter;
}

if ($vetFilter > 0) {
    $sql .= " AND app.veterinarian_id = :vet_id";
    $params[':vet_id'] = $vetFilter;
}

if (!empty($dateFilter)) {
    $sql .= " AND app.appointment_date = :app_date";
    $params[':app_date'] = $dateFilter;
}

$sql .= " ORDER BY app.appointment_date DESC, app.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Veterinarians for filter
$vetsList = $pdo->query("SELECT id, full_name FROM users WHERE role = 'veterinarian' ORDER BY full_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Master Appointments Management</h5>
                    <small class="text-muted">System-wide clinical appointments and schedule oversight</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Filter Controls -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('admin/appointments.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="scheduled" <?= $statusFilter === 'scheduled' ? 'selected' : '' ?>>Scheduled (Confirmed)</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select class="form-select" name="vet_id">
                            <option value="">All Veterinarians</option>
                            <?php foreach ($vetsList as $vl): ?>
                                <option value="<?= $vl['id'] ?>" <?= $vetFilter === (int)$vl['id'] ? 'selected' : '' ?>><?= e($vl['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date" value="<?= e($dateFilter) ?>" placeholder="Filter by date">
                    </div>

                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if (!empty($statusFilter) || $vetFilter > 0 || !empty($dateFilter)): ?>
                            <a href="<?= url('admin/appointments.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table of Appointments -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-calendar-event-fill text-primary me-2"></i> All Bookings</span>
                    <span class="badge bg-primary-subtle text-primary"><?= count($appointments) ?> Appointments</span>
                </div>

                <?php if (empty($appointments)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                        <h5 class="fw-bold">No Appointments Found</h5>
                        <p class="text-muted small">No appointment records match the selected criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Ref #</th>
                                    <th>Patient Animal</th>
                                    <th>Owner</th>
                                    <th>Attending Clinician</th>
                                    <th>Date & Time</th>
                                    <th>Reason / Symptoms</th>
                                    <th>Status</th>
                                    <th class="text-end">Change Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $app): ?>
                                    <tr>
                                        <td><span class="font-monospace small text-muted">#APT-<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($app['animal_name']) ?></div>
                                            <small class="text-muted"><?= e($app['species']) ?></small>
                                        </td>
                                        <td>
                                            <div><?= e($app['owner_name']) ?></div>
                                            <small class="text-muted"><?= e($app['owner_phone']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= e($app['vet_name']) ?></div>
                                            <small class="text-muted"><?= e($app['specialization']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= format_date($app['appointment_date']) ?></div>
                                            <small class="text-muted"><?= format_time($app['appointment_time']) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-truncate text-dark fw-medium" style="max-width: 180px;"><?= e($app['reason']) ?></div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 180px;"><?= e($app['symptoms']) ?></small>
                                        </td>
                                        <td><?= badge_status($app['appointment_status']) ?></td>
                                        <td class="text-end">
                                            <div class="dropdown d-inline">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Status
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                                                    <li>
                                                        <form action="<?= url('actions/appointment_update.php') ?>" method="POST">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                                                            <input type="hidden" name="status" value="scheduled">
                                                            <input type="hidden" name="redirect_url" value="admin/appointments.php">
                                                            <button type="submit" class="dropdown-item py-1.5"><i class="bi bi-calendar-check text-primary me-2"></i> Mark Scheduled</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form action="<?= url('actions/appointment_update.php') ?>" method="POST">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                                                            <input type="hidden" name="status" value="completed">
                                                            <input type="hidden" name="redirect_url" value="admin/appointments.php">
                                                            <button type="submit" class="dropdown-item py-1.5"><i class="bi bi-check-circle text-success me-2"></i> Mark Completed</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form action="<?= url('actions/appointment_update.php') ?>" method="POST">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <input type="hidden" name="redirect_url" value="admin/appointments.php">
                                                            <button type="submit" class="dropdown-item py-1.5"><i class="bi bi-x-circle text-secondary me-2"></i> Mark Cancelled</button>
                                                        </form>
                                                    </li>
                                                </ul>
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
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
