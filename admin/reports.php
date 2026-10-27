<?php
/**
 * Administrator: System Analytics & Reports
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'System Analytics & Reports';
$isDashboard = true;
$activePage = 'reports';
$user = current_user();

$reportType = $_GET['report_type'] ?? 'appointments_date';
$fromDate = trim($_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days')));
$toDate = trim($_GET['to_date'] ?? date('Y-m-d', strtotime('+30 days')));

// Validate dates
if (strtotime($fromDate) > strtotime($toDate)) {
    $temp = $fromDate;
    $fromDate = $toDate;
    $toDate = $temp;
}

// 1. Appointments by Date Range Report
$appDateStmt = $pdo->prepare("
    SELECT app.*, a.animal_name, a.species, ow.full_name AS owner_name, doc.full_name AS vet_name, vp.consultation_fee
    FROM appointments app
    JOIN animals a ON a.id = app.animal_id
    JOIN users ow ON ow.id = app.owner_id
    JOIN users doc ON doc.id = app.veterinarian_id
    JOIN veterinarian_profiles vp ON vp.user_id = doc.id
    WHERE app.appointment_date BETWEEN :from_date AND :to_date
    ORDER BY app.appointment_date DESC, app.appointment_time DESC
");
$appDateStmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
$appointmentsByDate = $appDateStmt->fetchAll();

// 2. Appointments by Status Summary
$statusSummary = $pdo->query("
    SELECT appointment_status, COUNT(*) AS count,
           ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM appointments)), 1) AS percentage
    FROM appointments
    GROUP BY appointment_status
    ORDER BY count DESC
")->fetchAll();

// 3. Animal Species Summary
$speciesSummary = $pdo->query("
    SELECT species, COUNT(*) AS count,
           SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) AS males,
           SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) AS females,
           ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM animals)), 1) AS percentage
    FROM animals
    GROUP BY species
    ORDER BY count DESC
")->fetchAll();

// 4. Veterinarian Performance Summary
$vetSummary = $pdo->query("
    SELECT u.id, u.full_name, vp.specialization, vp.consultation_fee,
           COUNT(app.id) AS total_assigned,
           SUM(CASE WHEN app.appointment_status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
           SUM(CASE WHEN app.appointment_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
           SUM(CASE WHEN app.appointment_status = 'completed' THEN vp.consultation_fee ELSE 0 END) AS total_billed
    FROM users u
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    LEFT JOIN appointments app ON app.veterinarian_id = u.id
    WHERE u.role = 'veterinarian'
    GROUP BY u.id, u.full_name, vp.specialization, vp.consultation_fee
    ORDER BY completed_count DESC
")->fetchAll();

// 5. Vaccination Due & Overdue Report
$vacDueStmt = $pdo->query("
    SELECT v.*, a.animal_name, a.species, ow.full_name AS owner_name, ow.phone AS owner_phone, doc.full_name AS vet_name,
           DATEDIFF(v.next_due_date, CURDATE()) AS days_left
    FROM vaccinations v
    JOIN animals a ON a.id = v.animal_id
    JOIN users ow ON ow.id = a.owner_id
    JOIN users doc ON doc.id = v.veterinarian_id
    WHERE v.next_due_date IS NOT NULL AND v.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY v.next_due_date ASC
");
$vaccinesDue = $vacDueStmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">System Reports & Analytics</h5>
                    <small class="text-muted">Dynamic clinical audit queries & operational reports</small>
                </div>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary rounded-pill px-3 no-print">
                    <i class="bi bi-printer me-1"></i> Print / Save Report
                </button>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Report Selector Tabs -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white no-print">
                <ul class="nav nav-pills gap-2" id="reportTabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $reportType === 'appointments_date' ? 'active' : '' ?>" href="<?= url('admin/reports.php?report_type=appointments_date') ?>">
                            <i class="bi bi-calendar-range me-1"></i> Appointments by Date
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $reportType === 'status_breakdown' ? 'active' : '' ?>" href="<?= url('admin/reports.php?report_type=status_breakdown') ?>">
                            <i class="bi bi-pie-chart me-1"></i> Status Breakdown
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $reportType === 'species_summary' ? 'active' : '' ?>" href="<?= url('admin/reports.php?report_type=species_summary') ?>">
                            <i class="bi bi-bar-chart me-1"></i> Species Summary
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $reportType === 'vet_summary' ? 'active' : '' ?>" href="<?= url('admin/reports.php?report_type=vet_summary') ?>">
                            <i class="bi bi-award me-1"></i> Veterinarian Performance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $reportType === 'vaccine_due' ? 'active' : '' ?>" href="<?= url('admin/reports.php?report_type=vaccine_due') ?>">
                            <i class="bi bi-eyedropper me-1"></i> Vaccination Due Report
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 1. Report: Appointments by Date Range -->
            <?php if ($reportType === 'appointments_date'): ?>
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white no-print">
                    <form method="GET" action="<?= url('admin/reports.php') ?>" class="row g-3 align-items-end">
                        <input type="hidden" name="report_type" value="appointments_date">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date" value="<?= e($fromDate) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="to_date" value="<?= e($toDate) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-1"></i> Generate Date Range Report
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Appointments Between <?= format_date($fromDate) ?> and <?= format_date($toDate) ?></span>
                        <span class="badge bg-primary-subtle text-primary"><?= count($appointmentsByDate) ?> Appointments Found</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient Animal</th>
                                    <th>Owner</th>
                                    <th>Veterinarian</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointmentsByDate)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No appointments found in this date range.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($appointmentsByDate as $app): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= format_date($app['appointment_date']) ?></div>
                                                <small class="text-muted"><?= format_time($app['appointment_time']) ?></small>
                                            </td>
                                            <td><?= e($app['animal_name']) ?> (<?= e($app['species']) ?>)</td>
                                            <td><?= e($app['owner_name']) ?></td>
                                            <td><?= e($app['vet_name']) ?></td>
                                            <td><div class="text-truncate" style="max-width: 200px;"><?= e($app['reason']) ?></div></td>
                                            <td><?= badge_status($app['appointment_status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- 2. Report: Status Breakdown -->
            <?php elseif ($reportType === 'status_breakdown'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden p-4">
                    <h5 class="fw-bold mb-4">Appointments Status Distribution Breakdown</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered custom-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Appointment Status</th>
                                    <th>Total Records</th>
                                    <th>Share of Total</th>
                                    <th>Visual Ratio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusSummary as $ss): ?>
                                    <tr>
                                        <td><?= badge_status($ss['appointment_status']) ?></td>
                                        <td><strong><?= $ss['count'] ?></strong> bookings</td>
                                        <td><span class="fw-bold"><?= $ss['percentage'] ?>%</span></td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $ss['percentage'] ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- 3. Report: Species Summary -->
            <?php elseif ($reportType === 'species_summary'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden p-4">
                    <h5 class="fw-bold mb-4">Patient Population by Animal Species</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered custom-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Animal Species</th>
                                    <th>Total Count</th>
                                    <th>Males</th>
                                    <th>Females</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($speciesSummary as $sp): ?>
                                    <tr>
                                        <td><strong class="text-primary"><?= e($sp['species']) ?></strong></td>
                                        <td><strong><?= $sp['count'] ?></strong> animals</td>
                                        <td><?= (int)$sp['males'] ?></td>
                                        <td><?= (int)$sp['females'] ?></td>
                                        <td>
                                            <span class="fw-bold"><?= $sp['percentage'] ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- 4. Report: Veterinarian Performance -->
            <?php elseif ($reportType === 'vet_summary'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden p-4">
                    <h5 class="fw-bold mb-4">Veterinarian Clinical Caseload & Performance Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered custom-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Doctor Name</th>
                                    <th>Specialization</th>
                                    <th>Fee Rate</th>
                                    <th>Assigned</th>
                                    <th>Completed</th>
                                    <th>Pending</th>
                                    <th>Total Consultation Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vetSummary as $vs): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= e($vs['full_name']) ?></strong></td>
                                        <td><?= e($vs['specialization']) ?></td>
                                        <td>$<?= number_format((float)$vs['consultation_fee'], 2) ?></td>
                                        <td><?= $vs['total_assigned'] ?></td>
                                        <td><span class="badge bg-success-subtle text-success"><?= $vs['completed_count'] ?></span></td>
                                        <td><span class="badge bg-warning-subtle text-warning-emphasis"><?= $vs['pending_count'] ?></span></td>
                                        <td><strong class="text-primary">$<?= number_format((float)$vs['total_billed'], 2) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- 5. Report: Vaccination Due -->
            <?php elseif ($reportType === 'vaccine_due'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden p-4">
                    <h5 class="fw-bold mb-4">Vaccinations Due & Overdue (Next 60 Days)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered custom-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Patient Animal</th>
                                    <th>Species</th>
                                    <th>Owner & Phone</th>
                                    <th>Vaccine Name</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vaccinesDue)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No vaccinations due in the next 60 days.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($vaccinesDue as $vd): 
                                        $days = (int)$vd['days_left'];
                                        $isOverdue = $days < 0;
                                    ?>
                                        <tr>
                                            <td><strong><?= e($vd['animal_name']) ?></strong></td>
                                            <td><?= e($vd['species']) ?></td>
                                            <td><?= e($vd['owner_name']) ?> (<?= e($vd['owner_phone']) ?>)</td>
                                            <td class="text-primary fw-semibold"><?= e($vd['vaccine_name']) ?></td>
                                            <td><strong><?= format_date($vd['next_due_date']) ?></strong></td>
                                            <td>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Overdue (<?= abs($days) ?>d)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Due in <?= $days ?>d</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
