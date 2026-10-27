<?php
/**
 * Veterinarian Appointments Manager
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_VET);

$pageTitle = 'Manage Appointments';
$isDashboard = true;
$activePage = 'appointments';
$user = current_user();
$vetId = (int)$user['id'];

$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT app.*, a.animal_name, a.species, a.breed, a.identification_number,
           u.full_name AS owner_name, u.phone AS owner_phone, u.email AS owner_email
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users u ON u.id = app.owner_id
    WHERE app.veterinarian_id = :vet_id
";
$params = [':vet_id' => $vetId];

if (!empty($statusFilter)) {
    $sql .= " AND app.appointment_status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY app.appointment_date DESC, app.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Appointment Requests & Consultations</h5>
                    <small class="text-muted">Accept bookings, complete visits, and manage schedules</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/availability.php') ?>" class="btn btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-clock-history me-1"></i> Availability Slots
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Status Filters -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted small fw-bold me-2">Filter Status:</span>
                    <a href="<?= url('veterinarian/appointments.php') ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        All (<?= count($appointments) ?>)
                    </a>
                    <a href="<?= url('veterinarian/appointments.php?status=pending') ?>" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        Pending Confirmation
                    </a>
                    <a href="<?= url('veterinarian/appointments.php?status=scheduled') ?>" class="btn btn-sm <?= $statusFilter === 'scheduled' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        Scheduled
                    </a>
                    <a href="<?= url('veterinarian/appointments.php?status=completed') ?>" class="btn btn-sm <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        Completed
                    </a>
                    <a href="<?= url('veterinarian/appointments.php?status=cancelled') ?>" class="btn btn-sm <?= $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        Cancelled
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <?php if (empty($appointments)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                        <h5 class="fw-bold">No Appointments Found</h5>
                        <p class="text-muted small">No appointment records match the current filter.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Ref #</th>
                                    <th>Patient Animal</th>
                                    <th>Owner</th>
                                    <th>Date & Time</th>
                                    <th>Reason / Symptoms</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $app): ?>
                                    <tr>
                                        <td><span class="font-monospace text-muted small">#APT-<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($app['animal_name']) ?></div>
                                            <small class="text-muted"><?= e($app['species']) ?> &bull; <?= e($app['breed'] ?: 'Mixed') ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($app['owner_name']) ?></div>
                                            <small class="text-muted"><?= e($app['owner_phone']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= format_date($app['appointment_date']) ?></div>
                                            <small class="text-muted"><?= format_time($app['appointment_time']) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-truncate text-dark fw-medium" style="max-width: 200px;"><?= e($app['reason']) ?></div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 200px;"><?= e($app['symptoms']) ?></small>
                                        </td>
                                        <td><?= badge_status($app['appointment_status']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($app['appointment_status'] === 'pending'): ?>
                                                    <form action="<?= url('actions/appointment_update.php') ?>" method="POST" class="d-inline m-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                                                        <input type="hidden" name="status" value="scheduled">
                                                        <input type="hidden" name="redirect_url" value="veterinarian/appointments.php">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Accept visit">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </form>
                                                    <form action="<?= url('actions/appointment_update.php') ?>" method="POST" class="d-inline m-0" onsubmit="return confirm('Reject this appointment request?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <input type="hidden" name="redirect_url" value="veterinarian/appointments.php">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject request">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($app['appointment_status'] === 'scheduled'): ?>
                                                    <a href="<?= url('veterinarian/medical_record_add.php?animal_id=' . $app['animal_id'] . '&appointment_id=' . $app['id']) ?>" class="btn btn-sm btn-primary" title="Record Consultation">
                                                        <i class="bi bi-clipboard2-pulse"></i> Consult
                                                    </a>
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
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
