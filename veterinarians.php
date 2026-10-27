<?php
/**
 * Public Veterinarian Directory
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Veterinarian Directory';
$activeNav = 'veterinarians';

$search = trim($_GET['search'] ?? '');
$specialization = trim($_GET['specialization'] ?? '');

$sql = "
    SELECT u.id, u.full_name, u.email, u.phone,
           vp.qualification, vp.specialization, vp.license_number,
           vp.experience_years, vp.bio, vp.consultation_fee
    FROM users u
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE u.role = 'veterinarian' 
      AND u.account_status = 'active' 
      AND vp.approval_status = 'approved'
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE :search OR vp.specialization LIKE :search OR vp.bio LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($specialization)) {
    $sql .= " AND vp.specialization = :spec";
    $params[':spec'] = $specialization;
}

$sql .= " ORDER BY vp.experience_years DESC, u.full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$veterinarians = $stmt->fetchAll();

// Get unique specializations for filter dropdown
$specStmt = $pdo->query("
    SELECT DISTINCT specialization 
    FROM veterinarian_profiles 
    WHERE approval_status = 'approved' AND specialization IS NOT NULL AND specialization != ''
    ORDER BY specialization ASC
");
$allSpecs = $specStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-primary-subtle py-5 border-bottom">
    <div class="container text-center">
        <span class="badge bg-primary text-white rounded-pill px-3 py-2 mb-2">Certified Animal Practitioners</span>
        <h1 class="fw-bold text-dark">Veterinarian Directory</h1>
        <p class="text-muted lead max-w-700 mx-auto">
            Browse our licensed, board-approved veterinary clinicians specializing in canine, feline, equine, bovine, and exotic animal healthcare.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-5 bg-white">
        <form method="GET" action="<?= url('veterinarians.php') ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label">Search Specialist</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="search" name="search" value="<?= e($search) ?>" placeholder="Search by name, expertise, or keyword...">
                </div>
            </div>

            <div class="col-md-4">
                <label for="specialization" class="form-label">Filter by Specialization</label>
                <select class="form-select" id="specialization" name="specialization">
                    <option value="">All Specializations</option>
                    <?php foreach ($allSpecs as $spec): ?>
                        <option value="<?= e($spec) ?>" <?= $specialization === $spec ? 'selected' : '' ?>>
                            <?= e($spec) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel-fill me-1"></i> Apply Filter
                </button>
                <?php if (!empty($search) || !empty($specialization)): ?>
                    <a href="<?= url('veterinarians.php') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Veterinarian Cards List -->
    <?php if (empty($veterinarians)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <div class="empty-state-icon mx-auto mb-3">
                <i class="bi bi-person-x fs-2"></i>
            </div>
            <h4 class="fw-bold">No Veterinarians Found</h4>
            <p class="text-muted">No approved veterinarians matched your search criteria. Try clearing the filter or checking back later.</p>
            <a href="<?= url('veterinarians.php') ?>" class="btn btn-outline-primary mx-auto">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($veterinarians as $vet): ?>
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white transition hover-shadow">
                        <div class="d-flex flex-column flex-sm-row gap-4">
                            <div class="flex-shrink-0 text-center">
                                <div class="bg-primary text-white rounded-4 d-inline-flex align-items-center justify-content-center fw-bold fs-2 shadow-sm" style="width: 80px; height: 80px;">
                                    <?= strtoupper(substr($vet['full_name'], 0, 1)) ?>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                        <i class="bi bi-patch-check-fill me-1"></i> Approved
                                    </span>
                                </div>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h5 class="fw-bold text-dark mb-0"><?= e($vet['full_name']) ?></h5>
                                    <span class="fw-bold text-primary fs-5">$<?= number_format((float)$vet['consultation_fee'], 2) ?></span>
                                </div>
                                
                                <div class="text-primary fw-semibold small mb-1"><?= e($vet['specialization']) ?></div>
                                <div class="text-muted small mb-2"><i class="bi bi-mortarboard-fill me-1"></i> <?= e($vet['qualification']) ?></div>
                                
                                <div class="d-flex flex-wrap gap-2 text-muted small mb-3">
                                    <span><i class="bi bi-briefcase-fill text-secondary me-1"></i> <?= (int)$vet['experience_years'] ?> Years Experience</span>
                                    <span>&bull;</span>
                                    <span><i class="bi bi-card-text text-secondary me-1"></i> Lic: <?= e($vet['license_number']) ?></span>
                                </div>

                                <p class="text-secondary small mb-3">
                                    <?= e($vet['bio'] ?? 'Dedicated clinical specialist focused on animal well-being and health outcomes.') ?>
                                </p>

                                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                                    <span class="text-muted small">
                                        <i class="bi bi-clock-history me-1 text-success"></i> Accepts Online Appointments
                                    </span>
                                    <a href="<?= url('owner/appointment_add.php?vet_id=' . $vet['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="bi bi-calendar-plus me-1"></i> Book Appointment
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
