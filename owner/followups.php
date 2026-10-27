<?php
/**
 * Owner Follow-Up Schedule
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'Follow-Up Schedule';
$isDashboard = true;
$activePage = 'followups';
$user = current_user();
$ownerId = (int)$user['id'];

$stmt = $pdo->prepare("
    SELECT mr.*, a.animal_name, a.species, a.breed,
           u.full_name AS vet_name, vp.specialization,
           DATEDIFF(mr.follow_up_date, CURDATE()) AS days_left
    FROM medical_records mr
    JOIN animals a ON a.id = mr.animal_id
    JOIN users u ON u.id = mr.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE a.owner_id = ? AND mr.follow_up_date IS NOT NULL
    ORDER BY mr.follow_up_date ASC
");
$stmt->execute([$ownerId]);
$followups = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">Follow-Up Schedule</h5>
                    <small class="text-muted">Post-consultation reassessments and recovery milestones</small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-primary rounded-pill px-3">
                    <i class="bi bi-calendar-plus me-1"></i> Book Follow-Up
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <?php if (empty($followups)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-bell-slash"></i></div>
                        <h5 class="fw-bold">No Follow-Up Dates Pending</h5>
                        <p class="text-muted small">Your veterinarian will schedule clinical follow-ups during consultations when needed.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Target Follow-up Date</th>
                                    <th>Patient Animal</th>
                                    <th>Assigned Doctor</th>
                                    <th>Original Diagnosis</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($followups as $f): 
                                    $days = (int)$f['days_left'];
                                    $isPast = $days < 0;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= format_date($f['follow_up_date']) ?></div>
                                            <small class="text-muted">Initial Visit: <?= format_date($f['visit_date']) ?></small>
                                        </td>
                                        <td>
                                            <a href="<?= url('owner/animal_view.php?id=' . $f['animal_id']) ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= e($f['animal_name']) ?>
                                            </a>
                                            <small class="text-muted d-block"><?= e($f['species']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($f['vet_name']) ?></div>
                                            <small class="text-muted"><?= e($f['specialization']) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark small text-truncate" style="max-width: 240px;"><?= e($f['diagnosis']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($isPast): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">Past Due</span>
                                            <?php elseif ($days === 0): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Due Today</span>
                                            <?php else: ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">In <?= $days ?> Days</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('owner/appointment_add.php?animal_id=' . $f['animal_id'] . '&vet_id=' . $f['veterinarian_id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                                <i class="bi bi-calendar2-check me-1"></i> Book Slot
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
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
