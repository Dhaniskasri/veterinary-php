<?php
/**
 * Administrator Master Dashboard
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Administrator Dashboard';
$isDashboard = true;
$activePage = 'dashboard';
$user = current_user();

// 1. MySQL Database-Driven Counters
$totalOwners = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn();
$totalApprovedVets = (int)$pdo->query("SELECT COUNT(*) FROM veterinarian_profiles WHERE approval_status = 'approved'")->fetchColumn();
$pendingVets = (int)$pdo->query("SELECT COUNT(*) FROM veterinarian_profiles WHERE approval_status = 'pending'")->fetchColumn();
$totalAnimals = (int)$pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn();
$totalAppointments = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$completedConsultations = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_status = 'completed'")->fetchColumn();
$unreadInquiries = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();

// 2. Appointment Status Breakdown (for Chart.js)
$statusStmt = $pdo->query("
    SELECT appointment_status, COUNT(*) as count 
    FROM appointments 
    GROUP BY appointment_status
");
$statusData = $statusStmt->fetchAll();
$statusLabels = [];
$statusCounts = [];
foreach ($statusData as $sd) {
    $statusLabels[] = ucfirst($sd['appointment_status']);
    $statusCounts[] = (int)$sd['count'];
}

// 3. Animal Species Distribution (for Chart.js)
$speciesStmt = $pdo->query("
    SELECT species, COUNT(*) as count 
    FROM animals 
    GROUP BY species 
    ORDER BY count DESC
");
$speciesData = $speciesStmt->fetchAll();
$speciesLabels = [];
$speciesCounts = [];
foreach ($speciesData as $sp) {
    $speciesLabels[] = $sp['species'];
    $speciesCounts[] = (int)$sp['count'];
}

// 4. Recent System Activity Logs
$logsStmt = $pdo->query("
    SELECT al.*, u.full_name, u.role, u.email
    FROM activity_logs al
    LEFT JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 6
");
$recentLogs = $logsStmt->fetchAll();

// 5. Pending Veterinarians List for quick approval
$pendingVetsList = $pdo->query("
    SELECT u.id, u.full_name, u.email, u.phone, vp.qualification, vp.specialization, vp.license_number, vp.created_at
    FROM users u
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE vp.approval_status = 'pending'
    ORDER BY vp.created_at ASC
")->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">System Administration</h5>
                    <small class="text-muted">Master Oversight & Live System Telemetry</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('admin/reports.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-bar-chart-line me-1"></i> System Reports
                </a>
                <a href="<?= url('admin/veterinarians.php') ?>" class="btn btn-sm btn-danger rounded-pill px-3 position-relative">
                    <i class="bi bi-person-lines-fill me-1"></i> Vet Approvals
                    <?php if ($pendingVets > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border">
                            <?= $pendingVets ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Pending Approvals Banner -->
            <?php if ($pendingVets > 0): ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark p-3 rounded-circle fs-4">
                            <i class="bi bi-person-exclamation"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Pending Veterinarian Applications (<?= $pendingVets ?>)</h5>
                            <p class="text-secondary small mb-0">Newly registered veterinary clinicians are awaiting license credentials review before clinical booking access is enabled.</p>
                        </div>
                    </div>
                    <a href="<?= url('admin/veterinarians.php?status=pending') ?>" class="btn btn-dark rounded-pill px-4">
                        Review Credentials
                    </a>
                </div>
            <?php endif; ?>

            <!-- System Metrics Grid -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-value"><?= $totalOwners ?></div>
                        <div class="stat-label">Registered Animal Owners</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-teal">
                            <i class="bi bi-hospital-fill"></i>
                        </div>
                        <div class="stat-value"><?= $totalApprovedVets ?></div>
                        <div class="stat-label">Approved Veterinarians</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-emerald">
                            <i class="bi bi-shield-heart-fill"></i>
                        </div>
                        <div class="stat-value"><?= $totalAnimals ?></div>
                        <div class="stat-label">Registered Animals</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-purple">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <div class="stat-value"><?= $totalAppointments ?></div>
                        <div class="stat-label">Total Appointments Booked</div>
                    </div>
                </div>
            </div>

            <!-- Visual Charts Row -->
            <div class="row g-4 mb-4">
                <!-- Appointment Breakdown Chart -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i> Appointment Status Breakdown</h5>
                            <span class="badge bg-light text-dark border">Real-time</span>
                        </div>
                        <div style="height: 280px; position: relative;">
                            <canvas id="appointmentStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Animal Species Distribution Chart -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-fill text-success me-2"></i> Patient Species Summary</h5>
                            <span class="badge bg-light text-dark border">By Species</span>
                        </div>
                        <div style="height: 280px; position: relative;">
                            <canvas id="speciesDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column: Quick Approvals & Recent Audit Logs -->
            <div class="row g-4">
                <!-- Pending Approvals Quick Table -->
                <div class="col-lg-7">
                    <div class="custom-table-card">
                        <div class="card-header bg-white">
                            <span><i class="bi bi-person-check-fill text-danger me-2"></i> Veterinarian Approval Queue</span>
                            <a href="<?= url('admin/veterinarians.php') ?>" class="btn btn-sm btn-link text-decoration-none">Manage All</a>
                        </div>
                        <?php if (empty($pendingVetsList)): ?>
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="bi bi-patch-check text-success"></i></div>
                                <h6 class="fw-bold">No Pending Approvals</h6>
                                <p class="text-muted small">All veterinary clinician applications have been reviewed.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Doctor Name</th>
                                            <th>Specialization</th>
                                            <th>License No</th>
                                            <th>Applied</th>
                                            <th class="text-end">Decision</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingVetsList as $pv): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= e($pv['full_name']) ?></div>
                                                    <small class="text-muted"><?= e($pv['email']) ?></small>
                                                </td>
                                                <td>
                                                    <div><?= e($pv['specialization']) ?></div>
                                                    <small class="text-muted"><?= e($pv['qualification']) ?></small>
                                                </td>
                                                <td><span class="font-monospace small"><?= e($pv['license_number']) ?></span></td>
                                                <td><small><?= format_date($pv['created_at']) ?></small></td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <form action="<?= url('actions/veterinarian_approval.php') ?>" method="POST" class="d-inline m-0">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="approve_vet">
                                                            <input type="hidden" name="user_id" value="<?= $pv['id'] ?>">
                                                            <input type="hidden" name="redirect_url" value="admin/dashboard.php">
                                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3" title="Approve Clinician">
                                                                <i class="bi bi-check-lg me-1"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form action="<?= url('actions/veterinarian_approval.php') ?>" method="POST" class="d-inline m-0" onsubmit="return confirm('Reject this veterinarian application?');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="reject_vet">
                                                            <input type="hidden" name="user_id" value="<?= $pv['id'] ?>">
                                                            <input type="hidden" name="redirect_url" value="admin/dashboard.php">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Reject Application">
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

                <!-- Recent Activity Audit Logs -->
                <div class="col-lg-5">
                    <div class="custom-table-card">
                        <div class="card-header bg-white">
                            <span><i class="bi bi-activity text-primary me-2"></i> System Activity Audit Log</span>
                        </div>
                        <?php if (empty($recentLogs)): ?>
                            <div class="empty-state-box">
                                <h6 class="fw-bold">No Activity Recorded</h6>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentLogs as $log): ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.725rem;">
                                                <?= e($log['action']) ?>
                                            </span>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?= format_date($log['created_at'], 'M j, g:i A') ?></small>
                                        </div>
                                        <div class="small text-dark">
                                            User: <strong><?= e($log['full_name'] ?: 'System / Guest') ?></strong>
                                            <?php if ($log['role']): ?>
                                                <span class="badge bg-secondary-subtle text-secondary py-0 px-1 ms-1"><?= e($log['role']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">Entity: <?= e($log['entity_type']) ?> #<?= $log['entity_id'] ?></div>
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

<!-- Chart.js initialization script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Appointment Status Doughnut Chart
    const statusCtx = document.getElementById('appointmentStatusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($statusLabels) ?>,
                datasets: [{
                    data: <?= json_encode($statusCounts) ?>,
                    backgroundColor: ['#f59e0b', '#0284c7', '#10b981', '#64748b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 15, font: { family: 'Inter' } }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Species Distribution Bar Chart
    const speciesCtx = document.getElementById('speciesDistributionChart');
    if (speciesCtx) {
        new Chart(speciesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($speciesLabels) ?>,
                datasets: [{
                    label: 'Registered Patients',
                    data: <?= json_encode($speciesCounts) ?>,
                    backgroundColor: '#0d9488',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
